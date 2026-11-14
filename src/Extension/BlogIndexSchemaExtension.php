<?php

namespace DorsetDigital\SchemaManager\Extension;

use DorsetDigital\SchemaManager\Control\SchemaIDs;
use DorsetDigital\SchemaManager\Control\SchemaRegistry;
use DorsetDigital\SchemaManager\Model\Schema\ItemListSchema;
use DorsetDigital\SchemaManager\Service\SchemaManager;
use SilverStripe\Blog\Model\Blog;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Extension;

/**
 * Optional Silverstripe Blog index integration.
 *
 * Runs after the Blog action has rendered, while the controller is still active
 * and its action-specific post list has been populated.
 */
class BlogIndexSchemaExtension extends Extension
{
    public function afterCallActionHandler($request, $action, $result): void
    {
        if (!in_array($action, ['index', 'category', 'tag', 'archive', 'profile'], true)) {
            return;
        }

        // Category and tag endpoints can also serve RSS; never register for them.
        if (strcasecmp((string) ($request->param('Rss') ?? ''), 'rss') === 0) {
            return;
        }

        $controller = $this->owner;
        $blog = $controller->data();

        if (!$blog instanceof Blog || !$blog->exists()) {
            return;
        }

        $posts = $controller->PaginatedList();
        $query = $request->getVars();
        ksort($query);
        $viewKey = $request->getURL() . '?' . http_build_query($query);
        $list = self::buildList((int) $blog->ID, (string) $blog->Title, $posts, $viewKey);

        if ($list === null) {
            return;
        }

        SchemaRegistry::add($list);

        if (SchemaManager::config()->get('automatic_webpage_schema')) {
            SchemaRegistry::addEntity(SchemaIDs::webPage($blog), [
                '@type' => 'CollectionPage',
                'hasPart' => SchemaIDs::ref($list->getEntityID()),
            ]);
        }
    }

    /**
     * Public for use by custom Blog implementations and isolated tests.
     *
     * @param iterable $posts BlogPost records from the actual rendered list.
     */
    public static function buildList(int $blogID, string $name, iterable $posts, string $viewKey): ?ItemListSchema
    {
        $identifier = 'blog-index-' . $blogID . '-' . substr(sha1($viewKey), 0, 12);
        $list = ItemListSchema::create($identifier, $name);
        $count = 0;

        foreach ($posts as $post) {
            $id = (int) ($post->ID ?? 0);
            $url = SchemaIDs::publicURL($post->AbsoluteLink());

            if ($id <= 0 || !is_string($url) || $url === '') {
                continue;
            }

            $list->addEntity(
                SchemaIDs::forEntity('blogposting', (string) $id),
                (string) $post->Title,
                $url
            );
            $count++;
        }

        return $count > 0 ? $list : null;
    }
}
