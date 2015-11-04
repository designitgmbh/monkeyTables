<?php
	namespace Designitgmbh\MonkeyTables\Report;

	use Designitgmbh\MonkeyTables\Data\oDataChain;

	use Designitgmbh\MonkeyTables\Http\Controllers\oTablesFrameworkDBController;

	/**
	 * An axis for a series. It contains the label and the valueKey (link to value key.. -> The valueKey is the column name of the entity or a eloquent valueKey... ).
	 * You can also set the squashing function for the x-Axis.
	 *
	 * @package    MonkeyTables
	 * @author     Philipp Pajak <p.pajak@design-it.de>
	 * @license    https://raw.githubusercontent.com/designitgmbh/monkeyTables/master/LICENSE  BSD
	 */
	class oReportAxis extends oDataChain
	{
		/**
		 * Label of the axis
		 * 
		 * @var string
		 */
		protected $label;

		/**
		 * Type of the axis
		 * 
		 * @var string
		 */
		protected $type;

		/**
		 * An additional filter that may be used to set the dimensions of the axes
		 * 
		 * @var oReportFilter
		 */
		protected $additionalFilter;

		/**
		 * ???
		 * TODO: refactor 
		 *			CHECK IF WE CAN REMOVE IT
		 * 
		 * @var array
		 */
		protected $values;

		/**
		 * TODO: refactor
		 *			CHECK IF WE CAN REMOVE IT
		 * 
		 * @var array
		 */
		protected $dataSet;

		/**
		 * Constructor.
		 * 
		 * @param string						$label			The label of the filter.
		 * @param string						$valueKey		The value key for the filter.
		 * @param string						$type			The type of the filter.
		 */
		public function __construct($label, $valueKey, $type = null) {
			parent::__construct($label, $valueKey);

			$this->additionalFilter = null;
			$this->renderValueKey = null;

			$this->setType($type);
			$this->setDataSet();
		}

		//setter
		public function setDataSet($dataSet = null) {
			$this->dataSet = $dataSet;

			return $this;
		}

		public function renderValueKey($valueKey) {
			$this->renderValueKey = $valueKey;

			return $this;
		}

		//getter
		public function getDataSet() {
			return $this->dataSet;
		}

		public function getTypeOptions() {
			$options = parent::getTypeOptions();

			//if this is an x Axis and we are squashing by date
			//we need to set the limits for the squasher in
			//case that the user provided some filters
			if($this->additionalFilter) {
				$filter = $this->additionalFilter;
				if($filter->getType() == 'date') {
					$dates = explode('|', $filter->currentFilterValue);

					if(count($dates) != 2)
						return $options;

					foreach([0 => 'from', 1 => 'to'] as $dateKey => $optionKey) {
						$date = $dates[$dateKey];

						if(!$date)
							continue;

						//check if date is in a timestamp + hour format
						$explodedDate = explode(' ', $date);
						if(count($explodedDate) > 1) {
							$date = $explodedDate[0];
						}

						//check if date is timestamp, and add "@"
						if($date == intval($date))
							$date = "@" . $date;

						//convert date to timestamp and pass it on
						$options[$optionKey] = "@" . strtotime($date);
					}
				}
			}

			return $options;
		}

		public function getAdditionalFilterChainNumber() {
			if($this->additionalFilter === null)
				$this->setAdditionalFilter();

			if($this->additionalFilter)
				return $this->additionalFilter->getChainNumber();

			return null;
		}

		public function getAdditionalFilter() {
			if($this->additionalFilter === null)
				$this->setAdditionalFilter();

			return $this->additionalFilter;
		}
		
		private function setAdditionalFilter() {
			$label = "";
			$valueKey = null;
			$type = "";

			//depends on the type of the axis and also
			//if it is an x or y axis, currently it
			//is only implmented for x axis

			switch($this->type) {
				case("DATE_YEAR"):
				case("DATE_YEAR_SEQ"):
					//one entry per year; format Y

				case("DATE_MONTH_YEAR"):
				case("DATE_MONTH"):
				case("DATE_MONTH_SEQ"):
					//one entry per month.year ; format m.Y
				
				case("DATE_DAY_MONTH_YEAR"):
				case("DATE_DAY"):
				case("DATE_DAY_SEQ"):
					//one entry per day.month.year ; format d.m.Y
				
					//for now each date type will just have a
					//normal datepicker - this should be enough
					$label = "x-Axis";
					$valueKey = $this->valueKey;
					$type = "date";

					break;

				default:
			}

			if($label && $valueKey && $type) {
				$hash = md5( $label . $valueKey . $type . "additionalFilter" );
				$filter = (new oReportFilter($label, $valueKey, $type))
						->setChainNumber($hash)
						->setHasAutoFilterValues(false);
			} else {
				$filter = false;
			}	

			$this->additionalFilter = $filter;
		}
		/*
		 * Render method for a given object.
		 *
		 * @param object						$obj			A database object to be rendered.
		 *
		 * @return string|int
		 */
		public function render($obj) {
			$valueKey = isset($this->renderValueKey) ? $this->renderValueKey : $this->valueKey;

			return oTablesFrameworkDBController::recursiveObjectGetter($obj, $valueKey);
		}
	}