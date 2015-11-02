<?php
	namespace Designitgmbh\MonkeyTables\Report;

	use Designitgmbh\MonkeyTables\Data\oDataPresetHandler;

	use Designitgmbh\MonkeyTables\Http\Controllers\oTablesFrameworkDBController;
	use Designitgmbh\MonkeyTables\Http\Controllers\oDataFrameworkHelperController;

	/**
	 * The main class for a report. A report contains a title, subtitle and the series.
	 *
	 * @package    MonkeyTables
	 * @author     Philipp Pajak <p.pajak@design-it.de>
	 * @license    https://raw.githubusercontent.com/designitgmbh/monkeyTables/master/LICENSE  BSD
	 */
	class oReport
	{
		/**
		 * Name of the data set
		 * 
		 * @var string
		 */
		private $name;
		/**
		 * Title for the report
		 * 
		 * @var string
		 */
		private $title;
		/**
		 * Subtitle for the report
		 * 
		 * @var string
		 */
		private $subtitle;
		/**
		 * Array of series in the report
		 * 
		 * @var array
		 */
		private $series;
		/**
		 * Preset variables
		 *
		 */
		private
			$activePresetId,
			$presetIsModified,
			$presetCanModify,
			$presetList = [];

		/**
		 * Constructor.
		 */
		public function __construct($name = null) {
			$this->name = $name;
			$this->title  = "";
			$this->subtitle = "";
			$this->series = [];

			$this->usePreparedStatement = false;
		}

		//setters
		/**
		 * Request setter.
		 * 
		 * @param string						$request		The request recieved by the controller.
		 *
		 * @return self
		 */
		public function setRequest($request) {
			$this->request = $request;
			$this->parseRequest();

			return $this;
		}

		/**
		 * Title setter.
		 * 
		 * @param string						$title 			Title of the report.
		 * @param string						$subtitle		Subtitle of the report.
		 *
		 * @return self
		 */
		public function setTitle($title,  $subtitle) {
			$this->title = $title;

			return $this;
		}
			public function setSubtitle($subtitle) {
				$this->subtitle = $subtitle;

				return $this;
			}

		public function addFilters($filters) {
			$this->filters = $filters;

			return $this;
		}


		public function usePreparedStatement() {
			$this->usePreparedStatement = true;

			return $this;
		}


		//getters

		//private functions
		/**
		 * Parse request
		 * 
		 * @return self
		 */
		private function parseRequest() {
			$filter 			= array();
			$columnArrangement 	= null;
			$hiddenColumns 		= null;

			if(isset($this->request['filter'])) {
				$filterObject = $this->request['filter'];

				$filter 			= isset($filterObject['filter']) ? 
					$filterObject['filter'] : $filter;
				$columnArrangement 	= isset($filterObject['columnArrangement']) ? 
					$filterObject['columnArrangement'] : $columnArrangement;
				$hiddenColumns 		= isset($filterObject['hiddenColumns']) ? 
					$filterObject['hiddenColumns'] : $hiddenColumns;
			}
			$this->filter 				= $filter;
			$this->columnArrangement	= $columnArrangement;
	 		$this->hiddenColumns		= $hiddenColumns;

			if(isset($this->request['preset'])) {
	 			$this->presetHandler = new oDataPresetHandler($this->name, $this->request['preset']);
	 		} else {
	 			$this->presetHandler = new oDataPresetHandler($this->name);
	 		}

	 		$this->handlePreset();
			
			return $this;
		}

		/**
		 * Handle preset
		 *
		 * @return self
		 */
		private function handlePreset() {
			$this->presetCanModify = false;

			$filterDefaultValue = [];
			$columnArrangementDefaultValue = null;

			$nonModifiedHash = md5(json_encode($filterDefaultValue).json_encode($columnArrangementDefaultValue));

			if($this->presetHandler->isDeleteAction()) {
				$this->presetHandler
					->deletePresetSetting("filter")
					->deletePresetSetting("columnArrangement")
					->deletePresetSetting(":hash");

				$this->filter = array();
				$this->columnArrangement = null;
			}

			if($this->presetHandler->isLoadAction()) {
				$this->filter 				= $this->presetHandler->loadPresetSetting("filter");
				$this->columnArrangement 	= $this->presetHandler->loadPresetSetting("columnArrangement");

				if($this->filter == null)
					$this->filter = array();
			}

			$presetHash = md5(
				json_encode($this->filter).
				json_encode($this->columnArrangement)
			);

			if($this->presetHandler->isSaveAction()) {
				$this->presetHandler
					->savePresetSetting("filter", $this->filter)
					->savePresetSetting("columnArrangement", $this->columnArrangement)
					->savePresetSetting(":hash", $presetHash);
			}

			if($this->presetHandler->isPresetActive()) {
				$preset = $this->presetHandler->getPreset();
				$this->activePresetId 	= $preset->getId();

				if($preset->isGeneral())
					$this->presetCanModify = oDataFrameworkHelperController::canModifyGeneralPresets();
				else
					$this->presetCanModify = true;

				if($this->activePresetId == "default")
					$this->presetIsModified = ($nonModifiedHash != $presetHash);
				else
					$this->presetIsModified = ($this->presetHandler->loadPresetSetting(":hash") != $presetHash);				
			} else {
				$this->activePresetId = null;
				$this->presetIsModified = ($nonModifiedHash != $presetHash);
			}

			$this->presetList = $this->presetHandler->getPresetList();
		}

		//public functions
		/**
		 * Add a series to the report.
		 * 
		 * @param oReportSeries					$series			A series object.
		 * 
		 * @return self
		 */
		public function addSeries(oReportSeries $series) {
			$series->setParsedRequest(
				$this->filter,
				$this->columnArrangement
			);

			$this->series[] = $series;

			return $this;
		}

		/**
		 * The render function. Used to generate the final output.
		 * 
		 * @param Controller					$helperController		The helper controller containing all essential framework helper functions.
		 * @param Controller					$DBController			The DB controller containing all essential DB helper functions.
 		 *
		 * @return array
		 */
		public function render() {
			$this->DBController 	= new oTablesFrameworkDBController;
			$this->helpController 	= new oDataFrameworkHelperController;

			$output 		= [];
			$renderedSeries = [];

			$this->DBController
				->prefilterFilterValues(false)
				->usePreparedStatement($this->usePreparedStatement);

			foreach($this->series as $key => $series) {
				foreach($this->filters as $filter) {
					$series->addFilter($filter[0], $filter[1]);
				}

				$render = $series->render($this->helpController, $this->DBController);

				if($render)
					$renderedSeries[] = $render;
			}

			$output["header"] = [
				"title" => $this->title,
				"subtitle" => $this->subtitle,
				"preset" => [
					"id" => $this->activePresetId,
					"modified" => $this->presetIsModified,
					"canModify" => $this->presetCanModify,
					"isDefault" => $this->presetHandler->isDefaultPresetActive(),
					"isGeneralDefault" => $this->presetHandler->isGeneralDefaultPresetActive(),
					"isGeneral" => $this->presetHandler->isGeneral()
				],
				"presetList" => $this->presetList,
			];
			$output["options"] = [];
			$output["series"]  = $renderedSeries;

			return $output;
		}

	}