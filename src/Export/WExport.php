<?php
namespace Designitgmbh\MonkeyTables\Export;

/**
 * The export class for monkey-data.
 *
 * @package    MonkeyTables
 * @author     Philipp Pajak <p.pajak@design-it.de>
 * @license    https://raw.githubusercontent.com/designitgmbh/monkeyTables/master/LICENSE  BSD
 */

class WExport
{
    
    public static function exportPDF($route, $postData = null, $returnResponse = true)
    {
        if ($cookieName = config('monkeyTables.export.cookie')) {
            $cookieEnc = $_COOKIE[$cookieName];
        } else {
            $encrypter = app()->make('encrypter');
            $cookieName= 'laravel_session';
            $cookieEnc = $encrypter->encrypt(Session::getId());
        }
        

        $businessPaper = (object)config('monkeyTables.businessPaper');

        $params = [
            'url' => $route,
            'download' => 'false',
            'format' => $businessPaper->paperSize['name'],
            'orientation' => $businessPaper->orientation,
            'margin' => $businessPaper->margin . $businessPaper->units,
            'sessionCookie' => json_encode(array(
                "name"   => $cookieName,
                "value"  => $cookieEnc,
                "domain" => $_SERVER['SERVER_NAME']
            )),
            'postData' => json_encode($postData)
        ];

        $output = WCurl::getRequest(config('monkeyTables.service.html2pdf'), $params);

        if ($returnResponse) {
            return response($output, 200, array('content-type' => 'application/pdf'));
        } else {
            return $output;
        }
    }
}
