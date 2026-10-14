<?php

namespace DorsetDigital\SchemaManager\Model\Schema;

use DorsetDigital\SchemaManager\Control\SchemaIDs;

class WebPageSchema extends Schema
{
    public static function create(string $identifier, string $name): static
    {
        return new static([
            '@type' => 'WebPage',
            '@id' => SchemaIDs::forEntity('webpage', $identifier),
            'name' => $name,
        ]);
    }

    public function setURL(?string $url): static { return $this->setOptional('url', $url); }
    public function setDescription(?string $description): static { return $this->setOptional('description', $description); }
    public function setDatePublished(?string $date): static { return $this->setOptional('datePublished', $date); }
    public function setDateModified(?string $date): static { return $this->setOptional('dateModified', $date); }
    public function setIsPartOf(?string $id): static { return $this->setOptional('isPartOf', $id === null ? null : ['@id' => $id]); }
    public function setBreadcrumb(?string $id): static { return $this->setOptional('breadcrumb', $id === null ? null : ['@id' => $id]); }
    public function setMainEntity(?string $id): static { return $this->setOptional('mainEntity', $id === null ? null : ['@id' => $id]); }

    private function setOptional(string $key, mixed $value): static
    {
        if ($value === null) unset($this->data[$key]);
        else $this->data[$key] = $value;
        return $this;
    }
}
