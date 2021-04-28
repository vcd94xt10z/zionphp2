<?php
// A configuração não é obrigatória, porém, se não for feita, não poderá ser utilizado todos os recursos
// como persistência por exemplo.
if(is_array($zionphp2)){
    $file = $zionphp2["configFile"];
    if(!file_exists($file)){
        http_response_code(500);
        echo "O arquivo de configuração ".$file." não existe";
        exit();
    }
}
?>