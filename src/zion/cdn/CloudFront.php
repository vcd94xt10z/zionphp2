<?php
namespace zion\cdn;

class CloudFront {
    private $config = array();
    
    public function __construct(array $config){
        $this->config = $config;
    }
    
    /**
     * Deleta o cache
     * @param mixed $key
     */
    public function delete($key){
        // TODO
    }
}
?>