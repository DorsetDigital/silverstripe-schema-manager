# Schema entities, IDs and relationships

Schema Manager emits a single JSON-LD `@graph` containing entities identified by stable `@id` values. Relationships use references such as `['@id' => $id]`, avoiding repeated copies of the same entity.

## Canonical IDs

Use `SchemaIDs` to reference automatically generated entities and create IDs for your own:

```php
use DorsetDigital\SchemaManager\Control\SchemaIDs;

$organisationID = SchemaIDs::organisation();
$websiteID = SchemaIDs::website();
$pageID = SchemaIDs::webPage($page);
$breadcrumbID = SchemaIDs::breadcrumb($page);
$personID = 'member-' . $member->ID;

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

Typed builders commonly use `setMainEntityOfPage($pageURL)` to declare the primary subject of a page, or `setIsPartOfPage($pageURL)` for secondary content. The registry resolves page relationships when `getGraph()`, `getSchema()` or `getJSON()` is called. If multiple schemas claim to be the page's primary entity, the last resolved claim takes precedence; model your page with one primary entity.

## CMS annotation entity fields

YAML-defined page annotations can accept references to existing entities or inline type/name values. The built-in Event annotation offers organiser (Organization or Person) and location (Place) fields; Service offers a provider (Organization or Person). An inline entity is embedded in the annotation's data, while an existing reference is emitted as `@id`.

See [Page annotations](page-annotations.md) for YAML overrides, supported field sources and custom handlers. A referenced entity is not automatically created by selecting its ID.

## Output lifecycle

The controller extension registers automatic site and page entities. Application integrations may contribute additional entities via `updateSchemaManagerEntities(array &$entities)` or the registry API. Response middleware then inserts the finished JSON-LD into successful HTML pages with a closing `</head>`. XML and other non-HTML responses are excluded. The registry is cleared at the start of normal page controller requests.
