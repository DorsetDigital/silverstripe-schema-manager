<?php

namespace DorsetDigital\SchemaManager\Middleware;

use DorsetDigital\SchemaManager\Control\SchemaRegistry;
use DorsetDigital\SchemaManager\Model\Schema\Schema;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\Middleware\HTTPMiddleware;

/**
 * Insert the final schema graph after downstream controller/template rendering.
 * Never modify responses unless they explicitly identify as HTML.
 */
class SchemaResponseMiddleware implements HTTPMiddleware
{
    public function process(HTTPRequest $request, callable $delegate)
    {
        $response = $delegate($request);

        if (!$response instanceof HTTPResponse || $response->getStatusCode() !== 200) {
            return $response;
        }

        $contentType = $response->getHeader('Content-Type');
        if (!is_string($contentType) || strtolower(trim(explode(';', $contentType, 2)[0])) !== 'text/html') {
            return $response;
        }

        if ($response->getHeader('Content-Encoding')) {
            return $response;
        }

        $body = $response->getBody();
        // Even an incorrectly labelled XML document must never be injected into.
        if (is_string($body) && str_starts_with(ltrim($body), '<?xml')) {
            return $response;
        }

        if (!is_string($body) || $body === '' || stripos($body, '</head>') === false) {
            return $response;
        }

        // Do not duplicate the script if another integration already emitted it.
        if (preg_match('~<script\b[^>]*\bid\s*=\s*["\']schema-manager-jsonld["\']~i', $body)) {
            return $response;
        }

        // Late contributions can use the controller's final state (for example,
        // the current page of a filtered Silverstripe Blog listing).
        $page = Director::get_current_page();
        if ($page) {
            $entities = [];
            $page->extend('updateSchemaManagerResponseEntities', $entities, $request);
            foreach ($entities as $entity) {
                if ($entity instanceof Schema) {
                    SchemaRegistry::add($entity);
                }
            }
        }

        $json = SchemaRegistry::getJSON();
        if ($json === '') {
            return $response;
        }

        $script = '<script id="schema-manager-jsonld" type="application/ld+json">' . $json . '</script>';
        $updated = preg_replace('~</head>~i', $script . "\n</head>", $body, 1);
        if (!is_string($updated) || $updated === $body) {
            return $response;
        }

        $response->setBody($updated);
        // Body length changed; let the web server recalculate it.
        $response->removeHeader('Content-Length');

        return $response;
    }
}
