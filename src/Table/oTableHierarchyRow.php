<?php
    namespace Designitgmbh\MonkeyTables\Table;

    /**
     * Extending oTableRow to support hierarchies
     *
     * @package    MonkeyTables
     * @author     Philipp Pajak <p.pajak@design-it.de>
     * @license    https://raw.githubusercontent.com/designitgmbh/monkeyTables/master/LICENSE  BSD
     */
class oTableHierarchyRow extends oTableRow
{
    /**
         * overwrite render row function
         *      adding a parameter to switch between levels
         *
         * @param $obj          The object to be rendered
         * @param $options      Options for the rendered row
         * @param $level        Level of the rendered row
         *
         * @return array        Rendered row
         */
    public function renderRow($obj, $options = null, $level = null)
    {
        $row = $this->generateDefaultRow($options);
            
        foreach ($this->columns as $column) {
            if ($column->isEnabled() && $column->isLevel($level)) {
                $row["DATA"][] = $column->render($obj);
            }
        }

        return $row;
    }

    /**
         * overwrite render header function
         *      ignoring columns that are not in root level
         *
         * @param $options      Options for the rendered header row
         *
         * @return array        Rendered header row
         */
    public function renderHeader($options)
    {
        $row = array();

        foreach ($this->columns as $column) {
            if ($column->isLevel(oTableHierarchy::ROOT_LEVEL)) {
                $row[] = $column->renderHeader();
            }
        }

        return $row;
    }
}
