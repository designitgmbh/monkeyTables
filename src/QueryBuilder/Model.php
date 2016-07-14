<?php
namespace Designitgmbh\MonkeyTables\QueryBuilder;

class Model
{
    private 
        $model,
        $realTableName,
        $asTableName;

    public function __construct($model) {
        $this->model = $model;

        $this->handleTableAsStatement();
    }
    
    public function getTableName() {
        return $this->asTableName;
    }

    public function getRealTableName() {
        return $this->realTableName;
    }

    public function getKeyName() {
        return $this->model->getKeyName();
    }

    private function handleTableAsStatement() {
        $modelTable = $this->model->getTable();
        $modelTableAs = explode(' as ', $modelTable);

        if(count($modelTableAs) > 1) {
            $this->realTableName = $modelTableAs[0];
            $this->asTableName = $modelTableAs[1];
        } else {
            $this->realTableName = $modelTable;
            $this->asTableName = $modelTable;
        }
    }

    public function getRelationKeysFor($relationString, $hasWhereClause = false) {
        $relation = $this->getRelation($relationString);

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

        $this->fixTableAsForModelKey($key1);

        return (object)[
            "key1" => $this->handelTableAsKey($key1),
            "key2" => $this->handelTableAsKey($key2),
        ];
    }

    private function fixTableAsForModelKey(&$key) {
        $modelClass = $this->getShortModelClass();
        $modelClassSnake = snake_case($modelClass);

        $key = str_replace($modelClassSnake, $this->getTableName(), $key);
    }

    private function handelTableAsKey($key) {
        $keyAs = explode(' as ', $key);
        if(count($keyAs) > 1) {
            $key = $keyAs[1];
        }

        return $key;
    }

    public function getRelatedModelFor($relationString) {
        $relation = $this->getRelation($relationString);
        return new self($relation->getRelated());
    }

    public function getRelation($relationString) {
        $this->hasRelation($relationString);
        return $this->model->$relationString();
    }

    private function hasRelation($relationString) {
        if(!method_exists($this->model, $relationString)) {
            $modelClass = $this->getModelClass();
            throw new \Exception("Relation $relationString could not be fetched from $modelClass. Probably related object does not exist or is morphed.");
        }

        return true;
    }

    public function getShortModelClass() {
        return (new \ReflectionClass($this->model))->getShortName();
    }

    private function getModelClass() {
        return get_class($this->model);
    }
}