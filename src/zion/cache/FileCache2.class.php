<?php
namespace zion\cache;

/**
 * Cache em arquivo usando opcache, pelos meus testes, essa classe só funciona com dados nativos do PHP.
 * Se você criar alguma classe, pode ser que não funcione.
 * 
 * Classe feita baseada no exemplo abaixo 
 * https://medium.com/@dylanwenzlau/500x-faster-caching-than-redis-memcache-apc-in-php-hhvm-dcd26e8447ad
 * 
 * @author Vinicius
 * @since 20/09/2019
 */
class FileCache2 extends CacheInterface {
    public function __construct(array $config){
        // diretório padrão
        if($config["folder"] == ""){
            $config["folder"] = \zion\ROOT."tmp".\DS."filecache2".\DS;
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
        
        $value = var_export($value, true);
        
        $value = str_replace('stdClass::__set_state', '(object)', $value);
        file_put_contents($fileTemp, '<?php $value = ' . $value . ';', LOCK_EX);
        rename($fileTemp, $file);
    }

    public function get(string $key){
        $key  = $this->parseKey($key);
        $file = $this->config["folder"].$key;
        
        if(!file_exists($file)){
            return null;
        }
        
        @include($file);
        return isset($value) ? $value : null;
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