# Product schema

`ProductSchema` is a generic builder; project code maps its own product model onto Schema.org.

```php
$productSchema = ProductSchema::create(
    $product->AbsoluteLink(),
    $product->Title,
    $product->MetaDescription
)
    ->setMainEntityOfPage($product->AbsoluteLink())
    ->setImage($product->Image?->getAbsoluteURL())
    ->setSKU($product->SKU)
    ->setBrand($product->Brand)
    ->setOffer($product->Price, 'GBP', $product->InStock);

SchemaRegistry::add($productSchema);
```

Products link to the automatic `WebPage` with `isPartOf` by default. The example explicitly promotes the product to `mainEntityOfPage` because it represents a dedicated product page. `setOffer()` creates an `Offer` and can include stock availability. Less common properties can be supplied with `Schema::update()`.
