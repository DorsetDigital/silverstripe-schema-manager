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

## Public builder methods

Methods declared by this builder, in addition to the [shared Schema methods](developer-api.md#shared-schema-builder-methods).

| Method and parameters | Purpose |
| --- | --- |
| `public static function create(string $identifier, string $name)` | Create a new schema entity. |
| `public function setURL(?string $url)` | Set the u r l property. |
| `public function setDescription(?string $description)` | Set the description property. |
| `public function setImage(?string $url)` | Set the image property. |
| `public function setTelephone(?string $telephone)` | Set the telephone property. |
| `public function setEmail(?string $email)` | Set the email property. |
| `public function setParentOrganization(string $id)` | Set the parent organization property. |
| `public function setAddress(array $address)` | Set the address property. |
| `public function setGeo(float $latitude, float $longitude)` | Set the geo property. |
| `public function setOpeningHours(array $specifications)` | Set the opening hours property. |
| `public function setPriceRange(?string $range)` | Set the price range property. |
