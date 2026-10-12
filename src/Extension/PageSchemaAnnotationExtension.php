<?php

namespace DorsetDigital\SchemaManager\Extension;

use DorsetDigital\SchemaManager\Annotation\SchemaDefinitionRegistry;
use DorsetDigital\SchemaManager\Annotation\SchemaAnnotationFactory;
use DorsetDigital\SchemaManager\Control\SchemaIDs;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\DatetimeField;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\HeaderField;

/**
 * Stores the single optional annotation on the versioned page itself.
 * Values are individual fields to allow normal SiteTree draft/publish semantics.
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
        $type = (string) $this->owner->SchemaAnnotationType;

        // A single annotation is edited at a time. Changing the type and saving
        // reveals the fields for the newly selected definition.
        $definition = $definitions[$type] ?? null;
        if (!$definition) {
            $fields->addFieldToTab('Root.Schema', HeaderField::create(
                'SchemaAnnotationHint',
                'Select an annotation type and save to edit its fields'
            ));
            return;
        }

        foreach (($definition['fields'] ?? []) as $property => $config) {
            if (!is_array($config) || array_key_exists('source', $config) && $config['source'] !== null) {
                continue;
            }

            $fieldName = 'SchemaAnnotationField_' . $property;
            $label = $config['label'] ?? ucfirst($property);
            $value = $values[$property] ?? ($config['default'] ?? null);
            $kind = $config['type'] ?? 'text';

            $field = match ($kind) {
                'textarea' => TextareaField::create($fieldName, $label),
                'datetime' => DatetimeField::create($fieldName, $label),
                'number' => NumericField::create($fieldName, $label),
                'select' => DropdownField::create($fieldName, $label, $config['options'] ?? []),
                'entity' => $this->entityField($fieldName, $label, $config, $value),
                default => TextField::create($fieldName, $label),
            };

            if ($kind !== 'entity') {
                $field->setValue($value);
            }
            if (!empty($config['required'])) {
                $field->setDescription('Required for schema output');
            }
            $fields->addFieldToTab('Root.Schema', $field);
        }
    }

    private function entityField(string $name, string $label, array $config, mixed $value): TextField
    {
        // The simple first-stage editor accepts a name for an inline entity.
        // Existing site organisation can be selected by entering its canonical ID.
        $display = is_array($value)
            ? (($value['mode'] ?? '') === 'existing' ? ($value['id'] ?? '') : ($value['name'] ?? ''))
            : '';
        return TextField::create($name, $label)
            ->setValue($display)
            ->setDescription('Enter a name for an inline ' . implode('/', $config['targets'] ?? ['entity'])
                . ', or an existing schema @id URL.');
    }

    public function onBeforeWrite(): void
    {
        // Fields are submitted to the page through the normal CMS form.
        // See updateCMSFields and the annotation save hook below.
    }

    public function updateSchemaManagerEntities(array &$entities): void
    {
        $type = (string) $this->owner->SchemaAnnotationType;
        if ($type === '') {
            return;
        }

        $values = json_decode((string) $this->owner->SchemaAnnotationValues, true);
        $schema = (new SchemaAnnotationFactory())->build(
            $this->owner,
            $type,
            is_array($values) ? $values : []
        );
        if ($schema) {
            $entities[] = $schema;
        }
    }
}
