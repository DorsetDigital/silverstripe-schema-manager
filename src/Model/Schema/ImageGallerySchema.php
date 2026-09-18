<?php

namespace DorsetDigital\SchemaManager\Model\Schema;

class ImageGallerySchema extends Schema
{
    public static function create(string $url, ?string $name = null, ?string $description = null): static
    {
        $url = rtrim($url, '/') . '/';

        $data = [
            '@type' => 'ImageGallery',
            '@id' => $url . '#imagegallery',
            'url' => $url,
            'mainEntityOfPage' => [
                '@id' => $url . '#webpage',
            ],
            'associatedMedia' => [],
        ];

        if ($name) {
            $data['name'] = $name;
        }

        if ($description) {
            $data['description'] = $description;
        }

        return new static($data);
    }

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

        $this->data['associatedMedia'][] = $image;

        return $this;
    }

    public function setThumbnail(?string $url): static
    {
        if ($url) {
            $this->data['thumbnailUrl'] = $url;
        }

        return $this;
    }
}
