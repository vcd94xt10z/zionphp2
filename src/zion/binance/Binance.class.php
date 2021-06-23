<?php
namespace zion\binance;

use Exception;

class Binance {
    public static $btc = null;
    public static $eth = null;
    
    public static function curl($url){
        $curl = curl_init();
        
        curl_setopt_array($curl, array(
          CURLOPT_URL => $url,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_SSL_VERIFYHOST => false,
          CURLOPT_SSL_VERIFYPEER => false,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'GET',
          CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json'
          ),
        ));
        
        $response = curl_exec($curl);
        $curlInfo = curl_getinfo($curl);
        
        if($response === false){
            $error = curl_error($curl);
            curl_close($curl);
            throw new Exception("Erro ao conectar no endereço ".$url.": ".$error);
        }
        
        if($curlInfo["http_code"] != 200){
            curl_close($curl);
            throw new Exception("Erro ao conectar no endereço, status ".$curlInfo["http_code"]);
        }
        
        curl_close($curl);
        return $response;
    }
    
    public static function getFutureList($symbol="USDT",$limit=10){
        $url = "https://fapi.binance.com/fapi/v1/ticker/24hr?symbol=";
        $list = json_decode(self::curl($url));
        
        $list2 = [];
        foreach($list AS $obj){
            if(strpos($obj->symbol,"USDT") === false){
               continue;
            }
            
            if($obj->symbol == "BTCUSDT"){
                self::$btc = $obj;
            }
            
            if($obj->symbol == "ETHUSDT"){
                self::$eth = $obj;
            }
            
            $list2[] = $obj;
        }
        
        // sort 
        usort($list2, function($a, $b)
        {
            return (abs($a->priceChangePercent) < abs($b->priceChangePercent));
        });
        
        // limitando
        if(count($list2) > $limit){
            $list2 = array_slice($list2, 0, $limit);
        }
        return $list2;
    }
    
    public static function notify($token,$title,$message,$type="type1"){
        $title = urlencode($title);
        $message = urlencode($message);
        
        $url = "https://wirepusher.com/send?id=".$token."&title=".$title."&message=".$message."&type=".$type;
        self::curl($url);
    }
}