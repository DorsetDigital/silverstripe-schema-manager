<?php

namespace DorsetDigital\SchemaManager\Model\Schema;

use DorsetDigital\SchemaManager\Control\SchemaIDs;

/**
 * The physical accommodation, distinct from its listing and commercial offer.
 */
class PropertySchema extends Schema
{
    private const TYPES = [
        'Accommodation', 'Residence', 'House', 'SingleFamilyResidence',
        'Apartment', 'Suite', 'Room', 'HotelRoom',
    ];

    public static function create(string $identifier, string $name, string $type = 'Accommodation'): static
    {
        if (!in_array($type, self::TYPES, true)) {
            throw new \InvalidArgumentException('Unsupported accommodation type: ' . $type);
        }

        return new static([
            '@type' => $type,
            '@id' => SchemaIDs::forEntity('property', $identifier),
            'name' => $name,
        ]);
    }

    public function setURL(?string $url): static { return $this->optional('url', $url); }
    public function setDescription(?string $description): static { return $this->optional('description', $description); }
    public function setIdentifier(?string $identifier): static { return $this->optional('identifier', $identifier); }

    public function setImages(array $urls): static
    {
        return $this->optional('image', array_values(array_filter($urls)) ?: null);
    }

    public function setAddress(?array $address): static
    {
        return $this->optional('address', $address === null ? null : ['@type' => 'PostalAddress'] + $address);
    }

    public function setGeo(?float $latitude, ?float $longitude): static
    {
        return $this->optional('geo', $latitude === null || $longitude === null ? null : [
            '@type' => 'GeoCoordinates',
            'latitude' => $latitude,
            'longitude' => $longitude,
        ]);
    }

    public function setBedrooms(?int $count): static { return $this->optional('numberOfBedrooms', $count); }
    public function setBathrooms(?int $count): static { return $this->optional('numberOfBathroomsTotal', $count); }
    public function setRooms(?int $count): static { return $this->optional('numberOfRooms', $count); }

    public function setFloorSize(float|int|null $value, string $unitCode = 'MTK'): static
    {
        return $this->optional('floorSize', $value === null ? null : [
            '@type' => 'QuantitativeValue',
            'value' => $value,
            'unitCode' => $unitCode,
        ]);
    }

    public function addAmenity(string $name, bool $value = true): static
    {
        $this->data['amenityFeature'][] = [
            '@type' => 'LocationFeatureSpecification',
            'name' => $name,
            'value' => $value,
        ];
        return $this;
    }

    public function addAdditionalProperty(string $name, string $value): static
    {
        $this->data['additionalProperty'][] = [
            '@type' => 'PropertyValue',
            'name' => $name,
            'value' => $value,
        ];
        return $this;
    }

    private function optional(string $key, mixed $value): static
    {
        if ($value === null) unset($this->data[$key]);
        else $this->data[$key] = $value;
        return $this;
    }
}
