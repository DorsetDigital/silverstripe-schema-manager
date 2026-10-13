# BlogPosting schema

Schema Manager can describe articles using Schema.org `BlogPosting` structured data. There are two ways to use it:

- **Automatic Silverstripe Blog integration:** enable the supplied extension if your site uses `silverstripe/blog`.
- **Manual builder:** create and register `BlogPostingSchema` from your own pages, controllers or integrations. The builder does **not** require `silverstripe/blog`.

Both approaches contribute entities to the same JSON-LD graph as the site's other schema.

## Automatic integration with Silverstripe Blog

The Blog integration is optional and **not enabled by default**. If your project uses `silverstripe/blog`, attach the bundled extension to `BlogPost` in your application's YAML configuration:

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

Apply the configuration:

```bash
vendor/bin/sake dev/build flush=1
```

On a BlogPost page, the extension contributes a `BlogPosting` entity and marks it as the main entity of that page. The usual automatic `WebPage` schema can then reference the post.

### Fields populated automatically

| Schema.org property | Source |
| --- | --- |
| `headline` | BlogPost `Title` |
| `description` | `Summary`, falling back to `MetaDescription` |
| `url` | The post's absolute URL |
| `datePublished` | `PublishDate`, falling back to `Created` |
| `dateModified` | `LastEdited` |
| `image` | Existing `FeaturedImage`, when available |
| `publisher` | The site's Organization entity, **if automatic organisation schema is enabled** |
| `author` | Credits returned by the Blog module, where available |
| `mainEntityOfPage` | A reference to the post's WebPage |

The post's numeric ID is used as the builder identifier, so the generated entity has a stable site-scoped `@id`.

**Authors:** Linked member credits are represented by separately registered `Person` entities and referenced by their IDs. Name-only author credits are embedded as `Person` objects. Profile URLs are only included for linked members when profiles are enabled and the URL is a valid HTTP(S) address.

If you need to supplement the generated entity with project-specific properties, use the `updateSchemaManagerEntities(array &$entities)` extension hook. See the [developer API reference](developer-api.md).

## Using the BlogPosting builder manually

Use `BlogPostingSchema` for articles that are **not** Silverstripe Blog posts, or when you want to control exactly which fields are emitted.

For example, a custom article page can register its own BlogPosting entity:

```php
use DorsetDigital\SchemaManager\Control\SchemaIDs;
use DorsetDigital\SchemaManager\Control\SchemaRegistry;
use DorsetDigital\SchemaManager\Model\Schema\BlogPostingSchema;

$url = $article->AbsoluteLink();

$schema = BlogPostingSchema::create(
    'article-' . $article->ID,
    $article->Title,
    $article->MetaDescription
)
    ->setURL($url)
    ->setDatePublished($article->Created)
    ->setDateModified($article->LastEdited)
    ->setPublisher(SchemaIDs::organisation())
    ->setMainEntityOfPage($url);

SchemaRegistry::add($schema);
```

The first argument to `create()` is a **stable identifier**, not a URL or a complete `@id`. Schema Manager uses it to generate a site-scoped entity ID, for example `https://example.com/#blogposting-article-42`. The headline is required; the description is optional.

`setURL()` supplies the article's public URL separately. `setMainEntityOfPage($url)` identifies the article as the primary subject of that page. For a BlogPosting that is merely mentioned on another page, use `setIsPartOfPage($url)` instead.

Only call `setPublisher(SchemaIDs::organisation())` when your site registers that organisation entity; otherwise supply the ID of a publisher entity you register yourself. Similarly, include image and date fields only when their values are available.

### Available builder methods

| Method | Purpose |
| --- | --- |
| `create($identifier, $headline, $description = null)` | Create the BlogPosting with a stable ID |
| `setURL($url)` | Set or clear the article URL |
| `setImage($url)` | Set or clear an image URL |
| `setDatePublished($date)` | Set or clear publication date |
| `setDateModified($date)` | Set or clear modification date |
| `setPublisher($entityID)` | Reference a publisher by its `@id` |
| `setAuthor($entityID)` | Reference a single author by `@id` |
| `setAuthors($authors)` | Set an array of author references or embedded author objects |
| `setMainEntityOfPage($pageURL)` | Mark the post as the primary entity of a page |
| `setIsPartOfPage($pageURL)` | Link a supporting post to a page |
| `update($properties)` | Add other Schema.org properties as an array |

The builder's optional setters accept `null` to remove a property.

### Adding authors manually

You can reference an author entity already registered elsewhere:

```php
$schema->setAuthor($person->getEntityID());
```

Or supply multiple authors, mixing entity references and inline names:

```php
$schema->setAuthors([
    ['@id' => $person->getEntityID()],
    ['@type' => 'Person', 'name' => 'Guest Contributor'],
]);
```

`setAuthor()` and `setPublisher()` accept **full entity IDs** (not builder identifiers). They create the `['@id' => ...]` reference for you; they do not register the referenced entities. Register reusable `Person` or `Organization` entities separately if needed.

For other properties not covered by the fluent methods, use `update()`:

```php
$schema->update([
    'articleSection' => 'News',
    'keywords' => ['Silverstripe', 'Structured data'],
]);
```

## Choosing an approach

For ordinary `silverstripe/blog` sites, the bundled extension is the simplest option and keeps schema aligned with the Blog module's fields and credits. For custom article types or other publishing systems, use the manual builder. You can use both approaches in the same project, but avoid registering two BlogPosting entities for the same article.

See [Schema entities, IDs and relationships](schema-entities.md) for more on registration and entity references.
