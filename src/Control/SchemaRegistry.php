<?php

namespace DorsetDigital\SchemaManager\Control;

use DorsetDigital\SchemaManager\Model\Schema\BreadcrumbListSchema;
use DorsetDigital\SchemaManager\Model\Schema\FAQPageSchema;
use DorsetDigital\SchemaManager\Model\Schema\WebPageSchema;
use DorsetDigital\SchemaManager\Model\Schema\Schema;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;

class SchemaRegistry
{
    private static array $entities = [];

    private static array $schemas = [];

    public static function add(Schema $schema): void
    {
        $id = $schema->getEntityID();

        if (!$id) {
            return;
        }

        // Multiple FAQ sections on the same page share an ID. Keep the first
        // typed instance, but append questions from every subsequent section.
        if ($schema instanceof FAQPageSchema && isset(self::$schemas[$id])
            && self::$schemas[$id] instanceof FAQPageSchema) {
            self::addEntity($id, $schema->toArray());
            return;
        }

        $previous = self::$entities[$id] ?? [];
        $incoming = $schema->toArray();

        // Preserve mainEntityOfPage over isPartOf when duplicate registrations
        // disagree about the same page, regardless of registration order.
        $oldMain = $previous['mainEntityOfPage']['@id'] ?? null;
        $newMain = $incoming['mainEntityOfPage']['@id'] ?? null;
        $oldPart = $previous['isPartOf']['@id'] ?? null;
        $newPart = $incoming['isPartOf']['@id'] ?? null;

        if ($oldMain !== null && $oldMain === $newPart) {
            unset($incoming['isPartOf']);
            $incoming['mainEntityOfPage'] = $previous['mainEntityOfPage'];
        }
        if ($newMain !== null && $newMain === $oldPart) {
            unset(self::$entities[$id]['isPartOf']);
        }

        self::$schemas[$id] = $schema;
        self::addEntity($id, $incoming);
    }

    public static function addEntity(string $id, array $data): void
    {
        if (isset(self::$entities[$id])) {
            // FAQ mainEntity is a list, not a map: concatenate questions rather
            // than recursively replacing entries with matching numeric indexes.
            if ((self::$entities[$id]['@type'] ?? null) === 'FAQPage'
                && ($data['@type'] ?? null) === 'FAQPage'
                && isset($data['mainEntity']) && is_array($data['mainEntity'])) {
                $data['mainEntity'] = array_merge(
                    self::$entities[$id]['mainEntity'] ?? [],
                    $data['mainEntity']
                );
            }

            self::$entities[$id] = array_replace_recursive(
                self::$entities[$id],
                $data
            );
            return;
        }

        self::$entities[$id] = $data;
    }

    /**
     * @deprecated Use FAQPageSchema::create() and SchemaRegistry::add() instead.
     */
    public static function addFAQ(string $question, string $answer, ?string $pageURL = null): void
    {
        @trigger_error(
            __METHOD__ . '() is deprecated. Use FAQPageSchema::create() and SchemaRegistry::add() instead.',
            E_USER_DEPRECATED
        );

        FAQPageHelper::addQuestion($question, $answer, $pageURL);
    }

    public static function addBreadCrumbs(
        SiteTree $page,
        int $maxDepth = 20,
        bool|string $stopAtPageType = false,
        bool $showHidden = false
    ): void {
        $items = $page->getBreadcrumbItems($maxDepth, $stopAtPageType, $showHidden);

        if (!$items || $items->count() < 2) {
            return;
        }

        $itemList = [];
        $position = 1;

        foreach ($items as $item) {
            if (!$item instanceof SiteTree) {
                continue;
            }

            $itemList[] = [
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => $item->MenuTitle ?: $item->Title,
                'item' => $item->AbsoluteLink(),
            ];
        }

        if (count($itemList) < 2) {
            return;
        }

        $id = rtrim($page->AbsoluteLink(), '/') . '/#breadcrumb';

        self::addEntity($id, [
            '@type' => 'BreadcrumbList',
            '@id' => $id,
            'itemListElement' => $itemList,
        ]);
    }

    public static function getGraph(): array
    {
        self::resolveRelationships();

        return array_values(self::$entities);
    }

    private static function resolveRelationships(): void
    {
        foreach (self::$schemas as $webPage) {
            if (!$webPage instanceof WebPageSchema) {
                continue;
            }

            $webPageID = $webPage->getEntityID();
            $pageURL = $webPage->toArray()['url'] ?? null;
            if (!is_string($pageURL) || $pageURL === '') {
                continue;
            }

            $pageURL = rtrim($pageURL, '/') . '/';
            $defaultID = SchemaIDs::forPageURL($pageURL, 'webpage');
            $mainEntities = [];

            foreach (self::$schemas as $schema) {
                if ($schema === $webPage) {
                    continue;
                }

                $data = self::$entities[$schema->getEntityID()] ?? $schema->toArray();
                $mainRef = $data['mainEntityOfPage']['@id'] ?? null;
                $partRef = $data['isPartOf']['@id'] ?? null;

                if ($mainRef === $defaultID || $mainRef === $webPageID) {
                    $mainEntities[] = $schema;
                }

                // Reconcile conventional references with a registered page's
                // custom ID. Only relationships for this page are rewritten.
                if ($webPageID !== $defaultID && $partRef === $defaultID) {
                    $data['isPartOf'] = ['@id' => $webPageID];
                    self::$entities[$schema->getEntityID()] = array_replace_recursive(
                        self::$entities[$schema->getEntityID()] ?? [],
                        $data
                    );
                }

                if ($schema instanceof BreadcrumbListSchema
                    && $schema->getEntityID() === SchemaIDs::forPageURL($pageURL, 'breadcrumb')
                    && !isset($webPage->toArray()['breadcrumb'])) {
                    $webPage->setBreadcrumb($schema->getEntityID());
                }
            }

            if ($mainEntities) {
                // Last registered main entity wins; previous contenders become
                // supporting entities. Resolve directly to the actual page ID
                // so repeated graph generation produces the same result.
                $winner = array_pop($mainEntities);
                foreach ($mainEntities as $demoted) {
                    $id = $demoted->getEntityID();
                    $data = self::$entities[$id] ?? $demoted->toArray();
                    unset($data['mainEntityOfPage']);
                    $data['isPartOf'] = ['@id' => $webPageID];
                    self::$entities[$id] = $data;
                }

                $winnerID = $winner->getEntityID();
                $data = self::$entities[$winnerID] ?? $winner->toArray();
                unset($data['isPartOf']);
                $data['mainEntityOfPage'] = ['@id' => $webPageID];
                self::$entities[$winnerID] = $data;
                self::$entities[$webPageID]['mainEntity'] = ['@id' => $winnerID];
            }

        }
    }

    public static function getSchema(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@graph' => self::getGraph(),
        ];
    }

    public static function getJSON(): string
    {
        if (!self::$entities) {
            return '';
        }

        return (string) json_encode(
            self::getSchema(),
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
        );
    }

    public static function flush(): void
    {
        self::$entities = [];
        self::$schemas = [];
    }
}
