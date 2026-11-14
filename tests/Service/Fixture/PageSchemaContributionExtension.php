<?php

namespace DorsetDigital\SchemaManager\Tests\Service\Fixture;

use DorsetDigital\SchemaManager\Control\FAQPageHelper;
use DorsetDigital\SchemaManager\Control\SchemaIDs;
use DorsetDigital\SchemaManager\Control\SchemaRegistry;
use DorsetDigital\SchemaManager\Model\Schema\ServiceSchema;
use SilverStripe\Core\Extension;

/**
 * Simulate an application integration that contributes schema during page init,
 * independently of any template execution.
 */
class PageSchemaContributionExtension extends Extension
{
    public function updateSchemaManagerEntities(array &$entities): void
    {
        $page = $this->owner;

        if ($page->URLSegment !== 'schema-contribution-test') {
            return;
        }

        $entities[] = ServiceSchema::create(
            'fixture-service-' . $page->ID,
            'Fixture consulting service'
        )->setMainEntityOfPage($page->AbsoluteLink());

        SchemaRegistry::addEntity(
            SchemaIDs::forPageURL($page->AbsoluteLink(), 'custom'),
            [
                '@type' => 'Thing',
                '@id' => SchemaIDs::forPageURL($page->AbsoluteLink(), 'custom'),
                'name' => 'Extension-added entity',
            ]
        );

        FAQPageHelper::addQuestion('First question?', 'First answer.', $page->AbsoluteLink());
        FAQPageHelper::addQuestion('Second question?', 'Second answer.', $page->AbsoluteLink());
    }
}
