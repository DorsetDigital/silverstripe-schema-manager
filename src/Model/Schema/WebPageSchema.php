<?php

namespace DorsetDigital\SchemaManager\Model\Schema;

use DorsetDigital\SchemaManager\Control\SchemaIDs;
use SilverStripe\CMS\Model\SiteTree;

class WebPageSchema extends Schema
{
    public static function fromPage(SiteTree $page): static
    {
        $url = rtrim($page->AbsoluteLink(), '/') . '/';

        $data = [
            '@type' => 'WebPage',
            '@id' => SchemaIDs::webPage($page),
            'url' => $url,
            'name' => $page->Title,
            'isPartOf' => [
                '@id' => SchemaIDs::website(),
            ],
        ];

        if ($page->MetaDescription) {
            $data['description'] = $page->MetaDescription;
        }

        if ($page->Created) {
            $data['datePublished'] = $page->Created;
        }

        if ($page->LastEdited) {
            $data['dateModified'] = $page->LastEdited;
        }

        return new static($data);
    }

    public function setBreadcrumb(?Schema $schema): static
    {
        if ($schema) {
            $this->data['breadcrumb'] = ['@id' => $schema->getID()];
        } else {
            unset($this->data['breadcrumb']);
        }

        return $this;
    }

    public function setMainEntity(?Schema $schema): static
    {
        if ($schema) {
            $this->data['mainEntity'] = ['@id' => $schema->getID()];
        } else {
            unset($this->data['mainEntity']);
        }

        return $this;
    }
}
