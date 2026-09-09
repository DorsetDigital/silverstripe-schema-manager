<?php

namespace DorsetDigital\SchemaManager\Model\Schema;

use SilverStripe\Control\Director;
use SilverStripe\SiteConfig\SiteConfig;

class OrganisationSchema extends Schema
{
    public static function fromSiteConfig(SiteConfig $config): static
    {
        $baseURL = rtrim(Director::absoluteBaseURL(), '/') . '/';
        $id = $baseURL . '#organisation';

        $data = [
            '@type' => 'Organization',
            '@id' => $id,
            'name' => $config->SchemaOrganisationName ?: $config->Title,
            'url' => $baseURL,
        ];

        if ($config->SchemaOrganisationLegalName) {
            $data['legalName'] = $config->SchemaOrganisationLegalName;
        }

        if ($config->SchemaOrganisationPhone) {
            $data['telephone'] = $config->SchemaOrganisationPhone;
        }

        if ($config->SchemaOrganisationEmail) {
            $data['email'] = $config->SchemaOrganisationEmail;
        }

        $logo = $config->SchemaOrganisationLogo();
        if ($logo && $logo->exists()) {
            $data['logo'] = $logo->getAbsoluteURL();
        }

        $address = array_filter([
            '@type' => 'PostalAddress',
            'streetAddress' => $config->SchemaOrganisationStreetAddress,
            'addressLocality' => $config->SchemaOrganisationLocality,
            'addressRegion' => $config->SchemaOrganisationRegion,
            'postalCode' => $config->SchemaOrganisationPostalCode,
            'addressCountry' => $config->SchemaOrganisationCountry,
        ]);

        if (count($address) > 1) {
            $data['address'] = $address;
        }

        $sameAs = preg_split('/\R+/', (string) $config->SchemaOrganisationSameAs, -1, PREG_SPLIT_NO_EMPTY);
        $sameAs = array_values(array_filter(array_map('trim', $sameAs ?: [])));
        if ($sameAs) {
            $data['sameAs'] = $sameAs;
        }

        return new static($data);
    }
}
