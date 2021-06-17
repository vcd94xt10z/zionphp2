<?php 
namespace zion\payment;

use Exception;
use StdClass;
use DateTime;
use zion\orm\ObjectVO;
use zion\core\System;

/**
 * @author Vinicius
 * @since 17/06/21
 */
class MercadoPago {
    public function testPIX($token){
        $obj = new ObjectVO();
        $obj->set("value",10.00);
        $obj->set("description","Pedido 1");
        
        $cus = new ObjectVO();
        $cus->set("email","");
        $cus->set("name","");
        $cus->set("docftype","CPF");
        $cus->set("docf","");
        
        $addr = new ObjectVO();
        $addr->set("zipcode","");
        $addr->set("address","");
        $addr->set("number","");
        $addr->set("neighborhood","");
        $addr->set("city","");
        $addr->set("state","");
        
        $cus->set("address",$addr);
        $obj->set("customer",$cus);
        
        $this->solicitarPIX($token, $obj);
    }
    
    /**
     * https://www.mercadopago.com.br/developers/pt/guides/online-payments/checkout-api/other-payment-ways
     */
    public function solicitarPIX($token, $obj){
        $customer = $obj->get("customer");
        $address  = $customer->get("address");
        
        $names  = explode(" ",$customer->get("name"));
        $index  = sizeof($names) - 1;
        
        $firstName = $names[0];
        $lastName = "";
        if($index > 0){
            $lastName = $names[$index];
        }
        
        if(ini_get("curl.cainfo") == ""){
            // verificar se é necessário informar
        }
        
        \MercadoPago\SDK::setAccessToken($token);
        
        $payment = new \MercadoPago\Payment();
        $payment->transaction_amount = $obj->get("value");
        $payment->description = $obj->get("description");
        $payment->payment_method_id = "pix";
        $payment->payer = array(
            "email" => $customer->get("email"),
            "first_name" => $firstName,
            "last_name" => $lastName,
            "identification" => array(
                "type" => $customer->get("docftype"),
                "number" => $customer->get("docf")
            ),
            "address"=>  array(
                "zip_code" => $address->get("zipcode"),
                "street_name" => $address->get("address"),
                "street_number" => $address->get("number"),
                "neighborhood" => $address->get("neighborhood"),
                "city" => $address->get("city"),
                "federal_unit" => $address->get("state")
            )
        );
        
        $ret = $payment->save();
        var_dump($ret);exit();
    }
}
?>