<?php
namespace zion\binance;

use Exception;
use StdClass;

class Binance {
    public static function getFutureSymbolInfo($symbol="ETHUSDT"){
        if($symbol == ""){
            return null;
        }
        $url = "https://api.binance.com/api/v1/ticker/24hr?symbol=".$symbol;
        $obj = json_decode(self::curl($url));
        return $obj;
    }

    public static function getSpotList($symbol=""){
        if($symbol == ""){
            return null;
        }
        $url = "https://api.binance.com/api/v1/ticker/24hr?symbol=".$symbol;
        $obj = json_decode(self::curl($url));
        return $obj;
    }

    public static function getFutureList($symbol="",$limit=10){
        $url = "https://fapi.binance.com/fapi/v1/ticker/24hr?symbol=".$symbol;
        $list = json_decode(self::curl($url));
        
        $output = new StdClass();
        $output->negativeCount = 0;
        $output->positiveCount = 0;
        $output->BTC = null;
        $output->ETH = null;
        $output->list = [];

        $list2 = [];
        foreach($list AS $obj){
            if(strpos($obj->symbol,"USDT") === false){
               continue;
            }

            if($obj->priceChangePercent < 0){
                $output->negativeCount++;
            }
            if($obj->priceChangePercent >= 0){
                $output->positiveCount++;
            }
            
            if($obj->symbol == "BTCUSDT"){
                $output->BTC = $obj;
            }
            
            if($obj->symbol == "ETHUSDT"){
                $output->ETH = $obj;
            }
            
            $list2[] = $obj;
        }
        
        // sort 
        usort($list2, function($a, $b)
        {
            return (abs($a->priceChangePercent) < abs($b->priceChangePercent));
        });
        
        // limitando
        if($limit != 0){
            if(count($list2) > $limit){
                $list2 = array_slice($list2, 0, $limit);
            }
        }

        $output->list = $list2;
        return $output;
    }
    
    public static function notify($token,$title,$message,$type="type1"){
        $title = urlencode($title);
        $message = urlencode($message);
        
        $url = "https://wirepusher.com/send?id=".$token."&title=".$title."&message=".$message."&type=".$type;
        self::curl($url);
    }

    public static function curl($url){
        $curl = curl_init();
        
        curl_setopt_array($curl, array(
          CURLOPT_URL => $url,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_SSL_VERIFYHOST => \zion\DEFAULT_CURLOPT_SSL_VERIFYHOST,
          CURLOPT_SSL_VERIFYPEER => \zion\DEFAULT_CURLOPT_SSL_VERIFYPEER,
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
}