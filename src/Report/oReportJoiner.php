<?php
	namespace Designitgmbh\MonkeyTables\Report;

	/**
	 * A static class containing all the joining methods that can be used in the reports.
	 * 
	 * @package    MonkeyTables
	 * @author     Philipp Pajak <p.pajak@design-it.de>
	 * @license    https://raw.githubusercontent.com/designitgmbh/monkeyTables/master/LICENSE  BSD
	 */
	class oReportJoiner
	{
		/**
		 * Main joining function.
		 * Has to be squashed before!
		 * 
		 * @param array							$dataSet			The corresponding dataSet that will be joined. Array of DataSetEntries.
		 * @param oReportAxis					$xAxis				The xAxis used for the squash
		 * @param string						$joinMethod			The joining method used
		 */
		public static function join(&$dataSet, $yAxis, $joinMethod) {
			//foreach dataSetEntry
			foreach($dataSet as $entry) {
				switch($joinMethod) {
					case("COUNT"):
						self::joinCount($entry, $yAxis);
						break;
					case("SUM"):
						self::joinSum($entry, $yAxis);
						break;
					case("CONCATENATE"):
					default:
						self::joinDefault($entry, $yAxis);
				}
			}
				
		}

		/* CONCATENATE */
		private static function joinDefault($dataSetEntry, $yAxis) {
			$value = "";

			foreach($dataSetEntry->getObjects() as $obj) {
				$value .= $yAxis->render($obj);
			}

			$dataSetEntry->setYValue($value);
		}

		private static function joinSum($dataSetEntry, $yAxis) {
			$value = 0;
			
			foreach($dataSetEntry->getObjects() as $obj) {
				$value += $yAxis->render($obj);
			}

			$dataSetEntry->setYValue($value);
		}

		private static function joinCount($dataSetEntry, $yAxis) {
			$counter = count($dataSetEntry->getObjects());

			$dataSetEntry->setYValue($counter);
		}
	}