<?php

namespace App\Core;

trait Hydratable
{
    public static function hydrate(array $row): static
    {
        $obj = new static();
        foreach ($row as $key => $value) {
            if (!is_string($key)) {
                continue;
            }
            $camel = lcfirst(str_replace('_', '', ucwords($key, '_')));
            if (!property_exists($obj, $camel)) {
                continue;
            }
            if ($value !== null) {
                $ref  = new \ReflectionProperty(static::class, $camel);
                $type = $ref->getType();
                if ($type instanceof \ReflectionNamedType && $type->isBuiltin()) {
                    settype($value, $type->getName());
                }
            }
            $obj->$camel = $value;
        }
        return $obj;
    }

    public static function hydrateAll(array $rows): array
    {
        return array_map(static fn(array $row) => static::hydrate($row), $rows);
    }
}
