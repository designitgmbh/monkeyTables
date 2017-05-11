<?php
    namespace Designitgmbh\MonkeyTables\Report;

    use DB;

    /**
     * Multiple series for a report. It contains the source entity and the x- and y-Axis.
     * Each series is defined by a variable of the source entity, that will be groupped by.
     * You can add filters to a series, so a user can use filters for the results in the frontend.
     *
     * @package    MonkeyTables
     * @author     Philipp Pajak <p.pajak@design-it.de>
     * @license    https://raw.githubusercontent.com/designitgmbh/monkeyTables/master/LICENSE  BSD
     */
class oReportMultiSeries extends oReportSeries
{
    private $seriesNameSelect,
    $seriesIDSelect,
    $sortingFieldSelect,
    $sortingFieldRaw,
    $joinSelect,
    $squashSelect,

    $xValues = [],
    $squashMethod,

    $xAxisName      = 'monkeyReportXAxis',
    $yAxisName      = 'monkeyReportYAxis',
    $seriesName     = 'monkeyReportSeriesName',
    $seriesID       = 'monkeyReportSeriesID',
    $sortingField   = 'monkeyReportSeriesSortingField';

    public function __construct($source, $xAxis, $yAxis)
    {
        parent::__construct($source, "", $xAxis, $yAxis);
    }

    public function setSeriesName($seriesName)
    {
        $this->seriesNameSelect = DB::raw($seriesName . " as " . $this->seriesName);

        return $this;
    }

    public function setSeriesId($seriesID)
    {
        $this->seriesIDSelect = $seriesID . " as " . $this->seriesID;

        return $this;
    }

    public function setSortingField($sortingField)
    {
        $this->sortingFieldRaw = $sortingField;
        $this->sortingFieldSelect = $sortingField . " as " . $this->sortingField;

        return $this;
    }

    public function setJoinMethod($joinMethod, $joinField = null)
    {
        $yAxisName = $this->yAxisName;

        switch ($joinMethod) {
            case ("COUNT"):
                $this->joinSelect = DB::raw(
                    "COUNT( $joinField ) as $yAxisName"
                );
                break;

            case ("SUM"):
                $this->joinSelect = DB::raw(
                    "SUM( $joinField ) as $yAxisName"
                );
                break;

            default:
                //NOT SUPPORTED
        }

        return $this;
    }

    public function setSquashMethod($squashMethod, $squashField)
    {
        $xAxisName = $this->xAxisName;
        $this->squashMethod = $squashMethod;

        switch ($squashMethod) {
            case ("DATE_BUSINESS_YEAR"):
                $this->squashSelect = DB::raw(
                    "CONCAT(
							'BY ',
							IF(
								MONTH(FROM_UNIXTIME( $squashField )) > 3, 
								YEAR(FROM_UNIXTIME( $squashField )) + 1, 
								YEAR(FROM_UNIXTIME( $squashField ))
							)
						) as $xAxisName"
                );
                break;

            case ("DATE_MONTH"):
                $this->squashSelect = DB::raw(
                    "CONCAT(
							DATE_FORMAT(FROM_UNIXTIME( $squashField ),'%m'), 
							'.', 
							YEAR(FROM_UNIXTIME( $squashField ))
						) as $xAxisName"
                );
                break;

            case ("DATE_DAY"):
                $this->squashSelect = DB::raw(
                    "CONCAT(
							DATE_FORMAT(FROM_UNIXTIME( $squashField ),'%d'),
							'.',
							DATE_FORMAT(FROM_UNIXTIME( $squashField ),'%m'),
							'.',
							YEAR(FROM_UNIXTIME( $squashField ))
						) as $xAxisName"
                );
                break;
                
            case ("DATE_WEEKDAY"):
                $this->squashSelect = DB::raw(
                    "CONCAT(
							WEEK( FROM_UNIXTIME( $squashField ) ),
							'/',
							YEAR(FROM_UNIXTIME( $squashField ))
						) as $xAxisName"
                );
                break;

            case ("VALUE"):
                $this->squashSelect = DB::raw(
                    "$squashField as $xAxisName"
                );
                break;

            default:
                //NOT SUPPORTED
                break;
        }

        return $this;
    }

    private function setSelects()
    {
        if (!$this->select) {
            $this->select = [];
        }

        $this->select = array_merge(
            $this->select,
            [
                $this->squashSelect,
                $this->joinSelect,
                $this->seriesNameSelect,
                $this->seriesIDSelect,
                $this->sortingFieldSelect
            ]
        );

        return $this;
    }

    private function setFilters()
    {
        $xAxisOptions = $this->xAxis->getTypeOptions();

        if (isset($xAxisOptions['from'])) {
            $from = $xAxisOptions['from'];
        } else {
            $from = "@0";
        }

        if (isset($xAxisOptions['to'])) {
            $to = $xAxisOptions['to'];
        } else {
            $to = "today";
        }

        $from = date_create($from);
        $to = date_create($to);
        $diff = date_diff($from, $to);

        switch ($this->squashMethod) {
            case ("DATE_BUSINESS_YEAR"):
                //limit to max 5 years
                if ($diff->format('%y') >= 5) {
                    //this will not work, leaving commented for now
                    //but should be tackled in a further release
                    //TODO: set from date to BY start date..
                    //$from = $to->sub(new \DateInterval('P5Y'));
                }

                break;

            case ("DATE_MONTH"):
                //limit to 24 months
                if ($diff->format('%y') >= 2 && $diff->format('%m') >= 1) {
                    $from = $to->sub(new \DateInterval('P2Y'));
                }

                break;

            case ("DATE_DAY"):
                if ($diff->format('%y') >= 1 && $diff->format('%m') >= 1) {
                    $from = $to->sub(new \DateInterval('P7D'));
                }

                break;

            case ("DATE_WEEKDAY"):
                if ($diff->format('%y') >= 1 && $diff->format('%m') >= 3) {
                    $from = $to->sub(new \DateInterval('P1M'));
                }

                break;
        }

        $this->prefilter($this->sortingFieldRaw, ">=", $from->format("U"));

        return $this;
    }

    private function prepareXValues($xValue, $sortingValue)
    {
        $this->xValues[$xValue] = $sortingValue;
    }

    private function fillUpSerie(&$serie)
    {
        $missingXValues = $this->xValues;

        foreach ($serie['data'] as $data) {
            $missingXValues[$data['xValue']] = false;
        }

        foreach ($missingXValues as $xValue => $sortingValue) {
            if ($sortingValue) {
                $serie['data'][] = [
                    'xValue' => $xValue,
                    'yValue' => 0,
                    'sorting' => $sortingValue
                ];
            }
        }

        usort($serie['data'], function ($a, $b) {
            return strcmp($a['sorting'], $b['sorting']);
        });
    }

    public function render($helperController, $DBController)
    {
        $this->DBController = $DBController;

        if (isset($this->request) && $this->filter == null) {
            $this->parseRequest();
        }

        $this->createFilters();

        $this
            ->setSelects()
            ->setFilters()
            ->groupBy([$this->seriesID, $this->xAxisName]);

        $this->prepareDBController();

        //then foreach entry given, assign it to the correct series
            //ergo make 'em seperated by whatever customer id stuff
        $header = $this->createHeader();
        $series = [];

        $seriesID = $this->seriesID;
        $seriesName = $this->seriesName;
        $xAxisName = $this->xAxisName;
        $yAxisName = $this->yAxisName;
        $sortingField = $this->sortingField;

        $header = $this->header;

        foreach ($this->dataSet as $data) {
            $header['name'] = $data->$seriesName;

            $xValue = $data->$xAxisName;
            $yValue = $data->$yAxisName;

            switch ($this->yAxis->getType()) {
                case ("integer"):
                case ("number"):
                case ("currency"):
                    $yValue = (int)$yValue;
                    break;
                case ("float"):
                    $yValue = (float)$yValue;
                    break;
            }
                
            $sortingValue = $data->$sortingField;

            if (isset($series[$data->$seriesID])) {
                $series[$data->$seriesID]['data'][] = [
                    "xValue" => $xValue,
                    "yValue" => $yValue,
                    "sorting" => $sortingValue
                ];
            } else {
                $series[$data->$seriesID] = [
                "header" => $header,
                "data"   => [
                    [
                        "xValue" => $xValue,
                        "yValue" => $yValue,
                        "sorting" => $sortingValue
                    ]
                ]
                ];
            }

            $this->prepareXValues($xValue, $sortingValue);

            //since filters are merged from each series in the frontend
            //anyways, we can unset it after the first series to
            //save some data volume
            $header["filters"] = [];
        }

        foreach ($series as &$serie) {
            $this->fillUpSerie($serie);
        }

        if (empty($series)) {
            $series[0] = [
                "header" => $header,
                "data" => []
            ];
        }

        return array_values($series);
    }
}
