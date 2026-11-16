# Schema entities, IDs and relationships

Schema Manager emits a single JSON-LD `@graph` containing entities identified by stable `@id` values. Relationships use references such as `['@id' => $id]`, avoiding repeated copies of the same entity.

## Page entities and content entities

These answer different questions:

- **Page entity**: what kind of page is this? Schema Manager creates a `WebPage` automatically for normal Silverstripe pages. A more specific page type (for example, `CollectionPage`) can take its place, keeping the same `@id`.
- **Content entity**: what does the page describe? A `Product`, `BlogPosting`, `Service` or similar entity is normally **added alongside** the page entity, not used to replace it.

For a dedicated content page, `setMainEntityOfPage($pageURL)` on the content entity declares that relationship. The registry can then add the corresponding `WebPage.mainEntity` reference. Use `setIsPartOfPage($pageURL)` for a supporting entity that is part of a page rather than its main subject (when appropriate for that schema type).

**`mainEntity` and `mainEntityOfPage` describe the same relationship from opposite directions**: a page points to its main content; the content points back to the page. They do not mean the content entity replaces the `WebPage`.

Only replace the automatic `WebPage` when you are describing **the same page** with a more specific page type. Preserve its `@id` so references to the page remain valid. See [WebPage and page types](web-page.md) and [Developer integration examples](developer-api.md#choosing-a-page-type-or-adding-content).

## Canonical IDs

Use `SchemaIDs` to reference automatically generated entities and create IDs for your own:

```php
use DorsetDigital\SchemaManager\Control\SchemaIDs;

$organisationID = SchemaIDs::organisation();
$websiteID = SchemaIDs::website();
$pageID = SchemaIDs::webPage($page);
$breadcrumbID = SchemaIDs::breadcrumb($page);
$personID = SchemaIDs::forEntity('person', 'member-' . $member->ID);

$reference = SchemaIDs::ref($personID); // ['@id' => '...']
```

`webPage()` and `breadcrumb()` accept an optional SiteTree page; without one, they use the current page and throw when no page is available. **Getting an ID does not register an entity.**

## Registering and linking entities

```php
use DorsetDigital\SchemaManager\Control\SchemaRegistry;
use DorsetDigital\SchemaManager\Model\Schema\PersonSchema;

SchemaRegistry::add(
    PersonSchema::create($personID, 'Example Person')
        ->setWorksFor(SchemaIDs::organisation())
        ->setMainEntityOfPage($page->AbsoluteLink())
);
```

`SchemaRegistry::add($schema)` registers a typed builder with an `@id`; `addEntity($id, $data)` registers raw data. Calling `addEntity()` again with the same ID recursively merges properties, including augmentations to typed entities. For `FAQPage` entities, `mainEntity` question lists are appended instead of replacing questions at the same numeric indexes.

The registry resolves `mainEntityOfPage` relationships when `getGraph()`, `getSchema()` or `getJSON()` is called. If multiple schemas claim to be the page's primary entity, the last resolved claim takes precedence; model your page with one primary entity. For when to use each relationship, see [Page entities and content entities](#page-entities-and-content-entities).

## CMS annotation entity fields

YAML-defined page annotations can accept references to existing entities or inline type/name values. The built-in Event annotation offers organiser (Organization or Person) and location (Place) fields; Service offers a provider (Organization or Person). An inline entity is embedded in the annotation's data, while an existing reference is emitted as `@id`.

See [Page annotations](page-annotations.md) for YAML overrides, supported field sources and custom handlers. A referenced entity is not automatically created by selecting its ID.

## Output lifecycle

The controller extension registers automatic site and page entities. Application integrations may contribute additional entities via `updateSchemaManagerEntities(array &$entities)` or the registry API. Response middleware then inserts the finished JSON-LD into successful HTML pages with a closing `</head>`. XML and other non-HTML responses are excluded. The registry is cleared at the start of normal page controller requests.
