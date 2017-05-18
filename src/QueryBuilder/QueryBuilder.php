<?php
namespace Designitgmbh\MonkeyTables\QueryBuilder;

use DB;
use Cache;

use Designitgmbh\MonkeyTables\Data\oData;

class QueryBuilder
{
    protected $source;

    private $select = [],
        $statement = null;

    public function __construct()
    {
        $this->prefilterFilterValues = true;
        $this->usePreparedStatement = false;
        $this->filterValuesCache = [];
    }

    public static function recursiveObjectGetter($obj, $string)
    {
        if (!$string) {
            return null;
        }

        //check if is SQL Command
        if (QueryColumn::isSQLCmd($string)) {
            $name = "sql" . md5($string);
            return $obj->$name;
        }

        //remove alias symbols
        if (strpos($string, "|", strpos($string, "|") + 1) !== false) {
            $string = str_replace("|", "", $string);
        }
        
        //check for recursion
        if (strpos($string, "->") === false && strpos($string, "()") === false) {
            return $obj->$string;
        } else {
            $parts = explode("->", $string);
        }

        foreach ($parts as $part) {
            if (!is_object($obj)) {
                return null;
            }

            //check for where clause
            if (strpos($part, "[") !== false && strpos($part, "]") !== false) {
                //extract condition
                $s1 = explode("[", $part);
                $s2 = explode("]", $s1[1]);

                $part = $s1[0];
                $condition = $s2[0];

                if (strpos($condition, " in ")) {
                    $operator = " in ";
                } elseif (strpos($condition, "!=")) {
                    $operator = "!=";
                } else {
                    $operator = "=";
                }

                $condition = explode($operator, $condition);

                $conditionField = $condition[0];
                $conditionValue = $condition[1];

                //use condition
                if ($operator == " in ") {
                    $obj = $obj->$part()->whereIn($conditionField, explode(";", $conditionValue))->first();
                } else {
                    $obj = $obj->$part()->where($conditionField, $operator, $conditionValue)->first();
                }
            } else {
                if ((strpos($part, "()") !== false)) {
                    $part = str_replace("()", "", $part);
                    $obj = $obj->$part();
                } else {
                    $obj = $obj->$part;
                }
            }
        }

        return $obj;
    }

    public function init($source, $prefetch, $prejoin, $prefilter, $groupBy)
    {
        $this->source = $source;
        $this->prefetch = $prefetch;
        $this->prejoin = $prejoin;
        $this->prefilter = $prefilter;
        $this->groupBy = $groupBy;

        $this->rowCount = false;
        $this->joinedTables = array();
        $this->mainTable = null;

        $this->select = null;

        $this->needsRowCount = true;

        QueryColumnFactoryStorage::prepare($this->getModel());
    }

    public function setTotalCountType($totalCountType)
    {
        $this->totalCountType = $totalCountType;
        $this->needsRowCount = oData::needsRowsCount($this->totalCountType);

        return $this;
    }

    public function setSelect($select = null)
    {
        $this->select = $select;
        return $this;
    }

    public function usePreparedStatement($use)
    {
        $this->usePreparedStatement = $use;

        return $this;
    }

    public function prefilterFilterValues($do)
    {
        $this->prefilterFilterValues = $do;

        return $this;
    }

    public function getRowsCount()
    {
        if ($this->rowCount !== false) {
            return $this->rowCount;
        }

        if ($this->needsRowCount === false) {
            return 0;
        }

        $query = QueryBuilder::fromModel($this->getModel());

        $this->applyPrejoin($query);
        $this->applyPrefilter($query);
        return $query->count();
    }

    public function getFilterValuesForColumns($columns)
    {
        foreach ($columns as $columnKey => $column) {
            $this->setFilterValuesForColumn($column);
        }

        return true;
    }

    private function getSource()
    {
        $source         = $this->modelNamespace . $this->source;
        if (!class_exists($source)) {
            $source     = $this->source;
        }

        return $source;
    }

    private function getModel()
    {
        return ModelFactory::fromSource($this->source);
    }

    private function setFilterValuesForColumn(&$column)
    {
        //early return checks
        if (!$column->hasAutoFilterValues()) {
            $column->setFilterValues([]);
            return;
        }

        if ($column->hasFilterValuesAlreadySet()) {
            return;
        }

        //the cache hash
        $hash = md5($column->valueKey . $this->source);

        if ($this->usePreparedStatement) {
            //we are reusing prepared statements, so we can
            //also cache the filter values for the columns,
            //as all series will have the same filters

            if (isset($this->filterValuesCache[$hash])) {
                $values = $this->filterValuesCache[$hash];
                $column->setFilterValues($values);

                return;
            }
        }

        $cacheName = 'monkeyTablesColumnFilterValues::' . $hash;
        $values = Cache::remember($cacheName, 120, function () use ($column, $hash) {
            $queryColumn = QueryColumnFactoryStorage::byValueKey($column->valueKey);

            if (!$queryColumn->isFetchable()) {
                $column
                    ->setSortable(false)
                    ->setFilterable(false);

                return;
            }

            $query = Query::fromModel($this->getModel());
            $columnKey = '___COLUMN_KEY___';

            $query->select(DB::raw($queryColumn->getFieldName() . " as `" . $columnKey . "`"));
            $query->getQuery()->distinct();

            if ($queryColumn->needsJoin()) {
                $query->join($queryColumn);
            }

            if ($this->prefilterFilterValues) {
                $this->applyPrejoin($query);
                $this->applyPrefilter($query);
            }

            $query->take(100);
            $query->prepare();
            
            $result = $query->get();

            $values = array();
            foreach ($result as $row) {
                if (($val = $row->$columnKey) && $val != "") {
                    $values[$val] = $val;
                }
            }

            if ($this->usePreparedStatement) {
                $this->filterValuesCache[$hash] = $values;
            }

            return $values;
        });

        if (is_array($values)) {
            $column->setFilterValues($values);
        }
    }

    public function getRows($filters)
    {
        $query = Query::fromModel($this->getModel());
        $query->prefetch($this->prefetch);

        $this->applyPrejoin($query);
        $this->applyPrefilter($query);

        $query->basicSelect($this->select);

        if (isset($filters['select'])) {
            foreach ($filters['select'] as $filter) {
                $queryColumn = QueryColumnFactoryStorage::byValueKey($filter['valueKey']);

                if (!$queryColumn->isFetchable()) {
                    continue;
                }

                if ($queryColumn->needsSelect()) {
                    $query->select($queryColumn->getSelectClause());
                }
            }
        }

        if (isset($filters['filter']) && is_array($filters['filter'])) {
            foreach ($filters['filter'] as $filter) {
                $queryColumn   = QueryColumnFactoryStorage::byValueKey($filter['valueKey']);

                if (!$queryColumn->isFetchable()) {
                    continue;
                }

                if ($queryColumn->needsJoin()) {
                    $query->join($queryColumn);
                }

                $query->filter($filter, $queryColumn);
            }
        }

        if (isset($filters['quickSearch']) && is_array($filters['quickSearch'])) {
            foreach ($filters['quickSearch'] as $key => $filter) {
                $queryColumn = QueryColumnFactoryStorage::byValueKey($filter['valueKey']);

                if (!$queryColumn->isFetchable() || $queryColumn->needsHaving()) {
                    continue;
                }

                if ($queryColumn->needsJoin()) {
                    $query->join($queryColumn);
                }

                $query->quickSearch($queryColumn->getFieldName(), $filter['value']);
            }
        }

        $query->groupBy($this->groupBy);

        $showAll = $this->totalCountType == oData::TOTAL_COUNT_TYPE_SHOW_ALL;
        $this->rowCount = $this->needsRowCount || $showAll ? $query->count() : 0;

        if (isset($filters['sorting'])) {
            $queryColumn = QueryColumnFactoryStorage::byValueKey($filters['sorting']['valueKey']);

            if ($queryColumn->isFetchable()) {
                if ($queryColumn->needsJoin()) {
                    $query->join($queryColumn);
                }

                $query->orderBy(
                    $queryColumn->getFieldName(),
                    $filters['sorting']['direction']
                );
            }
        }

        $skipRows = (isset($filters['skipRows']) && !$showAll ? $filters['skipRows'] : 0);
        $resultsPerPage = (isset($filters['resultsPerPage']) && !$showAll ? $filters['resultsPerPage'] : 0);

        if ($this->totalCountType == oData::TOTAL_COUNT_TYPE_NEXT_PAGE) {
            $resultsPerPage++;
        }

        if ($skipRows) {
            $query->skip($skipRows);
        }

        if ($resultsPerPage) {
            $query->take($resultsPerPage);
        }

        if ($this->usePreparedStatement) {
            $query->prepare();

            if (!$this->statement) {
                $this->statement = new Statement($query);
            }

            $results = $this->statement->fetchResults($query);
        }

        $results = $query->get();

        if ($this->totalCountType == oData::TOTAL_COUNT_TYPE_NEXT_PAGE || $showAll) {
            $fetchedResults = count($results);
            $fetchedNextPage = $fetchedResults === $resultsPerPage;

            $this->rowCount = $fetchedResults + $skipRows;
            
            if ($fetchedNextPage) {
                $results->pop();
            }
        }

        return $results;
    }

    /**
     * Joins the query if needed for prefiltering
     * @return void
     */
    protected function applyPrejoin($query)
    {
        if (count($this->prejoin)>0) {
            foreach ($this->prejoin as $prejoin) {
                $DBColumn = QueryColumnFactoryStorage::byValueKey($prejoin);

                if (!$DBColumn->isFetchable()) {
                    continue;
                }

                if ($DBColumn->needsJoin()) {
                    $query->join($DBColumn);
                }
            }
        }
    }

    /**
     * Applies prefilter conditions to a query
     * @return void
     **/
    protected function applyPrefilter(&$query)
    {
        $privateQuery = $query->getQuery();

        if (count($this->prefilter)>0) {
            foreach ($this->prefilter as $prefilter) {
                if (!isset($prefilter["field"])) {
                    $this->applyAdvancedPrefilter($privateQuery, $prefilter);
                    continue;
                }
            
                if ($prefilter["field"] === "!trashed") {
                    $privateQuery->onlyTrashed();
                    continue;
                }

                if ($prefilter["type"] == "OR") {
                    $privateQuery->where(function ($privateQuery) use ($prefilter) {
                        for ($i=0; $i<count($prefilter["field"]); $i++) {
                            $privateQuery->orWhere($prefilter["field"][$i], $prefilter["operator"][$i], $prefilter["value"][$i]);
                        }
                    });

                    continue;
                }

                switch ($prefilter["operator"]) {
                    case ('in'):
                    case ('IN'):
                        $privateQuery->whereIn($prefilter["field"], $prefilter["value"]);
                        break;
                    case ('null'):
                        $privateQuery->whereNull($prefilter["field"]);
                        break;
                    case ('!null'):
                        $privateQuery->whereNotNull($prefilter["field"]);
                        break;
                    default:
                        $privateQuery->where($prefilter["field"], $prefilter["operator"], $prefilter["value"]);
                        break;
                }
            }
        }
    }

    protected function applyAdvancedPrefilter(&$query, $prefilter)
    {
        // Each element represents an or condition, which could contain multiple and conditions
        $query->orWhere(function ($query) use ($prefilter) {
            foreach ($prefilter as $pf) {
                if (!is_null($pf["relation"]) && $pf["field"] instanceof Closure) {
                    // This is a whereHas clause
                    $query->whereHas($pf["relation"], $pf["field"], $pf["operator"], $pf["value"]);
                } elseif (is_null($pf['value'])) {
                    if ($pf['operator'] == '=') {
                        $query->whereNull($pf["field"]);
                    } else {
                        $query->whereNotNull($pf["field"]);
                    }
                } elseif ($pf["operator"] == 'in') {
                    $query->whereIn($pf["field"], $pf["value"]);
                } else {
                    $query->where($pf["field"], $pf["operator"], $pf["value"]);
                }
            }
        });
    }
}
