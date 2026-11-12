<?php

namespace DorsetDigital\SchemaManager\Tests\Service;

use DorsetDigital\SchemaManager\Control\SchemaRegistry;
use DorsetDigital\SchemaManager\Service\PageSchemaRenderer;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\SapphireTest;

class PageSchemaRendererTest extends SapphireTest
{
    protected function tearDown(): void
    {
        SchemaRegistry::flush();
        parent::tearDown();
    }

    public function testUnpublishedPageCannotBeRendered(): void
    {
        $page = SiteTree::create([
            'Title' => 'Unpublished',
            'URLSegment' => 'unpublished-schema-test',
        ]);
        $page->write();

        $this->expectException(\InvalidArgumentException::class);
        (new PageSchemaRenderer())->forPublishedPage($page);
    }

    public function testPublishedPageCollectsAutomaticSchemaAndRestoresRegistry(): void
    {
        $page = SiteTree::create([
            'Title' => 'Published schema test',
            'URLSegment' => 'published-schema-test',
        ]);
        $page->write();
        $page->publishSingle();

        SchemaRegistry::addEntity('https://example.com/#outer', [
            '@id' => 'https://example.com/#outer',
            '@type' => 'Thing',
        ]);

        $json = (new PageSchemaRenderer())->forPublishedPage($page);
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('https://schema.org', $data['@context']);

        $types = array_column($data['@graph'], '@type');
        $this->assertContains('WebPage', $types);
        $this->assertContains('WebSite', $types);
        $this->assertContains('Organization', $types);

        $this->assertCount(1, SchemaRegistry::getGraph());
        $this->assertSame('https://example.com/#outer', SchemaRegistry::getGraph()[0]['@id']);
    }
}
