<?php

namespace DorsetDigital\SchemaManager\Model\Schema;

use SilverStripe\Control\Director;
use SilverStripe\SiteConfig\SiteConfig;

class WebsiteSchema extends Schema
{
    public static function fromSiteConfig(SiteConfig $config): static
    {
        $baseURL = rtrim(Director::absoluteBaseURL(), '/') . '/';

        return new static([
            '@type' => 'WebSite',
            '@id' => $baseURL . '#website',
            'url' => $baseURL,
            'name' => $config->Title,
            'publisher' => [
                '@id' => $baseURL . '#organisation',
            ],
        ]);
    }
}
