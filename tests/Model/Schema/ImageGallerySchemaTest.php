<?php

namespace DorsetDigital\SchemaManager\Tests\Model\Schema;

use DorsetDigital\SchemaManager\Model\Schema\ImageGallerySchema;
use SilverStripe\Dev\SapphireTest;

class ImageGallerySchemaTest extends SapphireTest
{
    public function testDefaultIDIsPreserved(): void
    {
        $schema = ImageGallerySchema::create('https://example.com/gallery/');

        $this->assertSame(
            'https://example.com/gallery/#imagegallery',
            $schema->getID()
        );
    }

    public function testIdentifierCreatesUniqueGalleryID(): void
    {
        $first = ImageGallerySchema::create('https://example.com/gallery/', null, null, '42');
        $second = ImageGallerySchema::create('https://example.com/gallery/', null, null, '73');

        $this->assertSame('https://example.com/gallery/#imagegallery-42', $first->getID());
        $this->assertSame('https://example.com/gallery/#imagegallery-73', $second->getID());
        $this->assertNotSame($first->getID(), $second->getID());
    }
}
