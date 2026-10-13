<?php

namespace DorsetDigital\SchemaManager\Extension;

use DorsetDigital\SchemaManager\Control\SchemaIDs;
use DorsetDigital\SchemaManager\Model\Schema\BlogPostingSchema;
use DorsetDigital\SchemaManager\Service\SchemaManager;
use SilverStripe\Core\Extension;

class BlogPostSchemaExtension extends Extension
{
    public function updateSchemaManagerEntities(array &$entities): void
    {
        $post = $this->owner;
        $url = rtrim($post->AbsoluteLink(), '/') . '/';
        $description = null;

        if ($post->hasField('Summary') && $post->Summary) {
            $description = $post->Summary;
        } elseif ($post->hasField('MetaDescription') && $post->MetaDescription) {
            $description = $post->MetaDescription;
        }

        $schema = BlogPostingSchema::create((string) $post->ID, $post->Title, $description)
            ->setURL($url)
            ->setMainEntityOfPage($url);

        if ($post->hasField('PublishDate') && $post->PublishDate) {
            $schema->setDatePublished($post->PublishDate);
        } elseif ($post->Created) {
            $schema->setDatePublished($post->Created);
        }

        if ($post->LastEdited) $schema->setDateModified($post->LastEdited);

        if ($post->hasMethod('FeaturedImage')) {
            $image = $post->FeaturedImage();
            if ($image && $image->exists()) $schema->setImage($image->getAbsoluteURL());
        }

        if (SchemaManager::config()->get('automatic_organisation_schema')) {
            $schema->setPublisher(SchemaIDs::organisation());
        }

        $entities[] = $schema;
    }
}
