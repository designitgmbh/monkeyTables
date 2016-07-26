<?php
namespace Designitgmbh\MonkeyTables\QueryBuilder;

use DB;

class QueryColumn {
    public $name = '';

    private $isFetchable;

    private $model;
    private $mainTable;
    private $valueKey;

    private $fieldName;
    private $tableRealName;
    private $tableAliasName;

    private $needsJoin;
    private $joins;
    private $keysForJoin;
    private $hasWhereClause;
    private $whereClauses;

    private $isRelatedTable = null;

    public function __construct($model, $mainTable, $valueKey) {
        $this->model        = $model;
        $this->mainTable    = $mainTable;
        $this->valueKey     = $valueKey;

        $this->fieldName        = null;
        $this->tableRealName    = null;
        $this->tableAliasName   = null;

        $this->needsSelect      = false;
        $this->selectClause     = "";
        
        $this->needsJoin        = false;
        $this->joins            = array();
        $this->keysForJoin      = null;
        $this->hasWhereClause   = false;
        $this->whereClauses = null;

        $this->joinArray        = [];

        $this->evalFieldName();
    }

    public static function isSQLCmd($valueKey) {
        $hasParenthesis = (strpos($valueKey,"(") !== false)
                            && strpos($valueKey,")");
        $hasComma       = strpos($valueKey,",");
        $hasSQLCaseCmd  = stripos($valueKey,"CASE") 
                            && stripos($valueKey,"WHEN") 
                            && stripos($valueKey,"THEN") 
                            && stripos($valueKey,"END");

        return($hasParenthesis && ($hasComma || $hasSQLCaseCmd));
    }

    public function getValueKey() {
        return $this->valueKey;
    }

    public function isFetchable() {
        return $this->isFetchable;
    }

    public function getFieldName() {
        return $this->fieldName;
    }

    public function getTableRealName($key = null) {
        if($key !== null) {
            return $this->joinArray[$key]["realTableName"];
        }
        return $this->tableRealName;
    }

    public function getTableAliasName($key = null) {
        if($key !== null) {
            return $this->joinArray[$key]["aliasTableName"];
        }
        return $this->tableAliasName;
    }

    public function needsJoin() {
        return $this->needsJoin;
    }

    public function needsSelect() {
        return $this->needsSelect;
    }

    public function needsHaving() {
        if(self::isSQLCmd($this->valueKey)) {
            // http://dev.mysql.com/doc/refman/5.7/en/group-by-functions.html
            $sqlAggregateCommands = [
                "AVG",
                "BIT_AND",
                "BIT_OR",
                "BIT_XOR",
                "COUNT",
                "GROUP_CONCAT",
                "MAX",
                "MIN",
                "STD",
                "STDDEV",
                "STDDEV_POP",
                "STDDEV_SAMP",
                "SUM",
                "VAR_POP",
                "VAR_SAMP",
                "VARIANCE"
            ];

            foreach($sqlAggregateCommands as $aggregateCommand) {
                if(strpos($this->valueKey, $aggregateCommand) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    public function getSelectClause() {
        return DB::raw($this->selectClause);
    }

    public function getJoinArrayKeys() {
        if(is_array($this->joinArray) && count($this->joinArray) > 1)
            return array_keys($this->joinArray);
        return array(null);
    }

    public function getKeysForJoin($key = null) {
        if($key !== null) {
            return $this->joinArray[$key]["keysForJoin"];
        }
        return $this->keysForJoin;
    }

    public function hasWhereClause($key = null) {
        if($key !== null) {
            return $this->joinArray[$key]["hasWhereClause"];
        }
        return $this->hasWhereClause;
    }

    public function getWhereClauses($key = null) {
        if($key !== null) {
            return $this->joinArray[$key]["whereClauses"];
        }
        return $this->whereClauses;
    }

    public function isRelatedTable() {
        if($this->isRelatedTable == null) {
            if(strpos($this->valueKey,"->")) {
                $this->isRelatedTable = true;               
            } else {
                $this->isRelatedTable = false;
            }
        }
        return $this->isRelatedTable;
    }

    private function getAliasForJoinedTable($realTableName) {
        foreach($this->joinArray as $join) {
            if($join["realTableName"] == $realTableName) {
                return $join["aliasTableName"];
            }
        }

        return $realTableName;
    }

    private function evalFieldName() {
        //Check if is Model Function
        if(strpos($this->valueKey, "()") || strpos($this->valueKey, '#') === 0) {
            $this->isFetchable = false;
            return false;
        }

        $this->isFetchable = true;

        //Check if is SQL Command
        if(self::isSQLCmd($this->valueKey)) {
            $this->needsSelect = true;
            $this->fieldRealName = false;
            $this->fieldName = DB::raw($this->valueKey);
            $this->selectClause = $this->valueKey . " AS sql" . md5($this->fieldName);

            return true;
        }

        //Check if is Relation
        if(strpos($this->valueKey, "->")) {
            //join
            $this->needsJoin = true;
            $this->fieldName = $this->evalJoinFieldName();
        } else {
            //no join
            $this->fieldRealName    = $this->valueKey;
            $this->fieldName        = "`" . $this->mainTable . "`.`" . $this->valueKey . "`";
        }
    }

    private function evalJoinFieldName() {
        $this->evalKeysForJoin();

        return "`" . $this->tableAliasName . "`.`" . $this->fieldRealName . "`";
    }

    private function evalKeysForJoin() {
        $model = $this->model;

        $relations = explode("->", $this->valueKey);
        $relationCount = count($relations);

        $this->fieldRealName = array_pop($relations);

        foreach($relations as $relationKey => $relationString) {
            //set defaults
            $hasAlias           = false;
            $hasWhereClause     = false;
            $whereClauseAlias   = "";
            $whereClauses       = [];

            //extract where clause
            if(strpos($relationString, "[") && $str = explode("[", $relationString)) {
                $relationString     = $str[0];
                $str                = explode("]", $str[1]);
                $whereClause        = $str[0];

                foreach(explode(",", $whereClause) as $clause) {
                    if(strpos($clause, "!=")) {
                        $operator       = "!=";
                    } else {
                        $operator       = "=";
                    }

                    $condition          = explode($operator, $clause);

                    $hasWhereClause     = true;

                    $clauseArray        = [
                        "field"     => $condition[0],
                        "operator"  => $operator,
                        "value"     => $condition[1]
                    ];

                    $whereClauses[]     = $clauseArray;
                    $whereClauseAlias  .= join("", $clauseArray);
                }
            }

            if(strpos($relationString, "|", strpos($relationString, "|") + 1) !== false) {
                $hasAlias = true;
                $relationString = str_replace("|", "", $relationString);
            }

            try {
                $relationKeys = $model->getRelationKeysFor($relationString, $hasWhereClause);
                $model = $model->getRelatedModelFor($relationString);    
            } catch(Exception $e) {
                throw new \Exception("Error while fetching column for value key '$valueKey'. " . $e->getMessage());
            }

            $key1 = $relationKeys->key1;
            $key2 = $relationKeys->key2;
            
            $table = $model->getTableName();
            $aliasName = $table;

            if($table == $this->mainTable) {
                $aliasName      = "!!!!mainTable!!!!";

                //replace table name in other key
                $key2 = str_replace($table, $aliasName, $key2);
            }

            if($hasWhereClause) {
                //need to create alias if it has a where clause, so we can sort etc.
                $aliasName .= md5($whereClauseAlias);

                //replace table name in key
                
                //in the case that this table has a where clause AND is the main table
                //we need to change the alias of the key2 as well, to fit the new aliasName
                if($table == $this->mainTable) {
                    //if main table we only need to change key2
                    $key2 = str_replace("!!!!mainTable!!!!", $aliasName, $key2);        
                } else {
                    //if two different tables, replace any occurrence of table in any key
                    $key1 = str_replace($table, $aliasName, $key1);
                    $key2 = str_replace($table, $aliasName, $key2);
                }
            }

            if($aliasName == $table && $hasAlias) {
                $aliasName .= md5($this->valueKey);

                //replace table name in other key
                //preg_replace to ensure that it's changed only the name of the corresponding table
                $key1 = preg_match("!^({$table})\.!im", $key1)? str_replace($table, $aliasName, $key1):$key1;
                $key2 = preg_match("!^({$table})\.!im", $key2)? str_replace($table, $aliasName, $key2):$key2;
            }

            //the first key might need to use an alias name for its table
            $key1Table = strstr($key1, ".", true);
            $key1 = str_replace($key1Table, $this->getAliasForJoinedTable($key1Table), $key1);

            //the second key might need to use an alias name for its table
            $key2Table = strstr($key2, ".", true);
            $key2 = str_replace($key2Table, $this->getAliasForJoinedTable($key2Table), $key2);

            //save everything in join array
            $this->joinArray[] = array(
                "realTableName"     => $table,
                "aliasTableName"    => $aliasName,
                "keysForJoin"       => array($key1, $key2),
                "hasWhereClause"    => $hasWhereClause,
                "whereClauses"      => $whereClauses
            );          
        }

        //for backwards compatibility and shall be faster for standard tables
        $this->hasWhereClause = $hasWhereClause;
        if($this->hasWhereClause) {
            $this->whereClauses = $whereClauses;
        }

        $this->tableAliasName = $aliasName;
        $this->tableRealName  = $table;

        $this->keysForJoin = array($key1, $key2);
    }
}