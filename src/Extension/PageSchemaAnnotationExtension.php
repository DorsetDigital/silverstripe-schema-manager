<?php

namespace DorsetDigital\SchemaManager\Extension;

use DorsetDigital\SchemaManager\Annotation\SchemaAnnotationFactory;
use DorsetDigital\SchemaManager\Annotation\SchemaDefinitionRegistry;
use DorsetDigital\SchemaManager\Control\SchemaIDs;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\DatetimeField;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\View\Requirements;
use function _t;

/**
 * One optional annotation, stored on the versioned SiteTree record.
 */
class PageSchemaAnnotationExtension extends Extension
{
    private static array $db = [
        'SchemaAnnotationType' => 'Varchar(100)',
        'SchemaAnnotationValues' => 'Text',
    ];

    public function updateCMSFields(FieldList $fields): void
    {
        $definitions = SchemaDefinitionRegistry::definitions();
        $options = [];
        foreach ($definitions as $key => $definition) {
            $options[$key] = _t('SchemaManager.ANNOTATION_' . strtoupper($key), $definition['label'] ?? $key);
        }

        $fields->addFieldToTab('Root.Schema', DropdownField::create(
            'SchemaAnnotationType',
            _t('SchemaManager.PAGE_ANNOTATION', 'Page annotation'),
            $options
        )->setEmptyString(_t('SchemaManager.NONE', 'None')));

        $values = json_decode((string) $this->owner->SchemaAnnotationValues, true);
        $values = is_array($values) ? $values : [];
        $fields->addFieldToTab('Root.Schema', HiddenField::create(
            'SchemaAnnotationValues',
            '',
            json_encode($values, JSON_UNESCAPED_SLASHES)
        ));

        foreach ($definitions as $key => $definition) {
            $description = _t('SchemaManager.ANNOTATION_HELP_' . strtoupper($key), $definition['description'] ?? _t('SchemaManager.ANNOTATION_HELP', 'Add structured data for this page. Title and URL are taken from the page automatically.'));
            $sources = [];
            foreach (($definition['fields'] ?? []) as $property => $config) {
                if (is_array($config) && !empty($config['source'])) {
                    $sources[] = $property;
                }
            }
            if ($sources) {
                $description .= ' ' . sprintf(_t('SchemaManager.AUTO_POPULATED', 'Automatically populated: %s.'), implode(', ', $sources));
            }
            $fields->addFieldToTab('Root.Schema', LiteralField::create(
                'SchemaAnnotationHelp_' . $key,
                '<div class="schema-annotation-help" data-schema-type="' . htmlspecialchars((string) $key, ENT_QUOTES) . '">' .
                htmlspecialchars((string) $description, ENT_QUOTES) . '</div>'
            ));
            foreach (($definition['fields'] ?? []) as $property => $config) {
                if (!is_array($config) || isset($config['source'])) {
                    continue;
                }

                $name = 'SchemaAnnotationEditor_' . $key . '_' . $property;
                $label = _t('SchemaManager.FIELD_' . strtoupper($property), $config['label'] ?? ucfirst($property));
                $value = $key === $this->owner->SchemaAnnotationType
                    ? ($values[$property] ?? ($config['default'] ?? null))
                    : ($config['default'] ?? null);
                $kind = $config['type'] ?? 'text';

                if ($kind === 'entity') {
                    $this->addEntityFields($fields, $name, $label, $key, $property, $config, $value);
                    continue;
                }

                $field = match ($kind) {
                    'textarea' => TextareaField::create($name, $label),
                    'datetime' => DatetimeField::create($name, $label),
                    'number' => NumericField::create($name, $label),
                    'select' => DropdownField::create($name, $label, array_map(
                        fn ($option) => _t('SchemaManager.OPTION_' . strtoupper((string) $option), (string) $option),
                        $config['options'] ?? []
                    )),
                    default => TextField::create($name, $label),
                };
                $field->setValue($value);
                $field->setAttribute('data-schema-type', $key);
                $field->setAttribute('data-schema-property', $property);
                if (!empty($config['required'])) {
                    $field->setDescription(_t('SchemaManager.REQUIRED_HELP', 'Required for schema output'));
                }
                $fields->addFieldToTab('Root.Schema', $field);
            }
        }

        Requirements::javascript('dorsetdigital/silverstripe-schema-manager:client/dist/js/page-annotations.js');
    }

    private function addEntityFields(
        FieldList $fields,
        string $name,
        string $label,
        string $key,
        string $property,
        array $config,
        mixed $value
    ): void {
        $value = is_array($value) ? $value : [];
        $targets = $config['targets'] ?? [];
        $modes = [];
        if (!empty($config['allow_existing'])) {
            $modes['existing'] = _t('SchemaManager.EXISTING_ENTITY', 'Existing entity');
        }
        if (!empty($config['allow_inline'])) {
            $modes['inline'] = _t('SchemaManager.ANOTHER_ENTITY', 'Another entity');
        }
        $mode = $value['mode'] ?? (isset($modes['existing']) ? 'existing' : 'inline');

        $modeField = DropdownField::create($name . '_mode', $label, $modes)
            ->setEmptyString('None')
            ->setValue($value ? $mode : '');
        $modeField->setAttribute('data-schema-type', $key);
        $modeField->setAttribute('data-schema-property', $property);
        $modeField->setAttribute('data-schema-entity-part', 'mode');
        $fields->addFieldToTab('Root.Schema', $modeField);

        $refs = [];
        if (in_array('Organization', $targets, true)) {
            $refs[SchemaIDs::organisation()] = _t('SchemaManager.OUR_ORGANISATION', 'Our organisation');
        }
        $refField = DropdownField::create($name . '_id', _t('SchemaManager.EXISTING_ENTITY', 'Existing entity'), $refs)
            ->setEmptyString(_t('SchemaManager.SELECT_ENTITY', 'Select an entity'))
            ->setValue($value['id'] ?? '');
        $refField->setAttribute('data-schema-type', $key);
        $refField->setAttribute('data-schema-property', $property);
        $refField->setAttribute('data-schema-entity-part', 'id');
        $fields->addFieldToTab('Root.Schema', $refField);

        $typeField = DropdownField::create($name . '_type', _t('SchemaManager.ENTITY_TYPE', 'Entity type'), array_combine($targets, $targets) ?: [])
            ->setValue($value['type'] ?? ($targets[0] ?? null));
        $typeField->setAttribute('data-schema-type', $key);
        $typeField->setAttribute('data-schema-property', $property);
        $typeField->setAttribute('data-schema-entity-part', 'type');
        $fields->addFieldToTab('Root.Schema', $typeField);

        $nameField = TextField::create($name . '_name', _t('SchemaManager.ENTITY_NAME', 'Entity name'))->setValue($value['name'] ?? '');
        $nameField->setAttribute('data-schema-type', $key);
        $nameField->setAttribute('data-schema-property', $property);
        $nameField->setAttribute('data-schema-entity-part', 'name');
        $fields->addFieldToTab('Root.Schema', $nameField);
    }

    public function updateSchemaManagerEntities(array &$entities): void
    {
        $type = (string) $this->owner->SchemaAnnotationType;
        if ($type === '') {
            return;
        }
        $values = json_decode((string) $this->owner->SchemaAnnotationValues, true);
        if (!is_array($values)) {
            return;
        }
        $schema = (new SchemaAnnotationFactory())->build($this->owner, $type, $values);
        if ($schema) {
            $entities[] = $schema;
        }
    }
}
