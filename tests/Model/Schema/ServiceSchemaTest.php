<?php

namespace DorsetDigital\SchemaManager\Tests\Model\Schema;

use DorsetDigital\SchemaManager\Model\Schema\ServiceSchema;
use SilverStripe\Dev\SapphireTest;

class ServiceSchemaTest extends SapphireTest
{
    public function testServiceSchemaBuildsCommonServiceData(): void
    {
        $schema = ServiceSchema::create(
            'example',
            'Example service',
            'An example service.'
        )
            ->setURL('https://example.com/services/example/')
            ->setAreaServed('United Kingdom')
            ->setOffer(95, 'GBP');

        $data = $schema->toArray();

        $this->assertSame('Service', $data['@type']);
        $this->assertStringEndsWith('#service-example', $data['@id']);
        $this->assertArrayNotHasKey('isPartOf', $data);
        $this->assertArrayNotHasKey('provider', $data);
        $this->assertSame('United Kingdom', $data['areaServed']);
        $this->assertSame(95, $data['offers']['price']);
        $this->assertSame('GBP', $data['offers']['priceCurrency']);
        $this->assertSame('https://example.com/services/example/', $data['offers']['url']);
    }

    public function testServiceProviderCanBeOverridden(): void
    {
        $schema = ServiceSchema::create(
            'example',
            'Example service'
        )->setProvider('https://provider.example/#organisation');

        $this->assertSame(
            'https://provider.example/#organisation',
            $schema->toArray()['provider']['@id']
        );
    }
}
