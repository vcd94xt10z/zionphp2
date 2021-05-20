<?php
namespace zion\cache;

use Exception;

/**
 * Cache de dados
 * @author Vinicius
 * @since 20/09/2019
 */
class Cache {
    /**
     * Configurações
     * @var array
     */
    private static $config = [];
    
    /**
     * Instancia interna para fazer cache
     * @var CacheInterface
     */
    private static $instance = null;
    
    /**
     * Cria a instância interna se não existir
     * @throws Exception
     */
    private static function createInstance(){
        if(self::$instance != null){
            return;
        }
        
        $driver = self::$config["driver"];
        if($driver == ""){
            $driver = "file";
        }
        
        if($driver == "file"){
            self::$instance = new FileCache(self::$config);
            return;
        }
        
        if($driver == "file2"){
            self::$instance = new FileCache2(self::$config);
            return;
        }
        
        throw new Exception("Driver {$driver} indisponível");
    }
    
    /**
     * Configura o cache
     * @param array $config
     */
    public static function configure(array $config){
        self::$config = $config;
        
        // toda vez que a configuração mudar, cria novamente a instância
        self::$instance = null;
        self::createInstance();
    }
    
    public static function set(string $key, $value, int $maxAge){
        self::createInstance();
        self::$instance->set($key, $value, $maxAge);
    }
    
    public static function get(string $key){
        self::createInstance();
        return self::$instance->get($key);
    }
    
    public static function delete(string $key){
        self::createInstance();
        self::$instance->delete($key);
    }
    
    public static function clean(){
        self::createInstance();
        self::$instance->clean();
    }
}
?>