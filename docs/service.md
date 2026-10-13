# Service schema

`ServiceSchema` is a generic builder for project-defined services.

```php
$serviceSchema = ServiceSchema::create(
    'service-' . $service->ID,
    $service->Title,
    $service->MetaDescription
)->setURL($service->AbsoluteLink())
  ->setAreaServed('United Kingdom');

SchemaRegistry::add($serviceSchema);
```

Services have no automatic WebPage relationship or provider. Set these explicitly, for example with `setIsPartOfPage($url)` and `setProvider(SchemaIDs::organisation())`. On a dedicated service page, use `setMainEntityOfPage()` to promote the service to the page's main entity. `setProvider()` can override it and `setOffer()` can add pricing. Additional properties can be supplied with `Schema::update()`.

## Public builder methods

The following methods are declared by this builder (in addition to the [shared Schema methods](developer-api.md#shared-schema-builder-methods)).

| Method and parameters | Purpose |
| --- | --- |
| `public static function create(string $identifier, string $name, ?string $description = null)` | Create a new schema entity. |
| `public function setURL(?string $url)` | Set the u r l property. |
| `public function setAreaServed(string\|array\|null $area)` | Set the area served property. |
| `public function setProvider(?string $id)` | Set the provider property. |
| `public function setOffer( float\|int\|string $price, string $currency, ?string $url = null )` | Set the offer property. |
