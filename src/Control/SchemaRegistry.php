<?php

namespace DorsetDigital\SchemaManager\Control;

use DorsetDigital\SchemaManager\Model\Schema\BreadcrumbListSchema;
use DorsetDigital\SchemaManager\Model\Schema\FAQPageSchema;
use DorsetDigital\SchemaManager\Model\Schema\WebPageSchema;
use DorsetDigital\SchemaManager\Model\Schema\Schema;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;

class SchemaRegistry
{
    private static array $entities = [];

    private static array $schemas = [];

    public static function add(Schema $schema): void
    {
        $id = $schema->getEntityID();

        if (!$id) {
            return;
        }

        // Multiple FAQ sections on the same page share an ID. Keep the first
        // typed instance, but append questions from every subsequent section.
        if ($schema instanceof FAQPageSchema && isset(self::$schemas[$id])
            && self::$schemas[$id] instanceof FAQPageSchema) {
            self::addEntity($id, $schema->toArray());
            return;
        }

        self::$schemas[$id] = $schema;
        self::addEntity($id, $schema->toArray());
    }

    public static function addEntity(string $id, array $data): void
    {
        if (isset(self::$entities[$id])) {
            // FAQ mainEntity is a list, not a map: concatenate questions rather
            // than recursively replacing entries with matching numeric indexes.
            if ((self::$entities[$id]['@type'] ?? null) === 'FAQPage'
                && ($data['@type'] ?? null) === 'FAQPage'
                && isset($data['mainEntity']) && is_array($data['mainEntity'])) {
                $data['mainEntity'] = array_merge(
                    self::$entities[$id]['mainEntity'] ?? [],
                    $data['mainEntity']
                );
            }

            self::$entities[$id] = array_replace_recursive(
                self::$entities[$id],
                $data
            );
            return;
        }

        self::$entities[$id] = $data;
    }

    /**
     * @deprecated Use FAQPageSchema::create() and SchemaRegistry::add() instead.
     */
    public static function addFAQ(string $question, string $answer, ?string $pageURL = null): void
    {
        @trigger_error(
            __METHOD__ . '() is deprecated. Use FAQPageSchema::create() and SchemaRegistry::add() instead.',
            E_USER_DEPRECATED
        );

        if (!$pageURL) {
            $page = Director::get_current_page();

            if ($page && method_exists($page, 'AbsoluteLink')) {
                $pageURL = $page->AbsoluteLink();
            } else {
                $pageURL = Director::absoluteURL(Controller::curr()->getRequest()->getURL());
            }
        }

        $schema = FAQPageSchema::create($pageURL)->addQuestion($question, $answer);
        self::add($schema);
    }

    public static function addBreadCrumbs(
        SiteTree $page,
        int $maxDepth = 20,
        bool|string $stopAtPageType = false,
        bool $showHidden = false
    ): void {
        $items = $page->getBreadcrumbItems($maxDepth, $stopAtPageType, $showHidden);

        if (!$items || $items->count() < 2) {
            return;
        }

        $itemList = [];
        $position = 1;

        foreach ($items as $item) {
            if (!$item instanceof SiteTree) {
                continue;
            }

            $itemList[] = [
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => $item->MenuTitle ?: $item->Title,
                'item' => $item->AbsoluteLink(),
            ];
        }

        if (count($itemList) < 2) {
            return;
        }

        $id = rtrim($page->AbsoluteLink(), '/') . '/#breadcrumb';

        self::addEntity($id, [
            '@type' => 'BreadcrumbList',
            '@id' => $id,
            'itemListElement' => $itemList,
        ]);
    }

    public static function getGraph(): array
    {
        self::resolveRelationships();

        return array_values(self::$entities);
    }

    private static function resolveRelationships(): void
    {
        $webPages = [];

        foreach (self::$schemas as $schema) {
            if ($schema instanceof WebPageSchema) {
                $webPages[$schema->getEntityID()] = $schema;
            }
        }

        foreach ($webPages as $webPageID => $webPage) {
            $pageURL = $webPage->toArray()['url'] ?? null;
            if (!is_string($pageURL) || $pageURL === '') {
                continue;
            }

            $pageURL = rtrim($pageURL, '/') . '/';
            $defaultWebPageID = SchemaIDs::forPageURL($pageURL, 'webpage');
            $mainEntity = null;
            $breadcrumb = null;

            foreach (self::$schemas as $schema) {
                if ($schema === $webPage) {
                    continue;
                }

                if ($schema instanceof BreadcrumbListSchema
                    && $schema->getEntityID() === SchemaIDs::forPageURL($pageURL, 'breadcrumb')
                ) {
                    $breadcrumb = $schema;
                    continue;
                }

                if ($schema->isMainEntityOfPage($pageURL)) {
                    if ($mainEntity instanceof Schema) {
                        $mainEntity->setIsPartOfPage($pageURL);
                    }

                    $mainEntity = $schema;
                }
            }

            if ($mainEntity !== null) {
                $webPage->setMainEntity($mainEntity->getEntityID());
            }

            // A developer-specified breadcrumb relationship takes precedence.
            if ($breadcrumb !== null && !isset($webPage->toArray()['breadcrumb'])) {
                $webPage->setBreadcrumb($breadcrumb->getEntityID());
            }

            // The builder uses the conventional WebPage ID as a fallback.
            // When this page has a custom ID, resolve references to the
            // actual registered WebPage without changing the public API.
            if ($webPageID !== $defaultWebPageID) {
                foreach (self::$schemas as $schema) {
                    if ($schema === $webPage) {
                        continue;
                    }

                    $data = $schema->toArray();
                    foreach (['mainEntityOfPage', 'isPartOf'] as $relationship) {
                        if (($data[$relationship]['@id'] ?? null) === $defaultWebPageID) {
                            $schema->update([$relationship => ['@id' => $webPageID]]);
                        }
                    }
                }
            }
        }

        foreach (self::$schemas as $id => $schema) {
            self::$entities[$id] = array_replace_recursive(
                $schema->toArray(),
                self::$entities[$id] ?? []
            );
        }
    }

    public static function getSchema(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@graph' => self::getGraph(),
        ];
    }

    public static function getJSON(): string
    {
        if (!self::$entities) {
            return '';
        }

        return (string) json_encode(
            self::getSchema(),
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
        );
    }

    public static function flush(): void
    {
        self::$entities = [];
        self::$schemas = [];
    }
}
