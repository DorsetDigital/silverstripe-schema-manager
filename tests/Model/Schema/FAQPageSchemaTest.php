<?php

namespace DorsetDigital\SchemaManager\Tests\Model\Schema;

use DorsetDigital\SchemaManager\Model\Schema\FAQPageSchema;
use SilverStripe\Dev\SapphireTest;

class FAQPageSchemaTest extends SapphireTest
{
    public function testCreateBuildsFaqPageAsPartOfWebPage(): void
    {
        $schema = FAQPageSchema::create('faqs');
        $data = $schema->toArray();

        $this->assertSame('FAQPage', $data['@type']);
        $this->assertStringEndsWith('#faqpage-faqs', $data['@id']);
        $this->assertArrayNotHasKey('isPartOf', $data);
        $this->assertSame([], $data['mainEntity']);
    }

    public function testQuestionsCanBeAddedFluently(): void
    {
        $schema = FAQPageSchema::create('faqs')
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
