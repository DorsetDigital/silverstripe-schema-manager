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

## Still to review

- QuotationSchema and remaining builders: constructor arguments, ID scoping, required fields and relationships.
- Audit and update docs/examples/tests and internal callers as implementation proceeds.
