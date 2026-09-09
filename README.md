# Silverstripe Schema Manager

A small structured-data registry for Silverstripe CMS 6.

The module lets controllers, extensions and other application code register Schema.org entities during a request. Registered entities are emitted as a single JSON-LD `@graph` in the page `<head>`.

## Requirements

- PHP 8.3+
- Silverstripe Framework 6.x

## Installation

Until the package is published on Packagist, add the repository to your project's `composer.json`:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/DorsetDigital/silverstripe-schema-manager"
        }
    ]
}
```

Then install the development branch:

```bash
composer require dorsetdigital/silverstripe-schema-manager:dev-main
```

After installation, flush Silverstripe configuration:

```bash
vendor/bin/sake dev/build flush=1
```

## Basic usage

Use the registry from controllers, extensions or other request code:

```php
use DorsetDigital\SchemaManager\Control\SchemaRegistry;

SchemaRegistry::addEntity(
    'https://example.com/#organisation',
    [
        '@type' => 'Organization',
        '@id' => 'https://example.com/#organisation',
        'name' => 'Example Ltd',
        'url' => 'https://example.com/',
    ]
);
```

Multiple entities registered during the same request are returned in one schema graph:

```json
{
    "@context": "https://schema.org",
    "@graph": [
        {
            "@type": "Organization",
            "@id": "https://example.com/#organisation",
            "name": "Example Ltd"
        }
    ]
}
```

If an entity is registered again with the same ID, the new data is recursively merged into the existing entity.

## FAQ schema

FAQ markup has a convenience helper. From a page controller, for example:

```php
use DorsetDigital\SchemaManager\Control\SchemaRegistry;

foreach ($this->FAQs() as $faq) {
    SchemaRegistry::addFAQ(
        $faq->Question,
        $faq->Answer,
        $this->AbsoluteLink()
    );
}
```

The URL argument is optional. When omitted, Schema Manager uses the current request URL:

```php
foreach ($this->FAQs() as $faq) {
    SchemaRegistry::addFAQ(
        $faq->Question,
        $faq->Answer
    );
}
```

Each call appends another `Question` to a single `FAQPage` entity for the request.

Resulting schema is equivalent to:

```json
{
    "@type": "FAQPage",
    "@id": "https://example.com/example-page/#faq",
    "mainEntity": [
        {
            "@type": "Question",
            "name": "What is the question?",
            "acceptedAnswer": {
                "@type": "Answer",
                "text": "This is the answer."
            }
        }
    ]
}
```

## Rendering

No template change is required by default.

The module attaches an extension to Silverstripe controllers. At the start of a request it clears the registry, and after controller initialisation it inserts the collected JSON-LD into the page head using Silverstripe Requirements.

This allows schema to be registered by multiple pieces of application code without each one needing to render its own `<script type="application/ld+json">` element.

## Registry API

### `SchemaRegistry::addEntity(string $id, array $data)`

Registers a Schema.org entity. Existing data registered under the same ID is recursively merged.

### `SchemaRegistry::addFAQ(string $question, string $answer, ?string $pageURL = null)`

Adds a question and accepted answer to the FAQ schema for the current page.

### `SchemaRegistry::getGraph(): array`

Returns all registered entities as an indexed array.

### `SchemaRegistry::getSchema(): array`

Returns the complete JSON-LD structure containing `@context` and `@graph`.

### `SchemaRegistry::getJSON(): string`

Returns the complete schema graph as JSON suitable for output in an `application/ld+json` script element.

### `SchemaRegistry::flush(): void`

Clears all currently registered entities.

## Project structure

```text
_config/
  schema-manager.yml
src/
  Control/
    SchemaRegistry.php
  Extension/
    SchemaControllerExtension.php
```

The `DorsetDigital\\SchemaManager` namespace is intentionally split into conventional Silverstripe areas (`Control`, `Extension`, `Model`, `Page`, etc.) as features are added.

## Development status

This is an initial CMS 6 implementation. The base registry is deliberately small so additional schema types and Silverstripe integrations can be developed without locking the public API down too early.

Likely next additions include helpers or typed schema builders for:

- `Organization`
- `WebSite`
- `WebPage`
- `BreadcrumbList`
- `Service`
- `Product` / `Offer`
- configurable organisation and website defaults
- automated page/controller integration

## License

BSD 3-Clause License.
