<?php

namespace DorsetDigital\SchemaManager\Tests\Control;

use DorsetDigital\SchemaManager\Control\SchemaRegistry;
use DorsetDigital\SchemaManager\Control\FAQPageHelper;
use DorsetDigital\SchemaManager\Model\Schema\Schema;
use DorsetDigital\SchemaManager\Model\Schema\FAQPageSchema;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\SapphireTest;

class SchemaRegistryTest extends SapphireTest
{
    protected function tearDown(): void
    {
        SchemaRegistry::flush();
        parent::tearDown();
    }

    public function testIsolatedRegistryDoesNotLeakBetweenPages(): void
    {
        SchemaRegistry::addEntity('https://example.com/#outer', [
            '@id' => 'https://example.com/#outer',
            '@type' => 'Thing',
        ]);

        $inner = SchemaRegistry::withIsolatedRegistry(static function (): array {
            SchemaRegistry::addEntity('https://example.com/page-a/#inner', [
                '@id' => 'https://example.com/page-a/#inner',
                '@type' => 'WebPage',
            ]);
            return SchemaRegistry::getGraph();
        });

        $this->assertCount(1, $inner);
        $this->assertSame('https://example.com/page-a/#inner', $inner[0]['@id']);
        $outer = SchemaRegistry::getGraph();
        $this->assertCount(1, $outer);
        $this->assertSame('https://example.com/#outer', $outer[0]['@id']);
    }

    public function testIsolatedRegistryRestoresStateAfterException(): void
    {
        SchemaRegistry::addEntity('https://example.com/#outer', [
            '@id' => 'https://example.com/#outer',
            '@type' => 'Thing',
        ]);

        try {
            SchemaRegistry::withIsolatedRegistry(static function (): void {
                SchemaRegistry::addEntity('https://example.com/#inner', [
                    '@id' => 'https://example.com/#inner',
                    '@type' => 'Thing',
                ]);
                throw new \RuntimeException('Simulated collection failure');
            });
            $this->fail('Expected collection exception');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Simulated collection failure', $exception->getMessage());
        }

        $graph = SchemaRegistry::getGraph();
        $this->assertCount(1, $graph);
        $this->assertSame('https://example.com/#outer', $graph[0]['@id']);
    }

    public function testEntityCanBeAdded(): void
    {
        SchemaRegistry::addEntity('https://example.com/#thing', [
            '@type' => 'Thing',
            'name' => 'Example',
        ]);

        $graph = SchemaRegistry::getGraph();

        $this->assertCount(1, $graph);
        $this->assertSame('Thing', $graph[0]['@type']);
        $this->assertSame('Example', $graph[0]['name']);
    }

    public function testTypedSchemaCanBeAdded(): void
    {
        $schema = new class([
            '@type' => 'Thing',
            '@id' => 'https://example.com/#thing',
            'name' => 'Typed example',
        ]) extends Schema {
        };

        SchemaRegistry::add($schema);

        $graph = SchemaRegistry::getGraph();

        $this->assertCount(1, $graph);
        $this->assertSame('Typed example', $graph[0]['name']);
    }

    public function testEntityDataIsMergedById(): void
    {
        $id = 'https://example.com/#organisation';

        SchemaRegistry::addEntity($id, [
            '@type' => 'Organization',
            'name' => 'Example Ltd',
        ]);

        SchemaRegistry::addEntity($id, [
            'url' => 'https://example.com/',
        ]);

        $graph = SchemaRegistry::getGraph();

        $this->assertCount(1, $graph);
        $this->assertSame('Example Ltd', $graph[0]['name']);
        $this->assertSame('https://example.com/', $graph[0]['url']);
    }

    public function testMultipleFaqSectionsAreCombinedIntoOneEntity(): void
    {
        $url = 'https://example.com/faqs/';

        FAQPageHelper::addQuestion('First question?', 'First answer.', $url);
        FAQPageHelper::addQuestion('Second question?', 'Second answer.', $url);
        FAQPageHelper::addQuestion('Third question?', 'Third answer.', $url);

        $graph = SchemaRegistry::getGraph();

        $this->assertCount(1, $graph);
        $this->assertSame($url . '#faq', $graph[0]['@id']);
        $this->assertSame(
            ['First question?', 'Second question?', 'Third question?'],
            array_column($graph[0]['mainEntity'], 'name')
        );
        // Repeated serialization must not duplicate the aggregated questions.
        $this->assertSame($graph, SchemaRegistry::getGraph());
    }

    public function testDeprecatedFaqHelperStillRegistersFaqPage(): void
    {
        SchemaRegistry::addFAQ(
            'Question one?',
            'Answer one.',
            'https://example.com/faqs/'
        );

        $graph = SchemaRegistry::getGraph();

        $this->assertCount(1, $graph);
        $this->assertSame('FAQPage', $graph[0]['@type']);
        $this->assertSame('https://example.com/faqs/#faq', $graph[0]['@id']);
        $this->assertSame('Question one?', $graph[0]['mainEntity'][0]['name']);
    }

    public function testSilverstripeBreadcrumbsCanBeAdded(): void
    {
        $section = SiteTree::create([
            'Title' => 'About us',
            'MenuTitle' => 'About',
            'URLSegment' => 'about',
        ]);
        $section->write();

        $page = SiteTree::create([
            'Title' => 'Our team',
            'URLSegment' => 'team',
            'ParentID' => $section->ID,
        ]);
        $page->write();

        SchemaRegistry::addBreadCrumbs($page);

        $graph = SchemaRegistry::getGraph();

        $this->assertCount(1, $graph);
        $this->assertSame('BreadcrumbList', $graph[0]['@type']);
        $this->assertStringEndsWith('/about/team/#breadcrumb', $graph[0]['@id']);
        $this->assertCount(2, $graph[0]['itemListElement']);
        $this->assertSame(1, $graph[0]['itemListElement'][0]['position']);
        $this->assertSame('About', $graph[0]['itemListElement'][0]['name']);
        $this->assertSame('/about', rtrim(parse_url($graph[0]['itemListElement'][0]['item'], PHP_URL_PATH), '/'));
        $this->assertSame(2, $graph[0]['itemListElement'][1]['position']);
        $this->assertSame('Our team', $graph[0]['itemListElement'][1]['name']);
        $this->assertSame('/about/team', rtrim(parse_url($graph[0]['itemListElement'][1]['item'], PHP_URL_PATH), '/'));
    }

    public function testSingleBreadcrumbIsNotAdded(): void
    {
        $page = SiteTree::create([
            'Title' => 'About us',
            'URLSegment' => 'about',
        ]);
        $page->write();

        SchemaRegistry::addBreadCrumbs($page);

        $this->assertSame([], SchemaRegistry::getGraph());
    }

    public function testJsonContainsSchemaGraph(): void
    {
        SchemaRegistry::addEntity('https://example.com/#thing', [
            '@type' => 'Thing',
        ]);

        $decoded = json_decode(SchemaRegistry::getJSON(), true);

        $this->assertSame('https://schema.org', $decoded['@context']);
        $this->assertSame('Thing', $decoded['@graph'][0]['@type']);
    }
}
