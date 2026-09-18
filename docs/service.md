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

Services link to the automatic `WebPage` with `isPartOf` by default and use the site's `Organization` as provider. On a dedicated service page, use `setMainEntityOfPage()` to promote the service to the page's main entity. `setProvider()` can override it and `setOffer()` can add pricing. Additional properties can be supplied with `Schema::update()`.
