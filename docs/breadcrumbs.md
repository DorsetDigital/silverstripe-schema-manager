# Breadcrumb schema

Normal `SiteTree` pages automatically receive a Schema.org `BreadcrumbList` built from Silverstripe's `getBreadcrumbItems()`.

Entries use `MenuTitle` where available, falling back to `Title`, and contain the absolute page URL and list position.

Automatic breadcrumbs can be disabled:

```yaml
DorsetDigital\SchemaManager\Service\SchemaManager:
  automatic_breadcrumb_schema: false
```

The public helper remains available for custom breadcrumb behaviour:

```php
SchemaRegistry::addBreadCrumbs(
    $this,
    maxDepth: 20,
    stopAtPageType: false,
    showHidden: false
);
```
