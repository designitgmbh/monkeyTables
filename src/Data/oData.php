<?php
    namespace Designitgmbh\MonkeyTables\Data;

    /**
     * A basic abstract class that represents a filterable DataSet defined by a Entity source.
     * It supports prefetching, prefiltering and grouping.
     *
     * @package    MonkeyTables
     * @author     Philipp Pajak <p.pajak@design-it.de>
     * @license    https://raw.githubusercontent.com/designitgmbh/monkeyTables/master/LICENSE  BSD
     */
class oData
{
    /**
         * Name of the data set
         *
         * @var string
         */
    protected $name;

    /**
         * Source of the data set; an entity
         *
         * @var string
         */
    protected $source;

    /**
         * The total count type
         * This can be used to declare how the data should be counted
         *
         * @var integer
         */
    protected $totalCountType = self::TOTAL_COUNT_TYPE_FULL;

    const
    TOTAL_COUNT_TYPE_NONE       = 0,
    TOTAL_COUNT_TYPE_FULL       = 1,
    TOTAL_COUNT_TYPE_NEXT_PAGE  = 2,
    TOTAL_COUNT_TYPE_SHOW_ALL   = 3,
    TOTAL_COUNT_TYPE_JSON       = [
        'NONE'      => 0,
        'FULL'      => 1,
        'NEXT_PAGE' => 2,
        'SHOW_ALL'  => 3,
    ];

    protected /**
             * Array of entities to that have to be prefetched
             *
             * @var array
             */
    $prefetch,
    /**
             * Array of filters that are applied before fetching
             *
             * @var array
             */
    $prefilter,
    /**
             * A group by directive for the database fetching
             *
             * @var string
             */
    $groupBy,
    /**
             * An array of objects containing the result
             *
             * @var array
             */
    $dataSet,
    /**
             * An array of strings containing the select options for the DB request
             *
             * @var array
             */
    $select;

    public function __construct($name = "")
    {
        $this->prefetch = array();
        $this->prejoin  = array();
        $this->prefilter= array();
        $this->groupBy  = null;
        $this->name     = $name;

        $this->dataSet  = [];

        $this->presetHandler = null;
        $this->select = null;

        if (empty($name) && config('monkeyTables.general.dataSet.requireName')) {
            throw new \Exception("It is not possible to create data sets without name per configuration. Please ensure that your call to oTable or oReport includes a unique name parameter.");
        }
    }

    //setter
    public function source($source)
    {
        $this->source = $source;

        return $this;
    }

    public function prefetch($prefetch)
    {
        $this->prefetch = $prefetch;

        return $this;
    }

    public function prejoin($valueKey)
    {
        $this->prejoin[] = $valueKey;

        return $this;
    }

    public function prefilter($field, $operator = "=", $value = "1", $type = "")
    {
        array_push($this->prefilter, array(
                "field" => $field,
                "operator" => $operator,
                "value" => $value,
                "type" => $type
            ));

        return $this;
    }

    public function groupBy($groupBy)
    {
        $this->groupBy = $groupBy;

        return $this;
    }

    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    public function setRequest($request)
    {
        $this->request = $request;

        return $this;
    }

    public function select($select)
    {
        $this->select = $select;

        return $this;
    }

    //protected
    protected function parseRequest()
    {
        if (!is_array($this->request)) {
            parse_str($this->request, $this->request);
        }

        $filter = array();
        $columnArrangement = null;
        $hiddenColumns = null;

        if (isset($this->request['filter'])) {
            $filterObject = $this->request['filter'];

            $filter = isset($filterObject['filter']) ? $filterObject['filter'] : array();
            $columnArrangement = isset($filterObject['columnArrangement']) ? $filterObject['columnArrangement'] : null;
            $hiddenColumns = isset($filterObject['hiddenColumns']) ? $filterObject['hiddenColumns'] : null;
        }
        $this->filter               = $filter;
        $this->columnArrangement    = $columnArrangement;
        $this->hiddenColumns        = $hiddenColumns;

        if (isset($this->request['preset'])) {
            $this->presetHandler = new oDataPresetHandler($this->name, $this->request['preset']);
        } else {
            $this->presetHandler = new oDataPresetHandler($this->name);
        }
    }
        
    protected function createFilters()
    {
        /*
            overwrite this method
        */

        $filters = [];

        //filtering
        if (isset($this->filter) && is_array($this->filter)) {
            $filterArray = array();
            foreach ($this->filter as $filter) {
                if ($filter['value'] != "noFilter") {
                    $dataChain = $this->getDataChainByNumber($filter["column"]);

                    if (!$dataChain) {
                        continue;
                    }

                    $filterArray[] = array(
                        "valueKey"  => $dataChain->getValueKey(),
                        "compare"   => $filter['compare'],
                        "value"         => $filter['value']
                    );

                    $dataChain
                        ->setCurrentFilterValue($filter['value'])
                        ->setCurrentFilterMethod($filter['compare']);
                }
            }
            $filters['filter'] = $filterArray;
        }

        return $filters;
    }

    public function setTotalCountType($totalCountType = self::TOTAL_COUNT_TYPE_FULL)
    {
        $this->totalCountType = $totalCountType;

        return $this;
    }

    public static function needsRowsCount($totalCountType)
    {
        switch ($totalCountType) {
            case (self::TOTAL_COUNT_TYPE_NEXT_PAGE):
            case (self::TOTAL_COUNT_TYPE_NONE):
            case (self::TOTAL_COUNT_TYPE_SHOW_ALL):
                return false;

            default:
            case (self::TOTAL_COUNT_TYPE_FULL):
                return true;
        }
    }

    protected function prepareDBController()
    {
        $this->DBController->init(
            $this->source,
            $this->prefetch,
            $this->prejoin,
            $this->prefilter,
            $this->groupBy
        );

        $this->DBController
            ->setTotalCountType($this->totalCountType)
            ->setSelect($this->select);

        $this->dataSet      = $this->DBController->getRows($this->createFilters());
        $this->rowsCount    = $this->DBController->getRowsCount();
    }
}
