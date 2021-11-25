<?php
namespace zion\cache;

/**
 * Interface para cache
 * @author Vinicius
 * @since 20/09/2019
 */
abstract class CacheInterface {
    protected $config = [];
    
    public function __construct(array $config){
        $this->config = $config;
    }
    
    public function parseKey(string $key) : string {
        // trocando caracteres inválidos
        $key = preg_replace("/[^0-9a-zA-Z\_\-]/","_",$key);
        
        // deixando com hash para acelerar a busca
        $key = hash("sha256",$key);
        
        return $key;
    }
    
    /**
     * Coloca um valor em cache
     * @param string $key
     * @param mixed $value
     * @param int $maxAge
     */
    abstract public function set(string $key, $value, int $maxAge);
    
    /**
     * Retorna um valor do cache
     * @param string $key
     */
    abstract public function get(string $key);
    
    /**
     * Deleta um valor do cache
     * @param string $key
     */
    abstract public function delete(string $key);
    
    /**
     * Rotina de limpeza de cache: frio, expirado, inválido etc
     */
    abstract public function clean();
}
?>