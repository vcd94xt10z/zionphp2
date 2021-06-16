<?php
namespace zion\binance;

class Binance {
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
            curl_close($curl);
            throw new Exception("Erro ao conectar no endereço: ".curl_error($curl));
        }
        
        if($curlInfo["http_code"] != 200){
            curl_close($curl);
            throw new Exception("Erro ao conectar no endereço, status ".$curlInfo["http_code"]);
        }
        
        curl_close($curl);
        return $response;
    }
    
    public static function getFutureList($symbol="USDT"){
        $url = "https://fapi.binance.com/fapi/v1/ticker/24hr?symbol=";
        $list = json_decode(self::curl($url));
        
        $list2 = [];
        foreach($list AS $obj){
            if(strpos($obj->symbol,"USDT") === false){
               continue;
            }
            $list2[] = $obj;
        }
        
        // sort 
        usort($list2, function($a, $b)
        {
            return (abs($a->priceChangePercent) < abs($b->priceChangePercent));
        });
        
        // limitando
        $list2 = array_slice($list2, 0, 10);
        
        return $list2;
    }
    
    public static function notify($token,$title,$message,$type="type1"){
        $title = urlencode($title);
        $message = urlencode($message);
        
        $url = "https://wirepusher.com/send?id=".$token."&title=".$title."&message=".$message."&type=".$type;
        self::curl($url);
    }
}