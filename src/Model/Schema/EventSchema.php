<?php

namespace DorsetDigital\SchemaManager\Model\Schema;

class EventSchema extends Schema
{
    public static function create(string $url, string $name, string $startDate, ?string $description = null): static
    {
        $url = rtrim($url, '/') . '/';
        $data = ['@type' => 'Event', '@id' => $url . '#event', 'url' => $url, 'name' => $name, 'startDate' => $startDate];
        if ($description) $data['description'] = $description;
        return new static($data);
    }

    public function setEndDate(?string $date): static { if ($date) $this->data['endDate'] = $date; return $this; }
    public function setImage(?string $url): static { if ($url) $this->data['image'] = $url; return $this; }
    public function setOrganizer(string $id): static { $this->data['organizer'] = ['@id' => $id]; return $this; }
    public function setPerformer(string $id): static { $this->data['performer'] = ['@id' => $id]; return $this; }
    public function setLocation(string $name, ?string $address = null): static
    {
        $location = ['@type' => 'Place', 'name' => $name];
        if ($address) $location['address'] = $address;
        $this->data['location'] = $location;
        return $this;
    }
    public function setLocationReference(string $id): static { $this->data['location'] = ['@id' => $id]; return $this; }
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
