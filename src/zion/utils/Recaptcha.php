<?php 
namespace zion\utils;

/**
 * @author Vinicius Cesar Dias
 */
class Recaptcha {
    public static function check($secret,$token){
        $url = "https://www.google.com/recaptcha/api/siteverify";
        
        $data = [
            "secret" => $secret,
            "response" => $token
        ];
        
        $obj = \zion\utils\HTTPUtils::curl2($url,"POST",$data);
        if($obj->curlInfo["http_code"] == 200){
            $json = json_decode($obj->body);
            return $json;
        }
        return null;
    }
}
?>