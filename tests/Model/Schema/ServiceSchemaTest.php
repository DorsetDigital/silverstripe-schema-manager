<?php

namespace DorsetDigital\SchemaManager\Tests\Model\Schema;

use DorsetDigital\SchemaManager\Model\Schema\ServiceSchema;
use SilverStripe\Control\Director;
use SilverStripe\Dev\SapphireTest;

class ServiceSchemaTest extends SapphireTest
{
    public function testServiceSchemaBuildsCommonServiceData(): void
    {
        $schema = ServiceSchema::create(
            'https://example.com/services/example/',
            'Example service',
            'An example service.'
        )
            ->setAreaServed('United Kingdom')
            ->setOffer(95, 'GBP');

        $data = $schema->toArray();
        $baseURL = rtrim(Director::absoluteBaseURL(), '/') . '/';

        $this->assertSame('Service', $data['@type']);
        $this->assertSame('https://example.com/services/example/#service', $data['@id']);
        $this->assertSame('https://example.com/services/example/#webpage', $data['isPartOf']['@id']);
        $this->assertSame($baseURL . '#organisation', $data['provider']['@id']);
        $this->assertSame('United Kingdom', $data['areaServed']);
        $this->assertSame(95, $data['offers']['price']);
        $this->assertSame('GBP', $data['offers']['priceCurrency']);
        $this->assertSame('https://example.com/services/example/', $data['offers']['url']);
    }

    public function testServiceProviderCanBeOverridden(): void
    {
        $schema = ServiceSchema::create(
            'https://example.com/services/example/',
            'Example service'
        )->setProvider('https://provider.example/#organisation');

        $this->assertSame(
            'https://provider.example/#organisation',
            $schema->toArray()['provider']['@id']
        );
    }
}
