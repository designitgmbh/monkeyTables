<?php
	namespace Designitgmbh\MonkeyTables\Report;

	use Designitgmbh\MonkeyTables\Data\oData;

	/**
	 * A series for a report. It contains the source entity and the x- and y-Axis.
	 * You can add filters to a series, so a user can use filters for the results in the frontend.
	 * 
	 * @package    MonkeyTables
	 * @author     Philipp Pajak <p.pajak@design-it.de>
	 * @license    https://raw.githubusercontent.com/designitgmbh/monkeyTables/master/LICENSE  BSD
	 */
	class oReportSeries extends oData
	{
		//variables
		protected 
			/**
			 * The xAxis for the series
			 * 
			 * @var oReportAxis
			 */
			$xAxis,
			/**
			 * The yAxis for the series
			 * 
			 * @var oReportAxis
			 */
		 	$yAxis,
		 	/**
			 * The name of the join method to be used.
			 * 
			 * @var string
			 */
		 	$joinMethod,
		 	/**
			 * The header of the series that will be later sent to the client.
			 * 
			 * @var array
			 */
		 	$header;

        /**
         * The total count type
         * This can be used to declare how the data should be counted
         *
         * @var integer
         */
        protected $totalCountType = self::TOTAL_COUNT_TYPE_NONE;

		/**
		 * Constructor.
		 * 
		 * @param string						$source 			The source entity that will be used for the series.
		 * @param string						$name				The name of the series.
		 * @param oReportAxis					$xAxis				The xAxis
		 * @param oReportAxis					$yAxis				The yAxis
		 */
		public function __construct($source, $name, $xAxis, $yAxis) {
			parent::__construct($name);

			$this->filters = [];

			$this
				->setSource($source)
				->setName($name)
				->setXAxis($xAxis)
				->setYAxis($yAxis);
		}

		//setters
		public function setSource($source) {
			$this->source = $source;

			return $this;
		}

		public function setName($name) {
			$this->name = $name;

			return $this;
		}

		public function setXAxis($xAxis) {
			$this->xAxis = $xAxis;

			if($filter = $this->xAxis->getAdditionalFilter()) {
				$chainNumber = $filter->getChainNumber();
				$this->filters[$chainNumber] = $filter;
			}

			return $this;
		}

		public function setYAxis($yAxis) {
			$this->yAxis = $yAxis;

			return $this;
		}

		public function setJoinMethod($joinMethod) {
			$this->joinMethod = $joinMethod;

			return $this;
		}

		public function setParsedRequest($filter, $columnArrangement) {
			$this->filter = $filter;
			$this->columnArrangement = $columnArrangement;

			return $this;
		}

		/**
		 * Add a filter for the series.
		 * 
		 * @param string						$label			The label of the filter.
		 * @param string						$valueKey		The value key for the filter.
		 * @param string						$type			The type of the filter.
		 *
		 * @return self
		 */
		public function addFilter($label, $valueKey, $type = null) {
			$hash = md5( $label . $valueKey . $type . $this->getSource() );

			$this->filters[$hash] = (new oReportFilter($label, $valueKey, $type))
				->setChainNumber($hash);
			
			return $this;
		}

		//getters
		public function getSource() {
			return $this->source;
		}

		//private functions
		/*protected function createFilters() {

		}*/

		protected function getDataChainByNumber($number) {
			if(isset($this->filters[$number]))
				return $this->filters[$number];

			return null;
		}

		protected function createHeader() {
			$this->header = array(
				"name"			=> $this->name,
				"xAxisLabel" 	=> $this->xAxis->getLabel(),
				"yAxisLabel" 	=> $this->yAxis->getLabel(),
				"xAxisType" 	=> $this->xAxis->getType(),
				"yAxisType" 	=> $this->yAxis->getType(),
				"filters"		=> $this->createFilterHeader()
			);
		}

		private function createSeriesReturnObject() {
			return array(
				"header" => $this->header,
				"data"   => $this->dataSet
			);
		}

		private function createFilterHeader() {
			$filterHeader = [];

			$this->DBController->getFilterValuesForColumns($this->filters);

			foreach($this->filters as $key => $filter) {
				$filterHeader[] = $filter->renderHeader();
			}

			return $filterHeader;
		}

		//public functions
		/**
		 * Render method. Used to create the final array.
		 * 
		 * @param Controller					$helperController		The helper controller containing all essential framework helper functions.
		 * @param Controller					$DBController			The DB controller containing all essential DB helper functions.
		 *
		 * @return array
		 */
		public function render($helperController, $DBController) {
			$this->DBController = $DBController;

			if(isset($this->request) && $this->filter == null)
				$this->parseRequest();

			$this->prepareDBController();

			if(!$this->dataSet->isEmpty()) {
                oReportSquasher::squash($this->dataSet, $this->xAxis);
                oReportJoiner::join($this->dataSet, $this->yAxis, $this->joinMethod);    
            }
			
			$this->createHeader();
            
			return $this->createSeriesReturnObject();
		}
	}

?>