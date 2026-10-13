<?php

namespace DorsetDigital\SchemaManager\Tests;

use DorsetDigital\SchemaManager\Control\SchemaIDs;
use PHPUnit\Framework\TestCase;

class SchemaIDsTest extends TestCase
{
    public function testEntityIDUsesSiteBaseURLAndStableFragment(): void
    {
        $base = substr(SchemaIDs::organisation(), 0, -strlen('#organisation'));

        $this->assertSame($base . '#person-member-42', SchemaIDs::forEntity('person', 'member-42'));
        $this->assertSame($base . '#product_product_123', SchemaIDs::forEntity('product', 'product_123'));
        $this->assertSame(SchemaIDs::forEntity('person', 'member-42'), SchemaIDs::forEntity('person', 'member-42'));
    }

    /**
     * @dataProvider invalidEntityIdentifiers
     */
    public function testEntityIDRejectsInvalidComponents(string $type, string $identifier): void
    {
        $this->expectException(\InvalidArgumentException::class);
        SchemaIDs::forEntity($type, $identifier);
    }

    public static function invalidEntityIdentifiers(): array
    {
        return [
            'empty identifier' => ['person', ''],
            'empty type' => ['', 'record'],
            'space' => ['person', 'member 42'],
            'fragment delimiter' => ['person', 'member#42'],
            'path delimiter' => ['person', 'member/42'],
            'query delimiter' => ['person', 'member?42'],
            'leading hyphen' => ['person', '-member'],
            'invalid type' => ['person/type', 'member'],
        ];
    }

    public function testPageURLNormalisationAndReferences(): void
    {
        $id = SchemaIDs::forPageURL('https://example.com/about', 'webpage');
        $this->assertSame('https://example.com/about/#webpage', $id);
        $this->assertSame($id, SchemaIDs::forPageURL('https://example.com/about/', '#webpage'));
        $this->assertSame(['@id' => $id], SchemaIDs::ref($id));
    }
}
