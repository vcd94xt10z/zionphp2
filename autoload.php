<?php
// constantes
define("zion\ROOT",dirname(__FILE__)."/");
define("zion\APP_ROOT",dirname($_SERVER["DOCUMENT_ROOT"])."/");
define("DS",DIRECTORY_SEPARATOR);

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

/*
 * Exibição de erros
 * No ambiente de produção não é interessante exibir erros na tela pois
 * usuários mal intencionados podem usar as informações para explorar
 * vunerabilidades no sistema. Todos os erros relevantes devem ir para o
 * log para que sejam analisados posteriormente e corrigidos
 */
error_reporting(E_ALL ^ E_NOTICE);
if(\zion\ENV == "PRD"){
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
}else{
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
}

// funções
require(\zion\ROOT."functions.php");

// A configuração não é obrigatória, porém, se não for feita, não poderá ser utilizado todos os recursos
// como persistência por exemplo.
$customConfigFile = "";
if(is_array($zionphp2)){
    $file = $zionphp2["configFile"];
    if(!file_exists($file)){
        http_response_code(500);
        echo "O arquivo de configuração ".$file." não existe";
        exit();
    }
    
    $customConfigFile = $file;
}
define("zion\customConfigFile",$customConfigFile);
?>