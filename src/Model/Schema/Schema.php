<?php

namespace DorsetDigital\SchemaManager\Model\Schema;

abstract class Schema
{
    public function __construct(protected array $data)
    {
    }

    public function getID(): string
    {
        return (string) ($this->data['@id'] ?? '');
    }

    public function toArray(): array
    {
        return $this->data;
    }

    public function setMainEntityOfPage(string $pageURL): static
    {
        $pageURL = rtrim($pageURL, '/') . '/';
        $webPageID = $pageURL . '#webpage';

        if (($this->data['isPartOf']['@id'] ?? null) === $webPageID) {
            unset($this->data['isPartOf']);
        }

        $this->data['mainEntityOfPage'] = ['@id' => $pageURL . '#webpage'];

        return $this;
    }

    public function update(array $data): static
    {
        $this->data = array_replace_recursive($this->data, $data);
        return $this;
    }
}
