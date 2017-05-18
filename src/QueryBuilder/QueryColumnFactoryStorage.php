<?php
namespace Designitgmbh\MonkeyTables\QueryBuilder;

use DB;

class QueryColumnFactoryStorage
{
    static private $source = null,
        $model = null,
        $table = null,
        $columns = [];

    public static function prepare($model)
    {
        self::$model = $model;
        self::$table = $model->getTableName();

        self::$columns = [];
    }

    public static function byValueKey($valueKey)
    {
        $hash = md5($valueKey);

        if (!isset(self::$columns[$hash])) {
            self::$columns[$hash] = new QueryColumn(
                self::$model,
                self::$table,
                $valueKey
            );
        }

        return self::$columns[$hash];
    }
}
