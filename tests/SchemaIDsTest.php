<?php

namespace DorsetDigital\SchemaManager\Tests;

use DorsetDigital\SchemaManager\Control\SchemaIDs;
use PHPUnit\Framework\TestCase;

class SchemaIDsTest extends TestCase
{
    public function testPageURLNormalisationAndReferences(): void
    {
        $id = SchemaIDs::forPageURL('https://example.com/about', 'webpage');
        $this->assertSame('https://example.com/about/#webpage', $id);
        $this->assertSame($id, SchemaIDs::forPageURL('https://example.com/about/', '#webpage'));
        $this->assertSame(['@id' => $id], SchemaIDs::ref($id));
    }
}
