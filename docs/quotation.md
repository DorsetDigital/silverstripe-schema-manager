# Quotation schema

`QuotationSchema` represents an attributed quotation. It can be useful for testimonials where a quotation is more appropriate than a formal `Review`.

```php
use DorsetDigital\SchemaManager\Control\SchemaIDs;
use DorsetDigital\SchemaManager\Control\SchemaRegistry;
use DorsetDigital\SchemaManager\Model\Schema\QuotationSchema;

$pageURL = $page->AbsoluteLink();

SchemaRegistry::add(
    QuotationSchema::create(
        SchemaIDs::forPageURL($pageURL, 'quotation-1'),
        'A quotation from a customer.'
    )
        ->setAuthor('Example Customer')
        ->setCitation('Customer interview, October 2026')
        ->setIsPartOf(SchemaIDs::webPage($page))
);
```

`setAuthor($name)` creates an inline Person. To reuse a separately registered Person entity, use `setAuthorReference($personID)`. `setCitation($citation)` adds the attribution/source and `setIsPartOf($id)` links the quotation to another entity.

For feedback that explicitly reviews a product or service, consider [ReviewSchema](review.md) instead. Unlike the page-oriented builders, `QuotationSchema` takes a caller-supplied ID, so ensure it is stable and unique.
