<?php

namespace DorsetDigital\SchemaManager\Tests\Model\Schema;

use DorsetDigital\SchemaManager\Model\Schema\ImageGallerySchema;
use SilverStripe\Dev\SapphireTest;

class ImageGallerySchemaTest extends SapphireTest
{
    public function testStableSiteScopedID(): void
    {
        $schema = ImageGallerySchema::create('gallery');

        $this->assertStringEndsWith('#imagegallery-gallery', $schema->getEntityID());
    }

    public function testIdentifierCreatesUniqueGalleryID(): void
    {
        $first = ImageGallerySchema::create('gallery-42');
        $second = ImageGallerySchema::create('gallery-73');

        $this->assertStringEndsWith('#imagegallery-gallery-42', $first->getEntityID());
        $this->assertStringEndsWith('#imagegallery-gallery-73', $second->getEntityID());
        $this->assertNotSame($first->getEntityID(), $second->getEntityID());
    }
}
