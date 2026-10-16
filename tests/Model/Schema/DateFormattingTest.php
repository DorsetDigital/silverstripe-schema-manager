<?php

namespace DorsetDigital\SchemaManager\Tests\Model\Schema;

use DorsetDigital\SchemaManager\Model\Schema\BlogPostingSchema;
use DorsetDigital\SchemaManager\Model\Schema\EventSchema;
use DorsetDigital\SchemaManager\Model\Schema\JobPostingSchema;
use DorsetDigital\SchemaManager\Model\Schema\ReviewSchema;
use DorsetDigital\SchemaManager\Model\Schema\VideoObjectSchema;
use DorsetDigital\SchemaManager\Model\Schema\WebPageSchema;
use SilverStripe\Dev\SapphireTest;

/**
 * Regression tests: date properties are output as ISO 8601, also when given Silverstripe's "Y-m-d H:i:s" format.
 */
class DateFormattingTest extends SapphireTest
{
    private const RAW = '2025-10-03 08:50:00';
    private const ISO = '2025-10-03T08:50:00+02:00';

    private string $timezone;

    protected function setUp(): void
    {
        parent::setUp();
        $this->timezone = date_default_timezone_get();
        date_default_timezone_set('Europe/Copenhagen');
    }

    protected function tearDown(): void
    {
        date_default_timezone_set($this->timezone);
        parent::tearDown();
    }

    public function testWebPageDates(): void
    {
        $data = WebPageSchema::create('1', 'Page')
            ->setDatePublished(self::RAW)
            ->setDateModified('2026-08-03 09:57:23')
            ->toArray();

        $this->assertSame(self::ISO, $data['datePublished']);
        $this->assertSame('2026-08-03T09:57:23+02:00', $data['dateModified']);
    }

    public function testBlogPostingDates(): void
    {
        $data = BlogPostingSchema::create('post', 'Post')
            ->setDatePublished(self::RAW)
            ->setDateModified(self::RAW)
            ->toArray();

        $this->assertSame(self::ISO, $data['datePublished']);
        $this->assertSame(self::ISO, $data['dateModified']);
    }

    public function testEventDates(): void
    {
        $data = EventSchema::create('event', 'Event', self::RAW)
            ->setEndDate('2025-10-03 10:00:00')
            ->toArray();

        $this->assertSame(self::ISO, $data['startDate']);
        $this->assertSame('2025-10-03T10:00:00+02:00', $data['endDate']);
    }

    public function testReviewJobPostingAndVideoDates(): void
    {
        $review = ReviewSchema::create('review', 'Great', 'Jane', 'https://example.com/#product')
            ->setDatePublished(self::RAW)
            ->toArray();
        $this->assertSame(self::ISO, $review['datePublished']);

        $job = JobPostingSchema::create('job', 'Electrician')
            ->setDatePosted(self::RAW)
            ->setValidThrough('2025-11-01 00:00:00')
            ->toArray();
        $this->assertSame(self::ISO, $job['datePosted']);
        $this->assertSame('2025-11-01T00:00:00+01:00', $job['validThrough']);

        $video = VideoObjectSchema::create('video', 'Video')
            ->setUploadDate(self::RAW)
            ->toArray();
        $this->assertSame(self::ISO, $video['uploadDate']);
    }

    public function testEmptyDatesAreNotOutput(): void
    {
        $data = WebPageSchema::create('1', 'Page')
            ->setDatePublished(null)
            ->setDateModified('')
            ->toArray();

        $this->assertArrayNotHasKey('datePublished', $data);
        $this->assertArrayNotHasKey('dateModified', $data);
    }
}
