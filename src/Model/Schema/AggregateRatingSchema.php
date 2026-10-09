<?php

namespace DorsetDigital\SchemaManager\Model\Schema;

class AggregateRatingSchema extends Schema
{
    public static function create(float|int $value, int $ratingCount, ?int $reviewCount = null): static
    {
        $data = ['@type' => 'AggregateRating', 'ratingValue' => $value, 'ratingCount' => $ratingCount];
        if ($reviewCount !== null) $data['reviewCount'] = $reviewCount;
        return new static($data);
    }

    public function setScale(float $best, float $worst = 1): static
    {
        $this->data['bestRating'] = $best;
        $this->data['worstRating'] = $worst;
        return $this;
    }
}
