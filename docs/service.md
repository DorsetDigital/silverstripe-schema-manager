# Service schema

`ServiceSchema` is a generic builder for project-defined services.

```php
$serviceSchema = ServiceSchema::create(
    $service->AbsoluteLink(),
    $service->Title,
    $service->MetaDescription
)->setAreaServed('United Kingdom');

SchemaRegistry::add($serviceSchema);
```

Services link to the automatic `WebPage` and use the site's `Organization` as provider by default. `setProvider()` can override it and `setOffer()` can add pricing. Additional properties can be supplied with `Schema::update()`.
