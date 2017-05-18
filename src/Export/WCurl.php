<?php
namespace Designitgmbh\MonkeyTables\Export;

use Exception;

/**
 * A curl helper, to make requests easier.
 *
 * @package    MonkeyTables
 * @author     Philipp Pajak <p.pajak@design-it.de>
 * @license    https://raw.githubusercontent.com/designitgmbh/monkeyTables/master/LICENSE  BSD
 */

class WCurl
{
    private $curl = null;

    static function localGetRequest($url, $params = null, $withCurrentUserCookie = false)
    {
        return self::getRequest(Request::root() . $url, $params, $withCurrentUserCookie);
    }

    static function getRequest($url, $params = null, $withCurrentUserCookie = false)
    {
        if (isset($params) && $params != null) {
            $url .= '?'.http_build_query($params);
        }

        $curl = new WCurl($url);

        if ($withCurrentUserCookie) {
            $curl->addLaravelCookie();
        }

        return $curl->exec();
    }

    static function localPostRequest($url, $params = null, $withCurrentUserCookie = false)
    {
        return self::postRequest(route('root.url') . $url, $params, $withCurrentUserCookie);
    }

    static function postRequest($url, $params = null, $withCurrentUserCookie = false)
    {
        $curl = new WCurl($url, $params);

        if ($withCurrentUserCookie) {
            $curl->addLaravelCookie();
        }

        return $curl->exec();
    }

    function __construct($url = null, $post = false)
    {
        $this->curl = curl_init();
        curl_setopt($this->curl, CURLOPT_URL, $url);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4); //curl fails to fallback to ipv4 if ipv6 is not available, so force ipv4

        if ($post) {
            $this->setPost($post);
        }
    }

    public function addLaravelCookie()
    {
        $encrypter = new \Illuminate\Encryption\Encrypter(Config::get('app.key'));
        $cookieEnc = $encrypter->encrypt(Session::getId());
            
        curl_setopt($this->curl, CURLOPT_COOKIE, 'laravel_session='.$cookieEnc.'; path=/');

        return $this;
    }

    public function setCookie($cookie)
    {
        curl_setopt($this->curl, CURLOPT_COOKIE, $cookie);

        return $this;
    }

    public function setPost($post = true)
    {
        curl_setopt($this->curl, CURLOPT_POST, true);

        if ($post && !is_bool($post)) {
            $this->setPostData($post);
        }

        return $this;
    }

    public function setPostData($data)
    {
        if (is_array($data)) {
            $data = http_build_query($data);
        }
        
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $data);

        return $this;
    }

    public function exec()
    {
        $output = curl_exec($this->curl);

        $errno = curl_errno($this->curl);
        $error = curl_error($this->curl);
        
        $statusCode = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);

        curl_close($this->curl);
        if ($errno != 0) {
            throw new Exception($error);
        } elseif ($statusCode != 200) {
            throw new Exception($output, $statusCode);
        }

        return $output;
    }
}
