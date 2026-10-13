<?php

namespace DorsetDigital\SchemaManager\Model\Schema;

use DorsetDigital\SchemaManager\Control\SchemaIDs;

class ImageGallerySchema extends Schema
{
    public static function create(string $identifier, ?string $name = null, ?string $description = null): static
    {
        $data = [
            '@type' => 'ImageGallery',
            '@id' => SchemaIDs::forEntity('imagegallery', $identifier),
            'hasPart' => [],
        ];

        if ($name) {
            $data['name'] = $name;
        }

        if ($description) {
            $data['description'] = $description;
        }

        return new static($data);
    }

    public function setURL(?string $url): static { if ($url !== null) $this->data['url'] = $url; else unset($this->data['url']); return $this; }

    public function addImage(
        string $url,
        ?string $name = null,
        ?string $description = null,
        ?int $width = null,
        ?int $height = null
    ): static {
        $image = [
            '@type' => 'ImageObject',
            'contentUrl' => $url,
        ];

        if ($name) {
            $image['name'] = $name;
        }

        if ($description) {
            $image['description'] = $description;
        }

        if ($width) {
            $image['width'] = $width;
        }

        if ($height) {
            $image['height'] = $height;
        }

        $this->data['hasPart'][] = $image;

        return $this;
    }

    public function setThumbnail(?string $url): static
    {
        if ($url !== null) {
            $this->data['thumbnailUrl'] = $url;
        } else {
            unset($this->data['thumbnailUrl']);
        }

        return $this;
    }
}
