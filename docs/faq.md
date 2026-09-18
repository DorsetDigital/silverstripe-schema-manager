# FAQPage schema

Use `FAQPageSchema` to describe a page containing questions and accepted answers.

```php
use DorsetDigital\SchemaManager\Control\SchemaRegistry;
use DorsetDigital\SchemaManager\Model\Schema\FAQPageSchema;

$faqSchema = FAQPageSchema::create($page->AbsoluteLink());

foreach ($page->FAQs() as $faq) {
    $faqSchema->addQuestion($faq->Question, $faq->Answer);
}

SchemaRegistry::add($faqSchema);
```

By default the entity is linked to the page's automatic `WebPage` using `isPartOf`. If the FAQ is the primary subject of a dedicated page, call `$faqSchema->setMainEntityOfPage($page->AbsoluteLink())`. Each call to `addQuestion()` adds a Schema.org `Question` containing its `acceptedAnswer`.

`SchemaRegistry::addFAQ()` remains available for backwards compatibility but is deprecated. New integrations should use `FAQPageSchema`.
