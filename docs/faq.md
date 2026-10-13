# FAQPage schema

Schema Manager supports Schema.org `FAQPage` structured data, including questions and their accepted answers.

**For most sites, use `FAQPageHelper::addQuestion()`.** It's the simplest way to add FAQ schema from page content, especially when questions are spread across several elements or sections.

## Recommended: add questions with the convenience helper

Add a question wherever your application renders or processes an FAQ:

```php
use DorsetDigital\SchemaManager\Control\FAQPageHelper;

FAQPageHelper::addQuestion(
    'What is included?',
    'Everything listed in the description.'
);
```

That's all that's required: you don't need to create a schema object or register it yourself. When the page URL is omitted, the helper uses the current page URL (or falls back to the current request URL).

### FAQs across multiple page elements

Each FAQ element can contribute its own questions by calling the same helper:

```php
use DorsetDigital\SchemaManager\Control\FAQPageHelper;

foreach ($this->Questions() as $item) {
    FAQPageHelper::addQuestion($item->Question, $item->Answer);
}
```

**Questions from multiple elements on the same page are combined into one `FAQPage` entity**, rather than producing a separate entity for each element. This works regardless of the order in which sections contribute their questions.

You can also specify a page URL explicitly, useful when adding questions outside the current page context:

```php
FAQPageHelper::addQuestion(
    'Can I change my booking?',
    'Please contact us to discuss changes.',
    $page->AbsoluteLink()
);
```

The helper uses a stable, page-scoped FAQ ID and links the resulting FAQPage to its page.

## Manual: build a FAQPage entity

Use `FAQPageSchema` directly when you need an independently identified FAQ entity or more control over its properties and relationships:

```php
use DorsetDigital\SchemaManager\Control\SchemaRegistry;
use DorsetDigital\SchemaManager\Model\Schema\FAQPageSchema;

$schema = FAQPageSchema::create('faq-section-' . $section->ID)
    ->setIsPartOfPage($page->AbsoluteLink())
    ->addQuestion('What is included?', 'Everything listed in the description.')
    ->addQuestion('How do I get support?', 'Contact our team.');

SchemaRegistry::add($schema);
```

The identifier passed to `create()` is a stable identifier, **not** a URL or complete `@id`. The builder generates the entity ID. Use `setIsPartOfPage()` for supporting FAQs, or `setMainEntityOfPage()` if the FAQ is the page's primary subject.

## Which approach should I use?

Use **`FAQPageHelper::addQuestion()`** for ordinary FAQs, particularly when content comes from multiple page elements. Use **`FAQPageSchema`** when you need to construct and register a particular FAQ entity yourself.

`SchemaRegistry::addFAQ()` is retained for backwards compatibility but is deprecated. New integrations should call `FAQPageHelper::addQuestion()` instead.

See [Schema entities and relationships](schema-entities.md) for more on entity IDs and page relationships.
