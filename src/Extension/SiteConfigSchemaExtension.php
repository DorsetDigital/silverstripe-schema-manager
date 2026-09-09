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
            HeaderField::create('SchemaOrganisationHeading', 'Organisation'),
            TextField::create('SchemaOrganisationName', 'Organisation name')
                ->setDescription('Defaults to the site title when left blank.'),
            TextField::create('SchemaOrganisationLegalName', 'Legal name'),
            TextField::create('SchemaOrganisationPhone', 'Telephone'),
            EmailField::create('SchemaOrganisationEmail', 'Email'),
            UploadField::create('SchemaOrganisationLogo', 'Logo')
                ->setFolderName('schema'),
            HeaderField::create('SchemaOrganisationAddressHeading', 'Address'),
            TextField::create('SchemaOrganisationStreetAddress', 'Street address'),
            TextField::create('SchemaOrganisationLocality', 'Town / locality'),
            TextField::create('SchemaOrganisationRegion', 'County / region'),
            TextField::create('SchemaOrganisationPostalCode', 'Postcode'),
            TextField::create('SchemaOrganisationCountry', 'Country'),
            TextareaField::create('SchemaOrganisationSameAs', 'Profile URLs')
                ->setDescription('One external profile URL per line, for example LinkedIn, Facebook or Instagram.'),
        ]);
    }
}
