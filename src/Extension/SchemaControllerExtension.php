<?php

namespace DorsetDigital\SchemaManager\Extension;

use DorsetDigital\SchemaManager\Control\SchemaRegistry;
use DorsetDigital\SchemaManager\Control\SchemaIDs;
use SilverStripe\Control\Director;
use DorsetDigital\SchemaManager\Model\Schema\BreadcrumbListSchema;
use DorsetDigital\SchemaManager\Model\Schema\OrganisationSchema;
use DorsetDigital\SchemaManager\Model\Schema\Schema;
use DorsetDigital\SchemaManager\Model\Schema\WebPageSchema;
use DorsetDigital\SchemaManager\Model\Schema\WebsiteSchema;
use DorsetDigital\SchemaManager\Service\SchemaManager;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\Extension;
use SilverStripe\SiteConfig\SiteConfig;

class SchemaControllerExtension extends Extension
{
    public function onBeforeInit(): void
    {
        SchemaRegistry::flush();
    }

    public function onAfterInit(): void
    {
        $page = $this->owner->data();

        if (!$page instanceof SiteTree || !$page->exists()) {
            return;
        }

        $siteConfig = SiteConfig::current_site_config();
        $managerConfig = SchemaManager::config();

        $siteEntities = [];

        if ($managerConfig->get('automatic_organisation_schema')) {
            $siteEntities[] = $this->buildOrganisation($siteConfig);
        }

        if ($managerConfig->get('automatic_website_schema')) {
            $siteEntities[] = $this->buildWebsite($siteConfig);
        }

        $siteConfig->extend('updateSchemaManagerEntities', $siteEntities);
        $this->registerEntities($siteEntities);

        $pageEntities = [];

        if ($managerConfig->get('automatic_webpage_schema')) {
            $pageEntities[] = $this->buildWebPage($page);
        }

        $page->extend('updateSchemaManagerEntities', $pageEntities);

        if ($managerConfig->get('automatic_breadcrumb_schema')) {
            $breadcrumb = $this->buildBreadcrumb($page);

            if ($breadcrumb) {
                $pageEntities[] = $breadcrumb;
            }
        }

        $this->registerEntities($pageEntities);
    }

    private function buildOrganisation(SiteConfig $config): OrganisationSchema
    {
        $schema = OrganisationSchema::create('site', $config->SchemaOrganisationName ?: $config->Title)
            ->setURL(rtrim(Director::absoluteBaseURL(), '/') . '/');

        // Automatic site entities retain their established public IDs.
        $schema->update(['@id' => SchemaIDs::organisation()]);

        if ($config->SchemaOrganisationLegalName) $schema->setLegalName($config->SchemaOrganisationLegalName);
        if ($config->SchemaOrganisationPhone) $schema->setTelephone($config->SchemaOrganisationPhone);
        if ($config->SchemaOrganisationEmail) $schema->setEmail($config->SchemaOrganisationEmail);

        $logo = $config->SchemaOrganisationLogo();
        if ($logo && $logo->exists()) $schema->setLogo($logo->getAbsoluteURL());

        $address = array_filter([
            'streetAddress' => $config->SchemaOrganisationStreetAddress,
            'addressLocality' => $config->SchemaOrganisationLocality,
            'addressRegion' => $config->SchemaOrganisationRegion,
            'postalCode' => $config->SchemaOrganisationPostalCode,
            'addressCountry' => $config->SchemaOrganisationCountry,
        ]);
        if ($address) $schema->setAddress($address);

        $sameAs = preg_split('/\\R+/', (string) $config->SchemaOrganisationSameAs, -1, PREG_SPLIT_NO_EMPTY);
        $sameAs = array_values(array_filter(array_map('trim', $sameAs ?: [])));
        if ($sameAs) $schema->setSameAs($sameAs);

        return $schema;
    }

    private function buildWebsite(SiteConfig $config): WebsiteSchema
    {
        $schema = WebsiteSchema::create('site', $config->Title)
            ->setURL(rtrim(Director::absoluteBaseURL(), '/') . '/');
        $schema->update(['@id' => SchemaIDs::website()]);

        if (SchemaManager::config()->get('automatic_organisation_schema')) {
            $schema->setPublisher(SchemaIDs::organisation());
        }

        return $schema;
    }

    private function buildWebPage(SiteTree $page): WebPageSchema
    {
        $schema = WebPageSchema::create((string) $page->ID, $page->Title)
            ->setURL(rtrim($page->AbsoluteLink(), '/') . '/')
            ->setIsPartOf(SchemaIDs::website());

        $schema->update(['@id' => SchemaIDs::webPage($page)]);
        if ($page->MetaDescription) $schema->setDescription($page->MetaDescription);
        if ($page->Created) $schema->setDatePublished($page->Created);
        if ($page->LastEdited) $schema->setDateModified($page->LastEdited);

        return $schema;
    }

    private function buildBreadcrumb(SiteTree $page): ?BreadcrumbListSchema
    {
        $items = $page->getBreadcrumbItems();
        if (!$items || $items->count() < 2) return null;

        $schema = BreadcrumbListSchema::create((string) $page->ID);
        foreach ($items as $item) {
            if ($item instanceof SiteTree) {
                $schema->addItem($item->MenuTitle ?: $item->Title, $item->AbsoluteLink());
            }
        }

        if (count($schema->toArray()['itemListElement']) < 2) return null;
        $schema->update(['@id' => SchemaIDs::breadcrumb($page)]);
        return $schema;
    }

    private function registerEntities(array $entities): void
    {
        foreach ($entities as $entity) {
            if ($entity instanceof Schema) {
                SchemaRegistry::add($entity);
            }
        }
    }
}
