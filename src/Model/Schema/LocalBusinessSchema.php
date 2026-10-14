<?php

namespace DorsetDigital\SchemaManager\Model\Schema;

use DorsetDigital\SchemaManager\Control\SchemaIDs;

class LocalBusinessSchema extends Schema
{
    public static function create(string $identifier, string $name): static
    {
        $data = ['@type' => 'LocalBusiness', '@id' => SchemaIDs::forEntity('localbusiness', $identifier), 'name' => $name];
        return new static($data);
    }

    public function setURL(?string $url): static { if ($url !== null) $this->data['url'] = $url; else unset($this->data['url']); return $this; }
    public function setDescription(?string $description): static { if ($description) $this->data['description'] = $description; return $this; }
    public function setImage(?string $url): static { if ($url) $this->data['image'] = $url; return $this; }
    public function setTelephone(?string $telephone): static { if ($telephone) $this->data['telephone'] = $telephone; return $this; }
    public function setEmail(?string $email): static { if ($email) $this->data['email'] = $email; return $this; }
    public function setParentOrganization(string $id): static { $this->data['parentOrganization'] = ['@id' => $id]; return $this; }
    public function setAddress(array $address): static
    {
        $this->data['address'] = ['@type' => 'PostalAddress'] + $address;
        return $this;
    }
    public function setGeo(float $latitude, float $longitude): static
    {
        $this->data['geo'] = ['@type' => 'GeoCoordinates', 'latitude' => $latitude, 'longitude' => $longitude];
        return $this;
    }
    public function setOpeningHours(array $specifications): static { $this->data['openingHoursSpecification'] = $specifications; return $this; }
    public function setPriceRange(?string $range): static { if ($range) $this->data['priceRange'] = $range; return $this; }
}
