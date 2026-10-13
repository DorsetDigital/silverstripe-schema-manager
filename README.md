# Silverstripe Schema Manager

Structured-data management for Silverstripe CMS 5 and 6.

Schema Manager combines CMS-managed site information with automatic page schema and a request-scoped registry. Schema.org entities are emitted as a single linked JSON-LD `@graph` in the page `<head>`.

## Requirements

- PHP 8.1+ with Silverstripe CMS 5, or PHP 8.3+ with Silverstripe CMS 6. The CI suite tests PHP 8.1/8.3 with CMS 5 and PHP 8.3/8.4 with CMS 6.

## Installation

```bash
composer require dorsetdigital/silverstripe-schema-manager
vendor/bin/sake dev/build flush=1
```

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

The JSON-LD is inserted automatically by response middleware after the page has rendered. No template change is required. This avoids replacing Silverstripe's Requirements backend and allows modules such as Enhanced Requirements to retain control of asset handling. The middleware only modifies successful HTML responses containing a closing `</head>`; XML, JSON and other non-HTML responses are left untouched.

## Automatic WebPage schema

Every normal `SiteTree` page receives a `WebPage` entity containing its URL, title, description where available, created/modified dates and a reference to the site's `WebSite` entity. No page extension is required.

## Configuration

Each automatic layer can be disabled independently:

```yaml
DorsetDigital\SchemaManager\Service\SchemaManager:
  automatic_organisation_schema: true
  automatic_website_schema: true
  automatic_webpage_schema: true
  automatic_breadcrumb_schema: true
```

## Supported schema types

Detailed examples and configuration are kept in separate documentation:

- [Site schema](docs/site-schema.md) — `Organization` and `WebSite`
- [Breadcrumbs](docs/breadcrumbs.md) — automatic and manual `BreadcrumbList`
- [FAQPage](docs/faq.md) — questions and accepted answers, including multiple FAQ sections on one page
- [ImageGallery](docs/image-gallery.md) — image collections and `ImageObject` metadata
- [Product](docs/product.md) — products, offers, stock and brands
- [Service](docs/service.md) — services, providers and pricing
- [JobPosting](docs/job-posting.md) — vacancies, locations and salaries
- [BlogPosting](docs/blog-posting.md) — optional Silverstripe Blog integration

Page-level typed builders link to their `WebPage` with `isPartOf` by default. When an entity is the primary subject of a dedicated page, `setMainEntityOfPage($pageURL)` promotes that relationship explicitly.

All typed builders inherit `Schema::update()`, allowing projects to add less common Schema.org properties without the module needing to model every possible field.

## Using the schema builders

The module provides typed PHP builders for structured content. Create a builder from the relevant page URL and content, customise it using its fluent methods, then register it with `SchemaRegistry::add()`. Builders are intended to work with your project's existing data models; they do not create CMS fields or data records for products, jobs, galleries or FAQs.

| Builder | Purpose | Common methods |
| --- | --- | --- |
| `FAQPageSchema` | Questions and accepted answers | `create($url)`, `addQuestion($question, $answer)` |
| `ImageGallerySchema` | Image collections and image metadata | `create($url, $name)`, `addImage(...)`, `setThumbnail($url)` |
| `ProductSchema` | Products and their offers | `create($url, $name, $description)`, `setImage()`, `setSKU()`, `setBrand()`, `setOffer()` |
| `ServiceSchema` | Services and providers | `create($url, $name, $description)`, `setAreaServed()`, `setProvider()`, `setOffer()` |
| `JobPostingSchema` | Vacancies and employment details | `create($url, $title, $description)`, `setDatePosted()`, `setValidThrough()`, `setJobLocation()`, `setRemote()`, `setBaseSalaryRange()` |
| `BlogPostingSchema` | Silverstripe Blog posts (opt-in) | `fromBlogPost($post)` |
| `OrganisationSchema`, `WebsiteSchema`, `WebPageSchema`, `BreadcrumbListSchema` | Core site/page entities, normally generated automatically | See the site and breadcrumb guides |

For example, a dedicated product page can register its product as the main subject:

```php
use DorsetDigital\SchemaManager\Control\SchemaRegistry;
use DorsetDigital\SchemaManager\Model\Schema\ProductSchema;

$url = $product->AbsoluteLink();

SchemaRegistry::add(
    ProductSchema::create($url, $product->Title, $product->MetaDescription)
        ->setImage($product->Image?->getAbsoluteURL())
        ->setSKU($product->SKU)
        ->setBrand($product->Brand)
        ->setOffer($product->Price, 'GBP', $product->InStock)
        ->setMainEntityOfPage($url)
);
```

A builder normally links to the page's `WebPage` using `isPartOf`. Call `setMainEntityOfPage($url)` when the entity is the primary subject of a dedicated page. All builders inherit `update(array $data)` for additional Schema.org properties.

Multiple `FAQPageSchema` objects registered for the same page URL are combined into a single FAQ entity, allowing independent components to contribute questions. See the individual guides above for full examples and builder-specific options.

## Optional Silverstripe Blog integration

The `BlogPosting` integration is **not enabled automatically**. If your project uses `silverstripe/blog`, add the bundled extension to blog posts in your project's YAML configuration:

```yaml
---
Name: app-schema-blog
After:
  - schema-manager
---
SilverStripe\Blog\Model\BlogPost:
  extensions:
    - DorsetDigital\SchemaManager\Extension\BlogPostSchemaExtension
```

Run `vendor/bin/sake dev/build flush=1` after changing the configuration. Blog posts will then contribute `BlogPosting` schema with their headline, publication and modification dates, description and featured image where available. See [BlogPosting](docs/blog-posting.md) for details.

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

If an entity is registered again using the same `@id`, its data is recursively merged with the existing entity. For `FAQPage` entities, `mainEntity` questions are appended rather than overwritten, so separate FAQ sections on the same page contribute to one FAQ schema.

Typed schema objects can also be registered:

```php
SchemaRegistry::add($schema);
```

where `$schema` extends `DorsetDigital\SchemaManager\Model\Schema\Schema`.

## Extension point

The registry API can be called directly from application code; an extension hook is not required. For reusable integrations that need to contribute schema automatically to arbitrary pages, Schema Manager also calls this Silverstripe extension hook before page entities are registered:

```php
updateSchemaManagerEntities(array &$entities)
```

Extensions can therefore append, remove or modify typed schema objects without replacing the registry or controller integration. This is primarily an integration mechanism rather than a requirement for ordinary project code.

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

Registers a typed schema object. It can be called directly from controllers or other application code. Relationships are resolved when the graph is rendered, so schemas registered independently still participate in automatic `WebPage.mainEntity` and breadcrumb relationship resolution.

### `SchemaRegistry::addEntity(string $id, array $data)`

Registers a raw Schema.org entity. Existing data under the same ID is recursively merged, including entities previously registered as typed schemas with `add()`. FAQ question lists are concatenated when multiple `FAQPage` entities share an ID. This allows project code to augment a typed entity without replacing it.

### `SchemaRegistry::addFAQ(string $question, string $answer, ?string $pageURL = null)`

Deprecated compatibility helper for adding FAQ questions. New code should use `FAQPageSchema` and `SchemaRegistry::add()`.

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

## Page annotations

The page editor includes a **Schema** tab for one optional annotation per page. Built-in definitions support Event and Service; projects can add definitions or custom PHP handlers through YAML. Fields may be populated from page data or edited in the CMS, and supported entity fields can refer to existing entities or describe an inline entity. Annotation values are saved with the versioned page, and published annotations are included in the JSON-LD graph. Pages without an annotation retain the normal automatic schema output. See [Page annotations and advanced usage](docs/page-annotations.md) for configuration and examples.

## Documentation

The `docs/` directory contains the detailed guides for each supported schema type. The README is intentionally focused on installation, automatic behaviour, configuration and the common registry API.

## Project structure

```text
_config/
  schema-manager.yml
  schema-annotations.yml
src/
  Annotation/
    SchemaAnnotationFactory.php
    SchemaDefinitionRegistry.php
    SchemaAnnotationHandler.php
  Middleware/
    SchemaResponseMiddleware.php
  Control/
    SchemaRegistry.php
  Extension/
    BlogPostSchemaExtension.php
    PageSchemaAnnotationExtension.php
    SchemaControllerExtension.php
    SiteConfigSchemaExtension.php
  Model/
    Schema/
      BlogPostingSchema.php
      FAQPageSchema.php
      ImageGallerySchema.php
      JobPostingSchema.php
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
- **response middleware** inserts the final graph without replacing the Requirements backend
- **page annotations** provide a CMS editing interface for occasional structured data

This keeps the public API small while leaving room for additional schema types and project-specific extensions.

## License

BSD 3-Clause License.
