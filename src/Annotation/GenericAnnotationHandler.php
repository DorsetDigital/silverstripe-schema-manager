<?php

namespace DorsetDigital\SchemaManager\Annotation;

use DorsetDigital\SchemaManager\Control\SchemaIDs;
use DorsetDigital\SchemaManager\Model\Schema\GenericSchema;
use DorsetDigital\SchemaManager\Model\Schema\Schema;
use SilverStripe\CMS\Model\SiteTree;

class GenericAnnotationHandler implements SchemaAnnotationHandler
{
    public function build(SiteTree $page, array $values, array $definition): ?Schema
    {
        $type = $definition['schema_type'] ?? null;
        $key = $definition['key'] ?? null;
        if (!is_string($type) || !$type || !is_string($key) || !$key) {
            throw new \InvalidArgumentException('Annotation definition requires a key and schema_type.');
        }

        $url = rtrim($page->AbsoluteLink(), '/') . '/';
        $data = [
            '@type' => $type,
            '@id' => SchemaIDs::forPageURL($url, 'annotation-' . strtolower(preg_replace('/[^a-zA-Z0-9-]/', '-', $key))),
            'url' => $url,
        ];

        foreach (($definition['fields'] ?? []) as $property => $field) {
            if (!is_array($field)) {
                continue;
            }

            if (isset($field['source'])) {
                $value = match ($field['source']) {
                    'page.Title' => $page->Title,
                    'page.AbsoluteLink' => $url,
                    'page.MetaDescription' => $page->MetaDescription,
                    default => throw new \InvalidArgumentException('Unsupported annotation source: ' . $field['source']),
                };
            } else {
                $value = $values[$property] ?? ($field['default'] ?? null);
            }

            if ($value === null || $value === '') {
                if (!empty($field['required'])) {
                    return null;
                }
                continue;
            }

            if (($field['type'] ?? null) === 'entity') {
                $value = $this->resolveEntity($value, $field);
            } elseif (($field['type'] ?? null) === 'select') {
                if (!array_key_exists((string) $value, $field['options'] ?? [])) {
                    return null;
                }
                $value = ($field['prefix'] ?? '') . $value;
            }

            if ($value !== null) {
                $data[$property] = $value;
            }
        }

        return GenericSchema::fromArray($data);
    }

    private function resolveEntity(mixed $value, array $field): ?array
    {
        if (!is_array($value)) {
            return null;
        }

        if (($value['mode'] ?? '') === 'existing' && !empty($field['allow_existing'])) {
            $id = $value['id'] ?? null;
            return is_string($id) && $id !== '' ? ['@id' => $id] : null;
        }

        if (($value['mode'] ?? '') === 'inline' && !empty($field['allow_inline'])) {
            $type = $value['type'] ?? null;
            $name = $value['name'] ?? null;
            if (in_array($type, $field['targets'] ?? [], true) && is_string($name) && trim($name) !== '') {
                return ['@type' => $type, 'name' => trim($name)];
            }
        }

        return null;
    }
}
