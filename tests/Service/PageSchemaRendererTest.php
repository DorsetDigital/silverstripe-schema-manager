<?php

namespace DorsetDigital\SchemaManager\Tests\Service;

use DorsetDigital\SchemaManager\Control\SchemaRegistry;
use DorsetDigital\SchemaManager\Control\SchemaIDs;
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

    public function testPublishedPageIncludesAnnotationAndBreadcrumbs(): void
    {
        $parent = SiteTree::create([
            'Title' => 'Services',
            'URLSegment' => 'services-schema-test',
        ]);
        $parent->write();
        $parent->publishSingle();

        $page = SiteTree::create([
            'Title' => 'Consultancy',
            'URLSegment' => 'consultancy-schema-test',
            'ParentID' => $parent->ID,
            'SchemaAnnotationType' => 'service',
            'SchemaAnnotationValues' => json_encode([
                'serviceType' => 'Consulting',
            ]),
        ]);
        $page->write();
        $page->publishSingle();

        $data = $this->decodePage($page);
        $byType = [];
        foreach ($data['@graph'] as $entity) {
            $byType[$entity['@type']][] = $entity;
        }

        $this->assertCount(1, $byType['WebPage']);
        $this->assertCount(1, $byType['BreadcrumbList']);
        $this->assertCount(1, $byType['Service']);
        $this->assertSame('Consultancy', $byType['Service'][0]['name']);
        $this->assertSame('Consulting', $byType['Service'][0]['serviceType']);
        $this->assertSame(
            ['@id' => $byType['Service'][0]['@id']],
            $byType['WebPage'][0]['mainEntity']
        );
        $this->assertCount(2, $byType['BreadcrumbList'][0]['itemListElement']);
    }

    public function testConsecutivePagesHaveIndependentGraphs(): void
    {
        $pageA = SiteTree::create([
            'Title' => 'First page',
            'URLSegment' => 'schema-page-a',
            'SchemaAnnotationType' => 'service',
            'SchemaAnnotationValues' => '{}',
        ]);
        $pageA->write();
        $pageA->publishSingle();

        $pageB = SiteTree::create([
            'Title' => 'Second page',
            'URLSegment' => 'schema-page-b',
        ]);
        $pageB->write();
        $pageB->publishSingle();

        $renderer = new PageSchemaRenderer();
        $first = $this->decodePage($pageA, $renderer);
        $second = $this->decodePage($pageB, $renderer);

        $firstTypes = array_column($first['@graph'], '@type');
        $secondTypes = array_column($second['@graph'], '@type');
        $this->assertContains('Service', $firstTypes);
        $this->assertNotContains('Service', $secondTypes);

        $firstIDs = array_column($first['@graph'], '@id');
        $secondIDs = array_column($second['@graph'], '@id');
        $this->assertContains(
            SchemaIDs::webPage($pageA),
            $firstIDs
        );
        $this->assertNotContains(
            SchemaIDs::webPage($pageA),
            $secondIDs
        );
        $this->assertContains(
            SchemaIDs::webPage($pageB),
            $secondIDs
        );
    }

    private function decodePage(SiteTree $page, ?PageSchemaRenderer $renderer = null): array
    {
        $json = ($renderer ?? new PageSchemaRenderer())->forPublishedPage($page);
        return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
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
