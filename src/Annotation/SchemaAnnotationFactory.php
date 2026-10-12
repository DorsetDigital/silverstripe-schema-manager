<?php

namespace DorsetDigital\SchemaManager\Annotation;

use DorsetDigital\SchemaManager\Model\Schema\Schema;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\Injector\Injector;

class SchemaAnnotationFactory
{
    public function build(SiteTree $page, string $key, array $values): ?Schema
    {
        $definition = SchemaDefinitionRegistry::definition($key);
        if (!$definition) {
            return null;
        }

        $definition['key'] = $key;
        $handlerClass = $definition['handler'] ?? GenericAnnotationHandler::class;
        $handler = Injector::inst()->get($handlerClass);
        if (!$handler instanceof SchemaAnnotationHandler) {
            throw new \InvalidArgumentException(sprintf('Annotation handler %s must implement %s', $handlerClass, SchemaAnnotationHandler::class));
        }

        return $handler->build($page, $values, $definition);
    }
}
