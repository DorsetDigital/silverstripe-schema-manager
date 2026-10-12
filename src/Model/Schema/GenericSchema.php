<?php

namespace DorsetDigital\SchemaManager\Model\Schema;

/**
 * A schema entity constructed from a declarative annotation definition.
 */
class GenericSchema extends Schema
{
    public static function fromArray(array $data): static
    {
        if (empty($data['@id']) || empty($data['@type'])) {
            throw new \InvalidArgumentException('Generic schema requires @id and @type.');
        }

        return new static($data);
    }
}
