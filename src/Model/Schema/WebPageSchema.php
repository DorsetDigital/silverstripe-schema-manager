<?php

namespace DorsetDigital\SchemaManager\Model\Schema;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Director;

class WebPageSchema extends Schema
{
    public static function fromPage(SiteTree $page): static
    {
        $url = rtrim($page->AbsoluteLink(), '/') . '/';
        $baseURL = rtrim(Director::absoluteBaseURL(), '/') . '/';

        $data = [
            '@type' => 'WebPage',
            '@id' => $url . '#webpage',
            'url' => $url,
            'name' => $page->Title,
            'isPartOf' => [
                '@id' => $baseURL . '#website',
            ],
        ];

        if ($page->MetaDescription) {
            $data['description'] = $page->MetaDescription;
        }

        if ($page->Created) {
            $data['datePublished'] = $page->Created;
        }

        if ($page->LastEdited) {
            $data['dateModified'] = $page->LastEdited;
        }

        return new static($data);
    }
}
