<?php

namespace DorsetDigital\SchemaManager\Extension;

use DorsetDigital\SchemaManager\Control\SchemaIDs;
use DorsetDigital\SchemaManager\Model\Schema\BlogPostingSchema;
use DorsetDigital\SchemaManager\Model\Schema\PersonSchema;
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

        $authors = [];
        $profilesDisabled = $post->getProfilesDisabled();

        foreach ($post->getCredits() as $credit) {
            $name = trim((string) $credit->Name);
            if ($name === '') {
                continue;
            }

            // Linked Member credits expose an ID; static AuthorNames do not.
            $memberID = (int) ($credit->ID ?? 0);
            if ($memberID > 0) {
                $person = PersonSchema::create('blog-author-' . $memberID, $name);
                $profileURL = $credit->URL ?? null;

                if (!$profilesDisabled && is_string($profileURL)
                    && filter_var($profileURL, FILTER_VALIDATE_URL)
                    && in_array(parse_url($profileURL, PHP_URL_SCHEME), ['http', 'https'], true)) {
                    $person->setURL($profileURL);
                }

                $entities[] = $person;
                $authors[] = ['@id' => $person->getEntityID()];
            } else {
                $authors[] = ['@type' => 'Person', 'name' => $name];
            }
        }

        if ($authors) {
            $schema->setAuthors($authors);
        }

        $entities[] = $schema;
    }
}
