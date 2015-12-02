<?php
	namespace Designitgmbh\MonkeyTables\Table;

	/**
	 * Extending oTableColumn to support hierarchies
	 *
	 * @package    MonkeyTables
	 * @author     Philipp Pajak <p.pajak@design-it.de>
	 * @license    https://raw.githubusercontent.com/designitgmbh/monkeyTables/master/LICENSE  BSD
	 */
	class oTableHierarchyColumn extends oTableColumn
	{
		protected $canRearrangeColumns = false;

		/**
		 * extend initValues function
		 */
		public function initValues() 
		{
			parent::initValues();

			$this->level = null;
		}

		/**
		 * set level of column
		 *
		 * @param $level 		The level of the column
		 */
		public function setLevel($level)
		{
			$this->level = $level;

			return $this;
		}

		/**
		 * get level of column
		 *
		 * @return int 			The level of the column
		 */
		public function getLevel()
		{
			return $this->level;
		}

		/**
		 * is level of column
		 *
		 * @param $level 		The level of the column
		 * @return boolean 		Is the level provided the level of the column?
		 */
		public function isLevel($level)
		{
			return $this->level == $level;
		}
	}