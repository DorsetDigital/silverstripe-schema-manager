<?php

namespace DorsetDigital\SchemaManager\Model\Schema;

use DorsetDigital\SchemaManager\Control\SchemaIDs;

class BreadcrumbListSchema extends Schema
{
    public static function create(string $identifier): static
    {
        return new static([
            '@type' => 'BreadcrumbList',
            '@id' => SchemaIDs::forEntity('breadcrumblist', $identifier),
            'itemListElement' => [],
        ]);
    }

    public function addItem(string $name, string $url): static
    {
        $this->data['itemListElement'][] = [
            '@type' => 'ListItem',
            'position' => count($this->data['itemListElement']) + 1,
            'name' => $name,
            'item' => $url,
        ];
        return $this;
    }
}
