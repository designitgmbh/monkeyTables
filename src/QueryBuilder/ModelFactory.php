<?php
namespace Designitgmbh\MonkeyTables\QueryBuilder;

use Designitgmbh\MonkeyTables\Factory\Factory;

class ModelFactory extends Factory
{
    public static function fromSource($source)
    {
        return self::getModel($source);
    }

    private static function getModel($source)
    {
        $model = self::createInstance($source);

        return new Model($model);
    }
}
