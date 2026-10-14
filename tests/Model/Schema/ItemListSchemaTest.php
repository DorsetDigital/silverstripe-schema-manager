<?php

namespace DorsetDigital\SchemaManager\Tests\Model\Schema;

use DorsetDigital\SchemaManager\Control\SchemaIDs;
use DorsetDigital\SchemaManager\Model\Schema\ItemListSchema;
use SilverStripe\Dev\SapphireTest;

class ItemListSchemaTest extends SapphireTest
{
    public function testCreatesListWithEntityReferences(): void
    {
        $id = SchemaIDs::forEntity('blogposting', '123');
        $list = ItemListSchema::create('latest-news-42', 'Latest News')
            ->addEntity($id, 'An article', 'https://example.com/news/article/')
            ->addEntity(SchemaIDs::forEntity('blogposting', '456'), 'Another article', 'https://example.com/news/another/')
            ->setIsPartOfPage('https://example.com/home/');

        $data = $list->toArray();
        $this->assertSame('ItemList', $data['@type']);
        $this->assertStringEndsWith('#itemlist-latest-news-42', $list->getEntityID());
        $this->assertSame(2, $data['numberOfItems']);
        $this->assertSame(1, $data['itemListElement'][0]['position']);
        $this->assertSame(2, $data['itemListElement'][1]['position']);
        $this->assertSame($id, $data['itemListElement'][0]['item']['@id']);
        $this->assertSame('https://example.com/news/article/', $data['itemListElement'][0]['item']['url']);
        $this->assertStringEndsWith('/home/#webpage', $data['isPartOf']['@id']);
    }

    public function testSupportsSimpleLinkedItems(): void
    {
        $data = ItemListSchema::create('related', 'Related pages')
            ->addItem('https://example.com/one/', 'One')
            ->addItem('https://example.com/two/')
            ->toArray();

        $this->assertSame(2, $data['numberOfItems']);
        $this->assertSame('https://example.com/one/', $data['itemListElement'][0]['item']['@id']);
        $this->assertSame('One', $data['itemListElement'][0]['item']['name']);
        $this->assertArrayNotHasKey('name', $data['itemListElement'][1]['item']);
    }
}
