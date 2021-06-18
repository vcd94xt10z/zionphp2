<?php 
namespace zion\payment;

use Exception;
use StdClass;
use zion\orm\ObjectVO;

/**
 * @author Vinicius
 * @since 17/06/21
 */
class MercadoPago {
    private $token;
    
    private $requestLog     = null;
    private $requestLogHist = [];
    
    public function __construct($token){
        $this->token = $token;
    }
    
    public function newPix(){
        $obj = new StdClass();
        $obj->transaction_amount = 0;
        $obj->description        = "";
        $obj->payment_method_id  = "pix";
        
        $obj->payer = new StdClass();
        $obj->payer->email      = "test@test.com";
        $obj->payer->first_name = "Test";
        $obj->payer->last_name  = "User";
        
        $obj->payer->identification = new StdClass();
        $obj->payer->identification->type   = "CPF";
        $obj->payer->identification->number = "";
        
        $obj->payer->address = new StdClass();
        $obj->payer->address->zip_code      = "";
        $obj->payer->address->street_name   = "";
        $obj->payer->address->street_number = "";
        $obj->payer->address->neighborhood  = "";
        $obj->payer->address->city          = "";
        $obj->payer->address->federal_unit  = "";
        return $obj;
    }
    
    public function pix($obj){
        if($obj == null){
            throw new Exception("Objeto PIX null");
        }
        
        $url = "https://api.mercadopago.com/v1/payments";
        
        $headerList = [
            "Accept: application/json",
            "Authorization: Bearer ".$this->token,
            "Content-Type: application/json"
        ];
        
        $body = json_encode($obj);
        
        $ret = $this->curl($url,"POST",$headerList,$body);
        return $ret;
    }
    
    public function curl($url,$method="GET",$headers=[],$postDataString=""){
        $curl = curl_init();
        
        $options = array(
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 0,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_HTTPHEADER     => $headers,
        );
        
        if($postDataString != ""){
            $options[CURLOPT_POSTFIELDS] = $postDataString;
        }
        
        curl_setopt_array($curl, $options);
        
        $log = new ObjectVO();
        $log->set("req_url",$url);
        $log->set("req_method",$method);
        $log->set("req_headers",$headers);
        $log->set("req_body",$postDataString);
        
        $response = @curl_exec($curl);
        $curlInfo = curl_getinfo($curl);
        
        if($response === false){
            $curlError = curl_error($curl);
            
            $log->set("curl_error",$curlError);
            $this->requestLog[] = $log;
            
            curl_close($curl);
            throw new Exception("Erro ao conectar no endereço '".$url."': ".$curlError);
        }
        curl_close($curl);
        
        $log->set("res_status",$curlInfo["http_code"]);
        $log->set("res_body",$response);
        
        $this->requestLog       = $log;
        $this->requestLogHist[] = $log;
        
        $result = new StdClass();
        $result->responseBody = $response;
        $result->curlInfo = $curlInfo;
        return $result;
    }
    
    public function getLastRequestLog(){
        return $this->requestLog;
    }
}
?>