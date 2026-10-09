<?php

namespace DorsetDigital\SchemaManager\Model\Schema;

class QuotationSchema extends Schema
{
    public static function create(string $id, string $text): static
    {
        return new static(['@type' => 'Quotation', '@id' => $id, 'text' => $text]);
    }

    public function setAuthor(string $name): static { $this->data['author'] = ['@type' => 'Person', 'name' => $name]; return $this; }
    public function setAuthorReference(string $id): static { $this->data['author'] = ['@id' => $id]; return $this; }
    public function setCitation(?string $citation): static { if ($citation) $this->data['citation'] = $citation; return $this; }
    public function setIsPartOf(string $id): static { $this->data['isPartOf'] = ['@id' => $id]; return $this; }
}
