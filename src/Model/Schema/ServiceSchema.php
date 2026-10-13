<?php

namespace DorsetDigital\SchemaManager\Model\Schema;

use DorsetDigital\SchemaManager\Control\SchemaIDs;

class ServiceSchema extends Schema
{
    public static function create(string $identifier, string $name, ?string $description = null): static
    {
        $data = [
            '@type' => 'Service',
            '@id' => SchemaIDs::forEntity('service', $identifier),
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

    public function setAreaServed(string|array|null $area): static
    {
        if ($area) {
            $this->data['areaServed'] = $area;
        }

        return $this;
    }

    public function setProvider(?string $id): static
    {
        if ($id !== null) {
            $this->data['provider'] = ['@id' => $id];
        } else {
            unset($this->data['provider']);
        }

        return $this;
    }

    public function setOffer(
        float|int|string $price,
        string $currency,
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

        $this->data['offers'] = $offer;

        return $this;
    }
}
