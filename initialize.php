<?php
// constantes
define("zion\APP_ROOT",dirname($_SERVER["DOCUMENT_ROOT"])."/");
define("zion\ROOT",\zion\APP_ROOT."vendor/vcd94xt10z/zion2/");
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

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// funções
require(\zion\ROOT."functions.php");

// configuração do arquivo
$config = zion_get_config_all();

// deixando a configuração global
\zion\core\System::set("config",$config);

// inicialização
\zion\core\System::configure();
?>