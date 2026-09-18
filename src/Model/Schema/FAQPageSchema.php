<?php

namespace DorsetDigital\SchemaManager\Model\Schema;

class FAQPageSchema extends Schema
{
    public static function create(string $url): static
    {
        $url = rtrim($url, '/') . '/';

        return new static([
            '@type' => 'FAQPage',
            '@id' => $url . '#faq',
            'isPartOf' => [
                '@id' => $url . '#webpage',
            ],
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
