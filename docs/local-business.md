# LocalBusiness schema

Use `LocalBusinessSchema` to describe a business location. It is separate from the automatically generated site-wide `Organization` entity, and can reference that entity as its parent.

```php
use DorsetDigital\SchemaManager\Control\SchemaIDs;
use DorsetDigital\SchemaManager\Control\SchemaRegistry;
use DorsetDigital\SchemaManager\Model\Schema\LocalBusinessSchema;

$url = $page->AbsoluteLink();

SchemaRegistry::add(
    LocalBusinessSchema::create(
        'branch-main',
        'Example Branch'
    )
        ->setURL($url)
        ->setParentOrganization(SchemaIDs::organisation())
        ->setTelephone('+44 20 0000 0000')
        ->setAddress([
            'streetAddress' => '1 Example Street',
            'addressLocality' => 'London',
            'postalCode' => 'SW1A 1AA',
            'addressCountry' => 'GB',
        ])
        ->setGeo(51.5, -0.12)
        ->setPriceRange('££')
        ->setMainEntityOfPage($url)
);
```

`setAddress()` wraps the supplied properties in a `PostalAddress`; `setGeo()` creates `GeoCoordinates`. Additional methods include `setDescription()`, `setImage()`, `setEmail()` and `setOpeningHours(array $specifications)`. Opening-hour specifications are passed through as supplied, so construct valid Schema.org `OpeningHoursSpecification` entries in application code.

Choose a distinct `@id` for each branch. `setParentOrganization()` links to an existing organisation ID but does not register it.
