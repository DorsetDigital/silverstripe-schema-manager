<?php

namespace DorsetDigital\SchemaManager\Tests\View;

use DorsetDigital\SchemaManager\Control\SchemaRegistry;
use DorsetDigital\SchemaManager\View\SchemaRequirementsBackend;
use SilverStripe\Dev\SapphireTest;

class SchemaRequirementsBackendTest extends SapphireTest
{
    protected function tearDown(): void
    {
        SchemaRegistry::flush();
        parent::tearDown();
    }

    public function testSchemaIsReadWhenRequirementsAreRendered(): void
    {
        $backend = new SchemaRequirementsBackend();

        SchemaRegistry::addEntity('https://example.com/#thing', [
            '@type' => 'Thing',
            '@id' => 'https://example.com/#thing',
            'name' => 'Added after initialisation',
        ]);

        $html = $backend->includeInHTML('<html><head></head><body></body></html>');

        $this->assertStringContainsString('application/ld+json', $html);
        $this->assertStringContainsString('Added after initialisation', $html);
    }
}
