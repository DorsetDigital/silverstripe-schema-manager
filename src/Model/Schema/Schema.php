<?php

namespace DorsetDigital\SchemaManager\Model\Schema;

use DorsetDigital\SchemaManager\Control\SchemaIDs;

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
        $webPageID = SchemaIDs::forPageURL($pageURL, 'webpage');

        if (($this->data['isPartOf']['@id'] ?? null) === $webPageID) {
            unset($this->data['isPartOf']);
        }

        $this->data['mainEntityOfPage'] = ['@id' => SchemaIDs::forPageURL($pageURL, 'webpage')];

        return $this;
    }

    public function isMainEntityOfPage(string $pageURL): bool
    {
        $pageURL = rtrim($pageURL, '/') . '/';

        return ($this->data['mainEntityOfPage']['@id'] ?? null) === SchemaIDs::forPageURL($pageURL, 'webpage');
    }

    public function setIsPartOfPage(string $pageURL): static
    {
        $pageURL = rtrim($pageURL, '/') . '/';
        $webPageID = SchemaIDs::forPageURL($pageURL, 'webpage');

        if (($this->data['mainEntityOfPage']['@id'] ?? null) === $webPageID) {
            unset($this->data['mainEntityOfPage']);
        }

        $this->data['isPartOf'] = ['@id' => $webPageID];

        return $this;
    }

    public function update(array $data): static
    {
        $this->data = array_replace_recursive($this->data, $data);
        return $this;
    }
}
