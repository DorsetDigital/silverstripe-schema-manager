<?php

namespace DorsetDigital\SchemaManager\Extension;

use DorsetDigital\SchemaManager\Model\Schema\BlogPostingSchema;
use SilverStripe\Core\Extension;

class BlogPostSchemaExtension extends Extension
{
    public function updateSchemaManagerEntities(array &$entities): void
    {
        $entities[] = BlogPostingSchema::fromBlogPost($this->owner);
    }
}
