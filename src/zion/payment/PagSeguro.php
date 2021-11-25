<?php 
namespace zion\payment;

use Exception;
use StdClass;
use DateTime;
use zion\orm\ObjectVO;
use zion\core\System;

/**
 * @author Vinicius
 * @since 06/06/21
 * 
 * https://documenter.getpostman.com/view/13365079/TVYQ1DfW#d7feca1b-aa57-4f7d-8e5d-c59c89fafe11
 * https://dev.pagseguro.uol.com.br/reference/checkout-transparente#transparente-cartao-de-credito
 */
class PagSeguro {
    private $env            = "";
    private $email          = "";
    private $token          = "";
    private $servername     = "";
    private $servername2    = "";
    private $requestLog     = null;
    private $requestLogHist = [];
    
    public function __construct($env,$email,$token){
        $this->env   = $env;
        $this->email = $email;
        $this->token = $token;
        
        if($env == "PRD"){
            $this->servername = "ws.pagseguro.uol.com.br";
        }else{
            $this->servername = "ws.sandbox.pagseguro.uol.com.br";
            $this->servername2 = "sandbox.pagseguro.uol.com.br";
        }
    }
    
    public function getRequestLog(){
        return $this->requestLog;
    }
    
    public function curl($url,$method="GET",$headers=[],$postDataString=""){
        $curl = curl_init();
        
        $options = array(
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 0,
            CURLOPT_SSL_VERIFYHOST => \zion\DEFAULT_CURLOPT_SSL_VERIFYHOST,
            CURLOPT_SSL_VERIFYPEER => \zion\DEFAULT_CURLOPT_SSL_VERIFYPEER,
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
    
    public function criarSessao(){
        $url = 'https://'.$this->servername.'/v2/sessions?email='.$this->email.'&token='.$this->token;
        $result = $this->curl($url,"POST");

        if($result->curlInfo["http_code"] == 401){
            throw new Exception("PagSeguro: Sem autorização, verifique se as credenciais cadastradas estão corretas");
        }

        if($result->curlInfo["http_code"] == 500){
            throw new Exception("PagSeguro: Erro em criar sessão");
        }
        
        $xml = simplexml_load_string($result->responseBody);
        if($xml != null){
            return (string)$xml->id;
        }
        return null;
    }
    
    public function obterMeiosDePagamento($sessionid,$amount){
        $headers = [
            'Accept: application/vnd.pagseguro.com.br.v1+json;charset=ISO-8859-1'
        ];
        
        $url = 'https://'.$this->servername.'/payment-methods?amount='.$amount.'&sessionId='.$sessionid;
        $result = $this->curl($url,"GET",$headers);
        
        $obj = json_decode(utf8_decode($result->responseBody));
        return $obj;
    }
    
    public function obterBandeiraCartao($sessionid,$bin){
        $url = 'https://df.uol.com.br/df-fe/mvc/creditcard/v1/getBin?tk='.$sessionid."&creditCard=".$bin;
        $result = $this->curl($url,"GET");
        $obj = json_decode(utf8_decode($result->responseBody));
        return $obj;
    }
    
    /**
     * 
     * @param unknown $sessionId
     * @param unknown $amount Valor com duas casas decimais
     * @param unknown $cardNumber
     * @param unknown $cardBrand Bandeira do Cartão
     * @param unknown $cardCVV
     * @param unknown $cardExpirationMonth 2 Digitos
     * @param unknown $cardExpirationYear 4 Digitos 
     * @return mixed
     */
    public function obterTokenDoCartao($sessionId,$card){
        $headers = ['Content-Type: application/x-www-form-urlencoded'];
        
        $postData = [];
        $postData[] = "sessionId=".$sessionId;
        $postData[] = "amount=".$card->amountTotal;
        $postData[] = "cardNumber=".$card->number;
        $postData[] = "cardBrand=".$card->brand;
        $postData[] = "cardCvv=".$card->cvv;
        $postData[] = "cardExpirationMonth=".$card->expirationMonth;
        $postData[] = "cardExpirationYear=".$card->expirationYear;
        $postDataString = implode("&",$postData);
        
        $url = "https://df.uol.com.br/v2/cards";
        $result = $this->curl($url,"POST",$headers,$postDataString);
        
        //var_dump($result->responseBody);exit();
        $response = simplexml_load_string($result->responseBody);
        if($response != null){
            return (string)$response->token;
        }
        return null;
    }
    
    public function obterCondicoesDeParcelamento($sessionId,$card){
        $args = [];
        $args[] = "sessionId=".$sessionId;
        $args[] = "amount=".number_format($card->amountTotal,2);
        $args[] = "creditCardBrand=".$card->brand;
        $args[] = "maxInstallmentNoInterest=2";
        
        $url = "https://".$this->servername2."/checkout/v2/installments.json?".implode("&",$args);
        $result = $this->curl($url,"GET");
        $obj = json_decode(utf8_decode($result->responseBody));
        return $obj;
    }
    
    public function boleto(ObjectVO $so, ObjectVO $customer){
        $addr  = $so->get("deliveryAddress");
        $phone = $customer->get("cell_phone");
        if($phone == ""){
            $phone = $customer->get("home_phone");
        }

        $phoneDDD    = substr($phone,0,2);
        $phoneNumber = substr($phone,2);
        
        $extraAmount     = 0;
        $notificationURL = "https://".$_SERVER["SERVER_NAME"]."/pagseguro/notificacao";
        
        // dados do vendedor
        $receiverEmail = $this->email;
        
        // dados do comprador
        $senderEmail       = $customer->get("email");
        $senderPhoneDDD    = $phoneDDD;
        $senderPhoneNumber = $phoneNumber;
        
        if($this->env != "PRD"){
            $senderEmail = "comprador@sandbox.pagseguro.com.br";
        }
        
        $data = array(); 
        $data[] = "paymentMode=default";
        $data[] = "paymentMethod=boleto";
        $data[] = "receiverEmail=".urlencode($receiverEmail);
        $data[] = "currency=BRL";
        $data[] = "extraAmount=".$this->formatarMoeda($extraAmount);
        
        $itemId = 1;
        foreach($so->get("itemList") AS $item){
            $data[] = "itemId".$itemId."=".$item->get("itemid");
            $data[] = "itemDescription".$itemId."=".$item->get("title");
            $data[] = "itemAmount".$itemId."=".$this->formatarMoeda($item->get("price_unitary"));
            $data[] = "itemQuantity".$itemId."=".$item->get("quantity");
            $itemId++;
        }
        
        $data[] = "notificationURL=".urlencode($notificationURL);
        $data[] = "reference=".urlencode($so->get("soid"));
        $data[] = "senderName=".urlencode($so->get("customer_name"));
        $data[] = "senderCPF=".urlencode($customer->get("docf"));
        $data[] = "senderAreaCode=".urlencode($senderPhoneDDD);
        $data[] = "senderPhone=".urlencode($senderPhoneNumber);
        $data[] = "senderEmail=".urlencode($senderEmail);
        $data[] = "shippingAddressStreet=".urlencode($addr->get("address"));
        $data[] = "shippingAddressNumber=".urlencode($addr->get("number"));
        $data[] = "shippingAddressComplement=".urlencode($addr->get("complement"));
        $data[] = "shippingAddressDistrict=".urlencode($addr->get("neighborhood"));
        $data[] = "shippingAddressPostalCode=".urlencode($addr->get("zipcode"));
        $data[] = "shippingAddressCity=".urlencode($addr->get("city"));
        $data[] = "shippingAddressState=".urlencode($addr->get("state"));
        $data[] = "shippingAddressCountry=BRA";
        $data[] = "shippingType=1";
        $data[] = "shippingCost=".$this->formatarMoeda($so->get("freight_value"));
        $postData = implode("&",$data);
        
        $headers = ['Content-Type: application/x-www-form-urlencoded'];
        
        $url = "https://".$this->servername."/v2/transactions?email=".$this->email."&token=".$this->token;
        $result = $this->curl($url,"POST",$headers,$postData);
        
        $status        = "E";
        $errorCode     = "";
        $errorMessage  = "";
        $transactionid = $this->boletoLog($so);
        
        if($result->curlInfo["http_code"] == 200){
            $xml = simplexml_load_string($result->responseBody);
            $obj = json_decode(json_encode($xml));
            
            if($obj->code != ""){
                $status = "S";
            }
        }else{
            $xml = simplexml_load_string($result->responseBody);
            if($xml != null){
                $errorCode    = (int)$xml->error->code;
                $errorMessage = (string)$xml->error->message;
            }else{
                $errorCode    = 403;
                $errorMessage = "Erro ao processar pagamento, status ".$result->curlInfo["http_code"];
            }
        }
        
        return [
            "status"        => $status,
            "errorCode"     => $errorCode,
            "errorMessage"  => $errorMessage,
            "paymentLink"    => $obj->paymentLink,
            "transactionid" => $transactionid
        ];
    }
    
    public function boletoLog($so){
        $obj = new ObjectVO();
        $obj->set("transactionid",null);
        $obj->set("shopid",\SHOPID);
        $obj->set("soid",$so->get("soid"));
        $obj->set("created_at",new DateTime());
        $obj->set("req_url",$this->requestLog->get("req_url"));
        $obj->set("req_method",$this->requestLog->get("req_method"));
        $obj->set("req_body",$this->requestLog->get("req_body"));
        $obj->set("res_status",$this->requestLog->get("res_status"));
        $obj->set("res_body",utf8_encode($this->requestLog->get("res_body")));
        $obj->set("curl_error",$this->requestLog->get("curl_error"));
        
        if($this->requestLog->get("res_body") != ""){
            $xml = @simplexml_load_string($this->requestLog->get("res_body"));
            if($xml != null){
                $ret = json_decode(json_encode($xml));
                if($ret != null){
                    $obj->set("return_code",$ret->code);
                    $obj->set("return_reference",$ret->reference);
                    $obj->set("return_type",$ret->type);
                    $obj->set("return_status",$ret->status);
                    $obj->set("return_paymentlink",$ret->paymentlink);
                }
            }
        }
        
        $db = System::getConnection();
        $dao = System::getDAO($db,"pagseguro_boleto");
        $dao->insert($db,$obj);
        
        return $db->lastInsertId();
    }
    
    public function debitoOnline(){
    }
    
    private function formatarMoeda($valor){
        return number_format($valor,2,".","");
    }
    
    /**
     * Se atentar ao valores. A soma dos valor unitário dos itens x quantidade tem que bater com o valor da parcela
     * Se no valor a pagar tiver embutido frete, descontos ou acréscimos, o campo extraAmount deve ter a difereça entre
     * o valor das parcelas somadas e o valor dos itens
     * @param ObjectVO $so
     * @param ObjectVO $customer
     * @param StdClass $card
     * @throws Exception
     * @return mixed
     */
    public function cartaoDeCredito(ObjectVO $so, ObjectVO $customer, StdClass $card){
        $notificationURL = "https://".$_SERVER["SERVER_NAME"]."/pagseguro/notificacao";
        
        $phone = $customer->get("cell_phone");
        if($phone == ""){
            $phone = $customer->get("home_phone");
        }
        
        $phoneDDD    = substr($phone,0,2);
        $phoneNumber = substr($phone,2);
        $docType     = "CPF";
        if($customer->get("type") == "J"){
            $docType = "CNPJ";
        }
        $deliveryAddr   = $so->get("deliveryAddress");
        $emailComprador = $customer->get("email");
        
        if($this->env != "PRD"){
            $emailComprador = "comprador@sandbox.pagseguro.com.br";
        }
        
        $headers = ['Content-Type: application/xml'];
        $url = "https://".$this->servername."/v2/transactions?email=".urlencode($this->email)."&token=".urlencode($this->token);
        $postData = "
	    <payment>
            <mode>default</mode>
            <method>creditCard</method>";
        
        // dados do comprador (cliente)
        $postData .= "
            <sender>
                <name>".$customer->get("name1")."</name>
                <email>".$emailComprador."</email>
                <phone>
                    <areaCode>".$phoneDDD."</areaCode>
                    <number>".$phoneNumber."</number>
                </phone>
                <documents>
                    <document>
                        <type>".$docType."</type>
                        <value>".$customer->get("docf")."</value>
                    </document>
                </documents>
            </sender>
            <currency>BRL</currency>
            <notificationURL>".$notificationURL."</notificationURL>
            <items>";
        
            foreach($so->get("itemList") AS $item){
            $postData .= "
                <item>
                    <id>".$item->get("productid")."</id>
                    <description>".$item->get("title")."</description>
                    <quantity>".$item->get("quantity")."</quantity>
                    <amount>".$this->formatarMoeda($item->get("price_unitary"))."</amount>
                </item>";
            }
            
            // jogando a diferença no valor extra para não dar erro
            $extraAmount = $card->amountTotal - $so->get("total_itens");
            
            // segundo a validação, esse campo não pode ser menor que 2
            $noInterestInstallmentQuantity = $card->parcels;
            if($noInterestInstallmentQuantity < 2){
                $noInterestInstallmentQuantity = 2;
            }
            
            $postData .= "
            </items>
            <extraAmount>".$this->formatarMoeda($extraAmount)."</extraAmount>
            <reference>".$so->get("soid")."</reference>
            <shipping>
             <addressRequired>false</addressRequired>
            </shipping>
            <creditCard>
                <token>".$card->token."</token>
               <installment>
                    <noInterestInstallmentQuantity>".$noInterestInstallmentQuantity."</noInterestInstallmentQuantity>
                    <quantity>".$card->parcels."</quantity>
                    <value>".$this->formatarMoeda($card->amountParcel)."</value>
                </installment>
                <holder>
                    <name>".$card->owner."</name>
                    <documents>
                        <document>
                            <type>".$card->docType."</type>
                            <value>".$card->docf."</value>
                        </document>
                    </documents>
                    <birthDate>".$card->bornDate->format("d/m/Y")."</birthDate>
                    <phone>
                        <areaCode>".$phoneDDD."</areaCode>
                        <number>".$phoneNumber."</number>
                    </phone>
                </holder>
                <billingAddress>
                    <street>".$deliveryAddr->get("address")."</street>
                    <number>".$deliveryAddr->get("number")."</number>
                    <complement>".$deliveryAddr->get("complement")."</complement>
                    <district>".$deliveryAddr->get("neighborhood")."</district>
                    <city>".$deliveryAddr->get("city")."</city>
                    <state>".$deliveryAddr->get("state")."</state>
                    <country>BRA</country>
                    <postalCode>".$deliveryAddr->get("zipcode")."</postalCode>
                </billingAddress>
            </creditCard>
        </payment>";
            
        //echo $postData;exit();
        $result = $this->curl($url,"POST",$headers,$postData);
        
        $status        = "E";
        $errorCode     = "";
        $errorMessage  = "";
        $transactionid = $this->cartaoDeCreditoLog($so);
        
        if($result->curlInfo["http_code"] == 200){
            $xml = simplexml_load_string($result->responseBody);
            $obj = json_decode(json_encode($xml));
            
            if($obj->code != ""){
                $status = "S";
            }
        }else{
            $xml = simplexml_load_string($result->responseBody);
            if($xml != null){
                $errorCode    = (int)$xml->error->code;
                $errorMessage = (string)$xml->error->message;
            }else{
                $errorCode    = 403;
                $errorMessage = "Erro ao processar pagamento, status ".$result->curlInfo["http_code"];
            }
        }
        
        return [
            "status"        => $status,
            "errorCode"     => $errorCode,
            "errorMessage"  => $errorMessage,
            "transactionid" => $transactionid
        ];
    }
    
    public function cartaoDeCreditoLog($so){
        $obj = new ObjectVO();
        $obj->set("transactionid",null);
        $obj->set("shopid",\SHOPID);
        $obj->set("soid",$so->get("soid"));
        $obj->set("created_at",new DateTime());
        $obj->set("req_url",$this->requestLog->get("req_url"));
        $obj->set("req_method",$this->requestLog->get("req_method"));
        $obj->set("req_body",$this->requestLog->get("req_body"));
        $obj->set("res_status",$this->requestLog->get("res_status"));
        $obj->set("res_body",utf8_encode($this->requestLog->get("res_body")));
        $obj->set("curl_error",$this->requestLog->get("curl_error"));
        
        if($this->requestLog->get("res_body") != ""){
            $xml = @simplexml_load_string($this->requestLog->get("res_body"));
            if($xml != null){
                $ret = json_decode(json_encode($xml));
                if($ret != null){
                    $obj->set("return_code",$ret->code);
                    $obj->set("return_reference",$ret->reference);
                    $obj->set("return_type",$ret->type);
                    $obj->set("return_authorization_code",$ret->gatewaySystem->authorizationCode);
                    $obj->set("return_nsu",$ret->gatewaySystem->nsu);
                    $obj->set("return_tid",$ret->gatewaySystem->tid);
                    $obj->set("return_establishment_code",$ret->gatewaySystem->establishmentCode);
                    $obj->set("return_acquirer_name",$ret->gatewaySystem->acquirerName);
                }
            }
        }
        
        $db = System::getConnection();
        $dao = System::getDAO($db,"pagseguro_cartaodecredito");
        $dao->insert($db,$obj);
        
        return $db->lastInsertId();
    }
    
    public function consultaPorCodigoDeReferencia(){
    }
    
    public function consultaPorIntervaloDeDatas(){
    }
    
    public function detalhesDaTransacao(){
    }
    
    public function consultaPorCodigoDeNotificacao(){
    }
    
    public function cancelamento(){
    }
    
    public function estornoTotal(){
    }
    
    public function estornoParcial(){
    }
}
?>