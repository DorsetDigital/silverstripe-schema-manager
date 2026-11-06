<?php

namespace DorsetDigital\SchemaManager\Model\Schema;

/**
 * A CollectionPage reusing the already registered WebPage ID and properties.
 * Register an ItemListSchema separately as its main entity.
 */
class PropertyCollectionSchema extends WebPageSchema
{
    public static function fromWebPage(WebPageSchema $page, string $itemListID): static
    {
        $data = $page->toArray();
        $data['@type'] = 'CollectionPage';
        $data['mainEntity'] = ['@id' => $itemListID];
        return new static($data);
    }
}
