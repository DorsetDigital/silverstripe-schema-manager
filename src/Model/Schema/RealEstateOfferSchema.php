<?php

namespace DorsetDigital\SchemaManager\Model\Schema;

use DorsetDigital\SchemaManager\Control\SchemaIDs;

/**
 * A sale or lease offer referring to an independently registered property.
 */
class RealEstateOfferSchema extends Schema
{
    public static function create(string $identifier, string $propertyID): static
    {
        return new static([
            '@type' => 'Offer',
            '@id' => SchemaIDs::forEntity('property-offer', $identifier),
            'itemOffered' => SchemaIDs::ref($propertyID),
        ]);
    }

    public function setURL(?string $url): static { return $this->optional('url', $url); }

    public function setPrice(float|int|string|null $price, string $currency = 'GBP'): static
    {
        $this->optional('price', $price);
        $this->optional('priceCurrency', $price === null ? null : $currency);
        return $this;
    }

    public function setBusinessFunction(string $function): static
    {
        if (!in_array($function, ['Sell', 'LeaseOut'], true)) {
            throw new \InvalidArgumentException('Business function must be Sell or LeaseOut.');
        }
        return $this->optional('businessFunction', 'http://purl.org/goodrelations/v1#' . $function);
    }

    public function setSeller(?string $organisationID): static
    {
        return $this->optional('seller', $organisationID === null ? null : SchemaIDs::ref($organisationID));
    }

    /**
     * Rental price and billing period; unitCode MON means calendar month.
     */
    public function setRentalPrice(float|int|string $price, string $currency = 'GBP', string $unitCode = 'MON'): static
    {
        $this->setBusinessFunction('LeaseOut');
        unset($this->data['price'], $this->data['priceCurrency']);
        $this->data['priceSpecification'] = [
            '@type' => 'UnitPriceSpecification',
            'price' => $price,
            'priceCurrency' => $currency,
            'billingDuration' => 1,
            'unitCode' => $unitCode,
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
