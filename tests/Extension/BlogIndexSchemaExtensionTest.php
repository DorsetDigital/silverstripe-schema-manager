<?php

namespace DorsetDigital\SchemaManager\Tests\Extension;

use DorsetDigital\SchemaManager\Control\SchemaIDs;
use DorsetDigital\SchemaManager\Extension\BlogIndexSchemaExtension;
use PHPUnit\Framework\TestCase;

class BlogIndexSchemaExtensionTest extends TestCase
{
    public function testBuildListUsesStableArticleIDsAndURLs(): void
    {
        $post = new class {
            public int $ID = 42;
            public string $Title = 'Example post';
            public function AbsoluteLink(): string
            {
                return 'https://example.com/news/example-post/';
            }
        };

        $list = BlogIndexSchemaExtension::buildList(5, 'News', [$post], 'news/?start=0');
        $this->assertNotNull($list);
        $data = $list->toArray();

        $this->assertSame('ItemList', $data['@type']);
        $this->assertSame(1, $data['numberOfItems']);
        $this->assertSame('News', $data['name']);
        $this->assertSame(1, $data['itemListElement'][0]['position']);
        $this->assertSame(
            SchemaIDs::forEntity('blogposting', '42'),
            $data['itemListElement'][0]['item']['@id']
        );
        $this->assertSame('https://example.com/news/example-post/', $data['itemListElement'][0]['item']['url']);
        $this->assertSame($list->getEntityID(), BlogIndexSchemaExtension::buildList(5, 'News', [$post], 'news/?start=0')->getEntityID());
        $this->assertNotSame($list->getEntityID(), BlogIndexSchemaExtension::buildList(5, 'News', [$post], 'news/?start=10')->getEntityID());
    }

    public function testEmptyListIsOmitted(): void
    {
        $this->assertNull(BlogIndexSchemaExtension::buildList(5, 'News', [], 'news/'));
    }
}
