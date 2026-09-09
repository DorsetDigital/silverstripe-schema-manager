<?php

namespace DorsetDigital\SchemaManager\Extension;

use DorsetDigital\SchemaManager\Control\SchemaRegistry;
use SilverStripe\Core\Extension;
use SilverStripe\View\HTML;
use SilverStripe\View\Requirements;

class SchemaControllerExtension extends Extension
{
    public function onAfterInit(): void
    {
        SchemaRegistry::flush();
    }

    public function onAfterInitComplete(): void
    {
        $json = SchemaRegistry::getJSON();

        if (!$json) {
            return;
        }

        Requirements::insertHeadTags(
            '<script type="application/ld+json">' . $json . '</script>',
            'schema-manager-jsonld'
        );
    }
}
