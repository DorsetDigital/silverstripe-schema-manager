# ItemList

Use `ItemListSchema` for a collection of links shown on a page, such as a Latest News element, related content or a product category. Individual article or product pages should still publish their own full entities.

## Latest News element

The optional automatic Blog integration creates `BlogPosting` entities with IDs derived from the Silverstripe blog post ID. Reference those same IDs in the list, while including the article URL for discoverability.

```php
use DorsetDigital\SchemaManager\Control\SchemaIDs;
use DorsetDigital\SchemaManager\Control\SchemaRegistry;
use DorsetDigital\SchemaManager\Model\Schema\ItemListSchema;
use SilverStripe\Blog\Model\BlogPost;

$posts = BlogPost::get()->sort('PublishDate', 'DESC')->limit($this->PostsToShow);
$element = $this->getElement();

$list = ItemListSchema::create('latest-news-' . $element->ID, 'Latest News');

foreach ($posts as $post) {
    $list->addEntity(
        SchemaIDs::forEntity('blogposting', (string) $post->ID),
        $post->Title,
        $post->AbsoluteLink()
    );
}

SchemaRegistry::add($list->setIsPartOfPage($element->getPage()->AbsoluteLink()));
```

The list uses `ListItem` entries with automatic 1-based positions and `numberOfItems`. Each entry's `item` refers to the existing entity by `@id`; `name` and `url` can accompany the reference. Referencing an ID does not register or reproduce the full entity on the listing page.

For ordinary links without an existing schema entity, use `addItem($url, $name)`.

## Public methods

| Method | Purpose |
| --- | --- |
| `create(string $identifier, ?string $name = null)` | Create a stable ItemList entity |
| `addItem(string $url, ?string $name = null)` | Append a linked item |
| `addEntity(string $entityID, ?string $name = null, ?string $url = null)` | Append a reference to an existing entity |
| `getEntityID()` | Get this list's stable ID |
| `toArray()` | Get the JSON-LD entity data |
| `setIsPartOfPage(string $pageURL)` | Associate the list with its containing page |
| `setMainEntityOfPage(string $pageURL)` | Make the list the page's main entity |
| `isMainEntityOfPage(string $pageURL)` | Check the main entity relationship |
| `update(array $data)` | Merge additional schema properties |
