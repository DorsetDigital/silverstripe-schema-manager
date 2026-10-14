<?php

namespace DorsetDigital\SchemaManager\Tests\Annotation;

use DorsetDigital\SchemaManager\Annotation\GenericAnnotationHandler;
use DorsetDigital\SchemaManager\Annotation\SchemaAnnotationFactory;
use DorsetDigital\SchemaManager\Control\SchemaIDs;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\SapphireTest;

class GenericAnnotationHandlerTest extends SapphireTest
{
    public function testInlineOrganizer(): void
    {
        $page = new SiteTree(['Title' => 'Open Day']);
        $schema = (new GenericAnnotationHandler())->build($page, [
            'startDate' => '2026-11-14T10:00:00+00:00',
            'organizer' => ['mode' => 'inline', 'type' => 'Organization', 'name' => 'Village Hall Committee'],
        ], [
            'key' => 'event',
            'schema_type' => 'Event',
            'fields' => [
                'name' => ['source' => 'page.Title'],
                'startDate' => ['type' => 'datetime', 'required' => true],
                'organizer' => ['type' => 'entity', 'targets' => ['Organization', 'Person'], 'allow_inline' => true],
            ],
        ]);

        $this->assertSame('Open Day', $schema->toArray()['name']);
        $this->assertSame(['@type' => 'Organization', 'name' => 'Village Hall Committee'], $schema->toArray()['organizer']);
    }

    public function testServicePageAnnotationReferencesItsWebPage(): void
    {
        $page = new SiteTree(['Title' => 'Data Analytics']);
        $schema = (new SchemaAnnotationFactory())->build($page, 'service', []);

        $this->assertNotNull($schema);
        $this->assertSame(
            ['@id' => SchemaIDs::webPage($page)],
            $schema->toArray()['mainEntityOfPage']
        );
    }

    public function testEventPageAnnotationReferencesItsWebPage(): void
    {
        $page = new SiteTree(['Title' => 'Open Day']);
        $schema = (new SchemaAnnotationFactory())->build($page, 'event', [
            'startDate' => '2026-11-14T10:00:00+00:00',
        ]);

        $this->assertNotNull($schema);
        $this->assertSame(
            ['@id' => SchemaIDs::webPage($page)],
            $schema->toArray()['mainEntityOfPage']
        );
    }

    public function testIncompleteEventPageAnnotationIsSkipped(): void
    {
        $page = new SiteTree(['Title' => 'Open Day']);
        $this->assertNull((new SchemaAnnotationFactory())->build($page, 'event', []));
    }

    public function testMissingRequiredFieldSkipsSchema(): void
    {
        $page = new SiteTree(['Title' => 'Open Day']);
        $this->assertNull((new GenericAnnotationHandler())->build($page, [], [
            'key' => 'event',
            'schema_type' => 'Event',
            'fields' => ['startDate' => ['type' => 'datetime', 'required' => true]],
        ]));
    }

    public function testExistingReference(): void
    {
        $page = new SiteTree(['Title' => 'Service']);
        $schema = (new GenericAnnotationHandler())->build($page, [
            'provider' => ['mode' => 'existing', 'id' => 'https://example.org/#organisation'],
        ], [
            'key' => 'service',
            'schema_type' => 'Service',
            'fields' => ['provider' => ['type' => 'entity', 'allow_existing' => true]],
        ]);
        $this->assertSame(['@id' => 'https://example.org/#organisation'], $schema->toArray()['provider']);
    }
}
