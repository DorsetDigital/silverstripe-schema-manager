<?php

namespace DorsetDigital\SchemaManager\View;

use DorsetDigital\SchemaManager\Control\SchemaRegistry;
use SilverStripe\View\Requirements_Backend;

class SchemaRequirementsBackend extends Requirements_Backend
{
    public function includeInHTML($content)
    {
        $json = SchemaRegistry::getJSON();

        if ($json) {
            $this->insertHeadTags(
                '<script type="application/ld+json">' . $json . '</script>',
                'schema-manager-jsonld'
            );
        }

        return parent::includeInHTML($content);
    }
}
