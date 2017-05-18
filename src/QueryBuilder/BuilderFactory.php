<?php
namespace Designitgmbh\MonkeyTables\QueryBuilder;

use Designitgmbh\MonkeyTables\Factory\Factory;

class BuilderFactory extends Factory
{
    public static function fromModel($model)
    {
        $source = self::getFullClassName($model->getModelClass());
        return $source::with([]);
    }
}
