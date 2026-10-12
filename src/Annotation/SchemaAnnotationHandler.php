<?php

namespace DorsetDigital\SchemaManager\Annotation;

use DorsetDigital\SchemaManager\Model\Schema\Schema;
use SilverStripe\CMS\Model\SiteTree;

interface SchemaAnnotationHandler
{
    public function build(SiteTree $page, array $values, array $definition): ?Schema;
}
