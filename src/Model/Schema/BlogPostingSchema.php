<?php

namespace DorsetDigital\SchemaManager\Model\Schema;

use DorsetDigital\SchemaManager\Control\SchemaDate;
use DorsetDigital\SchemaManager\Control\SchemaIDs;

class BlogPostingSchema extends Schema
{
    public static function create(string $identifier, string $headline, ?string $description = null): static
    {
        $data = [
            '@type' => 'BlogPosting',
            '@id' => SchemaIDs::forEntity('blogposting', $identifier),
            'headline' => $headline,
        ];
        if ($description !== null) $data['description'] = $description;
        return new static($data);
    }

    public function setURL(?string $url): static { return $this->setOptional('url', $url); }
    public function setImage(?string $url): static { return $this->setOptional('image', $url); }
    public function setDatePublished(?string $date): static { return $this->setOptional('datePublished', SchemaDate::format($date)); }
    public function setDateModified(?string $date): static { return $this->setOptional('dateModified', SchemaDate::format($date)); }
    public function setPublisher(?string $id): static { return $this->setOptional('publisher', $id === null ? null : ['@id' => $id]); }
    public function setAuthor(?string $id): static { return $this->setOptional('author', $id === null ? null : ['@id' => $id]); }
    public function setAuthors(array $authors): static { return $this->setOptional('author', $authors); }

    private function setOptional(string $key, mixed $value): static
    {
        if ($value === null) unset($this->data[$key]);
        else $this->data[$key] = $value;
        return $this;
    }
}
