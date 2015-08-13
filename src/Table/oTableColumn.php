<?php
	namespace Designitgmbh\MonkeyTables\Table;

	use Designitgmbh\MonkeyTables\Data\oDataChain;
	use Designitgmbh\MonkeyTables\Http\Controllers\oTablesFrameworkDBController;

	use Designitgmbh\MonkeyTables\Format\Currency;

	/**
	 * Basic class that represents a column of a table
	 *
	 * @package    MonkeyTables
	 * @author     Philipp Pajak <p.pajak@design-it.de>
	 * @license    https://raw.githubusercontent.com/designitgmbh/monkeyTables/master/LICENSE  BSD
	 */
	class oTableColumn extends oDataChain
	{
		/* constructor */
		public function __construct($label, $valueKey, $sortable = true, $filterable = true, $displayHeader = true) {
			parent::__construct($label, $valueKey);

			$this->sortable 		= $sortable;
			$this->filterable 		= $filterable;
			$this->displayHeader	= $displayHeader;
		}

		public function initValues() {
			parent::initValues();

			$this->setColorFunction = function() {return null;};
			$this->href 			= null;
			$this->linkValueKey 	= null;
			$this->linkValueCallback= null;
			$this->class 			= null;
			$this->filters 			= array();
			$this->centerClass 		= null;
		}

		// getter functions //

		public function isEditable() {
			return (isset($this->editableRoute) && $this->editableRoute);
		}

		// setter functions //

		public function setClickable($href, $valueKey, $callback = null) {
			$this->href = $href;
			$this->linkValueKey = $valueKey;
			$this->linkValueCallback = $callback;

			return $this;
		}

		public function setEditable($route = null, $attribute = null, $argument = 'id') {
			if($route == null) {
				//TODO: possible to set route automatically?
				//not yet, because we dont have access to oTable to get the source entity
				//$route = $this->table->source . "." . $this->valueKey;
				return false;
			}

			if($attribute == null) {
				$attribute = $this->valueKey;
			}
			
			$this->editableRoute 	= $route;
			$this->editableAttribute= $attribute;
			$this->editableArgument	= $argument;
			
			return $this;
		}

		public function alterDisplayValue($func) {
			if(is_callable($func)) {
				$this->alterDisplayValueFunc = $func;
			}

			return $this;
		}

		public function setColor($obj) {
			//if $obj is string
			//$this->setColorFunc = anon func return string
			//else setColorFunc = obj

			return $this;
		}

		public function setClass($class) {
			$this->class = $class;

			return $this;
		}

		public function setTdClass($class) {
			$this->tdClass = $class;

			return $this;
		}

		public function setCentered($isDynamic = false) {
			$this->centerClass = 'center' . ($isDynamic ? ' dynamic-center' : '');

			return $this;
		}

		public function setFilter($type, $value) {
			array_push($this->filters, array(
					"type" => $type,
					"option" => $value
				)
			);

			return $this;
		}

		public function setFrameworkHelper($frameworkHelper) {
			$this->frameworkHelper = $frameworkHelper;

			return $this;
		}

		// private functions //

		private function getCellLink($obj) {
			if(is_string($this->href) && !empty($this->href)) {
				if (is_string($this->linkValueKey) && !empty($this->linkValueKey)) {
					$value = oTablesFrameworkDBController::recursiveObjectGetter($obj, $this->linkValueKey);
					if (is_callable($this->linkValueCallback)) {
						$value = call_user_func($this->linkValueCallback, $value, $obj);
						if (is_null($value)) {
							return $this->href;
						} else {
							return $value;
						}
					} else {
						return str_replace("{{ID}}", $value, $this->href);
					}
				} else {
					return $this->href;
				}
			}
			return null;			
		}

		private function getCellValue($obj) {
			if(!is_object($obj))
				return null;

			$value = oTablesFrameworkDBController::recursiveObjectGetter($obj, $this->valueKey);

			//callback
			if(isset($this->alterDisplayValueFunc)) {
				$func  = $this->alterDisplayValueFunc;
				$value = $func($value, $obj);
			}

			//type
			if(isset($this->type)) {
				switch($this->type) {
					case("date"):
						if($value != intval($value))
							$value = strtotime($value);

						if($value)
							$value = date(config('monkeyTables.date.displayDate.php'), $value);
						else
							$value = "";

						break;
					case("datetime"):
						if($value != intval($value))
							$value = strtotime($value);

						if($value)
							$value = date("d.m.Y H:i", $value);
						else
							$value = "";

						break;
					case("time"):
						if($value != intval($value))
							$value = strtotime($value);

						if($value)
							$value = date("H:i", $value);
						else
							$value = "";

						break;
					case("decimal"):
						$value = number_format($value, 2, ",", ".");
						break;
					case("bool"):
						$value = '<span class="glyphicon glyphicon-' . ($value ? 'ok' : 'remove') . '"></span>';
						break;
					case("flag"):
						if ($value)
						{
							$value = '<span class="glyphicon glyphicon-ok"></span>';
						}
						else
						{
							$value = '<span></span>';
						}
						break;
					case("switch"):
						$value = ($value ? 'true' : 'false');
						break;
					case("glyphicon"):
						$value = '<span class="glyphicon glyphicon-' . $value . '"></span>';
						break;
					case("icon"):
						$value = '<span class="icon icon-' . $value . '"></span>';
						break;
					case("toolbox"):
						$value = $this->frameworkHelper->generateToolbox($obj);
						break;
					case("array"):
						$value = implode(", ", $value);
						break;
					case("nullableValue"):
						$value = (is_null($value) ? oDataFrameworkHelperController::translate('labels.none') : $value);
						break;
					case("currency"):
						$value = (is_null($value) ? 0 : $value);
						$value = Currency::format($value);
						break;
					default:
						break;
				}
			}

			//filter
			//special functions for the values, like maxLength

			foreach($this->filters as $filter) {
				$type 	= $filter['type'];
				$option = $filter['option'];

				switch($type) {
					case("strip"):
						if(strlen($value) > $option) {
							$this->tooltip 	= $value;
							$value 			= substr($value, 0, $option) . "...";
						}						
						break;
					default:
						break;
				}
			}

			return $value;
		}

		// public render functions //
		public function getHeaderData() {
			return $this->label;
		}
		
		public function renderHeader() {
			if($this->type == "toolbox") {
				$this->sortable = false;
				$this->filterable = false;
			}

			$cell = parent::renderHeader();

			$cell["CLASS"] = "";

			if($this->type == "toolbox") {
				$cell["CLASS"] = "toolboxHeader ";
			}
			
			if($this->centerClass) {
				$cell["CLASS"] .= $this->centerClass . " ";
			}

			if($this->type == "timeline") {
				$cell["TIMELINE"] = true;
			}

			return $cell;
		}

		public function getData($obj, $raw = false) {
			if($raw) {
				if(	
					$this->type == "icon" || 
					$this->type == "glyphicon" ||
					$this->type == "toolbox"		)

					return "";

				$this->filters = array();
			}
			return $this->getCellValue($obj);
		}

		public function render($obj) {
			if(!$this->isEnabled())
				return null;

			$cell = array();

			$cell["DATA"] = $this->getCellValue($obj);
			$cell["LINK"] = $this->getCellLink($obj);

			if(isset($this->class))
				$cell["CLASS"] = $this->class;

			if(isset($this->tdClass))
				$cell["TDCLASS"] = $this->tdClass;

			if(isset($this->tooltip))
				$cell["TOOLTIP"] = $this->tooltip;

			if($this->isEditable()) {
				$arguments = $this->editableArgument;

				if(is_array($arguments)) {
					foreach ($arguments as $key => $argument) {
						if(is_string($argument)) {
							$arguments[$key] = oTablesFrameworkDBController::recursiveObjectGetter($obj, $argument);
						} else if(is_array($argument)) {
							$arguments[$key] = $argument['value'];
						}
					}
				} else {
					$arguments = oTablesFrameworkDBController::recursiveObjectGetter($obj, $arguments);
				}

				$cell["EDITABLE"] = $this->frameworkHelper->getRoute($this->editableRoute, $arguments);
				$cell["EDITABLEATTRIBUTE"] = $this->editableAttribute;
				$cell["EDITABLETYPE"] = $this->type;
			}

			if($this->type == "timeline") {
				$cell["TIMELINE"] = true;

				if($this->typeOptions) {
					$cell["TIMELINE"] = $this->typeOptions;
				}
			}

			//centered?
			if($this->centerClass) {
				if(!isset($cell["TDCLASS"]))
					$cell["TDCLASS"] = "";
				
				$cell["TDCLASS"] = $this->centerClass . " " . $cell["TDCLASS"];
			}

			return $cell;
		}

	}


?>