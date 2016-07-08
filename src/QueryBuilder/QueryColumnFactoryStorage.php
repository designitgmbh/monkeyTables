<?php
namespace Designitgmbh\MonkeyTables\QueryBuilder;

use DB;

class QueryColumnFactoryStorage
{
    static private 
        $source = null,
        $model = null,
        $table = null,
        $columns = [];

    static public function prepare($source, $model) {
        self::$source = $source;
        self::$model = $model;
        self::$table = $model->getTable();

        self::$columns = [];
    }

    static public function byValueKey($valueKey) {
        $hash = md5($valueKey);

        if(!isset(self::$columns[$hash])) {
            self::$columns[$hash] = new QueryColumn(
                self::$source, 
                self::$model, 
                self::$table, 
                $valueKey
            );
        }       

        return self::$columns[$hash];
    }
}