<?php

namespace DorsetDigital\SchemaManager\Model\Schema;

use DorsetDigital\SchemaManager\Control\SchemaIDs;
class ProductSchema extends Schema
{
    public static function create(string $identifier, string $name, ?string $description = null): static
    {
        $data = [
            '@type' => 'Product',
            '@id' => SchemaIDs::forEntity('product', $identifier),
            'name' => $name,
        ];

        if ($description) {
            $data['description'] = $description;
        }

        return new static($data);
    }

    public function setURL(?string $url): static
    {
        $previousURL = $this->data['url'] ?? null;
        if ($url !== null) {
            $this->data['url'] = $url;
        } else {
            unset($this->data['url']);
        }

        // Keep an implicitly inherited Offer URL in sync with the canonical
        // URL, without overriding an explicitly supplied Offer URL.
        if (isset($this->data['offers'])) {
            $offerURL = $this->data['offers']['url'] ?? null;
            if ($offerURL === null || $offerURL === $previousURL) {
                if ($url !== null) {
                    $this->data['offers']['url'] = $url;
                } else {
                    unset($this->data['offers']['url']);
                }
            }
        }

        return $this;
    }

    public function setImage(?string $url): static
    {
        if ($url) {
            $this->data['image'] = $url;
        }

        return $this;
    }

    public function setOffer(
        float|int|string $price,
        string $currency,
        ?bool $inStock = null,
        ?string $url = null
    ): static {
        $offer = [
            '@type' => 'Offer',
            'priceCurrency' => $currency,
            'price' => $price,
        ];

        if ($url) {
            $offer['url'] = $url;
        } elseif (!empty($this->data['url'])) {
            $offer['url'] = $this->data['url'];
        }

        if ($inStock !== null) {
            $offer['availability'] = $inStock
                ? 'https://schema.org/InStock'
                : 'https://schema.org/OutOfStock';
        }

        $this->data['offers'] = $offer;

        return $this;
    }

    public function setSKU(?string $sku): static
    {
        if ($sku) {
            $this->data['sku'] = $sku;
        }

        return $this;
    }

    public function setBrand(?string $name): static
    {
        if ($name) {
            $this->data['brand'] = [
                '@type' => 'Brand',
                'name' => $name,
            ];
        }

        return $this;
    }
}
