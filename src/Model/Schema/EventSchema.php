<?php

namespace DorsetDigital\SchemaManager\Model\Schema;

use DorsetDigital\SchemaManager\Control\SchemaIDs;

class EventSchema extends Schema
{
    public static function create(string $identifier, string $name, string $startDate, ?string $description = null): static
    {
        $data = ['@type' => 'Event', '@id' => SchemaIDs::forEntity('event', $identifier), 'name' => $name, 'startDate' => $startDate];
        if ($description) $data['description'] = $description;
        return new static($data);
    }

    public function setURL(?string $url): static { if ($url !== null) $this->data['url'] = $url; else unset($this->data['url']); return $this; }
    public function setEndDate(?string $date): static { if ($date) $this->data['endDate'] = $date; return $this; }
    public function setImage(?string $url): static { if ($url) $this->data['image'] = $url; return $this; }
    public function setOrganizer(?string $id): static { if ($id !== null) $this->data['organizer'] = ['@id' => $id]; else unset($this->data['organizer']); return $this; }
    public function setPerformer(?string $id): static { if ($id !== null) $this->data['performer'] = ['@id' => $id]; else unset($this->data['performer']); return $this; }
    public function setLocation(string $name, ?string $address = null): static
    {
        $location = ['@type' => 'Place', 'name' => $name];
        if ($address) $location['address'] = $address;
        $this->data['location'] = $location;
        return $this;
    }
    public function setLocationReference(?string $id): static { if ($id !== null) $this->data['location'] = ['@id' => $id]; else unset($this->data['location']); return $this; }
    public function setEventStatus(string $status): static { $this->data['eventStatus'] = $status; return $this; }
    public function setAttendanceMode(string $mode): static { $this->data['eventAttendanceMode'] = $mode; return $this; }
    public function setOffer(float|int|string $price, string $currency, ?string $url = null): static
    {
        $offer = ['@type' => 'Offer', 'price' => $price, 'priceCurrency' => $currency];
        if ($url) $offer['url'] = $url;
        $this->data['offers'] = $offer;
        return $this;
    }
}
