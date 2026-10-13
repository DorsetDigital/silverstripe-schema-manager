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

Schema Manager includes typed builders for the following Schema.org entities. Builders can be registered with `SchemaRegistry::add()`; they are not limited to the automatically generated schema.

| Type / builder | What it describes | Guide or source |
| --- | --- | --- |
| `Organization` (`OrganisationSchema`) and `WebSite` (`WebsiteSchema`) | Site-wide organisation and website details | [Site schema](docs/site-schema.md) |
| `WebPage` (`WebPageSchema`) | Standard page metadata, automatically generated | [Page schema](src/Model/Schema/WebPageSchema.php) |
| `BreadcrumbList` (`BreadcrumbListSchema`) | Page hierarchy and navigation | [Breadcrumbs](docs/breadcrumbs.md) |
| `FAQPage` (`FAQPageSchema`) | Questions and accepted answers from one or several sections | [FAQPage](docs/faq.md) |
| `ImageGallery` (`ImageGallerySchema`) | Galleries and their `ImageObject` entries | [ImageGallery](docs/image-gallery.md) |
| `Product` (`ProductSchema`) | Products, offers, availability and brands | [Product](docs/product.md) |
| `Service` (`ServiceSchema`) | Services, providers, areas served and pricing | [Service](docs/service.md) |
| `JobPosting` (`JobPostingSchema`) | Vacancies, locations, remote working and salaries | [JobPosting](docs/job-posting.md) |
| `BlogPosting` (`BlogPostingSchema`) | Blog posts, dates, images and publisher details (opt-in) | [BlogPosting](docs/blog-posting.md) |
| `Event` (`EventSchema`) | Dates, venues, organisers, performers and offers | [Event](docs/event.md) |
| `Person` (`PersonSchema`) | People, job titles, employer, profiles and credentials | [Person](docs/person.md) |
| `LocalBusiness` (`LocalBusinessSchema`) | Business locations, addresses, coordinates and opening hours | [LocalBusiness](docs/local-business.md) |
| `Review` (`ReviewSchema`) | Reviews and testimonials, including author, rating and reviewed entity | [Reviews and testimonials](docs/review.md) |
| `Rating` (`RatingSchema`) | Individual rating scores | [Reviews and ratings](docs/review.md) |
| `AggregateRating` (`AggregateRatingSchema`) | Overall rating value and rating/review counts | [Aggregate ratings](docs/aggregate-rating.md) |
| `Quotation` (`QuotationSchema`) | Quotations and attributed testimonials | [Quotations](docs/quotation.md) |
| Configurable type (`GenericSchema`) | Custom schema assembled from page annotation definitions | [Page annotations](docs/page-annotations.md) |

The following quick reference covers the main builder entry points. Each builder extends `Schema` and supports `update(array $data)` for additional properties.

| Builder | Creation and example methods |
| --- | --- |
| `EventSchema` | `create($url, $name, $startDate, $description)`, `setEndDate()`, `setLocation()`, `setOrganizer()`, `setOffer()` |
| `PersonSchema` | `create($id, $name)`, `setJobTitle()`, `setWorksFor()`, `setSameAs()`, `setCredentials()` |
| `LocalBusinessSchema` | `create($id, $name, $url)`, `setAddress()`, `setGeo()`, `setOpeningHours()`, `setPriceRange()` |
| `ReviewSchema` | `create($id, $body, $author)`, `setItemReviewed()`, `setRating()`, `setDatePublished()` |
| `RatingSchema` | `create($value, $best, $worst)` |
| `AggregateRatingSchema` | `create($value, $ratingCount, $reviewCount)`, `setScale()` |
| `QuotationSchema` | `create($id, $text)`, `setAuthor()`, `setAuthorReference()`, `setCitation()` |

**Example: register a review with a rating**

```php
use DorsetDigital\SchemaManager\Control\SchemaRegistry;
use DorsetDigital\SchemaManager\Model\Schema\RatingSchema;
use DorsetDigital\SchemaManager\Model\Schema\ReviewSchema;

SchemaRegistry::add(
    ReviewSchema::create(
        'https://example.com/reviews/#review-123',
        'Helpful and professional service.',
        'Example customer'
    )
        ->setItemReviewed('https://example.com/services/#service')
        ->setRating(RatingSchema::create(5, 5, 1))
);
```

`RatingSchema` and `AggregateRatingSchema` are value objects without their own `@id`, so use them as nested properties of another entity rather than registering them directly in the request registry.

### Product, FAQ, gallery and other page builders

`ProductSchema`, `ServiceSchema`, `JobPostingSchema`, `FAQPageSchema` and `ImageGallerySchema` provide fluent methods tailored to their content. For example:

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

Builders normally reference a page's `WebPage` with `isPartOf`. Use `setMainEntityOfPage($url)` where the entity is the primary subject of a dedicated page. Multiple `FAQPageSchema` objects for the same URL combine questions into one `FAQPage` entity.

## Schema entities, IDs and relationships

For a fuller explanation with examples, see the [schema entities and relationships guide](docs/schema-entities.md).

Schema Manager uses a linked JSON-LD `@graph`, not isolated script blocks. Each registered entity with an `@id` can be referenced by other entities. The `SchemaIDs` helper centralises stable IDs so integrations can refer to the same organisation, website, page and breadcrumb without hard-coding fragments:

```php
use DorsetDigital\SchemaManager\Control\SchemaIDs;
use DorsetDigital\SchemaManager\Control\SchemaRegistry;

$organisationID = SchemaIDs::organisation();
$websiteID = SchemaIDs::website();
$pageID = SchemaIDs::webPage($page);
$breadcrumbID = SchemaIDs::breadcrumb($page);

// Reference an existing entity from another schema.
SchemaRegistry::addEntity(
    'https://example.com/#custom-service',
    [
        '@id' => 'https://example.com/#custom-service',
        '@type' => 'Service',
        'provider' => SchemaIDs::ref($organisationID),
    ]
);
```

Use `SchemaIDs::forPageURL($url, 'fragment')` to create a canonical page-based ID for other entities. **Resolving an ID does not register the entity**: use `SchemaRegistry::add()` for typed schemas or `addEntity()` for raw entities.

Relationships are resolved when the registry produces its graph. The automatic `WebPage` can link to its `BreadcrumbList` and its primary entity; page builders can declare `mainEntityOfPage` or `isPartOf`. A later `addEntity()` call with the same ID augments existing properties rather than discarding them.

### Entity fields in CMS page annotations

Page annotations support **entity fields** in addition to ordinary text, dates and select fields. An entity field can reference an existing entity by `@id` or describe an inline entity using its type and name. For example, the bundled Event annotation allows an organiser (Organization or Person) and a location (Place); the Service annotation allows a provider (Organization or Person). Definitions can restrict the permitted entity types and whether existing or inline values are accepted.

The annotation definitions are YAML-driven, with built-in Event and Service definitions. Projects can override individual fields, add annotation types or supply custom handlers. See [Page annotations](docs/page-annotations.md).

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

The `docs/` directory contains detailed guides for the main integrations and schema types. The supported-types table links to dedicated guides, with the annotation-specific generic builder documented under page annotations. The README covers the public API, entity relationships and configuration.

## Project structure

```text
_config/
  schema-manager.yml
  schema-annotations.yml
src/
  Annotation/
    GenericAnnotationHandler.php
    SchemaAnnotationFactory.php
    SchemaAnnotationHandler.php
    SchemaDefinitionRegistry.php
  Control/
    SchemaIDs.php
    SchemaRegistry.php
  Extension/
    BlogPostSchemaExtension.php
    PageSchemaAnnotationExtension.php
    SchemaControllerExtension.php
    SiteConfigSchemaExtension.php
  Middleware/
    SchemaResponseMiddleware.php
  Model/
    Schema/
      AggregateRatingSchema.php
      BlogPostingSchema.php
      BreadcrumbListSchema.php
      EventSchema.php
      FAQPageSchema.php
      GenericSchema.php
      ImageGallerySchema.php
      JobPostingSchema.php
      LocalBusinessSchema.php
      OrganisationSchema.php
      PersonSchema.php
      ProductSchema.php
      QuotationSchema.php
      RatingSchema.php
      ReviewSchema.php
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
- **SchemaIDs** provides stable identifiers and reusable entity references
- **Silverstripe extensions** decide which entities should be registered for a request
- **response middleware** inserts the final graph without replacing the Requirements backend
- **page annotations** provide a CMS editing interface for occasional structured data

This keeps the public API small while leaving room for additional schema types and project-specific extensions.

## License

BSD 3-Clause License.
