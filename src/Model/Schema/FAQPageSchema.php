<?php

namespace DorsetDigital\SchemaManager\Model\Schema;

use DorsetDigital\SchemaManager\Control\SchemaIDs;

class FAQPageSchema extends Schema
{
    public static function create(string $identifier): static
    {
        return new static([
            '@type' => 'FAQPage',
            '@id' => SchemaIDs::forEntity('faqpage', $identifier),
            'mainEntity' => [],
        ]);
    }

    public function addQuestion(string $question, string $answer): static
    {
        $this->data['mainEntity'][] = [
            '@type' => 'Question',
            'name' => $question,
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => $answer,
            ],
        ];

        return $this;
    }
}
