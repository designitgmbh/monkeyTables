<?php
	namespace Designitgmbh\MonkeyTables\Format;

    use Designitgmbh\MonkeyTables\Http\Controllers\oTablesFrameworkHelperController;

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
			return number_format(floatval($number), $decimals, $dec_point, $thousands_sep);
		}

		/**
		 * Returns the correct currency string interpretation of a double value
		 * @param string $number - number to be formatted
		 * @return string - currency string interpretation of double value passed as parameter
		 */
		public static function format($number, $decimals = 2, $dec_point = ",", $thousands_sep = ".") 
		{	
			return self::decimal($number, $decimals, $dec_point, $thousands_sep);
		}

        /**
         * Returns the correct currency string interpretation of a double value with a currency symbol
         * @param string $number - number to be formatted
         * @return string - currency string interpretation of double value passed as parameter
         */
        public static function formatWithSymbol($number, $decimals = 2, $dec_point = ",", $thousands_sep = ".")
        {
            $currencySymbol = oTablesFrameworkHelperController::translate('labels.currency_symbol');
            $number = self::format($number, $decimals, $dec_point, $thousands_sep);

            if(config('monkeyTables.format.currency_symbol_prepend')) {
                return $currencySymbol . " " . $number;
            } else {
                return $number . " " . $currencySymbol;
            }
        }

	}