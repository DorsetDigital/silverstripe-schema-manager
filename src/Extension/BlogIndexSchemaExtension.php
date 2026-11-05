<?php

namespace DorsetDigital\SchemaManager\Extension;

use DorsetDigital\SchemaManager\Control\SchemaIDs;
use DorsetDigital\SchemaManager\Control\SchemaRegistry;
use DorsetDigital\SchemaManager\Model\Schema\ItemListSchema;
use DorsetDigital\SchemaManager\Service\SchemaManager;
use SilverStripe\Blog\Model\BlogController;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Extension;

/**
 * Optional Silverstripe Blog index integration.
 *
 * Runs after the response is rendered, when BlogController has already applied
 * its action-specific category, tag, archive or author filters and pagination.
 */
class BlogIndexSchemaExtension extends Extension
{
    public function updateSchemaManagerResponseEntities(array &$entities, HTTPRequest $request): void
    {
        $blog = $this->owner;
        $controller = Controller::curr();

        if (!$controller instanceof BlogController || (int) $controller->data()->ID !== (int) $blog->ID) {
            return;
        }

        // The Blog controller's RSS endpoint is not a listing page.
        $action = (string) ($request->param('Action') ?? 'index');
        if (!in_array($action, ['index', 'category', 'tag', 'archive', 'profile'], true)) {
            return;
        }

        $posts = $controller->PaginatedList();
        if (!$posts) {
            return;
        }

        // Different filtered/paginated views can show different lists.
        $query = $request->getVars();
        ksort($query);
        $viewKey = $request->getURL() . '?' . http_build_query($query);
        $list = self::buildList((int) $blog->ID, (string) $blog->Title, $posts, $viewKey);

        if ($list === null) {
            return;
        }

        $entities[] = $list;

        if (SchemaManager::config()->get('automatic_webpage_schema')) {
            // CollectionPage is a subtype of WebPage; hasPart describes the
            // list without claiming it is the page's main entity.
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
            $url = $post->AbsoluteLink();

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
