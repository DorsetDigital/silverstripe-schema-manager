<?php

namespace DorsetDigital\SchemaManager\Model\Schema;

use DorsetDigital\SchemaManager\Control\SchemaDate;

/**
 * A property advertisement is a WebPage, not the building being advertised.
 * Use fromWebPage() to preserve the automatic page's stable @id and metadata.
 */
class RealEstateListingSchema extends WebPageSchema
{
    public static function fromWebPage(WebPageSchema $page): static
    {
        $data = $page->toArray();
        $data['@type'] = 'RealEstateListing';
        return new static($data);
    }

    public function setDatePosted(?string $date): static
    {
        $date = SchemaDate::format($date);
        if ($date === null) {
            unset($this->data['datePosted']);
        } else {
            $this->data['datePosted'] = $date;
        }
        return $this;
    }

    public function setOffer(?string $offerID): static
    {
        return $this->setMainEntity($offerID);
    }
}
