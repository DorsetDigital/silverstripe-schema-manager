<?php

namespace DorsetDigital\SchemaManager\Tests\Model\Schema;

use DorsetDigital\SchemaManager\Model\Schema\ProductSchema;
use SilverStripe\Dev\SapphireTest;

class ProductSchemaTest extends SapphireTest
{
    public function testProductSchemaBuildsCommonProductData(): void
    {
        $schema = ProductSchema::create(
            'https://example.com/products/example/',
            'Example product',
            'An example product.'
        )
            ->setImage('https://example.com/images/product.jpg')
            ->setSKU('SKU-123')
            ->setBrand('Example Brand')
            ->setOffer(49.95, 'GBP', true);

        $data = $schema->toArray();

        $this->assertSame('Product', $data['@type']);
        $this->assertSame('https://example.com/products/example/#product', $data['@id']);
        $this->assertSame('https://example.com/products/example/#webpage', $data['mainEntityOfPage']['@id']);
        $this->assertSame('Example product', $data['name']);
        $this->assertSame('An example product.', $data['description']);
        $this->assertSame('https://example.com/images/product.jpg', $data['image']);
        $this->assertSame('SKU-123', $data['sku']);
        $this->assertSame('Example Brand', $data['brand']['name']);
        $this->assertSame(49.95, $data['offers']['price']);
        $this->assertSame('GBP', $data['offers']['priceCurrency']);
        $this->assertSame('https://schema.org/InStock', $data['offers']['availability']);
        $this->assertSame('https://example.com/products/example/', $data['offers']['url']);
    }

    public function testProductSchemaCanBeExtended(): void
    {
        $schema = ProductSchema::create(
            'https://example.com/products/example/',
            'Example product'
        )->update([
            'color' => 'Blue',
        ]);

        $this->assertSame('Blue', $schema->toArray()['color']);
    }
}
