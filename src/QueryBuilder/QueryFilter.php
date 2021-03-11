<?php
namespace Designitgmbh\MonkeyTables\QueryBuilder;

use DB;

class QueryFilter
{
    private $fieldName,
        $compare,
        $values,

        $needsHaving;

    public function __construct($fieldName, $compare, $values, $queryColumn = null)
    {
        $this->pdo = DB::getPdo();
        $this->fieldName = $fieldName;
        $this->compare = $compare;
        $this->values = $values;

        $this->needsHaving = $queryColumn ? $queryColumn->needsHaving() : false;
    }

    public function applyOn($query)
    {
        $query->where(function ($subQuery) use ($query) {
            $this->applyOnSubquery($subQuery, $query);
        });
    }

    public function applyOnSubquery($query, $mainQuery = null)
    {
        $having = [];

        foreach ($this->values as $value) {
            if ($this->needsHaving) {
                $having[] = $this->filteringHavingComparison($query, $value);
            } else {
                $this->filteringWhereComparison($query, $value);
            }
        }

        if (!empty($having)) {
            if ($mainQuery) {
                $mainQuery->havingRaw(implode(' OR ', $having));
            } else {
                $query->havingRaw(implode(' OR ', $having));
            }
        }
    }

    private function explodeCompare($value)
    {
        $compares = [];

        switch ($this->compare) {
            case ("between"):
                $between = explode("|", $value);
                if ($between[0]) {
                    $compares[] = (object)[
                        "function" => "where",
                        "compare" => ">=",
                        "value" => $between[0]
                    ];
                }
                if ($between[1]) {
                    $compares[] = (object)[
                        "function" => "where",
                        "compare" => "<=",
                        "value" => $between[1]
                    ];
                }

                break;
            case ("contains"):
                $compares[] = (object)[
                    "function" => "where",
                    "compare" => "LIKE",
                    "value" => "%" . $value . "%"
                ];

                break;
            case ("exists"):
                if ($value == "true" || $value === true) {
                    $function   = 'whereNotNull';
                } else {
                    $function   = 'whereNull';
                }

                $compares[] = (object)[
                    "function" => $function,
                    "compare" => "",
                    "value" => ""
                ];
                break;
            default:
                $compares[] = (object)[
                    "function" => "where",
                    "compare" => $this->compare,
                    "value" => $value
                ];

                break;
        }

        return $compares;
    }

    protected function transformValueForFilter($value)
    {
        if (is_numeric($value)) {
            return DB::raw("$value");
        } elseif (is_bool($value)) {
            return DB::raw($value ? 1 : 0);
        } elseif ($this->isDate($value)) {
            return DB::raw("'$value'");
        }

        $value = $this->pdo->quote($value);

        // hack to be able to compare regardless of the table collation
        return DB::raw("LOWER($value) COLLATE utf8mb4_unicode_ci");
    }

    protected function isDate($value)
    {
        return strtotime($value) !== false;
    }

    /**
     * Compares the values when filtering
     * @return void
     */
    protected function filteringWhereComparison($query, $value)
    {
        $compares = $this->explodeCompare($value);
        $query = $query->orWhere(function ($subquery) use ($compares) {
            foreach ($compares as $compare) {
                $function = $compare->function;
                $subquery = $subquery->$function(
                    $this->isDate($compare->value)
                        ? DB::raw("{$this->fieldName}")
                        : DB::raw("LOWER(" . $this->fieldName . ")"),
                    $compare->compare,
                    $this->transformValueForFilter($compare->value)
                );
            }
        });
    }

    protected function filteringHavingComparison($query, $value)
    {
        return "( LOWER(" . $this->fieldName . ") " . $this->compare . " " .
            $this->transformValueForFilter($value) . ")";
    }
}
