<?php
	namespace Designitgmbh\MonkeyTables\Data;
	
	use Designitgmbh\MonkeyTables\Http\Controllers\oDataFrameworkHelperController;

	/**
	 * An abstract class that represents a data chain inside of a data set. 
	 * It contains a label and a valueKey.
	 * It supports a various amount of options like:
	 * 		enabled, type, typeOptions, filterable, sortable, alwaysVisible, etc.
	 *
	 * @package    MonkeyTables
	 * @author     Philipp Pajak <p.pajak@design-it.de>
	 * @license    https://raw.githubusercontent.com/designitgmbh/monkeyTables/master/LICENSE  BSD
	 */
	class oDataChain
	{
		/**
		 * Contructor
		 *
		 * @param string 		$label 			The label of the dataChain
		 * @param string 		$valueKey 		The valueKay for the dataChain
		 */
		public function __construct($label, $valueKey) {
			$this->initValues();

			$this
			 ->setLabel($label)
			 ->setValueKey($valueKey);
		}

		public function initValues() {
			$this->label = "";
			$this->valueKey = "";

			$this->enabled = true;
			$this->type = null;
			$this->typeOptions = [];
			$this->filterable = false;
			$this->sortable = false;
			$this->alwaysVisible = false;
			$this->filterValues = [];
			$this->chainNumber = false;
			$this->currentFilterValue = null;
			$this->currentFilterMethod = null;
			$this->displayHeader = true;
		}

		/* PUBLIC METHODS */
		//setter

		public function enable() {
			$this->enabled = true;

			return $this;
		}

		public function disable() {
			$this->enabled = false;

			return $this;
		}

		public function setLabel($label) {
			$this->label = $label;

			return $this;
		}

		public function setValueKey($valueKey) {
			$this->valueKey = $valueKey;

			return $this;
		}

		public function setType($type, $options = []) {
			$this->type = $type;
			$this->typeOptions = $options;

			return $this;
		}

		public function setFilterable($filterable) {
			$this->filterable = $filterable;

			return $this;
		}

		public function setSortable($sortable) {
			$this->sortable = $sortable;

			return $this;
		}

		public function setAlwaysVisible($alwaysVisible) {
			$this->alwaysVisible = $alwaysVisible;

			return $this;
		}

		public function setFilterValues($values) {
			$this->filterValues = $values;

			if(count($values) > 0)
				$this->setHasAutoFilterValues(true);

			return $this;
		}

		public function setHasAutoFilterValues($value = true) {
			$this->hasAutoFilterValues = $value;

			return $this;
		}

		public function setChainNumber($number) {
			$this->chainNumber = $number;

			return $this;
		}

		public function setCurrentFilterValue($value) {
			$this->currentFilterValue = $value;

			return $this;
		}

		public function setCurrentFilterMethod($method) {
			$this->currentFilterMethod = $method;

			return $this;
		}

		//getter
		public function getLabel() {
			return $this->label;
		}

		public function getValueKey() {
			return $this->valueKey;
		}

		public function getType() {
			return $this->type;
		}

		public function getTypeOptions() {
			return $this->typeOptions;
		}

		public function isEnabled() {
			return $this->enabled;
		}

		public function isSortable() {
			return $this->sortable;
		}

		public function isFilterable() {
			return $this->filterable;
		}

		public function hasAutoFilterValues() {
			return (isset($this->hasAutoFilterValues) && $this->hasAutoFilterValues);
		}

		public function hasFilterValuesAlreadySet() {
			return (is_array($this->filterValues) && !empty($this->filterValues));
		}
		

		/* PRIVATE METHODS */
		private function prepareFilterValues() {
			switch($this->type) {
				case("bool"):
					$this->filterValues = array(
												"true"	=>	oDataFrameworkHelperController::translate('labels.yes'),
												"false"	=>	oDataFrameworkHelperController::translate('labels.no')
											);
					break;
				case("exists"):
					$this->filterValues = array(
												"true"	=>	oDataFrameworkHelperController::translate('labels.yes'),
												"false"	=>	oDataFrameworkHelperController::translate('labels.no')
											);
					break;
				default:
					break;
			}
		}

		private function getAutoFilterType() {
			if($this->type == "number")
				return "number";

			if($this->type == "currency")
				return "currency";

			if($this->type == "date")
				return "date";

			if($this->type == "datetime")
				return "datetime";

			if($this->type == "exists")
				return "exists";

			if($this->type == "bool")
				return "selection";

			if($this->type == "suggestion")
				return "suggestion";

			if($this->hasAutoFilterValues()) {
				return ((count($this->filterValues) > 25) ? "suggestion" : "selection");
			}

			return "suggestion";
		}

		private function getCurrentFilter() {
			return array(
				"value" 	=> $this->currentFilterValue, 
				"method" 	=> $this->currentFilterMethod
			);
		}

		public function renderHeader() {
			$cell = array();

			if(isset($this->chainNumber))
				$cell["COLUMNNUMBER"] = $this->chainNumber;

			$cell["DATA"] = $this->getLabel();

			$cell["SORTABLE"] = $this->sortable ? : false;
			$cell["DISPLAY_HEADER"] = $this->displayHeader;

			//auto suggestion values
			if($this->isFilterable()) {
				$this->prepareFilterValues();

				$cell["FILTERABLE"] = array(
					"values"		=> $this->filterValues,
					"type"			=> $this->getAutoFilterType(),
					"currentFilter" => $this->getCurrentFilter()
				);
			}

			if($this->alwaysVisible)
				$cell["ALWAYSVISIBLE"] = $this->alwaysVisible;

			if(!$this->isEnabled()) {
				$cell["DISABLED"] = "true";
			}

			return $cell;
		}

	}