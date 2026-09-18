# JobPosting schema

`JobPostingSchema` is a generic builder for recruitment projects.

```php
$jobSchema = JobPostingSchema::create(
    $job->AbsoluteLink(),
    $job->Title,
    $job->Description
)
    ->setMainEntityOfPage($job->AbsoluteLink())
    ->setDatePosted($job->PublishDate)
    ->setValidThrough($job->ClosingDate)
    ->setEmploymentType('FULL_TIME')
    ->setJobLocation(
        locality: 'Bournemouth',
        region: 'Dorset',
        country: 'GB'
    )
    ->setBaseSalaryRange(40000, 50000, currency: 'GBP', unit: 'YEAR');

SchemaRegistry::add($jobSchema);
```

The entity links to the automatic `WebPage` with `isPartOf` by default. The example promotes it to `mainEntityOfPage` because it represents a dedicated job page. The site's `Organization` is used as `hiringOrganization` by default.

For remote roles use `setRemote()`; an optional country adds `applicantLocationRequirements`. Fixed salaries use `setBaseSalary()`, while ranges use `setBaseSalaryRange()`. Both generate a `MonetaryAmount` with a `QuantitativeValue`.
