<?php

namespace DorsetDigital\SchemaManager\Extension;

use DorsetDigital\SchemaManager\Annotation\SchemaDefinitionRegistry;
use DorsetDigital\SchemaManager\Annotation\SchemaAnnotationFactory;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextareaField;

/**
 * Stores one annotation on SiteTree itself, so draft and published values
 * follow the page's existing Versioned lifecycle.
 */
class PageSchemaAnnotationExtension extends Extension
{
    private static array $db = [
        'SchemaAnnotationType' => 'Varchar(100)',
        'SchemaAnnotationValues' => 'Text',
    ];

    public function updateCMSFields(FieldList $fields): void
    {
        $options = [];
        foreach (SchemaDefinitionRegistry::definitions() as $key => $definition) {
            $options[$key] = $definition['label'] ?? $key;
        }

        $fields->addFieldToTab(
            'Root.Schema',
            DropdownField::create('SchemaAnnotationType', 'Page annotation', $options)
                ->setEmptyString('None')
        );
        $fields->addFieldToTab(
            'Root.Schema',
            TextareaField::create('SchemaAnnotationValues', 'Annotation values (JSON)')
                ->setDescription('Temporary developer-facing editor. A YAML-driven field editor will replace this.')
        );
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
