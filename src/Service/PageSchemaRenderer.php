<?php

namespace DorsetDigital\SchemaManager\Service;

use DorsetDigital\SchemaManager\Control\SchemaRegistry;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\HTTPApplication;
use SilverStripe\Control\HTTPRequestBuilder;
use SilverStripe\Core\CoreKernel;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\Versioned\Versioned;

/**
 * Experimental page-graph collection through the normal Silverstripe request
 * lifecycle. Only published pages are supported by this initial implementation.
 *
 * @internal Until draft-context and nested request behaviour are verified.
 */
final class PageSchemaRenderer
{
    public function forPublishedPage(SiteTree $page): string
    {
        if (!$page->exists()) {
            throw new \InvalidArgumentException('A saved SiteTree page is required.');
        }

        // Resolve the published record, never an unpublished URL supplied by
        // a caller operating in Draft context.
        $published = Versioned::get_by_stage(SiteTree::class, Versioned::LIVE, $page->ID);
        if (!$published || !$published->exists()) {
            throw new \InvalidArgumentException('The page is not published.');
        }

        $url = $published->AbsoluteLink();
        $parts = parse_url($url);

        if (!is_array($parts) || empty($parts['host'])) {
            throw new \RuntimeException('Unable to generate an absolute page URL.');
        }

        $query = [];
        if (isset($parts['query'])) {
            parse_str($parts['query'], $query);
        }

        return SchemaRegistry::withIsolatedRegistry(function () use ($parts, $query): string {
            $previousMode = Versioned::get_reading_mode();

            try {
                Versioned::set_reading_mode('Stage.Live');

                $now = DBDatetime::now()->getTimestamp();
                $request = HTTPRequestBuilder::createFromVariables([
                    '_SERVER' => [
                        'REQUEST_URI' => ($parts['path'] ?? '/') . (isset($parts['query']) ? '?' . $parts['query'] : ''),
                        'REQUEST_METHOD' => 'GET',
                        'REMOTE_ADDR' => '127.0.0.1',
                        'HTTPS' => ($parts['scheme'] ?? '') === 'https' ? 'on' : 'off',
                        'QUERY_STRING' => $parts['query'] ?? '',
                        'REQUEST_TIME' => $now,
                        'REQUEST_TIME_FLOAT' => (float) $now,
                        'HTTP_HOST' => $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : ''),
                        'HTTP_USER_AGENT' => 'silverstripe-schema-manager',
                    ],
                    '_GET' => $query,
                    '_POST' => [],
                ], '');

                $response = (new HTTPApplication(new CoreKernel(BASE_PATH)))->handle($request);

                if ($response->getStatusCode() !== 200) {
                    throw new \RuntimeException(sprintf(
                        'Page schema request returned HTTP %d.',
                        $response->getStatusCode()
                    ));
                }

                // Rendering has completed: controller hooks and explicit
                // registrations have populated the same registry as HTML output.
                return SchemaRegistry::getJSON();
            } finally {
                Versioned::set_reading_mode($previousMode);
            }
        });
    }
}
