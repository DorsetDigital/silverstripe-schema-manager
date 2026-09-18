<?php

namespace DorsetDigital\SchemaManager\Tests\Model\Schema;

use DorsetDigital\SchemaManager\Model\Schema\Schema;
use PHPUnit\Framework\TestCase;

class SchemaTest extends TestCase
{
    public function testMainEntityRelationshipReplacesPageIsPartOfRelationship(): void
    {
        $schema = new class([
            '@type' => 'Thing',
            '@id' => 'https://example.com/page/#thing',
            'isPartOf' => ['@id' => 'https://example.com/page/#webpage'],
        ]) extends Schema {
        };

        $schema->setMainEntityOfPage('https://example.com/page/');

        $data = $schema->toArray();

        $this->assertArrayNotHasKey('isPartOf', $data);
        $this->assertSame(
            'https://example.com/page/#webpage',
            $data['mainEntityOfPage']['@id']
        );
    }

    public function testMainEntityRelationshipPreservesUnrelatedIsPartOfRelationship(): void
    {
        $schema = new class([
            '@type' => 'Thing',
            '@id' => 'https://example.com/page/#thing',
            'isPartOf' => ['@id' => 'https://example.com/#website'],
        ]) extends Schema {
        };

        $schema->setMainEntityOfPage('https://example.com/page/');

        $data = $schema->toArray();

        $this->assertSame(
            'https://example.com/#website',
            $data['isPartOf']['@id']
        );
        $this->assertSame(
            'https://example.com/page/#webpage',
            $data['mainEntityOfPage']['@id']
        );
    }
}
