<?php
namespace zion\core;

use DateTime;
use Exception;
use zion\utils\DateTimeUtils;
use zion\utils\FileUtils;
use zion\utils\StringUtils;

/**
 * @author Vinicius
 * @since 08/05/21
 * 
 * Como usar
 * Chamar o método createSession() apenas para criar o cookie de sessão ao efetuar o login por exemplo.
 * Após o cookie estiver criado no frontend, basta chamar os métodos set e get. A sessão vai expirar no 
 * tempo configurado na classe e o arquivo no servidor será deletado. O cookie no frontend nunca é eliminado
 * pela classe (Cookie de Sessao)
 */
class Session {
    public static $sessionKey = "SESSIONID";
    
    /**
     * Tempo para expirar a sessão em segundos
     * 3600 segundos = 1 hora
     * 86400 segundos = 1 dia
     * @var integer
     */
    private static $expireTime = 14400; // 4 horas 
    
    private static $id          = "";
    private static $initialized = false;
    private static $data        = array();
    private static $info        = array();
    
    private static $folder      = "/tmp/";
    
    /**
     * Retorna o id da sessão
     * @return string
     */
    public static function getId(){
        return self::$id;
    }
    
    /**
     * Retorna todas as variáveis de sessão
     * @return array
     */
    public static function getAll(){
        self::init();
        return self::$data;
    }
    
    /**
     * Adiciona uma variável dentro de um array de sessão
     * @param string $key
     * @param mixed $value
     */
    public static function add($key,$value){
        self::init();
        if(!array_key_exists($key, self::$data)){
            self::$data[$key]= array();
        }
        self::$data[$key][] = $value;
        self::write();
    }
    
    /**
     * Define uma variável de sessão
     * @param string $key
     * @param mixed $value
     */
    public static function set($key,$value){
        self::init();
        self::$data[$key] = $value;
        self::write();
    }
    
    public static function hasData(){
        return (sizeof(self::$data) > 0);
    }
    
    public static function hasValidCookie(){
        return array_key_exists(self::$sessionKey,$_COOKIE) AND $_COOKIE[self::$sessionKey] != "";
    }
    
    /**
     * Retorna uma variável de sessão
     * @param string $key
     * @return mixed
     */
    public static function get($key){
        self::init();
        if(!array_key_exists($key, self::$data)){
            return null;
        }
        return self::$data[$key];
    }
    
    public static function init(){
        if(self::$initialized){
            return;
        }
        
        self::$folder = \zion\APP_ROOT."tmp".\DS."session".\DS;
        
        if(self::$id == null){
            self::$id = $_COOKIE[self::$sessionKey];
        }
        
        if(self::hasValidCookie()){
            self::load();
        }
        
        self::$initialized = true;
    }
    
    /**
     * Retorna o caminho do arquivo de sessão
     * @param string $id
     * @return string
     */
    private static function getFile($id=null){
        if($id !== null){
            return self::$folder.$id.".session";
        }
        return self::$folder.self::$id.".session";
    }
    
    public static function createSession($id = null){
        // se o cookie de sessão já esta no navegador, reutiliza o id
        if($_COOKIE[self::$sessionKey] != ""){
            self::$id = $_COOKIE[self::$sessionKey];
            if(sizeof(self::$info) <= 0){
                self::$info = self::createInfo();
            }
            return;
        }
        
        // gerando id de sessão
        if($id == null){
            $id = hash("sha256", uniqid("server1",true).random_int(100000,999999));
        }
        
        // enviando instrução para criar o cookie no cabeçalho da resposta
        // lembrando que cookies de sessão são eliminados ao sair do navegador
        //$domain = ".".$_SERVER["SERVER_NAME"];
        $domain = "";
        setcookie(self::$sessionKey,$id,0,"/",$domain,false,true);
        
        // definindo id e inicializando sessão
        self::$id   = $id;
        self::$info = self::createInfo();
    }
    
    /**
     * Gera metadados da sessão
     * @return array
     */
    private static function createInfo(){
        $created = new DateTime();
        $expire  = new DateTime();
        $expire->modify("+".self::$expireTime." seconds");
        
        return array(
            "ipv4"      => $_SERVER["REMOTE_ADDR"],
            "userAgent" => $_SERVER["HTTP_USER_AGENT"],
            "expireTime" => self::$expireTime,
            "created"   => $created,
            "expire"    => $expire
        );
    }
    
    /**
     * Retorna os metadados da sessão atual
     * @return array
     */
    public static function getInfo(){
        return self::$info;
    }
    
    /**
     * Carrega a sessão do arquivo para a memória
     */
    private static function load(){
        if(!self::hasValidCookie()){
            return;
        }
        
        $file = self::getFile();
        if(file_exists($file)){
            $content = unserialize(file_get_contents($file));
            if(is_array($content)){
                self::$data = $content["data"];
                self::$info = $content["info"];
                $content = null;
            }else{
                // o arquivo existe mas seu conteúdo é inválido, deletando-o
                if(FileUtils::canDelete($file)){
                    unlink($file);
                }
            }
            $content = null;
        }else{
            // o cookie existe mas o arquivo não. Nesse caso o info precisa ser inicializado!
            self::$info = self::createInfo();
        }
        
        // verifica se a sessão expirou
        if(self::$info["expire"] < new DateTime()){
            // limpando dados
            self::$data = array();
            
            // deletando arquivo
            if(FileUtils::canDelete($file)){
                unlink($file);
            }
        }
    }
    
    /**
     * Grava a sessão da memória para o disco
     * @throws Exception
     */
    private static function write(){
        $content = array(
            "data" => self::$data,
            "info" => self::$info
        );
        
        // verifica se há dados na sessão, se não tiver, não há necessidade de gravar um arquivo
        if(sizeof($content["data"]) <= 0){
            return;
        }
        
        if(sizeof($content["info"]) <= 0){
            throw new Exception("Erro ao gravar sessão, há data mas não info");
        }
        
        $file = self::getFile();
        $f = @fopen($file,"w");
        if($f !== false){
            fwrite($f,serialize($content));
            fclose($f);
        }
    }
    
    /**
     * Destrói a sessão
     * @param string $id
     */
    public static function destroy($id = null){
        self::init();
        
        // apagando dados do disco
        $file = self::getFile($id);
        if(file_exists($file) AND FileUtils::canDelete($file)){
            @unlink($file);
        }
        
        if($id === null){
            // apagando dados em memória (somente se a sessão for do próprio usuário)
            self::$data = array();
        }
        
        // já chama a rotina para limpar sessões antigas
        self::cleanFilesSession();
    }
    
    /**
     * Limpa os arquivos de sessão
     */
    public static function cleanFilesSession(){
        $folder = self::$folder;
        $files = scandir($folder);
        foreach($files AS $filename){
            if($filename == "." || $filename == ".."){
                continue;
            }
            
            if(strpos($filename,".session") === false){
                continue;
            }
            
            $file = $folder.$filename;
            
            $dateFile = new DateTime(date("Y-m-d H:i:s",filemtime($file)));
            $secs = DateTimeUtils::getSecondsDiff(new DateTime(),$dateFile);
            if($secs >= self::$expireTime){
                // deleta sessões antigas
                unlink($file);
            }
        }
    }
}
?>