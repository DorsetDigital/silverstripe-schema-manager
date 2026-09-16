<?php

namespace DorsetDigital\SchemaManager\Control;

use DorsetDigital\SchemaManager\Model\Schema\Schema;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;

class SchemaRegistry
{
    private static array $entities = [];

    public static function add(Schema $schema): void
    {
        $id = $schema->getID();

        if (!$id) {
            return;
        }

        self::addEntity($id, $schema->toArray());
    }

    public static function addEntity(string $id, array $data): void
    {
        if (isset(self::$entities[$id])) {
            self::$entities[$id] = array_replace_recursive(
                self::$entities[$id],
                $data
            );
            return;
        }

        self::$entities[$id] = $data;
    }

    public static function addFAQ(string $question, string $answer, ?string $pageURL = null): void
    {
        if (!$pageURL) {
            $page = Director::get_current_page();

            if ($page && method_exists($page, 'AbsoluteLink')) {
                $pageURL = $page->AbsoluteLink();
            } else {
                $pageURL = Director::absoluteURL(Controller::curr()->getRequest()->getURL());
            }
        }

        $pageURL = rtrim($pageURL, '/') . '/';
        $id = $pageURL . '#faq';

        if (!isset(self::$entities[$id])) {
            self::$entities[$id] = [
                '@type' => 'FAQPage',
                '@id' => $id,
                'mainEntityOfPage' => [
                    '@id' => $pageURL . '#webpage',
                ],
                'mainEntity' => [],
            ];
        }

        self::$entities[$id]['mainEntity'][] = [
            '@type' => 'Question',
            'name' => $question,
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => $answer,
            ],
        ];
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
        return array_values(self::$entities);
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
    }
}
