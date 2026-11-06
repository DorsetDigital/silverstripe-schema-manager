<?php

namespace DorsetDigital\SchemaManager\Tests\Model\Schema;

use DorsetDigital\SchemaManager\Control\SchemaIDs;
use DorsetDigital\SchemaManager\Control\SchemaRegistry;
use DorsetDigital\SchemaManager\Model\Schema\ItemListSchema;
use DorsetDigital\SchemaManager\Model\Schema\PropertyCollectionSchema;
use DorsetDigital\SchemaManager\Model\Schema\PropertySchema;
use DorsetDigital\SchemaManager\Model\Schema\RealEstateListingSchema;
use DorsetDigital\SchemaManager\Model\Schema\RealEstateOfferSchema;
use DorsetDigital\SchemaManager\Model\Schema\WebPageSchema;
use SilverStripe\Dev\SapphireTest;

class RealEstateSchemaTest extends SapphireTest
{
    protected function tearDown(): void
    {
        SchemaRegistry::flush();
        parent::tearDown();
    }

    public function testForSalePropertyGraph(): void
    {
        $url = 'https://example.com/properties/rose-cottage/';
        $page = WebPageSchema::create('123', 'Rose Cottage')->setURL($url);
        $page->update(['@id' => SchemaIDs::forPageURL($url, 'webpage')]);

        $property = PropertySchema::create('rose-cottage', 'Rose Cottage', 'House')
            ->setAddress(['addressLocality' => 'Oxford', 'addressCountry' => 'GB'])
            ->setBedrooms(3)->setBathrooms(2)->setFloorSize(125)
            ->setGeo(51.752, -1.2577)->setImages(['https://example.com/rose.jpg'])
            ->addAmenity('Garden')->addAdditionalProperty('EPC', 'C');
        $offer = RealEstateOfferSchema::create('rose-cottage', $property->getEntityID())
            ->setURL($url)->setPrice(625000)->setBusinessFunction('Sell')
            ->setSeller(SchemaIDs::organisation());
        $listing = RealEstateListingSchema::fromWebPage($page)
            ->setDatePosted('2026-10-15')->setOffer($offer->getEntityID());

        SchemaRegistry::add($listing);
        SchemaRegistry::add($property);
        SchemaRegistry::add($offer);

        $graph = [];
        foreach (SchemaRegistry::getGraph() as $entity) {
            $graph[$entity['@id']] = $entity;
        }

        $this->assertCount(3, $graph);
        $this->assertSame('RealEstateListing', $graph[$listing->getEntityID()]['@type']);
        $this->assertSame($offer->getEntityID(), $graph[$listing->getEntityID()]['mainEntity']['@id']);
        $this->assertSame('House', $graph[$property->getEntityID()]['@type']);
        $this->assertSame(3, $graph[$property->getEntityID()]['numberOfBedrooms']);
        $this->assertSame('MTK', $graph[$property->getEntityID()]['floorSize']['unitCode']);
        $this->assertSame($property->getEntityID(), $graph[$offer->getEntityID()]['itemOffered']['@id']);
        $this->assertSame(625000, $graph[$offer->getEntityID()]['price']);
    }

    public function testCollectionReusesExistingPageAndItemList(): void
    {
        $url = 'https://example.com/properties/';
        $page = WebPageSchema::create('10', 'Properties')->setURL($url);
        $page->update(['@id' => SchemaIDs::forPageURL($url, 'webpage')]);
        $list = ItemListSchema::create('properties-page-1', 'Properties for sale')
            ->addItem('https://example.com/properties/rose-cottage/', 'Rose Cottage');
        $collection = PropertyCollectionSchema::fromWebPage($page, $list->getEntityID());

        SchemaRegistry::add($collection);
        SchemaRegistry::add($list);

        $graph = SchemaRegistry::getGraph();
        $this->assertCount(2, $graph);
        $this->assertSame('CollectionPage', $graph[0]['@type']);
        $this->assertSame($page->getEntityID(), $graph[0]['@id']);
        $this->assertSame($list->getEntityID(), $graph[0]['mainEntity']['@id']);
        $this->assertSame(1, $graph[1]['numberOfItems']);
    }

    public function testRentalOfferAndOptionalFields(): void
    {
        $property = PropertySchema::create('flat', 'A flat', 'Apartment')
            ->setBedrooms(0)->setGeo(0.0, 0.0)->setFloorSize(null);
        $offer = RealEstateOfferSchema::create('flat', $property->getEntityID())
            ->setRentalPrice(1450);

        $this->assertSame(0, $property->toArray()['numberOfBedrooms']);
        $this->assertSame(0.0, $property->toArray()['geo']['latitude']);
        $this->assertArrayNotHasKey('floorSize', $property->toArray());
        $this->assertSame('http://purl.org/goodrelations/v1#LeaseOut', $offer->toArray()['businessFunction']);
        $this->assertSame('MON', $offer->toArray()['priceSpecification']['unitCode']);
        $this->assertArrayNotHasKey('price', $offer->toArray());
    }

    public function testUnsupportedTypesAreRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        PropertySchema::create('foo', 'Foo', 'Product');
    }
}
