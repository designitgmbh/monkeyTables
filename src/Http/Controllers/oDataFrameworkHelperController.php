<?php

namespace Designitgmbh\MonkeyTables\Http\Controllers;

//use App\Models\UserSetting;
//use App\Models\SystemSetting;
use Designitgmbh\MonkeySettings\Models\UserSetting;
use Designitgmbh\MonkeySettings\Models\SystemSetting;

class oDataFrameworkHelperController extends Controller
{
	//framework config variables
	protected $_subjectTranslateString 	= "L_OTABLE_EXPORT_SUBJECT";
	protected $_mailView 				= "emails.oTableExport";
	protected $_printViewRoute 			= "oTable.printView";

	public static function setSystemUserSetting($settingKey, $settingValue, $userId = 0)
	{
		return UserSetting::set($settingKey, $settingValue, $userId);
	}

	public static function getSystemUserSetting($settingKey, $userId = 0)
	{		
		return UserSetting::get($settingKey, $userId);
	}

	public static function getGeneralSetting($settingKey) 
	{
		return SystemSetting::get($settingKey);
	}

	public static function setGeneralSetting($settingKey, $settingValue)
	{
		return SystemSetting::set($settingKey, $settingValue);
	}

	public static function canModifyGeneralPresets()
	{
		/*return WAccessRight::can('manage', 'generalPresets');*/
		return true;
	}

	public static function translate($string)
	{
		return trans($string);
	}

	public function getRoute($route, $arguments)
	{
		return URL::route($route, $arguments);
	}

	public function sendMail() 
	{
		$validator 		= Validator::make(Input::all(), [
				'userID' => 'required|exists:user,id',
				'exportType' => 'required|in:pdf,csv',
				'request' => 'required']);
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
		$JSONRequest = $JSONRequest ?: Input::get('JSON');
		$businessPaper = BusinessPaper::where('name', '=', 'Horizontal')->first();
		return WExport::exportPDF(URL::route($this->_printViewRoute), $businessPaper, array('JSON' => $JSONRequest), $returnResponse);
	}

	public function printView() 
	{
		//need to add slashes to JSON due to issues with slashes
		//probably because it is echoed into the blade view
		$JSONRequest = addslashes(Input::get('JSON'));

		return View::make($this->_printViewRoute, array('JSONRequest' => $JSONRequest));
	}
}