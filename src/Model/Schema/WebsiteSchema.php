<?php

namespace DorsetDigital\SchemaManager\Model\Schema;

use DorsetDigital\SchemaManager\Control\SchemaIDs;

class WebsiteSchema extends Schema
{
    public static function create(string $identifier, string $name): static
    {
        return new static([
            '@type' => 'WebSite',
            '@id' => SchemaIDs::forEntity('website', $identifier),
            'name' => $name,
        ]);
    }

    public function setURL(?string $url): static
    {
        if ($url === null) unset($this->data['url']);
        else $this->data['url'] = $url;
        return $this;
    }

    public function setPublisher(?string $id): static
    {
        if ($id === null) unset($this->data['publisher']);
        else $this->data['publisher'] = ['@id' => $id];
        return $this;
    }
}
