<?php

namespace DorsetDigital\SchemaManager\Model\Schema;

use DorsetDigital\SchemaManager\Control\SchemaIDs;

class ReviewSchema extends Schema
{
    public static function create(string $identifier, string $body, string $author, string $itemReviewedID): static
    {
        return new static([
            '@type' => 'Review', '@id' => SchemaIDs::forEntity('review', $identifier), 'reviewBody' => $body,
            'author' => ['@type' => 'Person', 'name' => $author],
            'itemReviewed' => ['@id' => $itemReviewedID],
        ]);
    }

    public function setAuthorReference(string $id): static { $this->data['author'] = ['@id' => $id]; return $this; }
    public function setAuthorOrganization(string $name): static { $this->data['author'] = ['@type' => 'Organization', 'name' => $name]; return $this; }
    public function setItemReviewed(string $id): static { $this->data['itemReviewed'] = ['@id' => $id]; return $this; }
    public function setDatePublished(?string $date): static { if ($date) $this->data['datePublished'] = $date; return $this; }
    public function setRating(RatingSchema $rating): static { $this->data['reviewRating'] = $rating->toArray(); return $this; }
    public function setName(?string $name): static { if ($name) $this->data['name'] = $name; return $this; }
}
