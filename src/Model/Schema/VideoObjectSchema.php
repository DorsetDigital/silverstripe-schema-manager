<?php

namespace DorsetDigital\SchemaManager\Model\Schema;

use DorsetDigital\SchemaManager\Control\SchemaDate;
use DorsetDigital\SchemaManager\Control\SchemaIDs;

class VideoObjectSchema extends Schema
{
    public static function create(string $identifier, string $name, ?string $description = null): static
    {
        $data = [
            '@type' => 'VideoObject',
            '@id' => SchemaIDs::forEntity('videoobject', $identifier),
            'name' => $name,
        ];

        if ($description !== null) {
            $data['description'] = $description;
        }

        return new static($data);
    }

    public function setURL(?string $url): static { return $this->setOptional('url', $url); }
    public function setEmbedURL(?string $url): static { return $this->setOptional('embedUrl', $url); }
    public function setContentURL(?string $url): static { return $this->setOptional('contentUrl', $url); }
    public function setThumbnailURL(?string $url): static { return $this->setOptional('thumbnailUrl', $url); }
    public function setUploadDate(?string $date): static { return $this->setOptional('uploadDate', SchemaDate::format($date)); }
    public function setDuration(?string $duration): static { return $this->setOptional('duration', $duration); }

    private function setOptional(string $key, mixed $value): static
    {
        if ($value === null) {
            unset($this->data[$key]);
        } else {
            $this->data[$key] = $value;
        }

        return $this;
    }
}
