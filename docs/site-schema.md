# Site schema

Schema Manager automatically creates site-wide `Organization` and `WebSite` entities.

After installation and `dev/build`, organisation details are managed under **Settings → Schema** in the CMS. Available fields include organisation name, legal name, telephone, email, logo, postal address and external profile URLs (`sameAs`).

The site title is used as a fallback organisation name. `WebSite` data is derived from SiteConfig and the canonical base URL.

Automatic site schema can be controlled independently:

```yaml
DorsetDigital\SchemaManager\Service\SchemaManager:
  automatic_organisation_schema: true
  automatic_website_schema: true
```

## Public builder methods

The following are the methods declared by these builders; see also [shared Schema methods](developer-api.md#shared-schema-builder-methods).

### `OrganisationSchema`

| Method and parameters | Purpose |
| --- | --- |
| `public static function create(string $identifier, string $name)` | Create the schema object. |
| `public function setURL(?string $url)` | Set the u r l property. |
| `public function setLegalName(?string $name)` | Set the legal name property. |
| `public function setTelephone(?string $phone)` | Set the telephone property. |
| `public function setEmail(?string $email)` | Set the email property. |
| `public function setLogo(?string $url)` | Set the logo property. |
| `public function setAddress(?array $address)` | Set the address property. |
| `public function setSameAs(array $urls)` | Set the same as property. |

### `WebsiteSchema`

| Method and parameters | Purpose |
| --- | --- |
| `public static function create(string $identifier, string $name)` | Create the schema object. |
| `public function setURL(?string $url)` | Set the u r l property. |
| `public function setPublisher(?string $id)` | Set the publisher property. |
