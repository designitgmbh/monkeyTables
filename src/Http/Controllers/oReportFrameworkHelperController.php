<?php

namespace Designitgmbh\MonkeyTables\Http\Controllers;

class oReportFrameworkHelperController extends oDataFrameworkHelperController
{
	//framework config variables
	protected $_subjectTranslateString 	= "L_OREPORT_EXPORT_SUBJECT";
	protected $_mailView 				= "emails.oReportExport";
	protected $_printViewRoute			= "oReport.printView";
	protected $printViewConfigURL		= 'monkeyTables.export.printView.reportUrl';
}