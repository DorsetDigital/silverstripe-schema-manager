# Product schema

`ProductSchema` is a generic builder; project code maps its own product model onto Schema.org.

```php
$productSchema = ProductSchema::create(
    'product-' . $product->ID,
    $product->Title,
    $product->MetaDescription
)
    ->setURL($product->AbsoluteLink())
    ->setMainEntityOfPage($product->AbsoluteLink())
    ->setImage($product->Image?->getAbsoluteURL())
    ->setSKU($product->SKU)
    ->setBrand($product->Brand)
    ->setOffer($product->Price, 'GBP', $product->InStock);

SchemaRegistry::add($productSchema);
```

Products do not automatically link to a WebPage. The identifier must remain stable across listings and product pages. The example explicitly promotes the product to `mainEntityOfPage` because it represents a dedicated product page. `setOffer()` creates an `Offer` and can include stock availability. Less common properties can be supplied with `Schema::update()`.

## Public builder methods

The following methods are declared by this builder (in addition to the [shared Schema methods](developer-api.md#shared-schema-builder-methods)).

| Method and parameters | Purpose |
| --- | --- |
| `public static function create(string $identifier, string $name, ?string $description = null)` | Create a new schema entity. |
| `public function setURL(?string $url)` | Set the u r l property. |
| `public function setImage(?string $url)` | Set the image property. |
| `public function setOffer( float\|int\|string $price, string $currency, ?bool $inStock = null, ?string $url = null )` | Set the offer property. |
| `public function setSKU(?string $sku)` | Set the s k u property. |
| `public function setBrand(?string $name)` | Set the brand property. |
