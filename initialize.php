<?php
// constantes
define("zion\ROOT",dirname(__FILE__).DIRECTORY_SEPARATOR);
define("zion\APP_ROOT",dirname(dirname(dirname(dirname(__FILE__)))).DIRECTORY_SEPARATOR);
define("zion\CHARSET","UTF-8");

// ambiente
$env = "PRD";
if(array_key_exists("SERVER_NAME",$_SERVER)){
    if(strpos($_SERVER["SERVER_NAME"],".des") !== false OR
        strpos($_SERVER["SERVER_NAME"],".dev") !== false OR
        strpos($_SERVER["SERVER_NAME"],"des.") !== false OR
        strpos($_SERVER["SERVER_NAME"],"dev.") !== false){
            $env = "DEV";
    }else if(strpos($_SERVER["SERVER_NAME"],".qas") !== false || strpos($_SERVER["SERVER_NAME"],"qas.") !== false){
        $env = "QAS";
    }
}else{
    $env = "DEV";
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