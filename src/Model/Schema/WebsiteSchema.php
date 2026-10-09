<?php

namespace DorsetDigital\SchemaManager\Model\Schema;

use DorsetDigital\SchemaManager\Control\SchemaIDs;
use SilverStripe\Control\Director;
use SilverStripe\SiteConfig\SiteConfig;

class WebsiteSchema extends Schema
{
    public static function fromSiteConfig(SiteConfig $config): static
    {

        return new static([
            '@type' => 'WebSite',
            '@id' => SchemaIDs::website(),
            'url' => rtrim(Director::absoluteBaseURL(), '/') . '/',
            'name' => $config->Title,
            'publisher' => [
                '@id' => SchemaIDs::organisation(),
            ],
        ]);
    }
}
