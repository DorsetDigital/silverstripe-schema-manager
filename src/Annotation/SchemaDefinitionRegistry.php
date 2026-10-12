<?php

namespace DorsetDigital\SchemaManager\Annotation;

use SilverStripe\Core\Config\Configurable;

class SchemaDefinitionRegistry
{
    use Configurable;

    private static array $definitions = [];

    public static function definitions(): array
    {
        return (array) static::config()->get('definitions');
    }

    public static function definition(string $key): ?array
    {
        $definition = static::definitions()[$key] ?? null;
        return is_array($definition) ? $definition : null;
    }
}
