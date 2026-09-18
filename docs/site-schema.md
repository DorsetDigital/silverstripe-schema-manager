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
