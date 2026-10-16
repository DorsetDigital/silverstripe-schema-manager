<?php

namespace DorsetDigital\SchemaManager\Tests\Control;

use DateTimeImmutable;
use DateTimeZone;
use DorsetDigital\SchemaManager\Control\SchemaDate;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\ORM\FieldType\DBField;

class SchemaDateTest extends SapphireTest
{
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

    public function testSilverstripeDatetimeIsFormattedAsIso8601WithOffset(): void
    {
        // Summer time (CEST, +02:00) and winter time (CET, +01:00).
        $this->assertSame('2025-10-03T08:50:00+02:00', SchemaDate::format('2025-10-03 08:50:00'));
        $this->assertSame('2026-01-15T09:30:00+01:00', SchemaDate::format('2026-01-15 09:30:00'));
        $this->assertSame('2026-01-15T09:30:00+01:00', SchemaDate::format('2026-01-15 09:30'));
    }

    public function testDateOnlyAndIsoValuesAreKept(): void
    {
        $this->assertSame('2026-10-01', SchemaDate::format('2026-10-01'));
        $this->assertSame('2026-11-14T10:00:00+00:00', SchemaDate::format('2026-11-14T10:00:00+00:00'));
        $this->assertSame('2026-11-14T10:00:00Z', SchemaDate::format('2026-11-14T10:00:00Z'));
    }

    public function testEmptyValuesReturnNull(): void
    {
        $this->assertNull(SchemaDate::format(null));
        $this->assertNull(SchemaDate::format(''));
        $this->assertNull(SchemaDate::format('   '));
    }

    public function testDateTimeObjectsAndDbFieldsAreFormatted(): void
    {
        $date = new DateTimeImmutable('2025-10-03 08:50:00', new DateTimeZone('UTC'));
        $this->assertSame('2025-10-03T08:50:00+00:00', SchemaDate::format($date));

        $field = DBField::create_field(DBDatetime::class, '2025-10-03 08:50:00');
        $this->assertSame('2025-10-03T08:50:00+02:00', SchemaDate::format($field));
    }

    public function testUnrecognisedValuesArePassedThrough(): void
    {
        $this->assertSame('next Tuesday', SchemaDate::format('next Tuesday'));
    }
}
