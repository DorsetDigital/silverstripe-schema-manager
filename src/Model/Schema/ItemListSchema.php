<?php

namespace DorsetDigital\SchemaManager\Model\Schema;

use DorsetDigital\SchemaManager\Control\SchemaIDs;

/**
 * An ordered list of links or references to existing schema entities.
 */
class ItemListSchema extends Schema
{
    public static function create(string $identifier, ?string $name = null): static
    {
        $data = [
            '@type' => 'ItemList',
            '@id' => SchemaIDs::forEntity('itemlist', $identifier),
            'itemListElement' => [],
        ];

        if ($name !== null) {
            $data['name'] = $name;
        }

        return new static($data);
    }

    public function addItem(string $url, ?string $name = null): static
    {
        $item = ['@id' => $url];
        if ($name !== null) {
            $item['name'] = $name;
        }

        return $this->appendItem($item);
    }

    public function addEntity(string $entityID, ?string $name = null, ?string $url = null): static
    {
        $item = ['@id' => $entityID];
        if ($name !== null) {
            $item['name'] = $name;
        }
        if ($url !== null) {
            $item['url'] = $url;
        }

        return $this->appendItem($item);
    }

    /**
     * ItemList is not a CreativeWork, so isPartOf is not a valid property.
     * Keep the inherited setter available for backwards compatibility, but
     * omit the relationship from serialized ItemList schema.
     */
    public function toArray(): array
    {
        $data = parent::toArray();
        unset($data['isPartOf']);

        return $data;
    }

    private function appendItem(array $item): static
    {
        $position = count($this->data['itemListElement']) + 1;
        $this->data['itemListElement'][] = [
            '@type' => 'ListItem',
            'position' => $position,
            'item' => $item,
        ];
        $this->data['numberOfItems'] = $position;

        return $this;
    }
}
