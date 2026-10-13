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

    public function setBreadcrumb(?string $entityID): static
    {
        if ($entityID !== null) {
            $this->data['breadcrumb'] = ['@id' => $entityID];
        } else {
            unset($this->data['breadcrumb']);
        }

        return $this;
    }

    public function setMainEntity(?string $entityID): static
    {
        if ($entityID !== null) {
            $this->data['mainEntity'] = ['@id' => $entityID];
        } else {
            unset($this->data['mainEntity']);
        }

        return $this;
    }
}
