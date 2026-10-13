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

## Public builder methods

Methods declared by this builder, in addition to the [shared Schema methods](developer-api.md#shared-schema-builder-methods).

| Method and parameters | Purpose |
| --- | --- |
| `public static function create(string $identifier, string $title, ?string $description = null)` | Create a new schema entity. |
| `public function setURL(?string $url)` | Set the u r l property. |
| `public function setHiringOrganization(?string $id)` | Set the hiring organization property. |
| `public function setDatePosted(?string $date)` | Set the date posted property. |
| `public function setValidThrough(?string $date)` | Set the valid through property. |
| `public function setEmploymentType(string\|array\|null $type)` | Set the employment type property. |
| `public function setJobLocation( ?string $locality = null, ?string $region = null, ?string $country = null, ?string $streetAddress = null, ?string $postalCode = null )` | Set the job location property. |
| `public function setRemote(?string $country = null)` | Set the remote property. |
| `public function setBaseSalary( float\|int\|string $value, string $currency, string $unit = 'YEAR' )` | Set the base salary property. |
| `public function setBaseSalaryRange( float\|int\|string $minValue, float\|int\|string $maxValue, string $currency, string $unit = 'YEAR' )` | Set the base salary range property. |
