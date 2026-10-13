<?php

namespace DorsetDigital\SchemaManager\Tests;

use DorsetDigital\SchemaManager\Model\Schema\AggregateRatingSchema;
use DorsetDigital\SchemaManager\Model\Schema\EventSchema;
use DorsetDigital\SchemaManager\Model\Schema\LocalBusinessSchema;
use DorsetDigital\SchemaManager\Model\Schema\PersonSchema;
use DorsetDigital\SchemaManager\Model\Schema\QuotationSchema;
use DorsetDigital\SchemaManager\Model\Schema\RatingSchema;
use DorsetDigital\SchemaManager\Model\Schema\ReviewSchema;
use SilverStripe\Dev\SapphireTest;

class AdditionalSchemaBuildersTest extends SapphireTest
{
    public function testPersonAndBusinessRelationships(): void
    {
        $business = LocalBusinessSchema::create('office', 'Office')
            ->setParentOrganization('https://example.com/#organisation')
            ->setAddress(['addressLocality' => 'London']);

        $person = PersonSchema::create('jane', 'Jane')
            ->setWorksFor($business->getEntityID());

        $this->assertSame(['@id' => $business->getEntityID()], $person->toArray()['worksFor']);
        $this->assertSame('PostalAddress', $business->toArray()['address']['@type']);
    }

    public function testReviewWithNestedRating(): void
    {
        $review = ReviewSchema::create('review-1', 'Great', 'Jane', 'https://example.com/#product')
            ->setRating(RatingSchema::create(4, 5, 1));

        $this->assertSame('Rating', $review->toArray()['reviewRating']['@type']);
        $this->assertSame(['@id' => 'https://example.com/#product'], $review->toArray()['itemReviewed']);
        $this->assertSame('', RatingSchema::create(5)->getEntityID());
    }

    public function testOtherBuilderOutputs(): void
    {
        $event = EventSchema::create('open-day', 'Open Day', '2026-11-01')
            ->setOrganizer('https://example.com/#organisation');
        $this->assertStringEndsWith('#event-open-day', $event->getEntityID());

        $aggregate = AggregateRatingSchema::create(4.5, 20, 18);
        $this->assertSame(18, $aggregate->toArray()['reviewCount']);

        $quote = QuotationSchema::create('quote', 'Hello')->setAuthor('Jane');
        $this->assertSame('Quotation', $quote->toArray()['@type']);
    }
}
