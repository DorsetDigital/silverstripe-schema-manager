<?php

namespace DorsetDigital\SchemaManager\Control;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Exception;
use SilverStripe\ORM\FieldType\DBDate;

/**
 * Formats date and date/time values for Schema.org output.
 *
 * Schema.org expects ISO 8601. Silverstripe stores datetimes as "Y-m-d H:i:s" in the site's timezone, so those are
 * converted to ISO 8601 with an explicit offset (e.g. 2025-10-03T08:50:00+02:00). Date-only values ("Y-m-d") are
 * valid Schema.org Dates and are kept as they are, as are values that are already ISO 8601.
 */
class SchemaDate
{
    /**
     * @param DateTimeInterface|DBDate|string|null $value
     * @return string|null ISO 8601 date or date/time, or null for an empty value
     */
    public static function format(mixed $value): ?string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format(DateTimeInterface::ATOM);
        }

        if ($value instanceof DBDate) {
            $value = $value->getValue();
        }

        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        // Date only, or already ISO 8601 with a "T" separator: valid as it is.
        if (preg_match('/^\d{4}-\d{2}-\d{2}(T.*)?$/', $value)) {
            return $value;
        }

        // Silverstripe datetime ("Y-m-d H:i" or "Y-m-d H:i:s"), interpreted in the site's default timezone.
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}(:\d{2})?$/', $value)) {
            try {
                $date = new DateTimeImmutable($value, new DateTimeZone(date_default_timezone_get()));

                return $date->format(DateTimeInterface::ATOM);
            } catch (Exception $e) {
                return $value;
            }
        }

        // Anything else is passed through unchanged rather than guessed at.
        return $value;
    }
}
