<?php

namespace DorsetDigital\SchemaManager\Model\Schema;

class RatingSchema extends Schema
{
    public static function create(float|int $value, ?float $best = null, ?float $worst = null): static
    {
        $data = ['@type' => 'Rating', 'ratingValue' => $value];
        if ($best !== null) $data['bestRating'] = $best;
        if ($worst !== null) $data['worstRating'] = $worst;
        return new static($data);
    }
}
