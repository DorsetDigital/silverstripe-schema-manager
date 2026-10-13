<?php

namespace DorsetDigital\SchemaManager\Model\Schema;

use DorsetDigital\SchemaManager\Control\SchemaIDs;

class OrganisationSchema extends Schema
{
    public static function create(string $identifier, string $name): static
    {
        return new static([
            '@type' => 'Organization',
            '@id' => SchemaIDs::forEntity('organisation', $identifier),
            'name' => $name,
        ]);
    }

    public function setURL(?string $url): static { return $this->setOptional('url', $url); }
    public function setLegalName(?string $name): static { return $this->setOptional('legalName', $name); }
    public function setTelephone(?string $phone): static { return $this->setOptional('telephone', $phone); }
    public function setEmail(?string $email): static { return $this->setOptional('email', $email); }
    public function setLogo(?string $url): static { return $this->setOptional('logo', $url); }
    public function setAddress(?array $address): static
    {
        return $this->setOptional('address', $address === null ? null : ['@type' => 'PostalAddress'] + $address);
    }
    public function setSameAs(array $urls): static { return $this->setOptional('sameAs', array_values(array_filter($urls))); }

    private function setOptional(string $key, mixed $value): static
    {
        if ($value === null) unset($this->data[$key]);
        else $this->data[$key] = $value;
        return $this;
    }
}
