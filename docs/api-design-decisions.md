# Schema builder API design decisions (work in progress)

This document records agreed decisions for the upcoming breaking API revision. **Do not treat examples as implemented until the refactor is complete.**

## Global conventions

- A JSON-LD entity ID (`@id`) is distinct from a Silverstripe DataObject database ID and from an entity's optional public URL.
- Replace `Schema::getID()` with `Schema::getEntityID()` everywhere. **Remove `getID()` entirely**; no deprecated alias. Update all internal callers, tests and documentation.
- Relationship methods and parameters accept an entity ID **string**, whether from `$schema->getEntityID()` or `SchemaIDs::organisation()` and other global helpers. They generate `['@id' => $id]` internally. Do not require a Schema object.
- Schema IDs must be stable across pages, so reusable content elements referencing the same record generate the same entity ID.
- Do not automatically establish `mainEntityOfPage` or other page relationships. The existing multi-entity/page coexistence behaviour must be preserved.
- For site-scoped repeatable entities, use a required stable identifier and a type-specific fragment, e.g. `https://example.com/#person-team-member-42`. Validate identifiers rather than silently altering malformed values.
- `SchemaIDs` remains the source of global organisation/website/page/breadcrumb entity IDs. Its `ref()` helper is only relevant to raw schema arrays, not normal typed builder use.
- Review builders individually; don't impose page URLs on entities that aren't intrinsically page-scoped. Existing constructor changes are intentional breaking changes; don't add legacy compatibility methods.

## PersonSchema — agreed

- Proposed signature: `PersonSchema::create(string $identifier, string $name)`.
- Generates a site-scoped stable ID (`#person-{identifier}`), not one based on the currently displayed page.
- Example: `PersonSchema::create('team-member-' . $member->ID, $member->Name)`. Same member on two pages has identical `@id`.
- No automatic page relationships.
- `setURL()` is optional and **only** for a dedicated, consistent public URL genuinely associated with that person (e.g. profile page or personal site); never use the URL of a team listing or reusable element's containing page. Explain this in PHPDoc and docs.
- Keep existing fluent methods including `setWorksFor(SchemaIDs::organisation())`.

## LocalBusinessSchema — agreed for this stage

- Proposed signature: `LocalBusinessSchema::create(string $identifier, string $name)`.
- Generates a stable site-scoped `#localbusiness-{identifier}` ID.
- Supports multiple offices/branches listed in a reusable contact element. Each office has distinct stable identifier and can use existing address, geo, telephone, email and opening-hours setters.
- Explicitly link a branch to the main organisation via `setParentOrganization(SchemaIDs::organisation())`; do not set automatically.
- Add optional `setURL()` for a consistent, dedicated public office URL, not the containing page's URL.
- Adding `Organization.location` references back to offices is possible but **not required for this stage**. Do not add PlaceSchema now.

## ReviewSchema — decisions to date

- Proposed signature: `ReviewSchema::create(string $identifier, string $body, string $author, string $itemReviewedID)`.
- A reviewed-entity relationship is mandatory. `$itemReviewedID` is a **string JSON-LD entity ID**, e.g. `$productSchema->getEntityID()` or `SchemaIDs::organisation()`, **not** a numeric DataObject `getID()`.
- Generate a site-scoped stable review entity ID from the required identifier.
- Author remains a plain string by default, with optional author-entity reference via existing methods.
- Validate nonempty referenced entity ID. Keep rating as a separate nested value builder.

## QuotationSchema — agreed

- Proposed signature: `QuotationSchema::create(string $identifier, string $text)`.
- Generate a stable site-scoped `#quotation-{identifier}` ID; the identifier is mandatory and must be unique for each testimonial/quotation within the site. In applications, derive it from a stable source record identifier (e.g. `'testimonial-' . $testimonial->ID`), not a slideshow position or the current page.
- Typical use case: attributed testimonial quotes in reusable content elements or slideshows, potentially displayed on multiple pages. The same quotation must retain the same entity ID wherever it appears.
- Attribution is optional: retain `setAuthor(string $name)` for a plain name and `setAuthorReference(string $entityID)` for an existing entity. Retain `setCitation()` and `setIsPartOf()` as explicit setters.
- Do not automatically associate quotations with the page where they are displayed.

## ProductSchema — agreed for this release

- Keep the Product builder simple for this release; product variants are explicitly deferred.
- Proposed constructor: `ProductSchema::create(string $identifier, string $name, ?string $description = null)`, generating a stable site-scoped `#product-{identifier}` entity ID. The identifier is mandatory and independent of the page displaying the product.
- Add an optional `setURL()` for a stable, dedicated product page, distinct from its entity ID. Keep existing image, SKU, brand and offer setters.
- **Future compatibility requirement:** The API and generated ID must not assume that a product can only ever have one variant. Preserve stable product identity when variants are introduced; do not encode a particular SKU, price, or variant into the parent product ID by default. Variants can later have their own IDs and relationships to the product without requiring a breaking change to this constructor.
- Do not implement variants in this release; assess future variant modelling separately.

## Relationship audit — required safeguards before implementation

The relationship audit identified behaviour that must be preserved when changing constructor signatures. **Do not implement removal of relationship capabilities as an incidental side effect of new ID conventions.**

- `Schema::setMainEntityOfPage($pageURL)` and `setIsPartOfPage($pageURL)` explicitly relate entities to a WebPage; they remove a conflicting relation to the same page. Keep both and their conflict-handling logic.
- `SchemaRegistry::resolveRelationships()` selects the WebPage's main entity and demotes a previous competing main entity to `isPartOf`. Preserve this coexistence behaviour and cover it with tests.
- `ProductSchema` currently sets `isPartOf` to its constructor URL's WebPage and sets its own `url`. The product guide explicitly uses `setMainEntityOfPage()` for a dedicated product page. A new site-scoped constructor must retain the ability to explicitly set either relationship. Do **not** automatically set `mainEntityOfPage`.
- `ProductSchema::setOffer()` currently defaults an Offer URL to the Product's `url`. Preserve this when introducing optional `setURL()`, and test call-order behaviour.
- `ServiceSchema` currently defaults `provider` to the global organisation and `isPartOf` to the constructor URL's WebPage. Retain default provider unless separately agreed otherwise; preserve explicit page-relationship APIs. **Service constructor/automatic page-link changes are not yet agreed.**
- `JobPostingSchema` similarly defaults `hiringOrganization` and page `isPartOf`. `BlogPostingSchema` links to the WebSite and organisation and explicitly marks its post as page main entity. Review these separately before changing.
- `WebPageSchema` points to `WebSite`, and the registry resolves breadcrumb and main entity references. `FAQPageSchema` merges questions from multiple sections under one page-scoped ID. Preserve both.
- A reusable entity's stable `@id` and the WebPage it is displayed on are distinct concepts. Do not silently derive entity identity from the rendering page.
- The current registry keys entities by `@id` and merges duplicate IDs. It also stores typed schemas by ID, with later registrations replacing earlier typed instances (except FAQ special handling). Test duplicate/reused entities to ensure no unintended relationship loss or cross-page assumptions.

## Still to review

- QuotationSchema and remaining builders: constructor arguments, ID scoping, required fields and relationships.
- Audit and update docs/examples/tests and internal callers as implementation proceeds.
