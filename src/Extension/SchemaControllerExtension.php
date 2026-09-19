<?php

namespace DorsetDigital\SchemaManager\Extension;

use DorsetDigital\SchemaManager\Control\SchemaRegistry;
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
            $siteEntities[] = OrganisationSchema::fromSiteConfig($siteConfig);
        }

        if ($managerConfig->get('automatic_website_schema')) {
            $siteEntities[] = WebsiteSchema::fromSiteConfig($siteConfig);
        }

        $siteConfig->extend('updateSchemaManagerEntities', $siteEntities);
        $this->registerEntities($siteEntities);

        $pageEntities = [];

        if ($managerConfig->get('automatic_webpage_schema')) {
            $pageEntities[] = WebPageSchema::fromPage($page);
        }

        $page->extend('updateSchemaManagerEntities', $pageEntities);

        if ($managerConfig->get('automatic_breadcrumb_schema')) {
            $breadcrumb = BreadcrumbListSchema::fromPage($page);

            if ($breadcrumb) {
                $pageEntities[] = $breadcrumb;
            }
        }

        $this->registerEntities($pageEntities);
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
