<?php
	namespace Designitgmbh\MonkeyTables\Report;

	/**
	 * A helper class that is used by the squashing and joining function to gather the data.
	 * The squashing function create one DataSetEntry for each x-Axis entry and adds all the values to be joined for the y-Axis value.
	 * 
	 * @package    MonkeyTables
	 * @author     Philipp Pajak <p.pajak@design-it.de>
	 * @license    https://raw.githubusercontent.com/designitgmbh/monkeyTables/master/LICENSE  BSD
	 */
	class oReportDataSetEntry
	{
		public
			/**
			 * Final xValue for the dataSetEntry
			 * 
			 * @var string
			 */
			$xValue,
			/**
			 * Final yValue for the dataSetEntry
			 * 
			 * @var string
			 */
			$yValue;
		private
			/**
			 * A list of objects that represents the xValue and will be joined into the yValue
			 * 
			 * @var array
			 */
			$objects;

		/**
		 * Constructor.
		 * 
		 * @param string						$xValue			The final xValue.
		 */
		function __construct($xValue) {
			$this
				->setXValue($xValue)
				->setObjects([]);
		}

		public function setXValue($value) {
			$this->xValue = $value;

			return $this;
		}

		public function setYValue($value) {
			$this->yValue = $value;

			return $this;
		}

		/**
		 * Set an array of objects for the dataSetEntry
		 * 
		 * @param array							$objects			An array with objects.
		 */
		public function setObjects($objects) {
			$this->objects = $objects;

			return $this;
		}

		/**
		 * Add an object to the list of objects for the dataSetEntry
		 * 
		 * @param object						$object			The object to be added.
		 */
		public function addObject($object) {
			$this->objects[] = $object;

			return $this;
		}

		//getter
		public function getXValue() {
			return $this->xValue;
		}

		public function getYValue() {
			return $this->yValue;
		}

		public function getObjects() {
			return $this->objects;
		}
	}