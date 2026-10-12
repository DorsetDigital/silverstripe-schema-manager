# Page annotations (in development)

Page annotations are intended for occasional Event or Service markup on otherwise unstructured pages. Structured event systems, product catalogues, job boards, FAQs and galleries should continue using the PHP builders.

The annotation foundation uses `SchemaDefinitionRegistry`, `SchemaAnnotationFactory`, `GenericAnnotationHandler` and `GenericSchema`. A CMS editing interface and published-page integration are still to be implemented.

## Definitions

Definitions are merged using Silverstripe YAML configuration under `DorsetDigital\\SchemaManager\\Annotation\\SchemaDefinitionRegistry.definitions`. Built-in definitions for `event` and `service` are in `_config/schema-annotations.yml`.

Supported field sources: `page.Title`, `page.AbsoluteLink`, `page.MetaDescription`. Supported value types initially include text, textarea, datetime, select, and entity. Entity values may refer to an existing `@id` or provide an inline type and name.

## Overriding bundled YAML definitions

Project configuration can override individual properties of the bundled definitions without copying the whole definition. Give your configuration block a unique `Name` and use `After: schema-manager-annotations` to ensure it is applied after the module's defaults.

For example, the bundled Event definition uses `page.MetaDescription` as the source of its description. To make that an editable textarea instead:

```yaml
---
Name: app-schema-annotations
After: schema-manager-annotations
---
DorsetDigital\\SchemaManager\\Annotation\\SchemaDefinitionRegistry:
  definitions:
    event:
      fields:
        description:
          source: null
          type: textarea
```

This overrides only the Event description field; the other Event fields and the Service definition remain available. Setting `source: null` is important: simply adding `type: textarea` would leave the inherited `source: page.MetaDescription` in place, so the generic handler would continue reading the page metadata.

You can use the same pattern to change labels, defaults, options, or a definition's `handler`. After changing YAML configuration, flush Silverstripe's configuration cache (for example with `?flush=1`).

## Advanced Usage: custom PHP handlers

Use the optional `handler` key to process non-standard page annotations:

```yaml
---
Name: app-schema-annotations
After: schema-manager-annotations
---
DorsetDigital\\SchemaManager\\Annotation\\SchemaDefinitionRegistry:
  definitions:
    event:
      handler: App\\Schema\\CustomEventHandler
```

A handler must implement `SchemaAnnotationHandler`. It receives the page, editor values, and the resolved definition, and returns a `Schema` object or `null`:

```php
<?php

namespace App\Schema;

use DorsetDigital\SchemaManager\Annotation\SchemaAnnotationHandler;
use DorsetDigital\SchemaManager\Model\Schema\EventSchema;
use DorsetDigital\SchemaManager\Model\Schema\Schema;
use SilverStripe\CMS\Model\SiteTree;

class CustomEventHandler implements SchemaAnnotationHandler
{
    public function build(SiteTree $page, array $values, array $definition): ?Schema
    {
        if (empty($values['startDate'])) {
            return null;
        }

        return EventSchema::create(
            $page->AbsoluteLink(),
            $page->Title,
            $values['startDate'],
            $page->MetaDescription ?: null
        )->setMainEntityOfPage($page->AbsoluteLink());
    }
}
```

Handlers are resolved through Silverstripe Injector. If no handler is configured, the default `GenericAnnotationHandler` creates a `GenericSchema`.

For complex content with an existing data model, prefer constructing builders directly and registering them with `SchemaRegistry::add()`.
