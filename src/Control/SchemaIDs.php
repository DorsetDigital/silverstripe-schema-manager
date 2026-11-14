<?php

namespace DorsetDigital\SchemaManager\Control;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Director;
use SilverStripe\Core\Environment;

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
            if (!preg_match('/\A[a-zA-Z0-9][a-zA-Z0-9_-]*\z/D', $value)) {
                throw new \InvalidArgumentException(
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
        return rtrim(self::publicURL($url), '/') . '/#' . ltrim($fragment, '#');
    }

    public static function ref(string $id): array
    {
        return ['@id' => $id];
    }

    /**
     * Convert a URL belonging to this Silverstripe site to the public website
     * origin, when PUBLIC_SITE_URL is configured. Third-party URLs are retained.
     */
    public static function publicURL(string $url): string
    {
        $override = Environment::getEnv('PUBLIC_SITE_URL');
        if (!is_string($override) || trim($override) === '') {
            return $url;
        }

        $public = self::validatedPublicBaseURL($override);
        $internal = parse_url(Director::absoluteBaseURL());
        $source = parse_url($url);

        if (!is_array($internal) || !is_array($source)) {
            return $url;
        }

        // Absolute external URLs must never be rewritten.
        if (isset($source['host']) && (
            strcasecmp($source['host'], $internal['host'] ?? '') !== 0
            || ($source['port'] ?? null) !== ($internal['port'] ?? null)
        )) {
            return $url;
        }

        // Keep the path, query and fragment, replacing only the site origin.
        $path = $source['path'] ?? '';
        $suffix = (isset($source['query']) ? '?' . $source['query'] : '')
            . (isset($source['fragment']) ? '#' . $source['fragment'] : '');

        return $public . '/' . ltrim($path, '/') . $suffix;
    }

    private static function baseURL(): string
    {
        $override = Environment::getEnv('PUBLIC_SITE_URL');
        if (is_string($override) && trim($override) !== '') {
            return self::validatedPublicBaseURL($override) . '/';
        }

        return rtrim(Director::absoluteBaseURL(), '/') . '/';
    }

    private static function validatedPublicBaseURL(string $url): string
    {
        $url = trim($url);
        $parts = parse_url($url);
        if (!is_array($parts)
            || !in_array($parts['scheme'] ?? null, ['http', 'https'], true)
            || empty($parts['host'])
            || isset($parts['user']) || isset($parts['pass'])
            || !empty($parts['query'])
            || !empty($parts['fragment'])
            || (!empty($parts['path']) && $parts['path'] !== '/')
        ) {
            throw new \InvalidArgumentException('PUBLIC_SITE_URL must be an absolute HTTP(S) site origin.');
        }

        return rtrim($url, '/');
    }
}
