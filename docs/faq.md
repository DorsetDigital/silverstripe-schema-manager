# FAQPage schema

Use a stable identifier when creating a generic FAQ entity. Page relationships are explicit:

```php
use DorsetDigital\SchemaManager\Control\SchemaRegistry;
use DorsetDigital\SchemaManager\Control\FAQPageHelper;
use DorsetDigital\SchemaManager\Model\Schema\FAQPageSchema;

$schema = FAQPageSchema::create('faq-section-' . $section->ID)
    ->setIsPartOfPage($page->AbsoluteLink())
    ->addQuestion('What is included?', 'Everything listed in the description.');
SchemaRegistry::add($schema);
```

For multiple FAQ sections on the same page, use `FAQPageHelper::addQuestion($question, $answer, $pageURL)` for each question. The helper combines questions into one page-scoped FAQ entity regardless of section order.

`SchemaRegistry::addFAQ()` remains available as a deprecated forwarding method.
