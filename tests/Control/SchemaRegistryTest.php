<?php

namespace DorsetDigital\SchemaManager\Tests\Control;

use DorsetDigital\SchemaManager\Control\SchemaRegistry;
use DorsetDigital\SchemaManager\Model\Schema\Schema;
use SilverStripe\Dev\SapphireTest;

class SchemaRegistryTest extends SapphireTest
{
    protected function tearDown(): void
    {
        SchemaRegistry::flush();
        parent::tearDown();
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

    public function testFaqQuestionsAreAddedToSingleFaqPage(): void
    {
        $url = 'https://example.com/faqs/';

        SchemaRegistry::addFAQ('Question one?', 'Answer one.', $url);
        SchemaRegistry::addFAQ('Question two?', 'Answer two.', $url);

        $graph = SchemaRegistry::getGraph();

        $this->assertCount(1, $graph);
        $this->assertSame('FAQPage', $graph[0]['@type']);
        $this->assertSame('https://example.com/faqs/#faq', $graph[0]['@id']);
        $this->assertCount(2, $graph[0]['mainEntity']);
        $this->assertSame('Question two?', $graph[0]['mainEntity'][1]['name']);
        $this->assertSame('Answer two.', $graph[0]['mainEntity'][1]['acceptedAnswer']['text']);
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
