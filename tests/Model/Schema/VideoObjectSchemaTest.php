<?php

namespace DorsetDigital\SchemaManager\Tests\Model\Schema;

use DorsetDigital\SchemaManager\Model\Schema\VideoObjectSchema;
use SilverStripe\Dev\SapphireTest;

class VideoObjectSchemaTest extends SapphireTest
{
    public function testCreatesStableVideoObject(): void
    {
        $schema = VideoObjectSchema::create('youtube-abc123', 'Example video', 'An example description');
        $data = $schema->toArray();

        $this->assertSame('VideoObject', $data['@type']);
        $this->assertStringEndsWith('#videoobject-youtube-abc123', $schema->getEntityID());
        $this->assertSame('Example video', $data['name']);
        $this->assertSame('An example description', $data['description']);
    }

    public function testSupportsYouTubeEmbedAndPageRelationship(): void
    {
        $schema = VideoObjectSchema::create('youtube-abc123', 'Example video')
            ->setURL('https://www.youtube.com/watch?v=abc123')
            ->setEmbedURL('https://www.youtube.com/embed/abc123')
            ->setThumbnailURL('https://example.com/thumb.jpg')
            ->setUploadDate('2026-10-01')
            ->setDuration('PT3M25S')
            ->setIsPartOfPage('https://example.com/articles/');

        $data = $schema->toArray();
        $this->assertSame('https://www.youtube.com/embed/abc123', $data['embedUrl']);
        $this->assertSame('https://example.com/thumb.jpg', $data['thumbnailUrl']);
        $this->assertSame('2026-10-01', $data['uploadDate']);
        $this->assertSame('PT3M25S', $data['duration']);
        $this->assertStringEndsWith('/articles/#webpage', $data['isPartOf']['@id']);
    }

    public function testSupportsVimeoAndOptionalPropertyRemoval(): void
    {
        $schema = VideoObjectSchema::create('vimeo-12345', 'Vimeo video')
            ->setURL('https://vimeo.com/12345')
            ->setEmbedURL('https://player.vimeo.com/video/12345')
            ->setContentURL('https://example.com/video.mp4');

        $this->assertSame('https://player.vimeo.com/video/12345', $schema->toArray()['embedUrl']);
        $this->assertSame('https://example.com/video.mp4', $schema->toArray()['contentUrl']);

        $schema->setContentURL(null)->setEmbedURL(null);
        $this->assertArrayNotHasKey('contentUrl', $schema->toArray());
        $this->assertArrayNotHasKey('embedUrl', $schema->toArray());
    }
}
