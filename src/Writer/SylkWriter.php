<?php
	namespace Designitgmbh\MonkeyTables\Writer;

	/**
	 *  Sylk Writer
	 * 		This file provides helper functions, to write a "Sylk" file
	 *		These files can be opened by EXCEL and support limited formatting
	 *
	 * @package    MonkeyTables
	 * @author     Philipp Pajak <p.pajak@design-it.de>
	 * @license    https://raw.githubusercontent.com/designitgmbh/monkeyTables/master/LICENSE  BSD
	 */
	class SylkWriter
	{
		private
			$numberOfColumns = 0,
			$columns = [],
			$rows = [];

		public function defineFormatting($cells) 
		{
			foreach($cells as $idx => $cell)
				$this->defineFormattingForCell($idx, $cell);
		}

		public function defineFormattingForCell($idx, $format) 
		{
			$this->columns[$idx] = $format;
		}


		public function addRow($cells) 
		{
			foreach($cells as $idx => $cell) 
			{
				$cells[$idx] = $this->replaceStrings($cell);
			}

			$this->rows[] = $cells;
		}

		private function replaceStrings($string) 
		{
			$search = array('@<script[^>]*?>.*?</script>@si',  // Strip out javascript 
			               '@<[\/\!]*?[^<>]*?>@si',            // Strip out HTML tags 
			               '@<style[^>]*?>.*?</style>@siU',    // Strip style tags properly 
			               '@<![\s\S]*?--[ \t\n\r]*>@'         // Strip multi-line comments including CDATA 
			); 
			$string = preg_replace($search, '', $string);
			$this->replaceUmlaute($string);

			return $string;
		}

		private function replaceUmlaute(&$string) 
		{
			$search = [
				"ä",
				"ö",
				"ü",
				"Ä",
				"Ö",
				"Ü",
			];
			$replace = [
				hex2bin("1b")."NHa",
				hex2bin("1b")."NHo",
				hex2bin("1b")."NHu",
				hex2bin("1b")."NHA",
				hex2bin("1b")."NHO",
				hex2bin("1b")."NHU"
			];

			$string = str_replace($search, $replace, $string);
		}

		public function render() 
		{
			$sylk = '';
			$sylk .= SylkWriterTemplate::fileHeader();
			$sylk .= $this->renderFormatting();
			$sylk .= $this->renderRows();
			$sylk .= SylkWriterTemplate::fileEnd();

			return $sylk;
		}

		private function renderFormatting() 
		{
			$sylkFormatting = '';

			$C = 1;
			foreach($this->columns as $column) 
			{
				switch($column) 
				{
					case("currency"):
						$sylkFormatting .= "F;P36;FF2G;C$C\n";
						break;
					case("date"):
					case("timeline"):
						$sylkFormatting .= "F;P19;FG0G;C$C\n";
						break;
					case("number"):
						break;
					default:
						break;
				}

				$C++;
			}

			return $sylkFormatting;
		}

		private function renderRows() 
		{
			$sylkRows = '';
			$Y = 1;
			foreach($this->rows as $rowIdx => $row) 
			{
				$X = 1;
				foreach($row as $columnIdx => $cell) 
				{
					$sylkCell = "C;Y$Y;X$X;N;K" . $this->formatValue($cell, $columnIdx, $rowIdx);
					$sylkRows .= $sylkCell . "\n";
					$X++;
				}
				$Y++;
			}

			return $sylkRows;
		}

		private function formatValue($value, $columnIdx, $rowIdx) 
		{
			if($rowIdx == 0)
				return '"' . $value . '"';

			switch($this->columns[$columnIdx]) 
			{
				case("currency"):
					$value = str_replace(".", "", $value);
					$value = str_replace(",", ".", $value);
					break;
				case("date"):
				case("timeline"):
					//... divide by 86400 + 25569
					//as suggested by wikipedia to 
					//convert unix timestamp into 
					//SYLK compatible format

					if($value != intval($value))
						$value = strtotime($value);
					$value = round(($value/86400) + 25569);
					break;
				case("number"):
					$value = str_replace(".", "", $value);
					$value = str_replace(",", ".", $value);
					break;
				default:
					$value = '"' . $value . '"';
					break;
			}

			return $value;
		}
	}

	Class SylkWriterTemplate
	{
		public static function fileHeader() 
		{
			return 'ID;PWXL;N;E
P;PGeneral
P;P0
P;P0.00
P;P#,##0
P;P#,##0.00
P;P#,##0_);;\(#,##0\)
P;P#,##0_);;[Red]\(#,##0\)
P;P#,##0.00_);;\(#,##0.00\)
P;P#,##0.00_);;[Red]\(#,##0.00\)
P;P"$"#,##0_);;\("$"#,##0\)
P;P"$"#,##0_);;[Red]\("$"#,##0\)
P;P"$"#,##0.00_);;\("$"#,##0.00\)
P;P"$"#,##0.00_);;[Red]\("$"#,##0.00\)
P;P0%
P;P0.00%
P;P0.00E+00
P;P##0.0E+0
P;P#\ ?/?
P;P#\ ??/??
P;Pm/d/yyyy
P;Pd\-mmm\-yy
P;Pd\-mmm
P;Pmmm\-yy
P;Ph:mm\ AM/PM
P;Ph:mm:ss\ AM/PM
P;Ph:mm
P;Ph:mm:ss
P;Pm/d/yyyy\ h:mm
P;Pmm:ss
P;Pmm:ss.0
P;P@
P;P[h]:mm:ss
P;P_("$"* #,##0_);;_("$"* \(#,##0\);;_("$"* "-"_);;_(@_)
P;P_(* #,##0_);;_(* \(#,##0\);;_(* "-"_);;_(@_)
P;P_("$"* #,##0.00_);;_("$"* \(#,##0.00\);;_("$"* "-"??_);;_(@_)
P;P_(* #,##0.00_);;_(* \(#,##0.00\);;_(* "-"??_);;_(@_)
P;P_-* #,##0.00\ [$'.hex2bin("1b").'(0-407]_-;;\-* #,##0.00\ [$'.hex2bin("1b").'(0-407]_-;;_-* "-"??\ [$'.hex2bin("1b").'(0-407]_-;;_-@_-
P;FCalibri;M220;L9
P;FCalibri;M220;L9
P;FCalibri;M220;L9
P;FCalibri;M220;L9
P;ECalibri;M220;L9
P;ECalibri Light;M360;L55
P;ECalibri;M300;SB;L55
P;ECalibri;M260;SB;L55
P;ECalibri;M220;SB;L55
P;ECalibri;M220;L18
P;ECalibri;M220;L21
P;ECalibri;M220;L61
P;ECalibri;M220;L63
P;ECalibri;M220;SB;L64
P;ECalibri;M220;SB;L53
P;ECalibri;M220;L53
P;ECalibri;M220;SB;L10
P;ECalibri;M220;L11
P;ECalibri;M220;SI;L24
P;ECalibri;M220;SB;L9
P;ECalibri;M220;L10
P;ESegoe UI;M200;L9
P;ESegoe UI;M200;SB;L9
F;P0;DG0G10;M300
B;Y3;X4;D0 0 2 3
O;L;D;V0;K47;G100 0.001
';
		}

		public static function fileEnd() 
		{
			return 'E
';
		}
	}