<?php
namespace zion\session;

use DateTime;

// Atenção! Classe em desenvolvimento, ainda não liberada para uso!

/**
 * @author Vinicius Cesar Dias <dias.viniciuscesar@gmail.com>
 */
abstract class AbstractSession {
    /**
     * Id de sessão
     */
    protected $id;

    /**
     * Configurações da sessão
     * @var array
     */
    protected $config = [];

    /**
     * Dados da sessão
     * @var array
     */
    protected $data = [];

    /**
     * Metadados da sessão
     * @var array
     */
    protected $info = array();

    /**
     * Chave da sessão
     */
    protected $sessionKey = "PHPSESSIONID";

    public function __construct(array $config = []){
        $this->config = $config;
    }

    /**
     * Gera um Id único
     */
    protected function generateId(){
        return hash("sha256",uniqid(random_int(100000,999999), true));
    }

    /**
     * Define uma variável de sessão
     *
     * @param $key
     * @param $value
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
     * @param $key
     * @return bool
     */
    public function has(string $key) : bool {
        return array_key_exists($key,$this->data);
    }

    /**
     * Destroy a sessão atual
     * @return void
     */
    public function destroy(){
        $this->data = [];
        $this->info = [];
    }

    /**
     * Gera metadados da sessão
     * @return array
     */
    protected function createInfo(){
        $created = new DateTime();
        
        return array(
            "ipv4"      => $_SERVER["REMOTE_ADDR"],
            "userAgent" => $_SERVER["HTTP_USER_AGENT"],
            "created"   => $created
        );
    }

    /**
     * Cria um cookie de sessão se não existir
     * @return bool
     */
    public function createSessionCookieIfNotExists() : bool {
        if($_COOKIE[$this->sessionKey] != null){
            $this->id = $_COOKIE[$this->sessionKey];
            return true;
        }

        $this->id = $this->generateId();
        $name     = $this->sessionKey;
        $value    = $this->id;
        $expires  = 0; // Cookie de Sessão, expira quando o navegador fechar
        $path     = "/";
        $domain   = $_SERVER["SERVER_NAME"];
        $secure   = false; // false = http/https, true = só https
        $httponly = true; // não acessível por JavaScript, só por HTTP
        setcookie($name,$value,$expires,$path,$domain,$secure,$httponly);
        
        return true;
    }

    /**
     * Retorna se um cookie de sessão esta presente na requisição atual
     * @return bool
     */
    public function hasValidCookie() : bool {
        return (array_key_exists($this->sessionKey,$_COOKIE) AND $_COOKIE[$this->sessionKey] != "");
    }
}
?>