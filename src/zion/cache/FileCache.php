<?php
namespace zion\cache;

/**
 * Cache em arquivo
 * @author Vinicius
 * @since 20/09/2019
 */
class FileCache extends CacheInterface {
    public function __construct(array $config){
        // diretório padrão
        if($config["folder"] == ""){
            $config["folder"] = \zion\APP_ROOT."tmp".\DS."zion-filecache".\DS;
        }
        
        // criando diretório
        if(!file_exists($config["folder"])){
            mkdir($config["folder"],0777,true);
        }
        
        parent::__construct($config);
    }
    
    public function set(string $key, $value, int $maxAge){
        $key = $this->parseKey($key);
        
        // atomicidade, nome temporário
        $file     = $this->config["folder"].$key;
        $fileTemp = $this->config["folder"].$key.uniqid('', true).'.tmp';
        
        // empacotando
        $value = array(
            "timestamp" => intval(date("YmdHis")),
            "maxAge"    => $maxAge,
            "value"     => $value
        );
        
        // convertendo valor de qualquer tipo para string
        $value = serialize($value);
        
        // gravando no disco
        file_put_contents($fileTemp, $value, LOCK_EX);
        
        // renomeando para nome final
        rename($fileTemp, $file);
    }

    public function get(string $key){
        // gravando key original para ser usada em outra chamada
        $originalKey = $key;
        
        $key   = $this->parseKey($key);
        $file  = $this->config["folder"].$key;
        
        if(!file_exists($file)){
            return null;
        }
        
        $value = file_get_contents($file);
        if(!is_string($value)){
            return null;
        }
        
        $value = unserialize($value);
        if($value === false){
            return null;
        }
        
        // verificando se o cache expirou
        $ts1 = intval(date("YmdHis"));
        $ts2 = $value["timestamp"];
        
        $diff = $ts1 - $ts2;
        if($diff > $value["maxAge"]){
            $this->delete($originalKey);
            return null;
        }
        
        return $value["value"];
    }

    public function delete(string $key){
        $key  = $this->parseKey($key);
        $file = $this->config["folder"].$key;
        
        if(file_exists($file)){
            unlink($file);
        }
    }
    
    public function clean(){
        $folder = $this->config["folder"];
        if(!file_exists($folder)){
            return;
        }
        
        $ignore = array(".","..");
        $files = scandir($folder);
        foreach($files AS $filename){
            if(in_array($filename,$ignore)){
                continue;
            }
            
            $file = $folder.$filename;
            $age = time()-filemtime($file);
            
            // deletando caches antigos
            // 1 hora = 3600 segundos
            if($age >= 3600){
                unlink($file);
            }
        }
    }    
}
?>