<?php

namespace Designitgmbh\MonkeyTables\Http\Controllers;

class oTablesFrameworkHelperController extends oDataFrameworkHelperController
{
	public function generateToolbox($obj)
	{
		//TODO: refactor getToolboxCell function so that it calculates these values automatically
		$modelName = lcfirst(get_class($obj));
		$routeName = str_replace('_','.',snake_case($modelName));

		if(Route::has($routeName)||Route::has($routeName.".destroy")) 
		{
			$strModel = snake_case($modelName);
		} 
		 else 
		{
			$routeName = strtolower($modelName);
			$strModel = $routeName;
		}

		$modalName = '#edit_' . $strModel . '_modal';

		return $obj->getToolboxCell(
			$obj->id,
			$routeName,
			array(
				'canShow' => true, 
				'canEdit' => true, 
				'canDelete' => true
			),
			'javascript:void(0)',
			true,
			$modalName
		);
	}
}

?>