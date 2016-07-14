<?php
namespace Designitgmbh\MonkeyTables\Factory;

class Factory
{
    private static $modelNamespace = "App\\Models\\";

    protected static function getFullClassName($shortClassName) {
        $className = self::$modelNamespace . $shortClassName;
        if(!class_exists($className))
            $className = $shortClassName;

        return $className;
    }

    protected static function createInstance($className) {
        $className = self::getFullClassName($className);
        return new $className;
    }
}