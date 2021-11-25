<?php
namespace zion\utils;

use Exception;

/**
 * @author Vinicius
 * @since 25/05/2019
 */
class StreamUtils {
    /**
     * Envia o conteúdo de um arquivo do servidor para o cliente no buffer de saída
     * Atenção! Não esquecer de enviar antes de chamar este método, os cabeçalhos de tipo, 
     * tamanho etc, para que o cliente consiga receber o arquivo adequadamente
     * 
     * @param string $file
     * @throws Exception
     */
    public static function sendFile($file){
        if(!file_exists($file)){
            throw new Exception("O arquivo não existe",1);
        }
        
        $fp = fopen($file, 'r');
        if($fp === false){
            throw new Exception("Erro em criar ponteiro para o arquivo",2);
        }
        $readBytes = fpassthru($fp);
        if($readBytes === false){
            fclose($fp);
            throw new Exception("Erro em ler dados do arquivo por stream",3);
        }
        
        fclose($fp);
    }
    
    /**
     * Cria um arquivo codificado por gzip a partir de outro arquivo
     * @param string $fileSource
     * @param string $fileDest
     * @param int $level
     * @throws Exception
     * @see https://stackoverflow.com/questions/6073397/how-do-you-create-a-gz-file-using-php
     */
    public static function gzipFile($fileSource, $fileDest, $level = 9) {
        if($fileSource == ""){
            throw new Exception("O arquivo origem esta vazio",1);
        }
        
        if(!file_exists($fileSource)){
            throw new Exception("O arquivo origem não existe",2);
        }
        
        if(file_exists($fileDest)){
            throw new Exception("O arquivo destino já existe, remova-o e tente novamente",3);
        }
        
        $mode = 'wb' . $level;
        $error = false;
        if($fp_out = gzopen($fileDest, $mode)) {
            if($fp_in = fopen($fileSource, 'rb')) {
                while(!feof($fp_in)) {
                    gzwrite($fp_out, fread($fp_in, 1024 * 512));
                }
                fclose($fp_in);
            } else {
                $error = true;
            }
            gzclose($fp_out);
        } else {
            $error = true;
        }
        
        if($error){
            throw new Exception("Erro em gzip arquivo",4);
        }
    }
    
    /**
     * Baixa um arquivo de uma URL utilizando streams
     * @param string $url
     * @param string $file
     * @param int $timeout
     * @throws Exception
     * @see https://stackoverflow.com/questions/6914912/streaming-a-large-file-using-php
     */
    public static function downloadFile($url,$file,$timeout=60){
        $fp = @fopen($file, 'w+');
        if($fp === false){
            throw new Exception("Erro em ler arquivo",1);
        }
        
        $ch = curl_init();
        if($ch === false){
            fclose($fp);
            throw new Exception("Erro em iniciar curl",2);
        }
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, \zion\DEFAULT_CURLOPT_SSL_VERIFYHOST);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, \zion\DEFAULT_CURLOPT_SSL_VERIFYPEER);
        $result = curl_exec($ch);
        if($result === false){
            $error = curl_error($ch);
            curl_close($ch);
            fclose($fp);
            throw new Exception("Erro em executar curl: ".$error,3);
        }
        
        if(curl_errno($ch) == 28) {
            curl_close($ch);
            fclose($fp);
            throw new Exception("Timeout",4);
        }
        
        $curlInfo = curl_getinfo($ch);
        if(strpos($curlInfo["http_code"],"2") !== 0){
            curl_close($ch);
            fclose($fp);
            throw new Exception("Erro em executar curl, status ".$curlInfo["http_code"],5);
        }
        
        curl_close($ch);
        fclose($fp);
    }
}
?>