<?php

namespace DorsetDigital\SchemaManager\Tests;

use DorsetDigital\SchemaManager\Model\Schema\AggregateRatingSchema;
use DorsetDigital\SchemaManager\Model\Schema\EventSchema;
use DorsetDigital\SchemaManager\Model\Schema\LocalBusinessSchema;
use DorsetDigital\SchemaManager\Model\Schema\PersonSchema;
use DorsetDigital\SchemaManager\Model\Schema\QuotationSchema;
use DorsetDigital\SchemaManager\Model\Schema\RatingSchema;
use DorsetDigital\SchemaManager\Model\Schema\ReviewSchema;
use PHPUnit\Framework\TestCase;

class AdditionalSchemaBuildersTest extends TestCase
{
    public function testPersonAndBusinessRelationships(): void
    {
        $business = LocalBusinessSchema::create('https://example.com/#office', 'Office')
            ->setParentOrganization('https://example.com/#organisation')
            ->setAddress(['addressLocality' => 'London']);

        $person = PersonSchema::create('https://example.com/#jane', 'Jane')
            ->setWorksFor($business->getID());

        $this->assertSame(['@id' => $business->getID()], $person->toArray()['worksFor']);
        $this->assertSame('PostalAddress', $business->toArray()['address']['@type']);
    }

    public function testReviewWithNestedRating(): void
    {
        $review = ReviewSchema::create('https://example.com/#review-1', 'Great', 'Jane')
            ->setItemReviewed('https://example.com/#product')
            ->setRating(RatingSchema::create(4, 5, 1));

        $this->assertSame('Rating', $review->toArray()['reviewRating']['@type']);
        $this->assertSame(['@id' => 'https://example.com/#product'], $review->toArray()['itemReviewed']);
        $this->assertSame('', RatingSchema::create(5)->getID());
    }

    public function testOtherBuilderOutputs(): void
    {
        $event = EventSchema::create('https://example.com/events/open-day', 'Open Day', '2026-11-01')
            ->setOrganizer('https://example.com/#organisation');
        $this->assertSame('https://example.com/events/open-day/#event', $event->getID());

        $aggregate = AggregateRatingSchema::create(4.5, 20, 18);
        $this->assertSame(18, $aggregate->toArray()['reviewCount']);

        $quote = QuotationSchema::create('https://example.com/#quote', 'Hello')->setAuthor('Jane');
        $this->assertSame('Quotation', $quote->toArray()['@type']);
    }
}
