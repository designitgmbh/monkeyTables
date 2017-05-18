<?php
    namespace Designitgmbh\MonkeyTables\Table;

    use Designitgmbh\MonkeyTables\QueryBuilder\QueryBuilder;

    /**
     * Extending oTable to support hierarchies
     *
     * @package    MonkeyTables
     * @author     Philipp Pajak <p.pajak@design-it.de>
     * @license    https://raw.githubusercontent.com/designitgmbh/monkeyTables/master/LICENSE  BSD
     */
class oTableHierarchy extends oTable
{
    const ROOT_LEVEL = "root";
    protected $oTableRowClassName = __NAMESPACE__ . "\\" . "oTableHierarchyRow";
    protected $levels;

    public function __construct($name = "")
    {
        parent::__construct($name);

        $this->levels = [];
    }

    /**
         * overwrite createQuickSearchFilters function
         *
         * @param &$filters         Array filter
         */
    protected function createQuickSearchFilters(&$filters)
    {
        $quickSearchArray = [];

        if ($this->quickSearchString) {
            foreach ($this->columns as $column) {
                if (!$column->isLevel(oTableHierarchy::ROOT_LEVEL)) {
                    continue;
                }

                $valueKey = $column->getValueKey();
                if (!$valueKey) {
                    continue;
                }

                $quickSearchArray[] = [
                    "valueKey"  => $column->getValueKey(),
                    "value"         => $this->quickSearchString
                ];
            }
            $filters['quickSearch'] = $quickSearchArray;
        }

        return $this;
    }

    /**
         * extend getOptionsForObject function
         *
         * @param $obj          The object to retrieve options for
         * @param $level        The level of the object
         */
    protected function getOptionsForObject($obj, $level = null)
    {
        $options = parent::getOptionsForObject($obj);

        if ($level != null) {
            $options["level"] = array_search($level, $this->levels);
        }

        return $options;
    }

    /**
         * overwrite render rows function
         *
         * @param $dataSet      The dataSet to be used or null
         * @param $levels       The levels to be used or null
         */
    protected function renderRows($dataSet = null, $levels = null)
    {
        if ($dataSet == null) {
            $dataSet = $this->dataSet;
        }

        if ($levels == null) {
            $levels = $this->levels;
        }

        $tableOptions = $this->getOptionsForTable();
        $level = array_shift($levels);

        foreach ($dataSet as $obj) {
            $this->renderLevelRow($obj, $tableOptions, $level);

            if (!empty($levels)) {
                $children = $this->getDataSetForSubLevel($obj, $levels[0]);
                $this->renderRows($children, $levels);
            }
        }
    }

    private function getDataSetForSubLevel($obj, $level)
    {
        return QueryBuilder::recursiveObjectGetter($obj, $level);
    }

    private function renderLevelRow($obj, $tableOptions, $level)
    {
        $options = array_merge(
            $tableOptions,
            $this->getOptionsForObject($obj, $level)
        );

        $this->rows[] = $this->rowController->renderRow($obj, $options, $level);
    }

    /**
         * add level
         *
         * @param $level    value key of the level
         */
    private function addLevel($level)
    {
        $this->levels[] = $level;

        return $this;
    }


    /* public functions */

    /**
         * add hierarchies with columns as array
         *
         * @param $level    The hierarchy level to be used
         * @param $columns  The columns to be added
         */
    public function addHierarchies($hierarchies)
    {
        foreach ($hierarchies as $level => $hierarchy) {
            $this->addHierarchy($level, $hierarchy);
        }
    }

    /**
         * add hierarchy with columns
         *
         * @param $level    The hierarchy level to be used
         * @param $columns  The columns to be added
         */
    public function addHierarchy($level, $columns)
    {
        if (!is_array($columns)) {
            $columns = [$columns];
        }

        foreach ($columns as $column) {
            $this->addHierarchyColumn($level, $column);
        }

        $this->addLevel($level);

        return $this;
    }

    /**
         * add column for level
         *
         * @param $level    The hierarchy level to be used
         * @param $columns  The columns to be added
         */
    public function addHierarchyColumn($level, $column)
    {
        $column->setLevel($level);

        $this->add($column);
    }

    /**
         * overwrite the displayed row count, so child rows are not counted
         *
         * @return int  row count
         */
    protected function getDisplayedRowCount()
    {
        $count = 0;

        foreach ($this->rows as $row) {
            if ($row['OPTIONS'] && $row['OPTIONS']['level'] == 0) {
                $count++;
            }
        }

        return $count;
    }
}
