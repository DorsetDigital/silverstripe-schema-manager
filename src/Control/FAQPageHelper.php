<?php

namespace DorsetDigital\SchemaManager\Control;

use DorsetDigital\SchemaManager\Model\Schema\FAQPageSchema;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;

/**
 * Collect FAQ questions into a single page-scoped entity.
 */
final class FAQPageHelper
{
    public static function addQuestion(string $question, string $answer, ?string $pageURL = null): void
    {
        if (!$pageURL) {
            $page = Director::get_current_page();
            if ($page && method_exists($page, 'AbsoluteLink')) {
                $pageURL = $page->AbsoluteLink();
            } else {
                $pageURL = Director::absoluteURL(Controller::curr()->getRequest()->getURL());
            }
        }

        $pageURL = rtrim($pageURL, '/') . '/';
        $id = SchemaIDs::forPageURL($pageURL, 'faq');
        $schema = FAQPageSchema::create('page-' . substr(sha1($pageURL), 0, 16))
            ->setIsPartOfPage($pageURL)
            ->addQuestion($question, $answer);
        // Preserve the historical page-scoped FAQ ID for collected questions.
        $schema->update(['@id' => $id]);
        SchemaRegistry::add($schema);
    }
}
