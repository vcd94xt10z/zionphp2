<?php
namespace zion\session;

// Atenção! Classe em desenvolvimento, ainda não liberada para uso!

class SessionPHP extends AbstractSession {
    public function __construct(){
        parent::__construct();
        session_start();
        session_write_close();
    }

    public function set(string $key,$value){
        session_start();
        parent::set($key,$value);
        $_SESSION[$key] = $value;
        session_write_close();
    }

    public function destroy(){
        parent::destroy();
        session_start();
        session_destroy();
        session_write_close();
    }
}
?>