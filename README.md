# Silverstripe Schema Manager

Structured data for **Silverstripe CMS 5 and 6**, combining automatic site and page metadata with optional, content-specific Schema.org entities.

Schema Manager produces a linked JSON-LD `@graph` and inserts it into the page's `<head>` automatically. It provides a consistent way to describe your organisation, website and content to search engines, without requiring changes to your templates.

## Installation

```bash
composer require dorsetdigital/silverstripe-schema-manager
vendor/bin/sake dev/build flush=1
```

Requires PHP 8.1+ for CMS 5 or PHP 8.3+ for CMS 6.

## What happens automatically?

On standard Silverstripe pages, Schema Manager provides:

- **Organization** — details of your organisation, managed through SiteConfig.
- **WebSite** — the website and its relationship to the organisation.
- **WebPage** — page URL, title, description and available publication metadata.
- **BreadcrumbList** — the page's position in the site hierarchy.

These entities are linked together using stable identifiers and rendered as a single JSON-LD graph. **No template changes or additional PHP are required.**

Automatic organisation, website, webpage and breadcrumb output can each be enabled or disabled independently. See [Site schema](docs/site-schema.md) and [Breadcrumbs](docs/breadcrumbs.md).

## Supported schema types

Beyond the automatically generated entities, the module provides builders and integrations for:

| Schema.org type | Documentation |
| --- | --- |
| Organization and WebSite | [Site schema](docs/site-schema.md) |
| WebPage | [WebPage builder](docs/web-page.md) |
| BreadcrumbList | [Breadcrumbs](docs/breadcrumbs.md) |
| FAQPage | [FAQs](docs/faq.md) |
| ImageGallery | [Image galleries](docs/image-gallery.md) |
| VideoObject | [Videos (YouTube and Vimeo)](docs/video-object.md) |
| Product | [Products](docs/product.md) |
| Service | [Services](docs/service.md) |
| JobPosting | [Job postings](docs/job-posting.md) |
| BlogPosting | [Blog integration](docs/blog-posting.md) |
| Event | [Events](docs/event.md) |
| Person | [People](docs/person.md) |
| LocalBusiness | [Local businesses](docs/local-business.md) |
| Review and Rating | [Reviews and ratings](docs/review.md) |
| AggregateRating | [Aggregate ratings](docs/aggregate-rating.md) |
| Quotation | [Quotations](docs/quotation.md) |

## Additional functionality

**CMS page annotations:** Editors can add structured data to individual pages through a Schema tab. Built-in annotations cover Event and Service; developers can configure additional definitions. [Read the page annotation guide](docs/page-annotations.md).

**FAQ aggregation:** Questions from multiple sections can be combined into a single FAQPage entity for the page. [Read the FAQ guide](docs/faq.md).

**Optional Blog integration:** Projects using `silverstripe/blog` can opt in to automatic BlogPosting output. [Read the Blog guide](docs/blog-posting.md).

**Developer integrations:** Register custom entities, link them to pages or existing site entities, and extend the generated graph through hooks. [Working with schema entities](docs/schema-entities.md) · [Developer API reference](docs/developer-api.md).

## License

BSD 3-Clause License.
