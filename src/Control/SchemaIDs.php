<?php

namespace DorsetDigital\SchemaManager\Control;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Director;

/**
 * Stable identifiers shared by the schema builders and application integrations.
 * Resolving an ID does not register an entity.
 */
final class SchemaIDs
{
    /**
     * Build a stable, site-scoped ID for a repeatable schema entity.
     *
     * Identifiers are deliberately not slugified: invalid input is rejected so
     * that different source identifiers cannot silently collapse to one ID.
     */
    public static function forEntity(string $type, string $identifier): string
    {
        foreach (['type' => $type, 'identifier' => $identifier] as $label => $value) {
            if (!preg_match('/\\A[a-zA-Z0-9][a-zA-Z0-9_-]*\\z/D', $value)) {
                throw new \\InvalidArgumentException(
                    sprintf('Schema %s must contain only letters, numbers, hyphens or underscores and start with a letter or number.', $label)
                );
            }
        }

        return self::baseURL() . '#' . $type . '-' . $identifier;
    }

    public static function organisation(): string
    {
        return self::baseURL() . '#organisation';
    }

    public static function website(): string
    {
        return self::baseURL() . '#website';
    }

    public static function webPage(?SiteTree $page = null): string
    {
        $page ??= Director::get_current_page();

        if (!$page instanceof SiteTree) {
            throw new \InvalidArgumentException('A SiteTree page is required when there is no current page.');
        }

        return self::forPageURL($page->AbsoluteLink(), 'webpage');
    }

    public static function breadcrumb(?SiteTree $page = null): string
    {
        $page ??= Director::get_current_page();

        if (!$page instanceof SiteTree) {
            throw new \InvalidArgumentException('A SiteTree page is required when there is no current page.');
        }

        return self::forPageURL($page->AbsoluteLink(), 'breadcrumb');
    }

    public static function forPageURL(string $url, string $fragment): string
    {
        return rtrim($url, '/') . '/#' . ltrim($fragment, '#');
    }

    public static function ref(string $id): array
    {
        return ['@id' => $id];
    }

    private static function baseURL(): string
    {
        return rtrim(Director::absoluteBaseURL(), '/') . '/';
    }
}
