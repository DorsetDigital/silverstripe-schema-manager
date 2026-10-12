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
            $options[$key] = $definition['label'] ?? $key;
        }

        $fields->addFieldToTab('Root.Schema', DropdownField::create(
            'SchemaAnnotationType',
            'Page annotation',
            $options
        )->setEmptyString('None'));

        $values = json_decode((string) $this->owner->SchemaAnnotationValues, true);
        $values = is_array($values) ? $values : [];
        $fields->addFieldToTab('Root.Schema', HiddenField::create(
            'SchemaAnnotationValues',
            '',
            json_encode($values, JSON_UNESCAPED_SLASHES)
        ));

        foreach ($definitions as $key => $definition) {
            $description = $definition['description'] ?? 'Add structured data for this page. Title and URL are taken from the page automatically.';
            $sources = [];
            foreach (($definition['fields'] ?? []) as $property => $config) {
                if (is_array($config) && !empty($config['source'])) {
                    $sources[] = $property;
                }
            }
            if ($sources) {
                $description .= ' Automatically populated: ' . implode(', ', $sources) . '.';
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
                $label = $config['label'] ?? ucfirst($property);
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
                    'select' => DropdownField::create($name, $label, $config['options'] ?? []),
                    default => TextField::create($name, $label),
                };
                $field->setValue($value);
                $field->setAttribute('data-schema-type', $key);
                $field->setAttribute('data-schema-property', $property);
                if (!empty($config['required'])) {
                    $field->setDescription('Required for schema output');
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
            $modes['existing'] = 'Existing entity';
        }
        if (!empty($config['allow_inline'])) {
            $modes['inline'] = 'Another entity';
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
            $refs[SchemaIDs::organisation()] = 'Our organisation';
        }
        $refField = DropdownField::create($name . '_id', 'Existing entity', $refs)
            ->setEmptyString('Select an entity')
            ->setValue($value['id'] ?? '');
        $refField->setAttribute('data-schema-type', $key);
        $refField->setAttribute('data-schema-property', $property);
        $refField->setAttribute('data-schema-entity-part', 'id');
        $fields->addFieldToTab('Root.Schema', $refField);

        $typeField = DropdownField::create($name . '_type', 'Entity type', array_combine($targets, $targets) ?: [])
            ->setValue($value['type'] ?? ($targets[0] ?? null));
        $typeField->setAttribute('data-schema-type', $key);
        $typeField->setAttribute('data-schema-property', $property);
        $typeField->setAttribute('data-schema-entity-part', 'type');
        $fields->addFieldToTab('Root.Schema', $typeField);

        $nameField = TextField::create($name . '_name', 'Entity name')->setValue($value['name'] ?? '');
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
