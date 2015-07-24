<?php
	namespace Designitgmbh\MonkeyTables\Report;

	use Designitgmbh\MonkeyTables\Data\oDataChain;

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

			$this->setType($type);
			$this->setDataSet();
		}

		//setter
		public function setDataSet($dataSet = null) {
			$this->dataSet = $dataSet;

			return $this;
		}

		//getter
		public function getDataSet() {
			return $this->dataSet;
		}
		
		/*
		 * Render method for a given object.
		 *
		 * @param object						$obj			A database object to be rendered.
		 *
		 * @return string|int
		 */
		public function render($obj) {
			//return value
			return \oTablesFrameworkDBController::recursiveObjectGetter($obj, $this->valueKey);
		}
	}