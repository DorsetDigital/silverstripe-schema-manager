<?php

namespace DorsetDigital\SchemaManager\Tests\Model\Schema;

use DorsetDigital\SchemaManager\Model\Schema\FAQPageSchema;
use PHPUnit\Framework\TestCase;

class FAQPageSchemaTest extends TestCase
{
    public function testCreateBuildsFaqPageLinkedToWebPage(): void
    {
        $schema = FAQPageSchema::create('https://example.com/faqs');
        $data = $schema->toArray();

        $this->assertSame('FAQPage', $data['@type']);
        $this->assertSame('https://example.com/faqs/#faq', $data['@id']);
        $this->assertSame(
            ['@id' => 'https://example.com/faqs/#webpage'],
            $data['mainEntityOfPage']
        );
        $this->assertSame([], $data['mainEntity']);
    }

    public function testQuestionsCanBeAddedFluently(): void
    {
        $schema = FAQPageSchema::create('https://example.com/faqs/')
            ->addQuestion('Question one?', 'Answer one.')
            ->addQuestion('Question two?', 'Answer two.');

        $data = $schema->toArray();

        $this->assertCount(2, $data['mainEntity']);
        $this->assertSame('Question', $data['mainEntity'][0]['@type']);
        $this->assertSame('Question one?', $data['mainEntity'][0]['name']);
        $this->assertSame('Answer', $data['mainEntity'][0]['acceptedAnswer']['@type']);
        $this->assertSame('Answer one.', $data['mainEntity'][0]['acceptedAnswer']['text']);
        $this->assertSame('Question two?', $data['mainEntity'][1]['name']);
        $this->assertSame('Answer two.', $data['mainEntity'][1]['acceptedAnswer']['text']);
    }
}
