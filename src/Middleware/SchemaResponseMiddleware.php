<?php

namespace DorsetDigital\SchemaManager\Middleware;

use DorsetDigital\SchemaManager\Control\SchemaRegistry;
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
        if (is_string($body) && preg_match('/^\s*<\?xml\b/i', $body)) {
            return $response;
        }

        if (!is_string($body) || $body === '' || stripos($body, '</head>') === false) {
            return $response;
        }

        // Do not duplicate the script if another integration already emitted it.
        if (preg_match('~<script\b[^>]*\bid\s*=\s*["\']schema-manager-jsonld["\']~i', $body)) {
            return $response;
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
