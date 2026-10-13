# Reviews, testimonials and ratings

Use `ReviewSchema` for an individual review or testimonial. `RatingSchema` describes an individual score nested inside a review. `AggregateRatingSchema` represents a summary across many ratings; see [Aggregate ratings](aggregate-rating.md).

```php
use DorsetDigital\SchemaManager\Control\SchemaRegistry;
use DorsetDigital\SchemaManager\Model\Schema\RatingSchema;
use DorsetDigital\SchemaManager\Model\Schema\ReviewSchema;

SchemaRegistry::add(
    ReviewSchema::create(
        'review-42',
        'The team were helpful throughout.',
        'Example Customer',
        'https://example.com/#product-example'
    )
        ->setName('Customer feedback')
        ->setDatePublished('2026-10-01')
        ->setRating(RatingSchema::create(5, 5, 1))
);
```

`ReviewSchema::create($identifier, $body, $author, $itemReviewedID)` creates an inline Person author. Use `setAuthorReference($personID)` to link an existing Person or `setAuthorOrganization($name)` for an inline Organization. `setItemReviewed($id)` references the reviewed entity, which must be registered separately if it is to appear in the graph.

A testimonial without a numeric score can omit `setRating()`. `RatingSchema` has no `@id`, so embed it using `setRating()` rather than passing it to `SchemaRegistry::add()`. Publishing review markup does not guarantee a search-engine rich result; follow the relevant search-engine eligibility policies.
