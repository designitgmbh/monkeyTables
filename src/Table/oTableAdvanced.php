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
        public function prefilter($field, $operator, $value, $relation = null, $boolOp = 'and') {
            if ($boolOp === 'and') {
                if (empty($this->prefilter)) {
                    array_push($this->prefilter, []);
                }
                array_push($this->prefilter[count($this->prefilter) - 1], [
                        "field" => $field,
                        "operator" => $operator,
                        "value" => $value,
                        "relation" => $relation
                    ]
                );
            } elseif ($boolOp === 'or') {
                array_push($this->prefilter, [
                    [
                        "field" => $field,
                        "operator" => $operator,
                        "value" => $value,
                        "relation" => $relation
                    ]
                ]);
            } else {
                throw new Exception('Invalid boolean operation');
            }
            return $this;
        }

        public function orPrefilter($field, $operator, $value, $relation = null) {
            return $this->prefilter($field, $operator, $value, $relation, 'or');
        }
    }