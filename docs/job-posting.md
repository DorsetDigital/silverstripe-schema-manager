# JobPosting schema

`JobPostingSchema` is a generic builder for recruitment projects.

```php
$jobSchema = JobPostingSchema::create(
    'job-' . $job->ID,
    $job->Title,
    $job->Description
)
    ->setURL($job->AbsoluteLink())
    ->setMainEntityOfPage($job->AbsoluteLink())
    ->setHiringOrganization(\DorsetDigital\SchemaManager\Control\SchemaIDs::organisation())
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

The entity does not link to a WebPage by default. The example promotes it to `mainEntityOfPage` because it represents a dedicated job page. The hiring organisation must be set explicitly.

For remote roles use `setRemote()`; an optional country adds `applicantLocationRequirements`. Fixed salaries use `setBaseSalary()`, while ranges use `setBaseSalaryRange()`. Both generate a `MonetaryAmount` with a `QuantitativeValue`.
