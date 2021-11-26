<?php
namespace zion\session;

use zion\session\SessionPHP;
use zion\session\SessionFile;

// Atenção! Classe em desenvolvimento, ainda não liberada para uso!

/**
 * Classe criada para manipular os diversos tipos de sessão
 */
class Session {
    // tipos: php, file, mongo, s3
    public static $type = "php";

    public static $instance = null;

    /**
     * Cria uma instância de sessão se não existir
     * @return void
     */
    public static function createInstance(){
        if(self::$instance != null){
            return self::$instance;
        }

        switch(self::$type){
        case "php":
            self::$instance = new SessionPHP();
            break;
        case "file":
            self::$instance = new SessionFile();
            break;
        }
    }

    /**
     * Retorna uma instância de sessão
     * @return AbstractSession
     */
    public static function getInstance() : AbstractSession {
        self::createInstance();
        return self::$instance;
    }

    public function set($key,$value){
        self::createInstance();
        self::$instance->set($key,$value);
    }

    public function get($key,$value){
        self::createInstance();
        return self::$instance->get($key);
    }

    public function has($key) : bool {
        self::createInstance();
        return self::$instance->has($key);
    }

    public function destroy() : bool {
        self::createInstance();
        return self::$instance->destroy();
    }
}
?>