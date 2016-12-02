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
			$string = preg_replace($search, '', strip_tags($string));
            $string = SylkWriterString::fromUTF8($string);

			return trim($string);
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

					if($value && $value > 0)
					{
						$value = round(($value/86400) + 25569);	
					}
					else
					{
						$value = '""';
					}
					
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