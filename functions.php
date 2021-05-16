<?php
/**
 * Este arquivo deve conter funções que estaram disponível no escopo global
 * Coloque aqui as funções para ajustar algum comportamento incorreto
 */

/**
 * Lê a configuração dos arquivos
 * @return []
 */
function zion_get_config_all(){
    $all = [];
    
    // configuração básica
    $json = zion_get_config("config.json",false);
    $json = is_array($json)?$json:array();
    foreach($json AS $key => $value){
        $all[$key] = $value;
    }
    
    // configuração do ambiente
    $json = zion_get_config(\zion\ENV.".json",false);
    $json = is_array($json)?$json:array();
    foreach($json AS $key => $value){
        $all[$key] = $value;
    }
    
    return $all;
}

/**
 * Lê a configuração de um arquivo
 * @param string $filename
 * @param boolean $stopOnError
 * @return []
 */
function zion_get_config($filename,$stopOnError=true){
    $file = dirname($_SERVER["DOCUMENT_ROOT"])."/".$filename;
    if(!file_exists($file)){
        if(!$stopOnError){
            return;
        }
        
        http_response_code(500);
        echo "Arquivo de configuração {$filename} não encontrado";
        exit();
    }
    
    $json = json_decode(file_get_contents($file),true);
    if(!is_array($json)){
        return;
    }
    
    return $json;
}

/**
 * Autoload do sistema
 * @param string $className
 * @return boolean
 */
function zionphp_autoload($className) {
    // zion: framework / biblioteca
    $className2 = str_replace("zion\\","src\\zion\\",$className);
    $file = \zion\ROOT.str_replace("\\","/",$className2).".class.php";
    if(file_exists($file)) {
        require_once($file);
        return true;
    }
    
    // app: módulos
    if(strpos($className, "mod\\") === 0){
        $parts = explode("\\", $className);
        $parts[0] = "modules";
        
        $file = rtrim($_SERVER["DOCUMENT_ROOT"])."/".implode("/", $parts).".class.php";
        if(file_exists($file)){
            require_once($file);
            return true;
        }
        return false;
    }
    
    // app: biblioteca
    $folder = rtrim(dirname($_SERVER["DOCUMENT_ROOT"]))."/src/";
    $file = $folder.$className.".class.php";
    $file = str_replace("\\","/",$file);
    
    if(file_exists($file)){
        require_once($file);
        return true;
    }
    
    return false;
}

/**
 * Função de callback para desserialização
 * @param string $className
 */
function zion_unserialize_callback_func($className){
    foreach(spl_autoload_functions() AS $function){
        $result = $function($className);
        if($result){
            return;
        }
    }
}

function old_count($arg){
    if(is_array($arg)){
        return count($arg);
    }else{
        return 0;
    }
}
?>