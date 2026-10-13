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
