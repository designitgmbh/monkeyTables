<?php
	namespace Designitgmbh\MonkeyTables\Report;

	/**
	 * A static class containing all the squashing methods that can be used in the reports.
	 * 
	 * @package    MonkeyTables
	 * @author     Philipp Pajak <p.pajak@design-it.de>
	 * @license    https://raw.githubusercontent.com/designitgmbh/monkeyTables/master/LICENSE  BSD
	 */
	class oReportSquasher
	{
		/**
		 * Main squashing function.
		 * 
		 * @param array							$dataSet			The corresponding dataSet the will be squashed. Array of objects.
		 * @param oReportAxis					$xAxis				The xAxis used for the squash.
		 */
		public static function squash(&$dataSet, $xAxis) {
			$xValues = [];
			foreach($dataSet as $obj) {
				$xValues[] = $xAxis->render($obj);
			}

			//find right squash function
			$axisType = $xAxis->getType();
			$options  = $xAxis->getTypeOptions();

			//squash
			switch($axisType) {
				case("UNIQUE BY COLUMN"):
					$dataSet = self::squashGroupBy($xValues, $dataSet, $options);
					break;

				case("DATE_YEAR"):
				case("DATE_YEAR_SEQ"):
					//one entry per year; format Y
					$dataSet = self::squashDateYearSeq($xValues, $dataSet, $options);
					break;

				case("DATE_MONTH_YEAR"):
				case("DATE_MONTH"):
				case("DATE_MONTH_SEQ"):
					//one entry per month.year ; format m.Y
					$dataSet = self::squashDateMonthYearSeq($xValues, $dataSet, $options);
					break;

				case("DATE_DAY_MONTH_YEAR"):
				case("DATE_DAY"):
				case("DATE_DAY_SEQ"):
					//one entry per day.month.year ; format d.m.Y
					$dataSet = self::squashDateDayMonthYearSeq($xValues, $dataSet, $options);
					break;

				case("DATE_MONTHS"):
					//one entry per month, years squashed!; format m
					$dataSet = self::squashDateSeq($xValues, $dataSet, $options, "m", (new \DateInterval("P1M")));
					break;

				case("DATE_DAYS"):
					//one entry per day, years and months squashed!; format d
					$dataSet = self::squashDateSeq($xValues, $dataSet, $options, "d", (new \DateInterval("P1D")));
					break;

				case("DATE_WEEKDAYS"):
					//one entry per weekday
					$dataSet = self::squashDateSeq($xValues, $dataSet, $options, "N", (new \DateInterval("P1D")));
					break;

				case("DATE"):
					//??
				case("UNIQUE"):
				default:
					$dataSet = self::squashDefault($xValues, $dataSet, $options);
			}
		}

		/* UNIQUE */
		private static function squashDefault($xValues, $xObjs) {
			$uniqueValues = [];

			foreach($xValues as $key => $value) {
				$obj = $xObjs[$key];

				if(!isset($uniqueValues[$value])) {
					$uniqueValues[$value] = new oReportDataSetEntry($value);
				}

				$uniqueValues[$value]->addObject($obj);
			}
			
			return array_values($uniqueValues);
		}

		/* UNIQUE BY COLUMN */
		private static function squashGroupBy($xValues, $xObjs, $groups) {
			$uniqueValues = [];

			foreach($groups as $group) {
				$groupValueKey 			= $group["valueKey"];
				$uniqueGroup 			= [];				

				foreach($xObjs as $obj) {
					$value = $obj->$groupValueKey;

					if(!isset($uniqueGroup[$value])) {
						$uniqueGroup[$value] = $obj;
					}
				}

				$uniqueValues[]	= (new oReportDataSetEntry($group["name"]))->setObjects($uniqueGroup);
			}

			return array_values($uniqueValues);
		}

		/* DATE SEQ */
		private static function squashDateSeq($xValues, $xObjs, $limits = null, $format = "Y", $dateInterval = null) {
			if($dateInterval == null) {
				new \DateInterval("P1Y");
			}

			$dates = [];

			switch($limits) {
				case(is_array($limits)):
					$dateMin = new \DateTime($limits["from"]);
					$dateMax = new \DateTime($limits["to"]);
					break;
				case("auto"):

				default:
					$dateMin = new \DateTime("-1 year january");
					$dateMax = new \DateTime("december");
					break;
			}

			do {
				$dateFormated = $dateMin->format($format);

				switch($format) {
					case("m"):
						//month only
						$xValue = \oTablesFrameworkHelperController::translate("labels.month".$dateFormated);
						break;
					case("N"):
						//weekday only
						$xValue = \oTablesFrameworkHelperController::translate("labels.weekday".$dateFormated);
						break;
					default:
						$xValue = $dateFormated;
						break;
				}

				$dates[$dateFormated] = new oReportDataSetEntry($xValue);
				$dateMin->add($dateInterval);
			} while( $dateMin <= $dateMax );

			if(strpos($format, ".") === false) {
				ksort($dates);
			}

			foreach($xValues as $key => $date) {
				$dateFormated = date($format, strtotime($date));

				if(isset($dates[$dateFormated]))
					$dates[$dateFormated]->addObject($xObjs[$key]);
			}

			return array_values($dates);
		}

		/* DATE YEAR SEQ */
		private static function squashDateYearSeq($xValues, $xObjs, $limits = null) {
			return self::squashDateSeq($xValues, $xObjs, $limits, "Y", (new \DateInterval("P1Y")));
		}

		/* DATE MONTH YEAR SEQ */
		private static function squashDateMonthYearSeq($xValues, $xObjs, $limits = null) {
			return self::squashDateSeq($xValues, $xObjs, $limits, "m.Y", (new \DateInterval("P1M")));
		}

		/* DATE DAY MONTH YEAR SEQ */
		private static function squashDateDayMonthYearSeq($xValues, $xObjs, $limits = null) {
			return self::squashDateSeq($xValues, $xObjs, $limits, Config::get('formats.displayDate.php'), (new \DateInterval("P1D")));
		}
	}