<?php

namespace DorsetDigital\SchemaManager\Tests\Middleware;

use DorsetDigital\SchemaManager\Control\SchemaRegistry;
use DorsetDigital\SchemaManager\Middleware\SchemaResponseMiddleware;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Dev\SapphireTest;

class SchemaResponseMiddlewareTest extends SapphireTest
{
    protected function tearDown(): void
    {
        SchemaRegistry::flush();
        parent::tearDown();
    }

    private function render(string $body, ?string $contentType = 'text/html', int $status = 200): HTTPResponse
    {
        $response = new HTTPResponse($body, $status);
        if ($contentType !== null) {
            $response->addHeader('Content-Type', $contentType);
        }

        return (new SchemaResponseMiddleware())->process(
            new HTTPRequest('GET', '/'),
            static function () use ($response) {
                return $response;
            }
        );
    }

    private function registerSchema(): void
    {
        SchemaRegistry::addEntity('https://example.com/#thing', [
            '@id' => 'https://example.com/#thing',
            '@type' => 'Thing',
        ]);
    }

    public function testHtmlIncludesSchemaAfterRendering(): void
    {
        $this->registerSchema();
        $response = $this->render('<html><head><title>Test</title></head><body>OK</body></html>', 'text/html; charset=utf-8');
        $this->assertSame(1, substr_count($response->getBody(), 'id="schema-manager-jsonld"'));
        $this->assertStringContainsString('"@type":"Thing"', $response->getBody());
        $this->assertStringContainsString('</script>' . "\n" . '</head>', $response->getBody());
    }

    public function testLateRegistrationIsIncluded(): void
    {
        $response = new HTTPResponse('<html><head></head><body></body></html>', 200);
        $response->addHeader('Content-Type', 'text/html');

        $result = (new SchemaResponseMiddleware())->process(
            new HTTPRequest('GET', '/'),
            static function () use ($response) {
                SchemaRegistry::addEntity('https://example.com/#late', [
                    '@id' => 'https://example.com/#late',
                    '@type' => 'Thing',
                ]);
                return $response;
            }
        );

        $this->assertStringContainsString('https://example.com/#late', $result->getBody());
    }

    public function testNonHtmlResponsesAreUntouched(): void
    {
        $this->registerSchema();
        foreach (['application/xml', 'text/xml', 'application/rss+xml', 'application/atom+xml', 'application/json', null] as $type) {
            $body = '<?xml version="1.0"?><root><head></head></root>';
            $response = $this->render($body, $type);
            $this->assertSame($body, $response->getBody(), (string) $type);
        }
    }

    public function testNonSuccessResponsesAreUntouched(): void
    {
        $this->registerSchema();
        $body = '<html><head></head><body></body></html>';
        $this->assertSame($body, $this->render($body, 'text/html', 404)->getBody());
        $this->assertSame($body, $this->render($body, 'text/html', 302)->getBody());
    }

    public function testNoHeadOrNoSchemaLeavesResponseUntouched(): void
    {
        $body = '<html><head></head><body></body></html>';
        $this->assertSame($body, $this->render($body)->getBody());

        $this->registerSchema();
        $noHead = '<html><body>Only body</body></html>';
        $this->assertSame($noHead, $this->render($noHead)->getBody());
    }

    public function testExistingScriptIsNotDuplicated(): void
    {
        $this->registerSchema();
        $body = '<html><head><script id="schema-manager-jsonld"></script></head><body></body></html>';
        $this->assertSame($body, $this->render($body)->getBody());
    }

    public function testContentLengthIsRemovedWhenBodyChanges(): void
    {
        $this->registerSchema();
        $response = new HTTPResponse('<html><head></head><body></body></html>', 200);
        $response->addHeader('Content-Type', 'text/html');
        $response->addHeader('Content-Length', '38');

        $result = (new SchemaResponseMiddleware())->process(
            new HTTPRequest('GET', '/'),
            static function () use ($response) {
                return $response;
            }
        );

        $this->assertEmpty($result->getHeader('Content-Length'));
    }
}
