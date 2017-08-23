<?php
    namespace Designitgmbh\MonkeyTables\Table;

    use Designitgmbh\MonkeyTables\Data\oData;

    use Designitgmbh\MonkeyTables\QueryBuilder\QueryBuilder;
    use Designitgmbh\MonkeyTables\Http\Controllers\oTablesFrameworkHelperController;

    /**
     * A basic class that represents a table
     *
     * @package    MonkeyTables
     * @author     Philipp Pajak <p.pajak@design-it.de>
     * @license    https://raw.githubusercontent.com/designitgmbh/monkeyTables/master/LICENSE  BSD
     */
class oTable extends oData
{
    protected $oTableRowClassName = __NAMESPACE__ . "\\" . "oTableRow";
    protected $columns;
    protected $quickSearchString;

    public function __construct($name = "")
    {
        parent::__construct($name);
            
        $this->columns  = array();
        $this->request  = null;
        $this->rows     = array();
        $this->exportType = null;
    }

    // setter functions //
    public function setRequest($request)
    {
        $this->request = $request;

        return $this;
    }

    public function setRowClass($class)
    {
        $this->rowClass = $class;

        return $this;
    }

    public function setRowIDValueKey($valueKey)
    {
        $this->rowIDValueKey = $valueKey;

        return $this;
    }

    public function addStatusBar($var, $arr)
    {
        if (is_callable($var)) {
            //custom function
            $callback = function ($obj) use ($var) {
                return $var($obj);
            };
        } else {
            //map function
            $callback = function ($obj) use ($var, $arr) {
                $value = $obj->$var;

                if (isset($arr[$value])) {
                    return $arr[$value];
                }
                return null;
            };
        }
        $this->statusBarFunction = $callback;

        return $this;
    }

    public function standardRowColor($color)
    {
        if (is_callable($color)) {
            $this->standardRowColorFunction = $color;
        } else {
            $this->standardRowColorFunction = function () {
                return null;
            };
        }

        return $this;
    }

    //private functions //
    protected function parseRequest()
    {
        parent::parseRequest();

        //TODO
        //  parse the request so that we fullfill the oTables2 specifications
        //      means: calculation fixes etc, that in future might be already done by the client, do them here!

        if (!isset($this->request['page'])) {
            $this->request['page'] = 0;
        }
        if (!isset($this->request['resultsPerPage'])) {
            $this->request['resultsPerPage'] = 0;
        }
        if (!isset($this->request['paginationType'])) {
            $this->request['paginationType'] = 'FULL';
        }

        $resultsPerPage = intval($this->request['resultsPerPage']);
        if ($resultsPerPage > 100) {
            $this->resultsPerPage = 100;
        } elseif ($resultsPerPage > 0) {
            $this->resultsPerPage = $resultsPerPage;
        } else {
            $this->resultsPerPage = 20;
        }

        $this->page             = (intval($this->request['page']) > 0) ? intval($this->request['page']) : 0;
        $this->skipRows         = $this->page * $this->resultsPerPage;
        $this->totalCountType   = self::TOTAL_COUNT_TYPE_JSON[$this->request['paginationType']];

        //sorting
        $this->sorting = (isset($this->request['sorting']) && $this->request['sorting'] != '') ? $this->request['sorting'] : array();

        //quickSearch
        $this->parseQuickSearch(isset($this->request['quickSearch']) ? $this->request['quickSearch'] : array());

        if (isset($this->request['trashed'])) {
            $this->prefilter('!trashed');
        }

        //export type
        $this->exportType = isset($this->request['exportType']) ? $this->request['exportType'] : null;
        $this->exportFilename = isset($this->request['exportFilename']) ? $this->request['exportFilename'] : null;

        //presets
        if ($this->presetHandler) {
            $this->handlePreset();
        }

        //caching
            //TODO
            //if caching for this table is enabled..
                //take request, create hash
                //check in cache if this request is already cached
                    //if cached -> check if it is not outdated
    }

    private function handlePreset()
    {
        $this->presetCanModify = false;

        if ($this->presetHandler->isDeleteAction()) {
            $this->presetHandler
                ->deletePresetSetting("filter")
                ->deletePresetSetting("sorting")
                ->deletePresetSetting("resultsPerPage")
                ->deletePresetSetting("columnArrangement")
                ->deletePresetSetting("hiddenColumns")
                ->deletePresetSetting(":hash");

            $this->filter = array();
            $this->sorting = array();
            $this->resultsPerPage = 20;
            $this->columnArrangement = null;
            $this->hiddenColumns = null;
        }

        if ($this->presetHandler->isLoadAction()) {
            $this->filter               = $this->presetHandler->loadPresetSetting("filter");
            $this->sorting              = $this->presetHandler->loadPresetSetting("sorting");
            $this->resultsPerPage       = $this->presetHandler->loadPresetSetting("resultsPerPage");
            $this->columnArrangement    = $this->presetHandler->loadPresetSetting("columnArrangement");
            $this->hiddenColumns        = $this->presetHandler->loadPresetSetting("hiddenColumns");

            if ($this->filter == null) {
                $this->filter = array();
            }

            if ($this->sorting == null) {
                $this->sorting = array();
            }

            if ($this->resultsPerPage == null) {
                $this->resultsPerPage = 20;
            }
        }

        $presetHash = md5(
            json_encode($this->filter).
            json_encode($this->sorting).
            json_encode($this->resultsPerPage).
            json_encode($this->columnArrangement).
            json_encode($this->hiddenColumns)
        );

        if ($this->presetHandler->isSaveAction()) {
            $this->presetHandler
                ->savePresetSetting("filter", $this->filter)
                ->savePresetSetting("sorting", $this->sorting)
                ->savePresetSetting("resultsPerPage", $this->resultsPerPage)
                ->savePresetSetting("columnArrangement", $this->columnArrangement)
                ->savePresetSetting("hiddenColumns", $this->hiddenColumns)
                ->savePresetSetting(":hash", $presetHash);
        }

        if ($this->presetHandler->isPresetActive()) {
            $preset = $this->presetHandler->getPreset();
            $this->activePresetId   = $preset->getId();

            if ($preset->isGeneral()) {
                $this->presetCanModify = oTablesFrameworkHelperController::canModifyGeneralPresets();
            } else {
                $this->presetCanModify = true;
            }

            if ($this->activePresetId == "default") {
                $this->presetIsModified = (
                    md5(json_encode(array()).json_encode(array()).json_encode(20).json_encode(null).json_encode(null)) != $presetHash
                );
            } else {
                $this->presetIsModified = ($this->presetHandler->loadPresetSetting(":hash") != $presetHash);
            }
        } else {
            $this->activePresetId = null;
            $this->presetIsModified = (
                md5(json_encode(array()).json_encode(array()).json_encode(20).json_encode(null).json_encode(null)) != $presetHash
            );
        }

        $this->presetList = $this->presetHandler->getPresetList();
    }

    private function parseQuickSearch($quickSearch)
    {
        /*
            enabled:        false,
            searchString:   ""
        */

        if (!is_array($quickSearch)) {
            return false;
        }

        if (isset($quickSearch['enabled']) && $quickSearch['enabled'] && $quickSearch['enabled'] != "false") {
            $this->quickSearchString = $quickSearch['searchString'];
        } else {
            $this->quickSearchString = false;
        }
    }

    protected function createFilters()
    {
        $filters = [];

        $this
            ->createSelectFilters($filters)
            ->createPaginationFilters($filters)
            ->createSortingFilters($filters)
            ->createFilteringFilters($filters)
            ->createQuickSearchFilters($filters)
            ->createSumFilters($filters);;
            
        return $filters;
    }

    protected function createPaginationFilters(&$filters)
    {
        $filters["resultsPerPage"]  = $this->resultsPerPage;
        $filters["skipRows"]        = $this->skipRows;

        return $this;
    }

    protected function createSortingFilters(&$filters)
    {
        if ($this->sorting) {
            $columnNumber = $this->sorting['column'];

            if (isset($this->columns[$columnNumber])) {
                $column = $this->columns[$columnNumber];

                if ($column->isSortable()) {
                    $sorting = [
                        "valueKey" => $column->getValueKey(),
                        "direction"=> $this->sorting['direction']
                    ];
                    $filters['sorting'] = $sorting;
                }
            }
        }

        return $this;
    }

    protected function createFilteringFilters(&$filters)
    {
        $filterArray = [];

        if (isset($this->filter) && is_array($this->filter)) {
            foreach ($this->filter as $filter) {
                if ($filter['value'] != "noFilter") {
                    $columnNumber = $filter["column"];

                    if (!isset($this->columns[$columnNumber])) {
                        continue;
                    }

                    $column = $this->columns[$columnNumber];
                    $filterArray[] = [
                        "valueKey"  => $column->getValueKey(),
                        "compare"   => $filter['compare'] ? : '=',
                        "value"         => $filter['value']
                    ];

                    $column
                        ->setCurrentFilterValue($filter['value'])
                        ->setCurrentFilterMethod($filter['compare']);
                }
            }
            $filters['filter'] = $filterArray;
        }

        return $this;
    }

    protected function createQuickSearchFilters(&$filters)
    {
        $quickSearchArray = [];

        if ($this->quickSearchString) {
            foreach ($this->columns as $column) {
                $valueKey = $column->getValueKey();
                if (!$valueKey) {
                    continue;
                }

                $quickSearchArray[] = [
                    "valueKey"  => $valueKey,
                    "value"         => $this->quickSearchString
                ];
            }
            $filters['quickSearch'] = $quickSearchArray;
        }

        return $this;
    }

    protected function createSelectFilters(&$filters)
    {
        $selectArray = [];

        foreach ($this->columns as $column) {
            if (!$column->isEnabled()) {
                continue;
            }

            $valueKey = $column->getValueKey();
            if (!$valueKey) {
                continue;
            }

            $selectArray[] = [
                "valueKey" => $valueKey
            ];
        }

        $filters['select'] = $selectArray;

        return $this;
    }

    protected function createSumFilters(&$filters) {
        $sumArray = [];

        foreach($this->columns as $column) {
            if($column->getType() != 'number') {
                continue;
            }

            $valueKey = $column->getValueKey();
            if(!$valueKey) {
                continue;
            }

            $sumArray[] = [
                "chainNumber" => $column->getChainNumber(),
                "valueKey" => $valueKey
            ];
        }

        $filters['sum'] = $sumArray;

        return $this;
    }

    private function rearrangeColumns()
    {
        //VISIBILITY
        //  array of numbers, which represent the column number
        //      no/empty array means display all otherwise do not display columns that are in the array
        //ORDER
        //  array of numbers, which represent the column number
        //      new order according to array order

        if (is_array($this->hiddenColumns) && count($this->hiddenColumns) > 0) {
            foreach ($this->hiddenColumns as $columnNumber) {
                if (isset($this->columns[$columnNumber])) {
                    $this->columns[$columnNumber]->disable();
                }
            }
        }

        if (is_array($this->columnArrangement)) {
            $columns = $this->columns;
            $this->columns = array();
            foreach ($this->columnArrangement as $key => $value) {
                if (isset($columns[$value])) {
                    $columns[$value]->setChainNumber($value);
                    $this->columns[$value] = $columns[$value];
                    unset($columns[$value]);
                }
            }
            foreach ($columns as $key => $column) {
                $column->setChainNumber($key);
                $this->columns[$key] = $columns[$key];
            }
        } else {
            foreach ($this->columns as $key => $column) {
                $column->setChainNumber($key);
            }
        }

        return true;
    }

    private function renderHeader()
    {
        $this->DBController->getFilterValuesForColumns($this->columns);
        //evaluate options
        //todo
        $options = array();
        $this->header = $this->rowController->renderHeader($options);
    }

    protected function getOptionsForTable()
    {
        $options = [];
            
        if (isset($this->rowClass)) {
            $options["CLASS"]   = $this->rowClass;
        }

        return $options;
    }

    protected function getOptionsForObject($obj)
    {
        $options = [];

        if (isset($this->rowIDValueKey)) {
            $key                = $this->rowIDValueKey;
            $options["ID"]      = $obj->$key;
        }

        if (isset($this->statusBarFunction) && is_callable($this->statusBarFunction) && ($func = $this->statusBarFunction)) {
            $options["STATUSBAR"] = $func($obj);
        }

        return $options;
    }

    protected function renderRows()
    {
        $tableOptions = $this->getOptionsForTable();

        foreach ($this->dataSet as $obj) {
            $options = array_merge(
                $tableOptions,
                $this->getOptionsForObject($obj)
            );
                
            $this->rows[] = $this->rowController->renderRow($obj, $options);
            unset($obj);
        }
    }

    protected function getDisplayedRowCount()
    {
        return count($this->rows);
    }

    protected function isShowAll()
    {
        return $this->totalCountType == self::TOTAL_COUNT_TYPE_SHOW_ALL;
    }

    private function toArray()
    {
        //format needed for current JS Client
            #general information
            #header
            #rows

        /*
        CANSAVEGENERALPRESETS: false
        GENERALPRESETS: null
        HASSTRIPES: null
        ISGENERAL: false
        ISSELECTABLE: null
        PAGES: 0
        PRESET: "default"
        PRESETLIST: null
        RESULTSPERPAGE: "20"
        ROWS: 6*/

        $displayedRowCount = $this->getDisplayedRowCount();

        if ($displayedRowCount > 0) {
            $pages = (int) floor($this->rowsCount / $this->resultsPerPage)
                        -
                     ( (($this->rowsCount % $this->resultsPerPage) == 0)? 1 : 0 );
        } else {
            $pages = 0;
        }

        if ($pages === 0) {
            $this->totalCountType = self::TOTAL_COUNT_TYPE_FULL;
        }

        $generalInformation = array(
            "ROWS" => $displayedRowCount,
            "PAGES" => $this->isShowAll() ? 0 : $pages,
            "SORTING" => $this->sorting,
            "RESULTSPERPAGE" => $this->isShowAll() ? $displayedRowCount : $this->resultsPerPage,
            "TOTALRESULTS" => $this->rowsCount,
            "PAGINATIONTYPE" => array_search($this->totalCountType, self::TOTAL_COUNT_TYPE_JSON),
            "PRESETID" => $this->activePresetId,
            "PRESETISMODIFIED" => $this->presetIsModified,
            "PRESETCANMODIFY" => $this->presetCanModify,
            "PRESETDEFAULTACTIVE" => $this->presetHandler->isDefaultPresetActive(),
            "PRESETGENERALDEFAULTACTIVE" => $this->presetHandler->isGeneralDefaultPresetActive(),
            "PRESETLIST" => $this->presetList
        );

        return array_merge(
            array(
                $generalInformation,
                $this->header
            ),
            $this->rows
        );
    }

    private function exportAsSYLK($exportOptions = array())
    {
        $this->rowController->renderSYLKHeader();
        foreach ($this->dataSet as $obj) {
            $this->rowController->renderSYLKRow($obj);
        }

        $sylkFileContent = $this->rowController->renderSYLK();

        if (isset($this->exportFilename)) {
            $filename = $this->exportFilename;
        } else {
            $filename = "export";
        }

        header('Content-Description: File Transfer');
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename='.$filename.'.slk');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        echo utf8_encode($sylkFileContent);
        die("");
    }

    private function exportAsCSV($exportOptions = array())
    {
        $options = array("A" => null);

        //add csv for header
        $csv = $this->rowController->renderCSVHeader($options);

        //add csv for rows
        foreach ($this->dataSet as $obj) {
            $csv .= $this->rowController->renderCSVRow($obj, $options);
            unset($obj);
        }

        if (isset($this->exportFilename)) {
            $filename = $this->exportFilename;
        } else {
            $filename = "export";
        }

        header('Content-Description: File Transfer');
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename='.$filename.'.csv');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        echo utf8_decode($csv);
        die("");
    }

    private function export()
    {
        return $this->toArray();
    }

    private function exportTotals() {
        return $this->dataSet;
    }


    // class functions //
    public function add($column)
    {
        $id = $column->getUniqueIdentifier();

        if (isset($this->columns[$id])) {
            throw new \Exception("Column with value key '{$column->getValueKey()}' already exists. Please change value key, and make sure you do not create duplicate columns. Maybe you tried to change the content of the column with the alterDisplayValue function, be aware that this can only be used to make small adjustments but must not be used to load different data.");
        }

        $this->columns[$id] = $column;

        return $this;
    }

    public function render()
    {
        $this->DBController     = new QueryBuilder;
        $this->helpController   = new oTablesFrameworkHelperController;

        //assign helpController to all columns
        foreach ($this->columns as $column) {
            $column->setFrameworkHelper($this->helpController);
        }

        $this->parseRequest();

        $this->rearrangeColumns();

        //if no dataSet available..
            $this->prepareDBController();
        
        $this->rowController = new $this->oTableRowClassName($this->columns);

        if($this->dataMode === self::DATA_MODE_TOTALS) {
            return $this->exportTotals();
        }

        switch ($this->exportType) {
            case ("sylk"):
                return $this->exportAsSYLK();
            case ("csv"):
                return $this->exportAsCSV();
            default:
                $this->renderHeader();
                $this->renderRows();

                return $this->export();
        }
    }
}
