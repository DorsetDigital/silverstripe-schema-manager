<?php

namespace DorsetDigital\SchemaManager\Service;

use SilverStripe\Core\Config\Configurable;

class SchemaManager
{
    use Configurable;

    private static bool $automatic_organisation_schema = true;

    private static bool $automatic_website_schema = true;

    private static bool $automatic_webpage_schema = true;
}
