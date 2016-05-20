<?php

namespace Designitgmbh\MonkeyTables\Http\Controllers;

use Request;
use Designitgmbh\MonkeyTables\Export\WExport;

class oDataFrameworkHelperController extends Controller
{
	//framework config variables
	protected $_subjectTranslateString 	= "L_OTABLE_EXPORT_SUBJECT";
	protected $_mailView 				= "emails.oTableExport";
	protected $_printViewRoute 			= "oTable.printView";
	protected $printViewConfigURL		= 'monkeyTables.export.printView.tablesUrl';

	private static function resolveUserSettingClass()
	{
		if(class_exists("UserSetting"))
			$class = "UserSetting";
		else if(class_exists("App\Models\UserSetting"))
			$class = "App\Models\UserSetting";
		else if(class_exists("App\UserSetting"))
			$class = "App\UserSetting";
		else
			$class = "Designitgmbh\MonkeySettings\Models\UserSetting";

		return $class;
	}

	public static function setSystemUserSetting($settingKey, $settingValue, $userId = 0)
	{
		$class = self::resolveUserSettingClass();
		return $class::set($settingKey, $settingValue, $userId);
	}

	public static function getSystemUserSetting($settingKey, $userId = 0)
	{
		$class = self::resolveUserSettingClass();
		return $class::get($settingKey, $userId);
	}

	private static function resolveSystemSettingClass()
	{
		if(class_exists("SystemSetting"))
			$class = "SystemSetting";
		else if(class_exists("App\Models\SystemSetting"))
			$class = "App\Models\SystemSetting";
		else if(class_exists("App\SystemSetting"))
			$class = "App\SystemSetting";
		else 
			$class = "Designitgmbh\MonkeySettings\Models\SystemSetting";

		return $class;
	}

	public static function getGeneralSetting($settingKey) 
	{
		$class = self::resolveSystemSettingClass();
		return $class::get($settingKey);
	}

	public static function setGeneralSetting($settingKey, $settingValue)
	{
		$class = self::resolveSystemSettingClass();
		return $class::set($settingKey, $settingValue);
	}

	public static function canModifyGeneralPresets()
	{
		if(method_exists("WAccessRight", "can")) {
			return \WAccessRight::can('manage', 'generalPresets');	
		}

		return true;
	}

	public static function translate($string, $choice = null)
	{
		if($choice !== null) {
			return trans_choice($string, $choice);
		}

		return trans($string);
	}

	public function getRoute($route, $arguments = null)
	{
		return route($route, $arguments, false);
	}

	public function sendMail() 
	{
		$validator = Validator::make(Input::all(), [
			'userID' => 'required|exists:user,id',
			'exportType' => 'required|in:pdf,csv',
			'request' => 'required'
		]);
		
		if($validator->fails())
			return Response::json(['status' => 'error', 'message' => $validator->failed()]);

		$userID 		= Input::get('userID');
		$exportType 	= Input::get('exportType');
		$request 		= Input::get('request');
		$subject 		= $this->translate($this->_subjectTranslateString);

		switch($exportType) 
		{
			case("pdf"):
				$attachment = $this->exportPDF($request, false);
				$mimeType   = 'application/pdf';
				$fileName   = 'export.pdf';
				break;
			case("csv"):
				$attachment = $this->exportCSV($request);
				$mimeType   = 'application/vnd.ms-excel';
				$fileName   = 'export.csv';
				break;
			default:
				$attachment = null;
				break;
		}

		$user = User::find($userID);
		Mail::send($this->_mailView, array('type' => $exportType), function($message) use($user, $subject, $attachment, $fileName, $mimeType)
		{
			$message->to($user->email, $user->name)->subject($subject);
			$message->attachData($attachment, $fileName, array('mime' => $mimeType));
		});

		return Response::json(['status' => 'ok']);
	}

	public function exportCSV($JSONRequest) 
	{
		$request = json_decode($JSONRequest, true);
		$url 	= $request['url'];
		$data 	= $request['queryData'];

		return WCurl::localPostRequest($url, $data, true);
	}

	public function exportPDF($JSONRequest = null, $returnResponse = true) 
	{
		//need to add slashes to JSON due to issues with slashes
		$JSONRequest = $JSONRequest ?: Request::get('JSON');

		$printViewUrl = config($this->printViewConfigURL);
		if($printViewUrl)
		{
			$printViewUrl = $this->generateFullUrl($printViewUrl);
		}
		else
		{
			$printViewUrl = route($this->_printViewRoute);
		}

		return WExport::exportPDF(
			$printViewUrl,
			array('JSON' => $JSONRequest), 
			$returnResponse
		);
	}

	public function printView() 
	{
		if($redirectUrl = config('monkeyTables.export.printView.redirect'))
		{
			return redirect($this->generateFullUrl($redirectUrl))
				->withInput();
		}

		//need to add slashes to JSON due to issues with slashes
		//probably because it is echoed into the blade view
		$JSONRequest = addslashes(Request::get('JSON'));

		return view($this->_printViewRoute, array('JSONRequest' => $JSONRequest));
	}

	private function generateFullUrl($url)
	{
		//we are "regenerating" the url, as otherwise lumen
		//would just take the 'base url path' and add it
		//although we want to get back to the root url
		$request = app()->make('request');
		
		$root = $request->getSchemeAndHttpHost();
		$tail = "";

		return trim($root.'/'.trim($url.'/'.$tail, '/'), '/');
	}
}