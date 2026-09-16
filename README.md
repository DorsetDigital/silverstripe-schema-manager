# Silverstripe Schema Manager

Structured-data management for Silverstripe CMS 6.

Schema Manager combines CMS-managed site information with automatic page schema and a request-scoped registry. Schema.org entities are emitted as a single linked JSON-LD `@graph` in the page `<head>`.

## Requirements

- PHP 8.3+
- Silverstripe CMS 6

## Installation

Once published on Packagist:

```bash
composer require dorsetdigital/silverstripe-schema-manager
vendor/bin/sake dev/build flush=1
```

Until then, add the GitHub repository as a Composer VCS repository and require `dev-main`.

## What is automatic?

By default Schema Manager adds site, page and breadcrumb schema to normal `ContentController` page requests:

```text
Organization
    ↑ publisher
WebSite
    ↑ isPartOf
WebPage

BreadcrumbList
```

Stable entity IDs are based on the canonical site/page URLs:

```text
https://example.com/#organisation
https://example.com/#website
https://example.com/about/#webpage
https://example.com/about/#breadcrumb
```

The JSON-LD is inserted automatically with Silverstripe Requirements. No template change is required.

## Organisation settings

After installing the module and running `dev/build`, open **Settings → Schema** in the CMS.

The organisation can be managed with:

- organisation name, with the site title used as a fallback
- legal name
- telephone and email
- logo
- postal address
- external profile URLs (`sameAs`), one per line

The organisation data is used to generate the site-wide `Organization` entity. `WebSite` schema is derived from SiteConfig and the canonical base URL.

## Automatic WebPage schema

Every normal `SiteTree` page receives a `WebPage` entity containing its URL, title, description where available, created/modified dates and a reference to the site's `WebSite` entity.

No page extension needs to be configured for this behaviour.

## Automatic breadcrumb schema

Every normal `SiteTree` page also receives a Schema.org `BreadcrumbList` built from Silverstripe's standard `getBreadcrumbItems()` functionality.

The breadcrumb entries use `MenuTitle` where available, falling back to `Title`, and include the absolute page URL and list position expected by Schema.org.

Automatic breadcrumb schema can be disabled independently while leaving the public helper available for manual use.

```yaml
DorsetDigital\SchemaManager\Service\SchemaManager:
  automatic_breadcrumb_schema: false
```

A project can then register breadcrumbs itself, for example when it needs different Silverstripe breadcrumb options:

```php
use DorsetDigital\SchemaManager\Control\SchemaRegistry;

SchemaRegistry::addBreadCrumbs(
    $this,
    maxDepth: 20,
    stopAtPageType: false,
    showHidden: false
);
```

## Configuration

Each automatic layer can be disabled independently in project YAML:

```yaml
DorsetDigital\SchemaManager\Service\SchemaManager:
  automatic_organisation_schema: true
  automatic_website_schema: true
  automatic_webpage_schema: true
  automatic_breadcrumb_schema: true
```

For example, a project already supplying its own organisation schema can set:

```yaml
DorsetDigital\SchemaManager\Service\SchemaManager:
  automatic_organisation_schema: false
```

## FAQ schema

FAQ markup has a convenience helper:

```php
use DorsetDigital\SchemaManager\Control\SchemaRegistry;

foreach ($this->FAQs() as $faq) {
    SchemaRegistry::addFAQ(
        $faq->Question,
        $faq->Answer
    );
}
```

An explicit page URL can be supplied as the third argument if required:

```php
SchemaRegistry::addFAQ(
    $faq->Question,
    $faq->Answer,
    $this->AbsoluteLink()
);
```

Each call appends another `Question` to one `FAQPage` entity for the current page.

## Product and service schema

The module includes generic builders for common `Product` and `Service` entities. They deliberately accept values rather than depending on a particular e-commerce or service module, so project code remains responsible for mapping its own data model onto Schema.org.

A product can be added with:

```php
use DorsetDigital\SchemaManager\Control\SchemaRegistry;
use DorsetDigital\SchemaManager\Model\Schema\ProductSchema;

$productSchema = ProductSchema::create(
    $product->AbsoluteLink(),
    $product->Title,
    $product->MetaDescription
)
    ->setImage($product->Image?->getAbsoluteURL())
    ->setSKU($product->SKU)
    ->setBrand($product->Brand)
    ->setOffer($product->Price, 'GBP', $product->InStock);

SchemaRegistry::add($productSchema);
```

The product is linked to the automatic `WebPage` entity using `mainEntityOfPage`. `setOffer()` adds a standard Schema.org `Offer` and can include stock availability.

A service follows the same pattern:

```php
use DorsetDigital\SchemaManager\Control\SchemaRegistry;
use DorsetDigital\SchemaManager\Model\Schema\ServiceSchema;

$serviceSchema = ServiceSchema::create(
    $service->AbsoluteLink(),
    $service->Title,
    $service->MetaDescription
)
    ->setAreaServed('United Kingdom');

SchemaRegistry::add($serviceSchema);
```

Services link to the automatic `WebPage` entity and, by default, use the site's `Organization` entity as their provider. `setProvider()` can override that relationship, and `setOffer()` can add pricing where appropriate.

Both builders inherit `Schema::update()`, so less common Schema.org properties can be added without requiring the module to model every possible product or service use case:

```php
$productSchema->update([
    'color' => $product->Colour,
    'material' => $product->Material,
]);
```

## Optional Silverstripe Blog support

The module does **not** require `silverstripe/blog`.

If the project uses Silverstripe Blog, enable the supplied extension in project YAML:

```yaml
SilverStripe\Blog\Model\BlogPost:
  extensions:
    - DorsetDigital\SchemaManager\Extension\BlogPostSchemaExtension
```

After a configuration flush, BlogPost pages retain their normal `WebPage` entity and also gain a linked `BlogPosting` entity containing the headline, publication/modification dates, description and featured image where available.

## Adding schema manually

Raw entities can still be registered directly:

```php
use DorsetDigital\SchemaManager\Control\SchemaRegistry;

SchemaRegistry::addEntity(
    'https://example.com/#service',
    [
        '@type' => 'Service',
        '@id' => 'https://example.com/#service',
        'name' => 'Example service',
    ]
);
```

If an entity is registered again using the same `@id`, its data is recursively merged with the existing entity.

Typed schema objects can also be registered:

```php
SchemaRegistry::add($schema);
```

where `$schema` extends `DorsetDigital\SchemaManager\Model\Schema\Schema`.

## Extension point

Before page entities are registered, Schema Manager calls this Silverstripe extension hook on the page:

```php
updateSchemaManagerEntities(array &$entities)
```

Extensions can therefore append, remove or modify typed schema objects without replacing the registry or controller integration.

For example:

```php
use SilverStripe\Core\Extension;

class MyPageSchemaExtension extends Extension
{
    public function updateSchemaManagerEntities(array &$entities): void
    {
        // Add another Schema object, or update an existing one.
    }
}
```

The same hook is called on `SiteConfig` for site-wide entities.

The bundled Blog integration uses this mechanism, so it also provides a reference implementation for future module integrations.

## Registry API

### `SchemaRegistry::add(Schema $schema)`

Registers a typed schema object.

### `SchemaRegistry::addEntity(string $id, array $data)`

Registers a raw Schema.org entity. Existing data under the same ID is recursively merged.

### `SchemaRegistry::addFAQ(string $question, string $answer, ?string $pageURL = null)`

Adds a question and accepted answer to the current page's `FAQPage` entity.

### `SchemaRegistry::addBreadCrumbs(SiteTree $page, int $maxDepth = 20, bool|string $stopAtPageType = false, bool $showHidden = false)`

Adds a `BreadcrumbList` using Silverstripe's standard breadcrumb hierarchy. The method remains available when automatic breadcrumb schema is disabled.

### `SchemaRegistry::getGraph(): array`

Returns all registered entities.

### `SchemaRegistry::getSchema(): array`

Returns the complete structure containing `@context` and `@graph`.

### `SchemaRegistry::getJSON(): string`

Returns JSON suitable for an `application/ld+json` script element.

### `SchemaRegistry::flush(): void`

Clears the request registry.

## Project structure

```text
_config/
  schema-manager.yml
src/
  Control/
    SchemaRegistry.php
  Extension/
    BlogPostSchemaExtension.php
    SchemaControllerExtension.php
    SiteConfigSchemaExtension.php
  Model/
    Schema/
      BlogPostingSchema.php
      OrganisationSchema.php
      ProductSchema.php
      Schema.php
      ServiceSchema.php
      WebPageSchema.php
      WebsiteSchema.php
  Service/
    SchemaManager.php
```

The module is deliberately split into small responsibilities:

- **schema classes** build individual Schema.org entities
- **the registry** collects, merges and renders the graph
- **Silverstripe extensions** decide which entities should be registered for a request

This keeps the public API small while leaving room for additional schema types and project-specific extensions.

## License

BSD 3-Clause License.
