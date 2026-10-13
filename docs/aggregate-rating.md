# AggregateRating schema

`AggregateRatingSchema` represents an overall rating and counts for a reviewed entity. Unlike an individual [Review](review.md), it describes a collection of ratings.

```php
use DorsetDigital\SchemaManager\Control\SchemaRegistry;
use DorsetDigital\SchemaManager\Model\Schema\AggregateRatingSchema;
use DorsetDigital\SchemaManager\Model\Schema\ProductSchema;

$url = $product->AbsoluteLink();

$rating = AggregateRatingSchema::create(4.7, 82, 54)
    ->setScale(5, 1);

SchemaRegistry::add(
    ProductSchema::create($url, $product->Title)
        ->update(['aggregateRating' => $rating->toArray()])
        ->setMainEntityOfPage($url)
);
```

`create($value, $ratingCount, $reviewCount = null)` sets `ratingValue`, `ratingCount` and optionally `reviewCount`. `setScale($best, $worst = 1)` sets the rating scale.

This builder does not create an `@id`; embed its `toArray()` result within a Product, Service or another appropriate entity rather than registering it directly. The module does not calculate averages or counts: supply real, accurate values from your data. Rich-result eligibility depends on the type of entity and the search engine's rules.
