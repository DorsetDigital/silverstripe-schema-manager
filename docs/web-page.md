# WebPage schema

Schema Manager automatically creates a `WebPage` entity for normal Silverstripe pages. It includes the page title, URL, description and available publication and modification dates. The entity can link to the site's `WebSite`, the page's breadcrumbs and its primary content entity.

The builder can also be used manually if you need to describe a page outside the automatic integration:

```php
use DorsetDigital\SchemaManager\Control\SchemaRegistry;
use DorsetDigital\SchemaManager\Model\Schema\WebPageSchema;

SchemaRegistry::add(
    WebPageSchema::create('landing-page', 'Landing page')
        ->setURL($page->AbsoluteLink())
        ->setDescription($page->MetaDescription)
);
```

Use a stable identifier in `create()`, rather than a complete URL or `@id`. Avoid registering a second WebPage entity for the same page when the automatic integration is already active.

## Public builder methods

These methods are declared by `WebPageSchema`, in addition to the [shared Schema methods](developer-api.md#shared-schema-builder-methods).

| Method | Purpose |
| --- | --- |
| `create(string $identifier, string $name)` | Create a WebPage entity. |
| `setURL(?string $url)` | Set or clear the page URL. |
| `setDescription(?string $description)` | Set or clear its description. |
| `setDatePublished(?string $date)` | Set or clear publication date. |
| `setDateModified(?string $date)` | Set or clear modification date. |
| `setIsPartOf(?string $id)` | Reference the containing WebSite entity by full ID. |
| `setBreadcrumb(?string $id)` | Reference a BreadcrumbList entity by full ID. |
| `setMainEntity(?string $id)` | Reference the page's primary content entity by full ID. |
