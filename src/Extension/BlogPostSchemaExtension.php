<?php

namespace DorsetDigital\SchemaManager\Extension;

use DorsetDigital\SchemaManager\Model\Schema\BlogPostingSchema;
use SilverStripe\ORM\DataExtension;

class BlogPostSchemaExtension extends DataExtension
{
    public function updateSchemaManagerEntities(array &$entities): void
    {
        $entities[] = BlogPostingSchema::fromBlogPost($this->owner);
    }
}
