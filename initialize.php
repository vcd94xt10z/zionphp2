<?php
// constantes
define("zion\APP_ROOT",dirname($_SERVER["DOCUMENT_ROOT"])."/");
define("zion\ROOT",\zion\APP_ROOT."vendor/vcd94xt10z/zion2/");
define("zion\CHARSET","UTF-8");

// ambiente
$env = "PRD";
if(strpos($_SERVER["SERVER_NAME"],".des") !== false OR
    strpos($_SERVER["SERVER_NAME"],".dev") !== false OR
    strpos($_SERVER["SERVER_NAME"],"des.") !== false OR
    strpos($_SERVER["SERVER_NAME"],"dev.") !== false){
        $env = "DEV";
}else if(strpos($_SERVER["SERVER_NAME"],".qas") !== false || strpos($_SERVER["SERVER_NAME"],"qas.") !== false){
    $env = "QAS";
}
define("zion\ENV",$env);

if(\zion\ENV == "PERFECTWORLD"){
    define("zion\DEFAULT_CURLOPT_SSL_VERIFYHOST",2);
    define("zion\DEFAULT_CURLOPT_SSL_VERIFYPEER",true);
}else{
    define("zion\DEFAULT_CURLOPT_SSL_VERIFYHOST",0);
    define("zion\DEFAULT_CURLOPT_SSL_VERIFYPEER",false);
}
?>