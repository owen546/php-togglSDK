<?php
require_once('Classloader.php');

$classLoader = new Toggl_Classloader();
spl_autoload_register(array(&$classLoader, "loadClass"));

class Toggl{

    /*
     * API URL parts
     */
    private static $token;
    public static $debug = true;
    public static $verifyPeer = true;

    public static function setKey($apiKey) {
        self::$token = $apiKey;
    }
    public static function verifyPeer($bool){
        self::$verifyPeer = $bool;
    }

    private static function sendWithAuth($params) {
        $url = $params['url'];
        if (self::$debug == true){
            Debug::write( 'Request URL: ' . $url );
        }
        unset($params['url']);
        $method = $params['method'];
        if (self::$debug == true){
            Debug::write( 'Request method: ' . $method );
        }
        unset($params['method']);
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_USERPWD, self::$token . ':api_token');
        if (self::$verifyPeer == false){
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        }

        if (self::$debug == true){
            Debug::write( 'API Token: ' . self::$token );
        }
        if ($method == 'POST'){
            curl_setopt($curl, CURLOPT_POST, true);
            $params = json_encode($params);
            if (self::$debug == true){
                Debug::write( "POST json: " . $params );
            }
            curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($params),
            ));
        }
        if ($method == 'PUT'){
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
            $params = json_encode($params);
            if (self::$debug == true){
                Debug::write( "PUT json: " . $params );
            }
            curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                                                        'Content-Type: application/json',
                                                        'Content-Length: ' . strlen($params),
                                                   ));
        }
        $result = curl_exec($curl);
        Debug::write( "Curl result: $result" );
        $info = curl_getinfo($curl);
        curl_close($curl);
        $resultJson = json_decode($result, true);
        if (is_array($resultJson)){
            if (count($resultJson) == 1 && isset($resultJson['data'])){
                $resultJson = $resultJson['data'];
            }
            return $resultJson;
        } else {
            $errorMessage = 'Toggl API call failed -- Request URL: ' . $url . (is_string($params)? ' Request Data: ' . $params : null) . ' Response code: ' . $info['http_code'] . ' Raw response dump: ' . $result . ' serialized CURL info: ' . serialize($info);
            throw new Exception($errorMessage);
        }
    }

    public static function send($params = array()) {
        return self::sendWithAuth($params);
    }

    public static function checkConnection(){
        TogglUser::getCurrentUserData();
    }

}

class Debug {
    public static function write( $message )
    {
        eZDebug::writeDebug( $message );
    }
}
