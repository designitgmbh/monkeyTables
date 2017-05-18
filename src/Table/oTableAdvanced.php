<?php
    namespace Designitgmbh\MonkeyTables\Table;

    /**
     * This class adds some advanced prefiltering options to oTables.
     *
     * @package    MonkeyTables
     * @author     Jorge Sosa <j.sosa@design-it.de>
     * @license    https://raw.githubusercontent.com/designitgmbh/monkeyTables/master/LICENSE  BSD
     */
class oTableAdvanced extends oTable
{
    public function orPrefilter($field, $operator, $value, $relation = null)
    {

        array_push($this->prefilter, [
            [
                "field" => $field,
                "operator" => $operator,
                "value" => $value,
                "relation" => $relation
            ]
        ]);
        return $this;
    }
}
