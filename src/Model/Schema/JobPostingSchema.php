<?php

namespace DorsetDigital\SchemaManager\Model\Schema;

use SilverStripe\Control\Director;

class JobPostingSchema extends Schema
{
    public static function create(string $url, string $title, ?string $description = null): static
    {
        $url = rtrim($url, '/') . '/';
        $baseURL = rtrim(Director::absoluteBaseURL(), '/') . '/';

        $data = [
            '@type' => 'JobPosting',
            '@id' => $url . '#jobposting',
            'url' => $url,
            'title' => $title,
            'mainEntityOfPage' => [
                '@id' => $url . '#webpage',
            ],
            'hiringOrganization' => [
                '@id' => $baseURL . '#organisation',
            ],
        ];

        if ($description) {
            $data['description'] = $description;
        }

        return new static($data);
    }

    public function setHiringOrganization(string $id): static
    {
        $this->data['hiringOrganization'] = [
            '@id' => $id,
        ];

        return $this;
    }

    public function setDatePosted(?string $date): static
    {
        if ($date) {
            $this->data['datePosted'] = $date;
        }

        return $this;
    }

    public function setValidThrough(?string $date): static
    {
        if ($date) {
            $this->data['validThrough'] = $date;
        }

        return $this;
    }

    public function setEmploymentType(string|array|null $type): static
    {
        if ($type) {
            $this->data['employmentType'] = $type;
        }

        return $this;
    }

    public function setJobLocation(array|string|null $location): static
    {
        if ($location) {
            $this->data['jobLocation'] = is_array($location)
                ? $location
                : [
                    '@type' => 'Place',
                    'address' => $location,
                ];
        }

        return $this;
    }

    public function setRemote(bool $remote = true): static
    {
        if ($remote) {
            $this->data['jobLocationType'] = 'TELECOMMUTE';
        } else {
            unset($this->data['jobLocationType']);
        }

        return $this;
    }

    public function setBaseSalary(
        float|int|string $value,
        string $currency,
        ?string $unitText = null
    ): static {
        $salary = [
            '@type' => 'MonetaryAmount',
            'currency' => $currency,
            'value' => $value,
        ];

        if ($unitText) {
            $salary['value'] = [
                '@type' => 'QuantitativeValue',
                'value' => $value,
                'unitText' => $unitText,
            ];
        }

        $this->data['baseSalary'] = $salary;
        $this->data['salaryCurrency'] = $currency;

        return $this;
    }
}
