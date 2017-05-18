<?php
namespace Designitgmbh\MonkeyTables\QueryBuilder;

class Statement
{
    public function __construct($query)
    {
        $this->statement = $this->setStatementFromQuery($query);
    }

    private function setStatementFromQuery($query)
    {
        $query = $query->getQuery()->getQuery();
        $this->connection = $query->getConnection();

        $sqlQuery = $query->toSql();
        $pdo = $this->connection->getReadPdo();
        $statement = $pdo->prepare($sqlQuery);

        return $statement;
    }

    public function fetchResults($query)
    {
        $query = $query->getQuery();
        $bindings = $query->getBindings();

        //execute statement
        $this->statement->execute($this->connection->prepareBindings($bindings));
        $results = $this->statement->fetchAll($this->connection->getFetchMode());

        //bind results to model
        $model = $query->getModel();
        //$connectionName = $model->getConnectionName();
        $models = $model->hydrate($results, $this->connection)->all();
        // If we actually found models we will also eager load any relationships that
        // have been specified as needing to be eager loaded, which will solve the
        // n+1 query issue for the developers to avoid running a lot of queries.
        if (count($models) > 0) {
            $models = $query->eagerLoadRelations($models);
        }
        
        return $model->newCollection($models);
    }
}
