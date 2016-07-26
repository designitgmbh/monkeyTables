<?php
namespace Designitgmbh\MonkeyTables\QueryBuilder;

use DB;

class Query
{
    private 
        $model,
        $modelTable,
        $modelKeyName,

        $query = null,
        $queryStatus,

        $prefetch = [],

        $select = [],
        $join = [],
        $filter = [],
        $quickSearchFilter = [],
        $groupBy = [];

    public static function fromTable($table) {
        return new Query(DB::table($table), $table, '');
    }

    public static function fromModel($model) {
        $modelTable = $model->getTableName();
        $modelKeyName = $model->getKeyName();

        $query = BuilderFactory::fromModel($model);

        return new Query($query, $modelTable, $modelKeyName);
    }

    public function __construct($query, $modelTable, $modelKeyName) {
        $this->query = $query;
        $this->modelTable = $modelTable;
        $this->modelKeyName = $modelKeyName;

        $this->queryStatus = (object)[
            "joinedTables" => [],
            "isSelected" => false,
            "isFiltered" => false,
            "isGrouped" => false
        ];
    }

    public function getQuery() {
        //TODO REMOVE THIS AND REPLACE CODE IN QUERY BUILDER!
        return $this->query;
    }

    public function prefetch($prefetch) {
        $this->query->with($prefetch);

        return $this;
    }

    public function basicSelect($selectClause = '') {
        if(empty($selectClause)) {
            $this->select[] = $this->modelTable . ".*";
        }

        if(!is_array($selectClause)) {
            $selectClause = [$selectClause];
        }
        
        foreach ($selectClause as $select) {
            if($select) {
                $this->select[] = $select;    
            }
        }

        return $this;
    }

    public function select($selectClause) {
        if(!is_array($selectClause)) {
            $selectClause = [$selectClause];
        }

        foreach($selectClause as $select) {
            if($select) {
                array_push($this->select, $select);
            }
        }        

        return $this;
    }

    private function doSelect() {
        if($this->queryStatus->isSelected) {
            return $this;
        }

        if(empty($this->select)) {
            $this->basicSelect();
        }

        $this->query->select($this->select);

        $this->queryStatus->isSelected = true;

        return $this;
    }

    public function join($queryColumn) {
        foreach($queryColumn->getJoinArrayKeys() as $key) {
            $realTableName  = $queryColumn->getTableRealName($key);
            $aliasTableName = $queryColumn->getTableAliasName($key);
            $keysForJoin    = $queryColumn->getKeysForJoin($key);

            $this->join[$aliasTableName] = (object)[
                "valueKey" => $queryColumn->getValueKey(),
                "joinKey" => $key
            ];            
        }

        return $this;
    }

    private function doJoin() {
        foreach($this->join as $aliasTableName => $join) {
            if(array_key_exists($aliasTableName, $this->queryStatus->joinedTables))
                continue;

            $valueKey = $join->valueKey;
            $joinKey = $join->joinKey;

            $queryColumn = QueryColumnFactoryStorage::byValueKey($valueKey);

            $realTableName  = $queryColumn->getTableRealName($joinKey);
            $keysForJoin    = $queryColumn->getKeysForJoin($joinKey);

            if($queryColumn->hasWhereClause($joinKey)) {
                $this->query->leftJoin(
                    $realTableName . " AS " . $aliasTableName,
                    function($join) use ($queryColumn, $keysForJoin, $aliasTableName, $joinKey) {
                        $join->on($keysForJoin[0], "=", $keysForJoin[1]);

                        foreach($queryColumn->getWhereClauses($joinKey) as $whereClause) {
                            $field      = $whereClause["field"];
                            $operator   = $whereClause["operator"];
                            $value      = $whereClause["value"];

                            $join->where($aliasTableName . "." . $field, $operator, $value);
                        }
                    }
                );
            } else {
                $this->query->leftJoin(
                    $realTableName . " AS " . $aliasTableName, 
                    $keysForJoin[0], "=", $keysForJoin[1]
                );
            }

            $this->queryStatus->joinedTables[$aliasTableName] = true;
        }

        $this->join = [];

        return $this;
    }

    public function filter($filter, $queryColumn) {
        $fieldName  = $queryColumn->getFieldName();             
        $compare    = $filter['compare'];
        $value      = $filter['value'];

        $values     = json_decode($value, true);

        if (($values === null && json_last_error() !== JSON_ERROR_NONE) ||
            ($value === "true" || $value === "false" || $value === "null")) {
            if($value === "true")
                $value = true;
            if($value === "false")
                $value = false;
            if($value === "null")
                $value = null;
            
            //value is not json formatted, so take its original value
            $values = [$value];
        }

        if(!is_array($values)) {
            $values = [$values];
        }

        $this->filter[] = new QueryFilter($fieldName, $compare, $values, $queryColumn);
    }

    private function doFilter() {
        if($this->queryStatus->isFiltered) {
            return $this;
        }

        foreach($this->filter as $filter) {
            $filter->applyOn($this->query);
        }

        $this->query->where(function($subQuery) {
            foreach($this->quickSearchFilter as $filter) {
                $filter->applyOnSubquery($subQuery);
            }
        });

        $this->queryStatus->isFiltered = true;

        return $this;
    }

    public function quickSearch($fieldName, $value) {
        $compare    = "LIKE";
        $value      = "%" . $value . "%";

        $this->quickSearchFilter[] = new QueryFilter($fieldName, $compare, [$value]);
    }

    public function groupBy($groupBy = null) {
        if($groupBy) {
            $this->groupBy = $groupBy;

            return;
        }

        if(is_array($this->modelKeyName)) {
            $this->groupBy = array_map(function($key) {
                return $this->modelTable . "." . $key;
            }, $this->modelKeyName);

            return;
        }

        $this->groupBy = $this->modelTable . "." . $this->modelKeyName;
    }

    private function doGroupBy() {
        if($this->queryStatus->isGrouped) {
            return $this;
        }
        
        if($this->groupBy) {
            $this->query->groupBy($this->groupBy);    
        }
        
        $this->queryStatus->isGrouped = true;

        return $this;
    }

    public function orderBy($fieldName, $direction) {
        $this->query->orderBy(
            DB::raw($fieldName), 
            $direction
        );
    }

    public function skip($skip = null) {
        if($skip) {
            $this->query->skip($skip);    
        }

        return $this;
    }

    public function take($take = null) {
        if($take) {
            $this->query->take($take);    
        }

        return $this;
    }

    private function cloneQuery() {
        $this->queryClone = clone $this->query;
        return $this->queryClone;
    }

    public function count() {
        $this->doSelect()
            ->doJoin()
            ->doFilter();

        $distinctCountColumn = $this->groupBy;

        if(count($this->query->getQuery()->havings) > 0) {
            $this->doGroupBy();

            $countQuery = $this->cloneQuery()
                ->select($distinctCountColumn);

            return DB::table( DB::raw("({$countQuery->toSql()}) as sub") )
                ->mergeBindings($countQuery->getQuery())
                ->count();
        }

        if(is_array($distinctCountColumn)) {
            foreach($distinctCountColumn as &$col) {
                $col = $this->modelTable . "." . $col;
            }
        }

        return $this->cloneQuery()
            ->distinct($distinctCountColumn)
            ->count($distinctCountColumn);
    }

    public function prepare() {
        $this->doSelect()
            ->doJoin()
            ->doFilter()
            ->doGroupBy();
    }

    public function get() {
        $this->doSelect()
            ->doJoin()
            ->doFilter()
            ->doGroupBy();

        $returnSet = null;

        try {
            $returnSet = $this->query->get();
        } catch(Exception $Err) {
            //try to reset the bindings and set them from the countCollection
            $this->query->getQuery()->setBindings([], 'select');
            $this->query->getQuery()->setBindings([], 'join');
            $this->query->getQuery()->setBindings([], 'where');
            $this->query->getQuery()->setBindings([], 'having');
            $this->query->getQuery()->setBindings([], 'order');          
            $this->query->getQuery()->mergeBindings($this->queryClone->getQuery());

            $returnSet = $this->query->get();
        }

        return $returnSet;
    }
}