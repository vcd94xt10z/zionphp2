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
    $file = \zion\APP_ROOT.$filename;
    if(!file_exists($file)){
        if(!$stopOnError){
            return null;
        }
        
        http_response_code(500);
        echo "Arquivo de configuração {$filename} não encontrado";
        exit();
    }
    
    $json = json_decode(file_get_contents($file),true);
    if(!is_array($json)){
        return null;
    }
    
    return $json;
}

function zion_escape_dbval($val){
    return addslashes($val);
}

function php5_count($arg){
    if(is_array($arg)){
        return count($arg);
    }else{
        return 0;
    }
}
?>