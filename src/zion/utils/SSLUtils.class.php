<?php
namespace zion\utils;

use StdClass;
use DateTime;
use Exception;

/**
 * @author Vinicius Cesar Dias
 */
class SSLUtils {
    public static function getCertInfo($url){
        $obj              = new StdClass();
        $obj->altNames    = "";
        $obj->validFrom   = null;
        $obj->validTo     = null;
        $obj->subject     = new StdClass();
        $obj->subject->CN = "";
        $obj->issuer      = new StdClass();
        $obj->issuer->CN  = "";

        try {
            $orignal_parse = parse_url($url, PHP_URL_HOST);
            $get = stream_context_create(array("ssl" => array("capture_peer_cert" => TRUE)));
            $read = stream_socket_client("ssl://".$orignal_parse.":443", $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $get);
            $cert = stream_context_get_params($read);
            $certinfo = openssl_x509_parse($cert['options']['ssl']['peer_certificate']);
            $altName = str_replace('\n',"",$certinfo["extensions"]["subjectAltName"]);

            $obj2 = clone $obj;
            $obj2->subject->CN = $certinfo["subject"]["CN"];
            $obj2->issuer->CN  = $certinfo["issuer"]["CN"];
            $obj2->altNames    = explode(",",$altName);
            $obj2->validFrom   = new DateTime(date(DATE_RFC2822,$certinfo['validFrom_time_t']));
            $obj2->validTo     = new DateTime(date(DATE_RFC2822,$certinfo['validTo_time_t']));
            
            return $obj2;
        }catch(Exception $e){
            return $obj;
        }
    }
}
?>