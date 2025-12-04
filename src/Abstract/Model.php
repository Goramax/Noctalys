<?php

namespace Goramax\NoctalysFramework\Abstract;
abstract class Model
{
    public static function fromArray(array $data): static
    {
        $instance = new static();
        $reflection = new \ReflectionClass($instance);
        $publicProperties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);
        $publicPropertyNames = array_map(fn($prop) => $prop->getName(), $publicProperties);
        
        foreach ($data as $key => $value) {
            if (in_array($key, $publicPropertyNames)) {
                $instance->$key = $value;
            }
        }
        return $instance;
    }

    public function toArray(): array
    {
        $reflection = new \ReflectionClass($this);
        $publicProperties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);
        $result = [];
        
        foreach ($publicProperties as $property) {
            $name = $property->getName();
            $result[$name] = $this->{$name};
        }
        
        return $result;
    }
}
