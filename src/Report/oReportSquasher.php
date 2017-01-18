<?php
	namespace Designitgmbh\MonkeyTables\Report;

	use Designitgmbh\MonkeyTables\Http\Controllers\oDataFrameworkHelperController;

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
			$formatFromMinDate = true;
			$businessYear = false;
			$prependLabel = "";

			switch($limits) {
				case(is_array($limits)):
					$dateMin = new \DateTime($limits["from"]);
					$dateMax = new \DateTime($limits["to"]);

					if(isset($limits['formatFromMaxDate']) && $limits['formatFromMaxDate']) {
						$formatFromMinDate = false;
					}

					if(isset($limits['businessYear']) && is_array($limits['businessYear'])) {
						$prependLabel = "BY ";
						$businessYear = $limits['businessYear'];
					}

					break;

				case("auto"):
				default:
					$dateMin = new \DateTime("-1 year january");
					$dateMax = new \DateTime("december");
					break;
			}

			do {
				if($formatFromMinDate)
					$dateFormated = $dateMin->format($format);

				if($dateMin->format('m') == 1){//Case next month is FEB
						$dateMin->add(new \DateInterval("P28D"));
				}else{
						$dateMin->add($dateInterval);
				}

				if(!$formatFromMinDate)
					$dateFormated = $dateMin->format($format);
				
				switch($format) {
					case("m"):
						//month only
						$xValue = oDataFrameworkHelperController::translate("labels.month".$dateFormated);
						break;
					case("N"):
						//weekday only
						$xValue = oDataFrameworkHelperController::translate("labels.weekday".$dateFormated);
						break;
					default:
						$xValue = $dateFormated;
						break;
				}

				$xValue = $prependLabel . $xValue;

				$dates[$dateFormated] = new oReportDataSetEntry($xValue);
			} while( $dateMin <= $dateMax );

			if(strpos($format, ".") === false) {
				ksort($dates);
			}

			foreach($xValues as $key => $date) {
				if(!is_numeric($date))
					$date = strtotime($date);

				$date = (new \DateTime())
					->setTimestamp($date);

				if($businessYear) {
					$month = $date->format('m');
					$businessYearStartMonth = date('m', strtotime($businessYear['start']));

					if($formatFromMinDate) {
						if ($month < $businessYearStartMonth)
							$date->sub($dateInterval);
					} else {
						if ($month > $businessYearStartMonth)
							$date->add($dateInterval);
					}
				}

				$dateFormated = $date->format($format);

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