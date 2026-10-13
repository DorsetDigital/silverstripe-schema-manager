# Event schema

`EventSchema` describes scheduled events, their location, organisers, performers and ticket offers. It can be created directly in PHP or through the built-in CMS [Event page annotation](page-annotations.md).

```php
use DorsetDigital\SchemaManager\Control\SchemaIDs;
use DorsetDigital\SchemaManager\Control\SchemaRegistry;
use DorsetDigital\SchemaManager\Model\Schema\EventSchema;

$url = $event->AbsoluteLink();

SchemaRegistry::add(
    EventSchema::create($url, $event->Title, '2026-11-20T19:00:00+00:00', $event->MetaDescription)
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
