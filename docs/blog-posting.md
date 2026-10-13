# BlogPosting

The optional Silverstripe Blog integration adds a Schema.org `BlogPosting` entity when a `SilverStripe\Blog\Model\BlogPost` page is viewed.

## Enable the integration

Install `silverstripe/blog` in the project, then attach the bundled extension in your project's YAML configuration:

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

Apply the configuration with `vendor/bin/sake dev/build flush=1`.

The integration is **opt-in**: installing Schema Manager alone does not attach the extension to BlogPost.

## Generated properties

The `BlogPosting` entity uses the blog post's URL and title, `PublishDate` (falling back to `Created`), `LastEdited`, and `Summary` (falling back to `MetaDescription`). A featured image is included when the post exposes an existing `FeaturedImage`.

When automatic organisation schema is enabled, it references the site's `Organization` as publisher and identifies the blog post as the main entity of its `WebPage`.

For custom fields or additional Schema.org properties, you can extend the generated schema through the standard `updateSchemaManagerEntities` hook.

Linked Blog authors are registered as reusable `Person` entities with stable IDs; name-only credits are embedded. Author profile URLs are omitted when Blog profiles are disabled. The generic `BlogPostingSchema::create($identifier, $headline, $description)` builder can also be used independently of Silverstripe Blog.
