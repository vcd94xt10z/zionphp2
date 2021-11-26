<?php
namespace zion\session;

// Atenção! Classe em desenvolvimento, ainda não liberada para uso!

class SessionFile extends AbstractSession {
    public function __construct(){
        $this->createSessionCookieIfNotExists();
    }

    public function set(string $key,$value){
        session_start();
        parent::set($key,$value);
        $_SESSION[$key] = $value;
        session_write_close();
    }

    public function destroy() : bool {
        session_start();
        session_destroy();
        session_write_close();
        
        $this->data = [];
        return true;
    }
}
?>