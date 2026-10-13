# Person schema

Use `PersonSchema` for people such as staff members, authors or speakers. Each person needs a stable, unique `@id` so other entities can reference them.

```php
use DorsetDigital\SchemaManager\Control\SchemaIDs;
use DorsetDigital\SchemaManager\Control\SchemaRegistry;
use DorsetDigital\SchemaManager\Model\Schema\PersonSchema;

$personID = 'member-' . $profile->ID;

SchemaRegistry::add(
    PersonSchema::create($personID, $profile->Title)
        ->setJobTitle($profile->JobTitle)
        ->setDescription($profile->MetaDescription)
        ->setURL($profile->AbsoluteLink())
        ->setImage($profile->Photo?->getAbsoluteURL())
        ->setWorksFor(SchemaIDs::organisation())
        ->setSameAs(['https://example.com/team/profile'])
        ->setMainEntityOfPage($profile->AbsoluteLink())
);
```

Other methods include `setEmail()`, `setTelephone()` and `setCredentials(array $credentials)`. `setCredentials()` assigns the supplied `hasCredential` values; prepare their Schema.org structure in application code.

`setWorksFor($id)` creates an `@id` reference to an organisation; it does not register that organisation. For a profile page, `setMainEntityOfPage()` links the person to the automatic `WebPage`. See [Schema entities](schema-entities.md).
