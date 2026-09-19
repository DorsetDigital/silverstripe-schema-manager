<?php

namespace DorsetDigital\SchemaManager\Model\Schema;

use SilverStripe\CMS\Model\SiteTree;

class BreadcrumbListSchema extends Schema
{
    public static function fromPage(
        SiteTree $page,
        int $maxDepth = 20,
        bool|string $stopAtPageType = false,
        bool $showHidden = false
    ): ?static {
        $items = $page->getBreadcrumbItems($maxDepth, $stopAtPageType, $showHidden);

        if (!$items || $items->count() < 2) {
            return null;
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
            return null;
        }

        $url = rtrim($page->AbsoluteLink(), '/') . '/';

        return new static([
            '@type' => 'BreadcrumbList',
            '@id' => $url . '#breadcrumb',
            'itemListElement' => $itemList,
        ]);
    }
}
