<?php
	namespace Designitgmbh\MonkeyTables\Table;

	use Designitgmbh\MonkeyTables\Data\oDataChain;
	use Designitgmbh\MonkeyTables\QueryBuilder\QueryBuilder;
	use Designitgmbh\MonkeyTables\Http\Controllers\oTablesFrameworkHelperController;

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
		protected $canRearrangeColumns = true;

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
			$this->editable 		= array();
		}

		// getter functions //

		public function isEditable($obj = null) {
			$hasRoute = isset($this->editable["route"]) && $this->editable["route"];
			$isNotBlocked = isset($this->editable["editableConstraint"]) ? 
				$this->editable["editableConstraint"]($obj) : true;			

			return ($hasRoute && $isNotBlocked);
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
			
			$this->editable["route"] 	= $route;
			$this->editable["attribute"]= $attribute;
			$this->editable["argument"]	= $argument;
			
			return $this;
		}

		public function setEditableType($type, $options = []) {
			$this->editable["type"] = $type;
			$this->setEditableOptions($options);

			return $this;
		}

		public function setEditableOptions($options) {
			foreach($options as $key => $option) {
				switch($key) {
					case('editableConstraint'):
						$this->editable["editableConstraint"] = $option;
						break;
					case('ajaxRoute'):
						$this->editable["ajaxRoute"] = $option;
						break;
					case('needsRefresh'):
						$this->editable["needsRefresh"] = $option ? true : false;
						break;
				}
			}

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

		public function setFilter($type, $value = null) {
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
					$value = QueryBuilder::recursiveObjectGetter($obj, $this->linkValueKey);
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

		private function parseDate($value, $format = null) {
			if($format === null) {
				$format = config('monkeyTables.date.displayDate.php');
			}

			if($value === oTablesFrameworkHelperController::translate('labels.unset', 1)) {
				return oTablesFrameworkHelperController::translate('labels.none', 1);
			}

			if(!is_numeric($value)) {
				$value = strtotime($value);
			}

			if($value) {
				return date($format, $value);
			}
			
			return "";
		}

		private function parseFloat($value) {
			if($value === null)
				return (float)0;

			if(is_numeric($value))
				return $value;

			$splitValue = array_filter(str_split($value), function($character) {
				return is_numeric($character) || $character == ',' || $character == '.';
			});
			
			return floatval(implode($splitValue, ''));
		}

		private function getCellValue($obj, $asHTML = true) {
			if(!is_object($obj))
				return null;

			$value = QueryBuilder::recursiveObjectGetter($obj, $this->valueKey);

			//callback
			if(isset($this->alterDisplayValueFunc)) {
				$func  = $this->alterDisplayValueFunc;
				$value = $func($value, $obj);
			}

			//type
			if(isset($this->type)) {
				switch($this->type) {
					case("shortDate"):
						$value = $this->parseDate($value, config('monkeyTables.date.displayDateShort.php'));
						break;
					case("date"):
						$value = $this->parseDate($value);
						break;
					case("datetime"):
						$value = $this->parseDate($value, "d.m.Y H:i");
						break;
					case("time"):
						$value = $this->parseDate($value, "H:i");
						break;
					case("decimal"):
						$value = number_format((float)$value, 2, ",", ".");
						break;
					case("bool"):
						if($asHTML) {
							$value = '<span class="glyphicon glyphicon-' . ($value ? 'ok' : 'remove') . '"></span>';	
						} else {
							$value = $value ? 
								oTablesFrameworkHelperController::translate('labels.yes') : 
								oTablesFrameworkHelperController::translate('labels.no');
						}
						
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
						$value = (is_null($value) ? oTablesFrameworkHelperController::translate('labels.none') : $value);
						break;
					case("string"):
						if(empty($value))
							$value = "-";
						break;
					case("currency-with-symbol"):
						$value = $this->parseFloat($value);
						$value = Currency::formatWithSymbol($value);
						break;
					case("currency"):
						$value = $this->parseFloat($value);
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
					case("not-available"):
						if(empty($value)) {
							$value = oTablesFrameworkHelperController::translate('labels.not_available');
						}

						break;
					case("nullable"):
						if(empty($value)) {
                            if($option) {
                                $value = $option;
                            } else {
                                $value = oTablesFrameworkHelperController::translate('labels.none');
                            }						
						}

						break;
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
			$isToolbox = $this->type == "toolbox" || $this->type == "given-toolbox";

			if($isToolbox) {
				$this->sortable = false;
				$this->filterable = false;
			}

			$cell = parent::renderHeader();

			$cell["CLASS"] = "";

			if($isToolbox) {
				$cell["CLASS"] = "toolboxHeader ";
			}
			
			if($this->centerClass) {
				$cell["CLASS"] .= $this->centerClass . " ";
			}

			if($this->type == "timeline") {
				$cell["TIMELINE"] = true;
			}

			$cell["CANREARRANGECOLUMNS"] = $this->canRearrangeColumns;

			return $cell;
		}

		public function getData($obj, $raw = false) {
			if($raw) {
				if(	
					$this->type == "icon" || 
					$this->type == "glyphicon" ||
					$this->type == "toolbox" ||
					$this->type == "given-toolbox")

					return "";

				$this->filters = array();
			}
			return $this->getCellValue($obj, false);
		}

		public function render($obj) {
			if(!$this->isEnabled())
				return null;

			$cell = array();

			$cell["DATA"] = $this->getCellValue($obj);
			$cell["LINK"] = $this->getCellLink($obj);

			if(isset($this->chainNumber))
				$cell["COLUMNNUMBER"] = $this->chainNumber;

			if(isset($this->class))
				$cell["CLASS"] = $this->class;

			if(isset($this->tdClass))
				$cell["TDCLASS"] = $this->tdClass;

			if(isset($this->tooltip))
				$cell["TOOLTIP"] = $this->tooltip;

			if($this->isEditable($obj)) {
				$arguments = $this->editable["argument"];

				if(is_array($arguments)) {
					foreach ($arguments as $key => $argument) {
						if(is_string($argument)) {
							$arguments[$key] = QueryBuilder::recursiveObjectGetter($obj, $argument);
						} else if(is_array($argument)) {
							$arguments[$key] = $argument['value'];
						}
					}
				} else {
					$arguments = QueryBuilder::recursiveObjectGetter($obj, $arguments);
				}

				$cell["EDITABLE"] = $this->frameworkHelper->getRoute($this->editable["route"], $arguments);
				$cell["EDITABLEATTRIBUTE"] = $this->editable["attribute"];
				$cell["EDITABLETYPE"] = isset($this->editable["type"]) ? $this->editable["type"] : $this->type;

				if(isset($this->editable["ajaxRoute"]))
					$cell["EDITABLEAJAXURL"] = $this->frameworkHelper->getRoute($this->editable["ajaxRoute"]);

				if(isset($this->editable["needsRefresh"]))
					$cell["EDITABLEMASSIVEUPDATE"] = true;
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