<?php
namespace zion\session;

// Atenção! Classe em desenvolvimento, ainda não liberada para uso!

/**
 * @author Vinicius Cesar Dias <dias.viniciuscesar@gmail.com>
 */
abstract class AbstractSession {
    /**
     * Id de sessão
     * @var [type]
     */
    protected $id;

    /**
     * Dados da sessão
     * @var array
     */
    protected $data = [];

    public function __construct(){
    }

    /**
     * Gera um Id único
     * @return void
     */
    public function generateId(){
        $this->id = hash("sha256",uniqid(random_int(100000,999999), true));
    }

    /**
     * Retorna o id atual
     *
     * @return string
     */
    public function getId(){
        return $this->id;
    }

    /**
     * Define uma variável de sessão
     *
     * @param [type] $key
     * @param [type] $value
     * @return void
     */
    public function set(string $key,$value){
        $this->data[$key] = $value;
    }

    /**
     * Retorna uma variável de sessão
     *
     * @param string $key
     * @return void
     */
    public function get(string $key){
        if($this->has($key)){
            return $this->data[$key];
        }
        return null;
    }

    /**
     * Verifica se uma variável existe na sessão
     *
     * @param [type] $key
     * @return boolean
     */
    public function has(string $key) : bool {
        return array_key_exists($key,$this->data);
    }

    /**
     * Destroy a sessão atual
     * @return void
     */
    abstract public function destroy() : bool;

    public function createSessionCookieIfNotExists(){
        if($_COOKIE["SESSIONID"] != null){
            return true;
        }

        $this->generateId();
        $name     = "SESSIONID";
        $value    = $this->id;
        $expires  = 0; // Cookie de Sessão, expira quando o navegador fechar
        $path     = "/";
        $domain   = $_SERVER["SERVER_NAME"];
        $secure   = false; // false = http/https, true = só https
        $httponly = true; // não acessível por JavaScript, só por HTTP
        setcookie($name,$value,$expires,$path,$domain,$secure,$httponly);
        
        return true;
    }
}
?>