<?php

namespace DorsetDigital\SchemaManager\Tests;

use DorsetDigital\SchemaManager\Control\SchemaRegistry;
use DorsetDigital\SchemaManager\Model\Schema\ProductSchema;
use DorsetDigital\SchemaManager\Model\Schema\WebPageSchema;
use SilverStripe\Dev\SapphireTest;

class SchemaRegistryRelationshipsTest extends SapphireTest
{
    protected function tearDown(): void
    {
        SchemaRegistry::flush();
        parent::tearDown();
    }

    public function testCompetingMainEntitiesAreStableAcrossGraphReads(): void
    {
        $url = 'https://example.com/products/widget/';
        $page = WebPageSchema::create('widget', 'Widget')->setURL($url);
        $first = ProductSchema::create('widget-a', 'A')->setMainEntityOfPage($url);
        $second = ProductSchema::create('widget-b', 'B')->setMainEntityOfPage($url);

        SchemaRegistry::add($page);
        SchemaRegistry::add($first);
        SchemaRegistry::add($second);

        $graph = $this->indexGraph(SchemaRegistry::getGraph());
        $this->assertSame($second->getEntityID(), $graph[$page->getEntityID()]['mainEntity']['@id']);
        $this->assertSame($page->getEntityID(), $graph[$first->getEntityID()]['isPartOf']['@id']);
        $this->assertArrayNotHasKey('mainEntityOfPage', $graph[$first->getEntityID()]);
        $this->assertSame($page->getEntityID(), $graph[$second->getEntityID()]['mainEntityOfPage']['@id']);
        $this->assertSame($graph, $this->indexGraph(SchemaRegistry::getGraph()));
    }

    public function testExplicitMainEntityWinsDuplicateRegistrationInEitherOrder(): void
    {
        foreach ([true, false] as $mainFirst) {
            SchemaRegistry::flush();
            $url = 'https://example.com/products/widget/';
            $page = WebPageSchema::create('widget', 'Widget')->setURL($url);
            $main = ProductSchema::create('widget', 'Widget')->setMainEntityOfPage($url);
            $part = ProductSchema::create('widget', 'Widget')->setIsPartOfPage($url);

            SchemaRegistry::add($page);
            SchemaRegistry::add($mainFirst ? $main : $part);
            SchemaRegistry::add($mainFirst ? $part : $main);

            $graph = $this->indexGraph(SchemaRegistry::getGraph());
            $product = $graph[$main->getEntityID()];
            $this->assertSame($page->getEntityID(), $product['mainEntityOfPage']['@id']);
            $this->assertArrayNotHasKey('isPartOf', $product);
        }
    }

    private function indexGraph(array $graph): array
    {
        $indexed = [];
        foreach ($graph as $entity) {
            $indexed[$entity['@id']] = $entity;
        }
        return $indexed;
    }
}
