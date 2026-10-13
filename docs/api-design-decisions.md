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

## Developer-controlled page relationships — agreed

- Entity builders should have clear, consistent, documented methods for explicitly declaring whether an entity is the main entity of a page (`setMainEntityOfPage($pageURL)`) or is part of a page (`setIsPartOfPage($pageURL)`).
- The developer is responsible for deciding which relationship applies in each rendering context. Constructors must not silently declare an entity to be the page's main entity.
- Stable entity `@id`, canonical `url`, and the URL of a page displaying the entity are separate concepts; `setURL()` must not implicitly create a page relationship.
- Keep the existing registry's main-entity conflict resolution and make duplicate registrations of the same entity ID deterministic, without silently losing valid relationships.
- Document practical examples for product listings, dedicated product pages, reusable elements, and duplicate registration, and add regression tests before implementation is considered complete.
- **Agreed duplicate-merge precedence:** when duplicate registrations of the same entity ID refer to the same page, an explicit `mainEntityOfPage` declaration takes precedence over `isPartOf`, regardless of registration order. Treat this as an edge case, but make it deterministic and cover both registration orders with regression tests. Other merge precedence details remain to be worked out during implementation.

## ServiceSchema — agreed

- `ServiceSchema::create(string $identifier, string $name, ?string $description = null)` creates a site-scoped, page-independent `#service-{identifier}` entity ID.
- Optional `setURL()` supplies a canonical service URL without implicitly creating a page relationship. Keep `setAreaServed()` and `setOffer()`, including Offer URL fallback when the Service URL is set.
- Preserve explicit `setMainEntityOfPage()` and `setIsPartOfPage()` relationship handling; no automatic page relationship from the constructor.
- **Conditional default provider:** the constructor sets `provider` to `SchemaIDs::organisation()` only when `SchemaManager::config()->get('automatic_organisation_schema')` is enabled. If automatic organisation schema is disabled, omit `provider` by default rather than emit a potentially dangling reference.
- `setProvider(?string $id)` overrides the default with an explicit entity ID; passing `null` removes the provider relationship. This allows a Service without a provider even when automatic organisation schema is enabled.
- `setProvider()` accepts an explicit entity ID even when automatic organisation schema is disabled (e.g. a custom organisation or LocalBusiness). Do not require registry membership for a reference.
- The constructor checks the existing SchemaManager configuration; do not introduce another configuration setting for this behaviour.

## EventSchema — agreed

- `EventSchema::create(string $identifier, string $name, string $startDate, ?string $description = null)` generates a stable, site-scoped `#event-{identifier}` entity ID, independent of the rendering page.
- Add optional `setURL()` for the event's canonical public URL; it does not automatically establish page relationships.
- Preserve explicit `setMainEntityOfPage($pageURL)` and `setIsPartOfPage($pageURL)`. No automatic WebPage or organisation relationships.
- Keep existing `setEndDate()`, `setImage()`, `setEventStatus()`, `setAttendanceMode()`, `setOffer()`, and `setLocation(string $name, ?string $address = null)` functionality.
- Allow nullable reference setters `setOrganizer(?string $id)`, `setPerformer(?string $id)`, and `setLocationReference(?string $id)`; passing `null` removes the corresponding relationship.
- Do not automatically set the event organiser to the site's global organisation; the site owner is not necessarily the organiser.

## JobPostingSchema — agreed

- `JobPostingSchema::create(string $identifier, string $title, ?string $description = null)` generates a stable, site-scoped `#jobposting-{identifier}` entity ID, independent of the page where the vacancy is shown.
- Add optional `setURL()` for the vacancy's canonical public URL. Setting it does **not** create or change a WebPage relationship.
- **Intentional change from the old constructor:** the builder no longer automatically assigns `isPartOf` from a constructor URL. Developers explicitly call `setIsPartOfPage($currentPageURL)` for a supporting listing/promo or `setMainEntityOfPage($currentPageURL)` for the vacancy's dedicated page. Keep the base methods and registry resolution, not the old automatic assignment.
- Default `hiringOrganization` to `SchemaIDs::organisation()` **only** when `SchemaManager::config()->get('automatic_organisation_schema')` is enabled. Otherwise omit it.
- Change `setHiringOrganization(?string $id)` to accept an explicit entity ID or `null` to remove the relationship, regardless of automatic organisation setting.
- Preserve existing date, employment type, location, remote-work and salary methods.
- Documentation must be **simple and explicit**, with practical examples of a vacancy on its dedicated page, in a general jobs listing, and in reusable homepage/promotional content. Clearly distinguish stable entity ID, canonical URL, and relationship to the current page. Apply the same clarity to Product and other reusable builders; avoid describing the removed automatic `isPartOf` assignment as 'preserved'.

## BlogPostingSchema and existing Blog extension — agreed

- Standardise the generic builder as `BlogPostingSchema::create(string $identifier, string $headline, ?string $description = null)`, with a stable site-scoped `#blogposting-{identifier}` entity ID.
- The generic builder is independent of `silverstripe/blog` and exposes fluent setters for canonical URL, publication/modified dates, image, author and publisher. Page relationships remain explicit.
- Remove the current `BlogPostingSchema::fromBlogPost(DataObject $post)` factory in this intentional breaking revision; keep Silverstripe-specific extraction out of the builder.
- **Reuse the existing `BlogPostSchemaExtension`**, which is explicitly applied to the Silverstripe BlogPost class by the consuming project. Do not add another integration/registration mechanism or automatically apply the extension.
- Update its existing `updateSchemaManagerEntities(array &$entities)` hook to map BlogPost data into the generic builder and append the resulting entity. Use a stable BlogPost record ID; set headline, description, canonical URL, dates and featured image where available, and explicitly establish the dedicated BlogPost page's main-entity relationship.
- The current BlogPost integration's publisher reference to the global organisation should be applied conditionally when automatic organisation schema is enabled, avoiding dangling default references. The generic builder does not assume a publisher.
- **Author handling belongs in the existing BlogPost extension**, not in the generic builder. The builder must support multiple authors, including references to `PersonSchema` entities and simple named authors; it must not depend on Silverstripe ORM.
- For Silverstripe Blog integration, use `BlogPost::getCredits()`, which combines linked `Authors()` members and comma-separated `AuthorNames` credits. Map linked members to stable `PersonSchema` entities and register them; represent name-only credits without inventing member IDs.
- **Respect disabled author profiles.** Upstream Blog 5 `getDynamicCredits()` still creates a `URL` via `Blog::ProfileLink()` even when `BlogController.disable_profiles` is true; the config is enforced by the profile controller instead. Check `$post->getProfilesDisabled()` in our extension and do not set Person canonical URLs when true. Also omit URLs when absent or invalid. Test both config states, multiple linked members and name-only authors.
- Keep documentation short and practical: demonstrate the generic builder separately from the opt-in BlogPost extension, and explain that applying the extension enables the integration across BlogPost pages. Test the mapping and relationships.

## ImageGallerySchema — agreed

- `ImageGallerySchema::create(string $identifier, ?string $name = null, ?string $description = null)` creates a stable site-scoped `#imagegallery-{identifier}` entity ID, independent of the page where the gallery appears.
- Add optional `setURL()` for a dedicated canonical gallery URL. It does not establish a page relationship.
- **Intentional constructor change:** no automatic `isPartOf` or other WebPage relationship. Developers explicitly call `setIsPartOfPage($pageURL)` for embedded/reused galleries or `setMainEntityOfPage($pageURL)` for dedicated gallery pages.
- Keep `addImage(string $url, ?string $name = null, ?string $description = null, ?int $width = null, ?int $height = null)` and its nested `ImageObject` values under `hasPart`; do not require registering each image as a separate entity.
- Keep `setThumbnail(?string $url)`, but make passing `null` remove an existing `thumbnailUrl` property, consistent with other nullable setters.
- Documentation should show a practical gallery in a reusable element, with a stable element-derived ID and explicit page relationship.

## Still to review

- QuotationSchema and remaining builders: constructor arguments, ID scoping, required fields and relationships.
- Audit and update docs/examples/tests and internal callers as implementation proceeds.
