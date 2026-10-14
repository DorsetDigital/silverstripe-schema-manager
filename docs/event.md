# Event schema

`EventSchema` describes scheduled events, their location, organisers, performers and ticket offers. It can be created directly in PHP or through the built-in CMS [Event page annotation](page-annotations.md).

```php
use DorsetDigital\SchemaManager\Control\SchemaIDs;
use DorsetDigital\SchemaManager\Control\SchemaRegistry;
use DorsetDigital\SchemaManager\Model\Schema\EventSchema;

$url = $event->AbsoluteLink();

SchemaRegistry::add(
    EventSchema::create('event-' . $event->ID, $event->Title, '2026-11-20T19:00:00+00:00', $event->MetaDescription)
        ->setURL($url)
        ->setEndDate('2026-11-20T22:00:00+00:00')
        ->setLocation('Example Hall', '1 Example Street, London')
        ->setOrganizer(SchemaIDs::organisation())
        ->setEventStatus('https://schema.org/EventScheduled')
        ->setAttendanceMode('https://schema.org/OfflineEventAttendanceMode')
        ->setOffer(15, 'GBP', $url)
        ->setMainEntityOfPage($url)
);
```

`setLocationReference($id)` references an existing Place entity instead of embedding a Place with `setLocation($name, $address)`. `setPerformer($id)` and `setOrganizer($id)` also create `@id` references. `setImage($url)` adds an event image.

Supply valid date/time strings and Schema.org URLs for event status and attendance mode. The builder does not validate these values. The CMS annotation uses a configurable subset of the Event fields; use the PHP builder for richer event models.

## Public builder methods

The following methods are declared by this builder (in addition to the [shared Schema methods](developer-api.md#shared-schema-builder-methods)).

| Method and parameters | Purpose |
| --- | --- |
| `public static function create(string $identifier, string $name, string $startDate, ?string $description = null)` | Create a new schema entity. |
| `public function setURL(?string $url)` | Set the u r l property. |
| `public function setEndDate(?string $date)` | Set the end date property. |
| `public function setImage(?string $url)` | Set the image property. |
| `public function setOrganizer(?string $id)` | Set the organizer property. |
| `public function setPerformer(?string $id)` | Set the performer property. |
| `public function setLocation(string $name, ?string $address = null)` | Set the location property. |
| `public function setLocationReference(?string $id)` | Set the location reference property. |
| `public function setEventStatus(string $status)` | Set the event status property. |
| `public function setAttendanceMode(string $mode)` | Set the attendance mode property. |
| `public function setOffer(float\|int\|string $price, string $currency, ?string $url = null)` | Set the offer property. |
