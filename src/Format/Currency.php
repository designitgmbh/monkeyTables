<?php
	namespace Designitgmbh\MonkeyTables\Format;

	/**
	 *  Currency
	 * 		This file provides helper functions, to format a currency value
	 *
	 * @package    MonkeyTables
	 * @author     Philipp Pajak <p.pajak@design-it.de>
	 * @license    https://raw.githubusercontent.com/designitgmbh/monkeyTables/master/LICENSE  BSD
	 */
	class Currency
	{
		/**
		 * Returns the correct decimal string interpretation of a double value
		 * @param double $number - number to be formatted
		 * @return string - decimal string interpretation of double value passed as parameter
		 */
		public static function decimal($number, $decimals = 2, $dec_point = ",", $thousands_sep = ".")
		{
			return number_format($number, $decimals, $dec_point, $thousands_sep);
		}

		/**
		 * Returns the correct currency string interpretation of a double value
		 * @param string $number - number to be formatted
		 * @return string - currency string interpretation of double value passed as parameter
		 */
		public static function format($number, $decimals = 2, $dec_point = ",", $thousands_sep = ".") {
			
			return self::decimal($number, $decimals, $dec_point, $thousands_sep);
		}
	}