<?php

namespace Designitgmbh\MonkeyTables\Http\Controllers;

use DB;

class oTablesFrameworkDBController
{
	protected $source;
	protected $modelNamespace = "App\\Models\\";

	private $DBColumns;
	private $selectClauses;

	public static function recursiveObjectGetter($obj, $string) {
		if(!$string)
			return null;

		//check if is SQL Command
		if(oTablesFrameworkDBControllerColumn::isSQLCmd($string)) {
			$name = "sql" . md5($string);
			return $obj->$name;
		}
		
		//check for recursion
		if(strpos($string, "->") === false && strpos($string, "()") === false) {
			return $obj->$string;
		} else {
			$parts = explode("->", $string);
		}

		foreach($parts as $part) {
			if(!is_object($obj))
				return null;

			//check for where clause
			if(strpos($part, "[") !== false && strpos($part, "]") !== false) {
				//extract condition
				$s1 = explode("[", $part);
				$s2 = explode("]", $s1[1]);

				$part = $s1[0];
				$condition = explode("=", $s2[0]);

				$conditionField = $condition[0];
				$conditionValue = $condition[1];

				//use condition
				$obj = $obj->$part()->where($conditionField, '=', $conditionValue)->first();
			} else {
				if((strpos($part, "()") !== false)) {
					$part = str_replace("()", "", $part);
					$obj = $obj->$part();
				} else {
					$obj = $obj->$part;	
				}
			}
		}

		return $obj;
	}

	public function init($source, $prefetch, $prejoin, $prefilter, $groupBy) {
		$this->source = $source;
		$this->prefetch = $prefetch;
		$this->prejoin = $prejoin;
		$this->prefilter = $prefilter;
		$this->groupBy = $groupBy;

		$this->rowCount = false;
		$this->joinedTables = array();
		$this->mainTable = null;

		$this->DBColumns = array();
		$this->selectClauses = [];
	}

	public function getRowsCount() {
		if($this->rowCount !== false)
			return $this->rowCount;

		$source = $this->modelNamespace . $this->source;
        $query = $source::with([]);
		$this->applyPrejoin($query);
		$this->applyPrefilter($query);
		return $query->count();
	}

	private function getDBColumnByValueKey($string) {
		$hash = md5($string);

		if(isset($this->DBColumns[$hash]))
			return $this->DBColumns[$hash];

		$source 		= $this->modelNamespace . $this->source;
		$model 			= new $source;
		$this->mainTable= $model->getTable();

		$this->DBColumns[$hash] = new oTablesFrameworkDBControllerColumn($source, $model, $this->mainTable, $string);

		return $this->DBColumns[$hash];
	}

	public function getFilterValuesForColumns($columns) {
		//need to cache this URGENTLY!
		$source 		= $this->modelNamespace . $this->source;
		$model 			= new $source;
		$this->mainTable= $model->getTable();

		foreach($columns as $key => $column) {
			if(!$column->hasAutoFilterValues()) {
				$column->setFilterValues([]);
				continue;
			}

			$DBColumn = $this->getDBColumnByValueKey($column->valueKey);

			if(!$DBColumn->isFetchable()) {
				$column
					->setSortable(false)
					->setFilterable(false);

				continue;
			}

			$query = DB::table($this->mainTable);

			$this->resetJoins();
			if($DBColumn->needsJoin()) {
				$this->joinTable($query, $DBColumn);
			}

			//prefilter
			$this->applyPrejoin($query);
			$this->applyPrefilter($query);

			$query = $query->select(array($DBColumn->getFieldName() . " as " . $key));
			$query = $query->distinct();

			$result = $query->take(100)->get();

			$values = array();
			foreach($result as $row) {
				if(($val = $row->$key) && $val != "")
					$values[$val] = $val;
			}

			$column->setFilterValues($values);
		}

		return true;
	}

	private function resetJoins() {
		$this->joinedTables = array();
	}

	private function joinTable(&$collection, $DBColumn) {

		foreach($DBColumn->getJoinArrayKeys() as $key) {
			$realTableName 	= $DBColumn->getTableRealName($key);
			$aliasTableName = $DBColumn->getTableAliasName($key);
			$fieldName 		= $DBColumn->getFieldName($key);
			$keysForJoin 	= $DBColumn->getKeysForJoin($key);

			if(!in_array($aliasTableName, $this->joinedTables)) {
				if($DBColumn->hasWhereClause($key)) {
					//add where clause to join
					$collection = $collection->leftJoin(
						$realTableName . " AS " . $aliasTableName, 
						function($join) use ($keysForJoin, $DBColumn, $aliasTableName, $key) {
							$join
								->on($keysForJoin[0], "=", $keysForJoin[1]);

							foreach($DBColumn->getWhereClauses($key) as $whereClause) {
								$field 		= $whereClause["field"];
								$operator 	= $whereClause["operator"];
								$value 		= $whereClause["value"];

								$join->where($aliasTableName . "." . $field, $operator, $value);
							}
						});
				} else {
					$collection = $collection->leftJoin($realTableName . " AS " . $aliasTableName, $keysForJoin[0], "=", $keysForJoin[1]);
				}
				$this->joinedTables[] = $aliasTableName;
			}
		}
	}

	public function getRows($filters) {
		$skipRows = (isset($filters['skipRows']) ? $filters['skipRows'] : 0);
		$resultsPerPage = (isset($filters['resultsPerPage']) ? $filters['resultsPerPage'] : 0);

		$this->resetJoins();

		$source 		= $this->modelNamespace . $this->source;
		$model 			= new $source;
		$this->mainTable= $model->getTable();
		$collection 	= $source::with($this->prefetch);

		/* PREFILTER */
		$this->applyPrejoin($collection);
		$this->applyPrefilter($collection);

		/* FILTERING */
		if(isset($filters['filter']) && is_array($filters['filter'])) {
			foreach($filters['filter'] as $filter) {
				$DBColumn 	= $this->getDBColumnByValueKey($filter['valueKey']);

				if(!$DBColumn->isFetchable())
					continue;

				if($DBColumn->needsJoin()) {
					$this->joinTable($collection, $DBColumn);
				}

				$fieldName  = $DBColumn->getFieldName();				
				$compare 	= $filter['compare'];
				$value 		= $filter['value'];

				$values 	= json_decode($value, true);

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

				$collection = $collection->where(function($query) use ($fieldName, $values, $compare) {
					foreach($values as $value) {
						$funcArr 	= array();
						$compareArr = array();
						$valueArr 	= array();

						switch($compare) {
							case("between"):
								$between = explode("|", $value);
								if($between[0]) {
									$funcArr[] 		= "where";
									$compareArr[] 	= ">=";
									$valueArr[] 	= $between[0];
								}
								if($between[1]) {
									$funcArr[] 		= "where";
									$compareArr[] 	= "<=";
									$valueArr[] 	= $between[1];
								}
								break;
							case("contains"):
								$funcArr[] 		= "where";
								$compareArr[] 	= "LIKE";
								$valueArr[]		= "%" . $value . "%";
								break;
							case("exists"):
								if($value == "true" || $value === true) {
									$funcArr[] 	= 'whereNotNull'; 
								} else {
									$funcArr[] 	= 'whereNull'; 
								}
								$compareArr[] = '';
								$valueArr[] = '';
								break;
							default:
								$funcArr[] 		= "where";
								$compareArr[] 	= $compare;
								$valueArr[] 	= $value;
								break;
						}

						$query = $query->orWhere(function($subquery) use($fieldName, $value, $compare, $compareArr, $funcArr, $valueArr) {
							foreach($compareArr as $key => $compare) {
								$function = $funcArr[$key];
								$value = $valueArr[$key];

								$subquery = $subquery->$function(
									DB::raw("LOWER(" . $fieldName . ")"), 
									$compare, 
									is_string($value) ? 
										DB::raw("LOWER('$value')") :
										$value
								);
							}
						});						
					}	
				});							
			}
		}

		/* QUICK SEARCH */
		if(isset($filters['quickSearch']) && is_array($filters['quickSearch'])) {
			$DBColumns = array();
			foreach($filters['quickSearch'] as $key => $filter) {
				$DBColumn = $this->getDBColumnByValueKey($filter['valueKey']);

				if(!$DBColumn->isFetchable())
					continue;

				if($DBColumn->needsJoin()) {
					$this->joinTable($collection, $DBColumn);
				}
				$DBColumns[$key] = $DBColumn;
			}

			$collection = $collection->where(function($collection) use($filters, $DBColumns) {
				foreach($filters['quickSearch'] as $key => $filter) {
					if(!isset($DBColumns[$key]))
						continue;

					$DBColumn 	= $DBColumns[$key];
					$compare 	= "LIKE";
					$value 		= "LOWER('%" . $filter['value'] . "%')";

					$collection->orWhere(
						DB::raw('LOWER( ' . $DBColumn->getFieldName() . ')'),
						$compare, 
						DB::raw($value)
					);
				}	
			});
		}

		/* TOTAL ROW COUNT */
		$countCollection = clone $collection;
		$this->rowCount = $countCollection->count();

		/* SORTING */
	
		//check if sorting is in this table or in a related table
		if(isset($filters['sorting'])) {
			$DBColumn = $this->getDBColumnByValueKey($filters['sorting']['valueKey']);

			if($DBColumn->isFetchable()) {

				if($DBColumn->needsJoin()) {
					$this->joinTable($collection, $DBColumn);
				}

				$collection = $collection->orderBy($DBColumn->getFieldName(), $filters['sorting']['direction']);
			}		
		}

		/* SELECT */
		if(isset($filters['select'])) {
			foreach($filters['select'] as $filter) {
				$DBColumn = $this->getDBColumnByValueKey($filter['valueKey']);

				if(!$DBColumn->isFetchable())
					continue;

				if($DBColumn->needsSelect()) {
					$this->selectClauses[] = $DBColumn->getSelectClause();
				}
			}
		}

		$this->selectClauses[] = $model->getTable().".*";
		$collection = $collection->select($this->selectClauses);

		/* GROUP */
		if(!$this->groupBy) {
			$this->groupBy = $model->getKeyName();
		}
		$collection = $collection->groupBy($this->groupBy);

		/* PAGINATION */
		if($skipRows)
			$collection = $collection->skip($skipRows);

		if($resultsPerPage)
			$collection = $collection->take($resultsPerPage);

		/*var_dump(null);
		var_dump($collection->toSql());
		//var_dump($collection->get());
		var_dump(DB::getQueryLog());
		$log = DB::getQueryLog();
		//echo $log[19]['query'];
		die("");//*/

		try {
			$returnSet = $collection->get();
		} catch(Exception $Err) {
			//try to reset the bindings and set them from the countCollection
			$collection->getQuery()->setBindings([], 'select');
			$collection->getQuery()->setBindings([], 'join');
			$collection->getQuery()->setBindings([], 'where');
			$collection->getQuery()->setBindings([], 'having');
			$collection->getQuery()->setBindings([], 'order');			
			$collection->getQuery()->mergeBindings($countCollection->getQuery());
			$returnSet = $collection->get();
		}

		return $returnSet;
	}

	/**
	 * Joins the query if needed for prefiltering
	 * @return void
	 */
	protected function applyPrejoin(&$query) {
		if(count($this->prejoin)>0) {
			foreach($this->prejoin as $prejoin) {
				$DBColumn 	= $this->getDBColumnByValueKey($prejoin);

				if(!$DBColumn->isFetchable())
					continue;

				if($DBColumn->needsJoin()) {
					$this->joinTable($query, $DBColumn);
				}		
			}
		}
		
	}

	/**
	 * Applies prefilter conditions to a query
	 * @return void
	 **/
	protected function applyPrefilter(&$query) {
		if(count($this->prefilter)>0) {
			foreach($this->prefilter as $prefilter) {
				$query->where($prefilter["field"], $prefilter["operator"], $prefilter["value"]);
			}
		}
	}
}





class oTablesFrameworkDBControllerColumn {
	private $isFetchable;

	private $source;
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

	public function __construct($source, $model, $mainTable, $valueKey) {
		$this->source 		= $source;
		$this->model 		= $model;
		$this->mainTable 	= $mainTable;
		$this->valueKey 	= $valueKey;

		$this->fieldName 		= null;
		$this->tableRealName 	= null;
		$this->tableAliasName 	= null;

		$this->needsSelect 		= false;
		$this->selectClause 	= "";
		
		$this->needsJoin 		= false;
		$this->joins 			= array();
		$this->keysForJoin 		= null;
		$this->hasWhereClause 	= false;
		$this->whereClauses = null;

		$this->joinArray 		= [];

		$this->evalFieldName();
	}

	public static function isSQLCmd($valueKey) {
		$hasParenthesis = strpos($valueKey,"(") 
							&& strpos($valueKey,")");
		$hasComma 		= strpos($valueKey,",");
		$hasSQLCaseCmd 	= stripos($valueKey,"CASE") 
							&& stripos($valueKey,"WHEN") 
							&& stripos($valueKey,"THEN") 
							&& stripos($valueKey,"END");

		return($hasParenthesis && ($hasComma || $hasSQLCaseCmd));
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
		if( strpos($this->valueKey,"()") ) {
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
		if( strpos($this->valueKey,"->") ) {
			//join
			$this->needsJoin = true;
			$this->fieldName = $this->evalJoinFieldName();
		} else {
			//no join
			$this->fieldRealName 	= $this->valueKey;
			$this->fieldName 		= $this->mainTable . "." . $this->valueKey;
		}
	}

	private function evalJoinFieldName() {
		$this->evalKeysForJoin();

		return $this->tableAliasName . "." . $this->fieldRealName;
	}

	private function evalKeysForJoin() {
		$model = $this->model;

		$relations = explode("->", $this->valueKey);
		$relationCount = count($relations);

		$this->fieldRealName = array_pop($relations);

		foreach($relations as $relationKey => $relationString) {
			//set defaults
			$hasWhereClause 	= false;
			$whereClauseAlias 	= "";
			$whereClauses 		= [];

			//extract where clause
			if(strpos($relationString, "[") && $str = explode("[", $relationString)) {
				$relationString 	= $str[0];
				$str 				= explode("]", $str[1]);
				$whereClause 		= $str[0];

				foreach(explode(",", $whereClause) as $clause) {
					if(strpos($clause, "!=")) {
						$operator 		= "!=";
					} else {
						$operator 		= "=";
					}

					$condition 			= explode($operator, $clause);

					$hasWhereClause 	= true;

					$clauseArray 		= [
						"field" 	=> $condition[0],
						"operator" 	=> $operator,
						"value" 	=> $condition[1]
					];

					$whereClauses[] 	= $clauseArray;
					$whereClauseAlias  .= join("", $clauseArray);
				}
			}

			//set relation and keys
			$relation = $model->$relationString();

			//TODO
				//change this, so that key1 & key2 are selected based on relation type
				//see the "hasOne" comment a bit down
			if($hasWhereClause) {
				$key1 = $relation->getForeignKey();

				if(method_exists($relation, "getQualifiedParentKeyName")) {
					$key2 = $relation->getQualifiedParentKeyName();
				} else if(method_exists($relation, "getOtherKey")) {
					$key2 = $relation->getOtherKey();
				} else {
					return "ERROR GETTING SECOND KEY";
				}

			} else {
				if(method_exists($relation, "getQualifiedForeignKey") && method_exists($relation, "getQualifiedOtherKeyName")) {
					$key1 = $relation->getQualifiedForeignKey();	
					$key2 = $relation->getQualifiedOtherKeyName();
				} else {
					//this works for "hasOne" relations...if it is not a "hasOne" relation, it might not!
						//in case ever other relations will be used here, we need to "if-else" them and execute the appropriate functions to get the keys
						//see API:
						// http://laravel.com/api/4.2/Illuminate/Database/Eloquent/Relations.html
						// http://laravel.com/api/4.2/Illuminate/Database/Eloquent/Relations/HasOne.html
					$key1 = $relation->getForeignKey();
					$key2 = $relation->getQualifiedParentKeyName();
				}
			}

			//set tableName and alias
			$table 		= $relation->getRelated()->getTable();
			$model		= $relation->getRelated();
			$aliasName 	= $table;

			if($table == $this->mainTable) {
				$aliasName 		= "!!!!mainTable!!!!";

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

			//the first key might need to use an alias name for its table
			$key1Table = strstr($key1, ".", true);
			$key1 = str_replace($key1Table, $this->getAliasForJoinedTable($key1Table), $key1);

			//the second key might need to use an alias name for its table
			$key2Table = strstr($key2, ".", true);
			$key2 = str_replace($key2Table, $this->getAliasForJoinedTable($key2Table), $key2);

			//save everything in join array
			$this->joinArray[] = array(
				"realTableName" 	=> $table,
				"aliasTableName"	=> $aliasName,
				"keysForJoin" 		=> array($key1, $key2),
				"hasWhereClause" 	=> $hasWhereClause,
				"whereClauses" 		=> $whereClauses
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




?>