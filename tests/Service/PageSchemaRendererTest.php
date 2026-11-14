<?php

namespace DorsetDigital\SchemaManager\Tests\Service;

use DorsetDigital\SchemaManager\Control\SchemaRegistry;
use DorsetDigital\SchemaManager\Control\SchemaIDs;
use DorsetDigital\SchemaManager\Service\PageSchemaRenderer;
use DorsetDigital\SchemaManager\Middleware\SchemaResponseMiddleware;
use DorsetDigital\SchemaManager\Tests\Service\Fixture\PageSchemaContributionExtension;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequestBuilder;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\SapphireTest;

class PageSchemaRendererTest extends SapphireTest
{
    protected function setUp(): void
    {
        parent::setUp();
        SiteTree::add_extension(PageSchemaContributionExtension::class);
    }

    public function testExtensionContributionsAndFaqAggregation(): void
    {
        $page = SiteTree::create([
            'Title' => 'Integration fixture',
            'URLSegment' => 'schema-contribution-test',
        ]);
        $page->write();
        $page->publishSingle();

        $data = $this->decodePage($page);
        $byType = [];
        foreach ($data['@graph'] as $entity) {
            $byType[$entity['@type']][] = $entity;
        }

        $this->assertCount(1, $byType['Service']);
        $this->assertSame('Fixture consulting service', $byType['Service'][0]['name']);
        $this->assertCount(1, $byType['Thing']);
        $this->assertSame('Extension-added entity', $byType['Thing'][0]['name']);
        $this->assertCount(1, $byType['FAQPage']);
        $this->assertSame(
            ['First question?', 'Second question?'],
            array_column($byType['FAQPage'][0]['mainEntity'], 'name')
        );
        $this->assertSame(
            SchemaIDs::forPageURL($page->AbsoluteLink(), 'faq'),
            $byType['FAQPage'][0]['@id']
        );
        $this->assertSame(
            ['@id' => $byType['Service'][0]['@id']],
            $byType['WebPage'][0]['mainEntity']
        );

        // Repeated generation must not duplicate questions or leak entities.
        $again = $this->decodePage($page);
        $this->assertEqualsCanonicalizing($data['@graph'], $again['@graph']);

        $other = SiteTree::create([
            'Title' => 'Unrelated page',
            'URLSegment' => 'schema-unrelated-test',
        ]);
        $other->write();
        $other->publishSingle();
        $otherTypes = array_column($this->decodePage($other)['@graph'], '@type');
        $this->assertNotContains('FAQPage', $otherTypes);
        $this->assertNotContains('Service', $otherTypes);
        $this->assertNotContains('Thing', $otherTypes);
    }

    public function testPublishedGraphMatchesNormalHtmlResponse(): void
    {
        $page = SiteTree::create([
            'Title' => 'HTML parity fixture',
            'URLSegment' => 'schema-contribution-test',
            'SchemaAnnotationType' => 'service',
            'SchemaAnnotationValues' => json_encode(['serviceType' => 'Parity check']),
        ]);
        $page->write();
        $page->publishSingle();

        $parts = parse_url($page->AbsoluteLink());
        $request = HTTPRequestBuilder::createFromVariables([
            '_SERVER' => [
                'REQUEST_URI' => $parts['path'] ?? '/',
                'REQUEST_METHOD' => 'GET',
                'HTTP_HOST' => $parts['host'],
                'HTTPS' => ($parts['scheme'] ?? '') === 'https' ? 'on' : 'off',
                'QUERY_STRING' => '',
            ],
            '_GET' => [],
            '_POST' => [],
        ], '');

        // Exercise the actual HTML middleware with a normal Silverstripe page
        // response, rather than assembling a second graph in the test itself.
        $response = (new SchemaResponseMiddleware())->process(
            $request,
            static function () use ($request) {
                $response = Director::singleton()->handleRequest($request);
                $response->addHeader('Content-Type', 'text/html');
                return $response;
            }
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(1, substr_count($response->getBody(), 'id="schema-manager-jsonld"'));
        $this->assertSame(1, preg_match(
            '~<script\\b[^>]*id="schema-manager-jsonld"[^>]*>(.*?)</script>~s',
            $response->getBody(),
            $matches
        ));

        $htmlGraph = json_decode($matches[1], true, 512, JSON_THROW_ON_ERROR);
        $apiGraph = $this->decodePage($page);

        $this->assertSame($htmlGraph['@context'], $apiGraph['@context']);
        $this->assertEqualsCanonicalizing($htmlGraph['@graph'], $apiGraph['@graph']);
    }

    protected function tearDown(): void
    {
        SchemaRegistry::flush();
        parent::tearDown();
    }

    public function testPublishedPageIncludesAnnotationAndBreadcrumbs(): void
    {
        $parent = SiteTree::create([
            'Title' => 'Services',
            'URLSegment' => 'services-schema-test',
        ]);
        $parent->write();
        $parent->publishSingle();

        $page = SiteTree::create([
            'Title' => 'Consultancy',
            'URLSegment' => 'consultancy-schema-test',
            'ParentID' => $parent->ID,
            'SchemaAnnotationType' => 'service',
            'SchemaAnnotationValues' => json_encode([
                'serviceType' => 'Consulting',
            ]),
        ]);
        $page->write();
        $page->publishSingle();

        $data = $this->decodePage($page);
        $byType = [];
        foreach ($data['@graph'] as $entity) {
            $byType[$entity['@type']][] = $entity;
        }

        $this->assertCount(1, $byType['WebPage']);
        $this->assertCount(1, $byType['BreadcrumbList']);
        $this->assertCount(1, $byType['Service']);
        $this->assertSame('Consultancy', $byType['Service'][0]['name']);
        $this->assertSame('Consulting', $byType['Service'][0]['serviceType']);
        $this->assertSame(
            ['@id' => $byType['Service'][0]['@id']],
            $byType['WebPage'][0]['mainEntity']
        );
        $this->assertCount(2, $byType['BreadcrumbList'][0]['itemListElement']);
    }

    public function testConsecutivePagesHaveIndependentGraphs(): void
    {
        $pageA = SiteTree::create([
            'Title' => 'First page',
            'URLSegment' => 'schema-page-a',
            'SchemaAnnotationType' => 'service',
            'SchemaAnnotationValues' => '{}',
        ]);
        $pageA->write();
        $pageA->publishSingle();

        $pageB = SiteTree::create([
            'Title' => 'Second page',
            'URLSegment' => 'schema-page-b',
        ]);
        $pageB->write();
        $pageB->publishSingle();

        $renderer = new PageSchemaRenderer();
        $first = $this->decodePage($pageA, $renderer);
        $second = $this->decodePage($pageB, $renderer);

        $firstTypes = array_column($first['@graph'], '@type');
        $secondTypes = array_column($second['@graph'], '@type');
        $this->assertContains('Service', $firstTypes);
        $this->assertNotContains('Service', $secondTypes);

        $firstIDs = array_column($first['@graph'], '@id');
        $secondIDs = array_column($second['@graph'], '@id');
        $this->assertContains(
            SchemaIDs::webPage($pageA),
            $firstIDs
        );
        $this->assertNotContains(
            SchemaIDs::webPage($pageA),
            $secondIDs
        );
        $this->assertContains(
            SchemaIDs::webPage($pageB),
            $secondIDs
        );
    }

    public function testPublicSiteUrlOverridesPublishedGraphURLs(): void
    {
        $previous = getenv('PUBLIC_SITE_URL');
        $previousEnv = $_ENV['PUBLIC_SITE_URL'] ?? null;
        $previousServer = $_SERVER['PUBLIC_SITE_URL'] ?? null;
        putenv('PUBLIC_SITE_URL=https://public.example.test');
        $_ENV['PUBLIC_SITE_URL'] = 'https://public.example.test';
        $_SERVER['PUBLIC_SITE_URL'] = 'https://public.example.test';

        try {
            $page = SiteTree::create([
                'Title' => 'Public URL fixture',
                'URLSegment' => 'schema-public-url-test',
            ]);
            $page->write();
            $page->publishSingle();

            $graph = $this->decodePage($page)['@graph'];
            $types = [];
            foreach ($graph as $entity) {
                $types[$entity['@type']] = $entity;
            }

            $this->assertSame('https://public.example.test/#organisation', $types['Organization']['@id']);
            $this->assertSame('https://public.example.test/', $types['Organization']['url']);
            $this->assertSame('https://public.example.test/#website', $types['WebSite']['@id']);
            $this->assertSame('https://public.example.test/', $types['WebSite']['url']);
            $this->assertSame(
                'https://public.example.test/schema-public-url-test/#webpage',
                $types['WebPage']['@id']
            );
            $this->assertSame(
                'https://public.example.test/schema-public-url-test/',
                $types['WebPage']['url']
            );
            $this->assertSame(
                ['@id' => 'https://public.example.test/#website'],
                $types['WebPage']['isPartOf']
            );
            $this->assertSame(
                'https://public.example.test/schema-public-url-test/#breadcrumb',
                SchemaIDs::breadcrumb($page)
            );
            $this->assertSame(
                'https://external.example.test/external',
                SchemaIDs::publicURL('https://external.example.test/external')
            );
        } finally {
            if ($previous === false) {
                putenv('PUBLIC_SITE_URL');
            } else {
                putenv('PUBLIC_SITE_URL=' . $previous);
            }
            if ($previousEnv === null) {
                unset($_ENV['PUBLIC_SITE_URL']);
            } else {
                $_ENV['PUBLIC_SITE_URL'] = $previousEnv;
            }
            if ($previousServer === null) {
                unset($_SERVER['PUBLIC_SITE_URL']);
            } else {
                $_SERVER['PUBLIC_SITE_URL'] = $previousServer;
            }
        }
    }

    private function decodePage(SiteTree $page, ?PageSchemaRenderer $renderer = null): array
    {
        $json = ($renderer ?? new PageSchemaRenderer())->forPublishedPage($page);
        return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    }

    public function testUnpublishedPageCannotBeRendered(): void
    {
        $page = SiteTree::create([
            'Title' => 'Unpublished',
            'URLSegment' => 'unpublished-schema-test',
        ]);
        $page->write();

        $this->expectException(\InvalidArgumentException::class);
        (new PageSchemaRenderer())->forPublishedPage($page);
    }

    public function testPublishedPageCollectsAutomaticSchemaAndRestoresRegistry(): void
    {
        $page = SiteTree::create([
            'Title' => 'Published schema test',
            'URLSegment' => 'published-schema-test',
        ]);
        $page->write();
        $page->publishSingle();

        SchemaRegistry::addEntity('https://example.com/#outer', [
            '@id' => 'https://example.com/#outer',
            '@type' => 'Thing',
        ]);

        $json = (new PageSchemaRenderer())->forPublishedPage($page);
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('https://schema.org', $data['@context']);

        $types = array_column($data['@graph'], '@type');
        $this->assertContains('WebPage', $types);
        $this->assertContains('WebSite', $types);
        $this->assertContains('Organization', $types);

        $this->assertCount(1, SchemaRegistry::getGraph());
        $this->assertSame('https://example.com/#outer', SchemaRegistry::getGraph()[0]['@id']);
    }
}
