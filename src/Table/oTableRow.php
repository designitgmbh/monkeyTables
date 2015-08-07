<?php
	namespace Designitgmbh\MonkeyTables\Table;

	use Designitgmbh\MonkeyTables\Writer\SylkWriter;

	/**
	 *  Row renderer
	 * 		this does NOT represent one specific row, it just contains row specific functions
	 *
	 * @package    MonkeyTables
	 * @author     Philipp Pajak <p.pajak@design-it.de>
	 * @license    https://raw.githubusercontent.com/designitgmbh/monkeyTables/master/LICENSE  BSD
	 */
	class oTableRow
	{
		protected 
			$columns,
			$writer = null;

		public function __construct($columns) {
			$this->columns = $columns;
		}

		public function generateDefaultRow($options) {
			return array(
				"OPTIONS" 	=> $options,
				"DATA"		=> array()
			);
		}

		public function renderCSVHeader($options, $dl = ";", $nl = "\n") {
			$csv = "";
			foreach ($this->columns as $column) {
				if($column->isEnabled())
					$csv .= $this->html2txt($column->getHeaderData()) . $dl;
			}
			$csv .= $nl;

			return $csv;
		}

		public function renderCSVRow($obj, $options = null, $dl = ";", $nl = "\n") {
			$csv = "";

			foreach ($this->columns as $column) {
				if($column->isEnabled())
					$csv .= $this->html2txt($column->getData($obj, true)) . $dl;
			}
			$csv .= $nl;

			return $csv;
		}

		public function renderSYLKHeader($options = null) {
			$this->writer = new SylkWriter();

			$formats = [];
			$header = [];
			foreach($this->columns as $column) {
				$formats[] = $column->getType();
				$header[] = $column->getHeaderData();
			}

			$this->writer->defineFormatting($formats);
			$this->writer->addRow($header);
		}

		public function renderSYLKRow($obj, $options = null) {
			$row = [];

			foreach($this->columns as $column) {
				$row[] = $column->getData($obj, true);
			}
			
			$this->writer->addRow($row);
		}

		public function renderSYLK() {
			return $this->writer->render();
		}

		public function renderHeader($options) {
			$row = array();

			foreach ($this->columns as $column) {
				$row[] = $column->renderHeader();
			}

			return $row;
		}

		public function renderRow($obj, $options = null) {
			$row = $this->generateDefaultRow($options);
			
			foreach($this->columns as $column) {
				if($column->isEnabled())
					$row["DATA"][] = $column->render($obj);
			}

			return $row;
		}

		private function html2txt($document){ 
			$search = array('@<script[^>]*?>.*?</script>@si',  // Strip out javascript 
			               '@<[\/\!]*?[^<>]*?>@si',            // Strip out HTML tags 
			               '@<style[^>]*?>.*?</style>@siU',    // Strip style tags properly 
			               '@<![\s\S]*?--[ \t\n\r]*>@'         // Strip multi-line comments including CDATA 
			); 
			$text = preg_replace($search, '', $document); 
			return $text; 
		}


	}


?>