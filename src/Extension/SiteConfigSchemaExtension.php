<?php

namespace DorsetDigital\SchemaManager\Extension;

use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Assets\Image;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\EmailField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\TextField;
use function _t;

class SiteConfigSchemaExtension extends Extension
{
    private static array $db = [
        'SchemaOrganisationName' => 'Varchar(255)',
        'SchemaOrganisationLegalName' => 'Varchar(255)',
        'SchemaOrganisationPhone' => 'Varchar(100)',
        'SchemaOrganisationEmail' => 'Varchar(255)',
        'SchemaOrganisationStreetAddress' => 'Varchar(255)',
        'SchemaOrganisationLocality' => 'Varchar(255)',
        'SchemaOrganisationRegion' => 'Varchar(255)',
        'SchemaOrganisationPostalCode' => 'Varchar(50)',
        'SchemaOrganisationCountry' => 'Varchar(100)',
        'SchemaOrganisationSameAs' => 'Text',
    ];

    private static array $has_one = [
        'SchemaOrganisationLogo' => Image::class,
    ];

    private static array $owns = [
        'SchemaOrganisationLogo',
    ];

    public function updateCMSFields(FieldList $fields): void
    {
        $fields->addFieldsToTab('Root.Schema', [
            HeaderField::create('SchemaOrganisationHeading', _t('SchemaManager.ORGANISATION', 'Organisation')),
            TextField::create('SchemaOrganisationName', _t('SchemaManager.ORGANISATION_NAME', 'Organisation name'))
                ->setDescription(_t('SchemaManager.ORGANISATION_NAME_HELP', 'Defaults to the site title when left blank.')),
            TextField::create('SchemaOrganisationLegalName', _t('SchemaManager.LEGAL_NAME', 'Legal name')),
            TextField::create('SchemaOrganisationPhone', _t('SchemaManager.TELEPHONE', 'Telephone')),
            EmailField::create('SchemaOrganisationEmail', _t('SchemaManager.EMAIL', 'Email')),
            UploadField::create('SchemaOrganisationLogo', _t('SchemaManager.LOGO', 'Logo'))
                ->setFolderName('schema'),
            HeaderField::create('SchemaOrganisationAddressHeading', _t('SchemaManager.ADDRESS', 'Address')),
            TextField::create('SchemaOrganisationStreetAddress', _t('SchemaManager.STREET_ADDRESS', 'Street address')),
            TextField::create('SchemaOrganisationLocality', _t('SchemaManager.LOCALITY', 'Town / locality')),
            TextField::create('SchemaOrganisationRegion', _t('SchemaManager.REGION', 'County / region')),
            TextField::create('SchemaOrganisationPostalCode', _t('SchemaManager.POSTCODE', 'Postcode')),
            TextField::create('SchemaOrganisationCountry', _t('SchemaManager.COUNTRY', 'Country'))
                ->setDescription(_t('SchemaManager.COUNTRY_HELP', 'Use the two-letter country code (ISO 3166-1 alpha-2), e.g. GB for the United Kingdom. Full country names are also accepted, but the two-letter code is recommended.')),
            TextareaField::create('SchemaOrganisationSameAs', _t('SchemaManager.PROFILE_URLS', 'Profile URLs'))
                ->setDescription(_t('SchemaManager.PROFILE_URLS_HELP', 'One external profile URL per line, for example LinkedIn, Facebook or Instagram.')),
        ]);
    }
}
