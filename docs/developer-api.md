# Developer integration and API reference

This guide contains the advanced integration examples and registry API reference moved out of the main README.

## Referencing global schema entities

Schema Manager automatically creates entities for the site's organisation, website and current page. When building an integration, you often need to **reference one of those existing entities** — for example, to identify the organisation providing a Service or the website containing a page.

Rather than reconstructing their `@id` values or hard-coding URL fragments, use `SchemaIDs`:

```php
use DorsetDigital\SchemaManager\Control\SchemaIDs;

$organisationID = SchemaIDs::organisation();
$websiteID = SchemaIDs::website();
$pageID = SchemaIDs::webPage($page);
$breadcrumbID = SchemaIDs::breadcrumb($page);
```

Each method above returns the entity's **ID as a string**, not a complete schema entity. This is useful because typed builders such as `setProvider()` and `setWorksFor()` accept an ID string and build the JSON-LD reference themselves.

`webPage()` and `breadcrumb()` can also be called without `$page` when there is a current Silverstripe page.

For your own entities, `SchemaIDs::forPageURL($url, 'fragment')` generates a consistent ID from a page URL and a name you choose. For example, `SchemaIDs::forPageURL('https://example.com/services/', 'service')` returns `https://example.com/services/#service`. It saves you manually joining the URL and fragment, and ensures the same format is used throughout the module.

For example, a custom Service integration can reference the **existing organisation entity** as its provider:

```php
use DorsetDigital\SchemaManager\Control\SchemaIDs;
use DorsetDigital\SchemaManager\Control\SchemaRegistry;

SchemaRegistry::addEntity(
    SchemaIDs::forPageURL($page->AbsoluteLink(), 'service'),
    [
        '@type' => 'Service',
        'name' => $page->Title,
        'provider' => SchemaIDs::ref(SchemaIDs::organisation()),
    ]
);
```

In this example, `forPageURL()` creates the **new Service entity's ID**. The `provider` property needs to point to the **existing organisation entity**. `SchemaIDs::organisation()` gives us that organisation's ID as a string, while `SchemaIDs::ref()` wraps the string in the JSON-LD reference format: `['@id' => 'https://example.com/#organisation']`.

That extra `ref()` call is only needed here because we're constructing a raw schema array. Typed builders such as `ServiceSchema::setProvider()` accept the string directly and create the reference for you. **Neither helper registers an entity**; they only generate IDs or format references.

See [Working with schema entities](schema-entities.md) for more on registering custom entities and linking them together.

### Entity fields in CMS page annotations

The CMS can also link schema entities without custom PHP. For example, an Event annotation can specify an organiser (Organization or Person) and a location, while a Service annotation can specify its provider. Each field can refer to an existing entity or describe one directly, depending on the project configuration.

See [Page annotations](page-annotations.md) for the available options and how to configure them.


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


## Choosing a page type or adding content

**Usually, add an entity; don't replace `WebPage`.** The two cases are:

### A product is the main content of a page

The page is still a `WebPage`. Register a separate `Product` and identify it as the primary content:

```php
use DorsetDigital\SchemaManager\Control\SchemaRegistry;
use DorsetDigital\SchemaManager\Model\Schema\ProductSchema;

SchemaRegistry::add(
    ProductSchema::create('product-' . $product->ID, $product->Title)
        ->setMainEntityOfPage($page->AbsoluteLink())
);
```

Schema Manager keeps the existing `WebPage` and can link it to the `Product` through `mainEntity`. The `Product` itself has its own `@id`. Register the entity during the request before the graph is rendered.

### A listings page is a CollectionPage

Here `CollectionPage` describes **the page itself**. When working in the `updateSchemaManagerEntities(array &$entities)` page hook, replace the existing `WebPageSchema` object **in that array**. Its `@id`, title, URL and metadata are retained by `fromWebPage()`:

```php
use DorsetDigital\SchemaManager\Model\Schema\PropertyCollectionSchema;
use DorsetDigital\SchemaManager\Model\Schema\WebPageSchema;

public function updateSchemaManagerEntities(array &$entities): void
{
    foreach ($entities as $index => $entity) {
        if ($entity instanceof WebPageSchema) {
            $entities[$index] = PropertyCollectionSchema::fromWebPage(
                $entity,
                $itemList->getEntityID()
            );
            $entities[] = $itemList;
            break;
        }
    }
}
```

In this example, `$itemList` is an `ItemListSchema` prepared by your integration. The `PropertyCollectionSchema` builder is a specialised `CollectionPage` convenience builder; ordinary content integrations do not need it. This example applies to the **early page extension hook**, before Schema Manager registers the page entities. If your integration runs later from a controller action, use `SchemaRegistry::add()` for the finished entities, retaining the existing page `@id` where you are changing its type.

See [Page entities and content entities](schema-entities.md#page-entities-and-content-entities) for the distinction, and [WebPage and page types](web-page.md) for the underlying page schema.

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

## Shared Schema builder methods

All typed builders extend `DorsetDigital\SchemaManager\Model\Schema\Schema` and inherit these public methods. Builder-specific methods are documented on their individual schema pages.

| Method | Description |
| --- | --- |
| `__construct(array $data)` | Initialise an entity from its raw schema properties; normally use the builder's `create()` factory instead. |
| `getEntityID(): string` | Return the entity's complete `@id`. |
| `toArray(): array` | Return the entity's schema properties as an array. |
| `setMainEntityOfPage(string $pageURL): static` | Mark this entity as the primary subject of a page. |
| `isMainEntityOfPage(string $pageURL): bool` | Check whether this entity declares that page as its primary page. |
| `setIsPartOfPage(string $pageURL): static` | Mark this entity as supporting content belonging to a page. |
| `update(array $data): static` | Add or overwrite schema properties from an array. |

These methods are inherited by all builders, including `RatingSchema` and `AggregateRatingSchema`, although those two are normally used as nested value objects rather than standalone registered entities.
