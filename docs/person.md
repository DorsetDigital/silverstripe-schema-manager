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

## Public builder methods

The following methods are declared by this builder (in addition to the [shared Schema methods](developer-api.md#shared-schema-builder-methods)).

| Method and parameters | Purpose |
| --- | --- |
| `public static function create(string $identifier, string $name)` | Create a new schema entity. |
| `public function setJobTitle(?string $title)` | Set the job title property. |
| `public function setDescription(?string $description)` | Set the description property. |
| `public function setImage(?string $url)` | Set the image property. |
| `public function setURL(?string $url)` | Set the u r l property. |
| `public function setWorksFor(string $id)` | Set the works for property. |
| `public function setSameAs(array $urls)` | Set the same as property. |
| `public function setEmail(?string $email)` | Set the email property. |
| `public function setTelephone(?string $telephone)` | Set the telephone property. |
| `public function setCredentials(array $credentials)` | Set the credentials property. |
