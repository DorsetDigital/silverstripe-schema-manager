# BlogPosting schema

Silverstripe Blog integration is optional; Schema Manager does not require `silverstripe/blog`.

Enable the supplied extension:

```yaml
SilverStripe\Blog\Model\BlogPost:
  extensions:
    - DorsetDigital\SchemaManager\Extension\BlogPostSchemaExtension
```

After a configuration flush, BlogPost pages retain their normal `WebPage` entity and gain a linked `BlogPosting` containing the headline, publication and modification dates, description, and featured image where available.
