<?php
namespace zion\core;

use Exception;
use PDOException;
use zion\utils\HTTPUtils;
use zion\orm\PDO;
use zion\orm\MySQLDAO;

/**
 * @author Vinicius Cesar Dias
 */
class System {
	// armazena variáveis globais no sistema
	public static $data = array();
	
	private static $connection = null; 

	public static function configure(){
		// constantes
		define("zion\APP_ROOT",dirname($_SERVER["DOCUMENT_ROOT"])."/");
		define("zion\ROOT",\zion\APP_ROOT."vendor/vcd94xt10z/zion2/");

		if(!defined("DS")){
			define("DS",DIRECTORY_SEPARATOR);
		}
		
		// ambiente
		$env = "PRD";
		if(strpos($_SERVER["SERVER_NAME"],".des") !== false OR
			strpos($_SERVER["SERVER_NAME"],".dev") !== false OR
			strpos($_SERVER["SERVER_NAME"],"des.") !== false OR
			strpos($_SERVER["SERVER_NAME"],"dev.") !== false){
				$env = "DEV";
		}else if(strpos($_SERVER["SERVER_NAME"],".qas") !== false || strpos($_SERVER["SERVER_NAME"],"qas.") !== false){
			$env = "QAS";
		}
		define("zion\ENV",$env);

		/*
		* Exibição de erros
		* No ambiente de produção não é interessante exibir erros na tela pois
		* usuários mal intencionados podem usar as informações para explorar
		* vunerabilidades no sistema. Todos os erros relevantes devem ir para o
		* log para que sejam analisados posteriormente e corrigidos
		*/
		error_reporting(E_ALL ^ E_NOTICE);
		if(\zion\ENV == "PRD"){
			ini_set('display_errors', 0);
			ini_set('display_startup_errors', 0);
		}else{
			ini_set('display_errors', 1);
			ini_set('display_startup_errors', 1);
		}

		ini_set('display_errors', 1);
		ini_set('display_startup_errors', 1);

		// funções
		require(\zion\ROOT."functions.php");

		// configuração do arquivo
		$config = zion_get_config_all();

		// deixando a configuração global
		\zion\core\System::set("config",$config);

	    // configurações do aplicativo
	    $all = zion_get_config_all();
	    foreach($all AS $key => $value){
	        System::set($key,$value);
	    }
	    
	    // constantes
	    define("zion\CHARSET","UTF-8");
	    
	    define("zion\HASH_PASSWORD_PREFIX","#198@Az9fF0%*");
	    
	    // diretórios
	    define("zion\TEMP",\zion\APP_ROOT."tmp".\DS);
	    
	    // configurações
	    ini_set("default_charset",\zion\CHARSET);
	    mb_internal_encoding(\zion\CHARSET);
	    
	    self::setTimezone("-03:00");
	    
	    // configurações do sistema
	    System::set("timezone", "-03:00");
	    System::set("dateFormat", "d/m/Y");
	    System::set("timeFormat", "H:i:s");
	    System::set("dateTimeFormat", "d/m/Y H:i:s");
	    System::set("dateTime2Format", "d/m/Y H:i");
	    System::set("country", "br");
	    System::set("lang", "pt");
	    System::set("langDir", "ltr");
	    System::set("currency", "BRL");
	    System::set("currencySymbol", "R\$");
	    System::set("currencyDecimalPlaces", 2);
	    System::set("currencyDecimalSep", ",");
	    System::set("currencyThousandSep", ".");
	    System::setTimezone(System::get("timezone"));
	}

	public static function enableErrorHandler($showErrors=false){
		set_error_handler("zion\core\ErrorHandler::handleError",E_ALL);
	    set_exception_handler("zion\core\ErrorHandler::handleException");
		\zion\core\ErrorHandler::$showErrors = $showErrors;
	}
	
	/**
	 * Retorna informações de espaço de um diretório
	 */
	public static function getDiskInfo($folder="/"){
	    $free        = disk_free_space($folder);
	    $total       = disk_total_space($folder);
	    $freePercent = ($free * 100)/$total;
	    
	    return array(
	        "free"        => $free,
	        "total"       => $total,
	        "freePercent" => $freePercent
	    );
	}
	
	/**
	 * Verifica se há um espaço minimo para o servidor funcionar
	 */
	public static function checkStorage($minFreePercent = 10){
	    // arquivos estaticos não precisam parar a execução por falta de espaço
	    // pois não gravam nada no disco e também são usados em páginas de erro
	    if(self::isStaticURI()){
	        return;
	    }

	    // raiz
	    $folder = "/";
	    $info = System::getDiskInfo($folder);
	    
	    if($info["freePercent"] < $minFreePercent){
	        $message = "Não há espaço suficiente em ".$folder.", é necessário pelo menos "
	            .$minFreePercent."%, contate o administrador";
	            
	            HTTPUtils::status(507);
	            echo $message;
	            exit();
	    }
	    
	    // pasta do aplicativo
	    $folder = $_SERVER["DOCUMENT_ROOT"];
	    $info = System::getDiskInfo($folder);
	    
	    if($info["freePercent"] < $minFreePercent){
	        $message = "Não há espaço suficiente em ".$folder.", é necessário pelo menos "
	            .$minFreePercent."%, contate o administrador";
	            
	            HTTPUtils::status(507);
	            echo $message;
	            exit();
	    }
	}
	
	public static function isStaticURI(){
	    $uri = explode("?",$_SERVER["REQUEST_URI"]);
	    $uri = $uri[0];
	    $ext = explode(".",$uri);
	    $ext = $ext[sizeof($ext)-1];
	    
	    if(in_array($ext,array("css","js"))){
	        return true;
	    }
	    return false;
	}
	
	public static function genUID($prefix="100000000"){
	    // gera um id de 32 caracteres com o prefixo '100000000'
	    return uniqid($prefix,true);
	}
	
	/**
	 * Seta uma variável
	 * Há duas assinaturas:
	 * - set(nome,valor) Seta apenas uma variável
	 * - set(array) Faz o mesmo efeito da primeira, só que em massa
	 */
	public static function set($arg1,$arg2=null){
	    if(is_array($arg1)){
	        foreach($arg1 AS $key => $value){
	            self::$data[$key] = $value;
	        }
	    }else{
	        self::$data[$arg1] = $arg2;
	    }
	}
	
	public static function set2($key1,$key2,$value){
	    self::$data[$key1][$key2] = $value;
	}
	
	public static function set3($key1,$key2,$key3,$value){
	    self::$data[$key1][$key2][$key3] = $value;
	}
	
	/**
	 * Adiciona um valor a um array
	 */
	public static function add($key,$value){
	    if(!array_key_exists($key,self::$data)){
	        self::$data[$key] = array();
	    }
	    
	    // se value for array, distribui os valores como se estivesse chamando vários add()
	    // Atenção: neste método não é possível adicionar um array dentro do array, faça direto no atributo data!
	    if(is_array($value)){
	        self::$data[$key] = array_merge(self::$data[$key],$value);
	    }else{
	        self::$data[$key][] = $value;
	    }
	}
	
	/**
	 * Retorna um valor
	 */
	public static function get($key,$key2=null,$key3=null){
	    if($key3 != null){
	        return self::$data[$key][$key2][$key3];
	    }
	    if($key2 != null){
	        return self::$data[$key][$key2];
	    }
	    return self::$data[$key];
	}
	
	public static function getAll(){
	    return self::$data;
	}
	
	/**
	 * Define o timezone do sistema
	 */
	public static function setTimezone($timezone){
	    // timezone formato +00:00
	    $signal = mb_substr($timezone,0,1);
	    $hour = intval(mb_substr($timezone,1,2));
	    $minute = intval(mb_substr($timezone,4,2));
	    
	    // validando adicional
	    if(($signal == "+" || $signal == "-") && ($hour >= -14 && $hour <= 14) && ($minute >= 0 && $minute < 60)){
	        // atenção! O PHP inverte o sinal
	        $signal = ($signal == "+")?"-":"+";
	        $timezonePHP = "Etc/GMT".$signal.$hour;
	        date_default_timezone_set($timezonePHP);
	    }
	}
	
	public static function getConnection(string $configKey = 'database'){
	    if(self::$connection == null){
	        self::$connection = self::createConnection($configKey);
	    }
	    return self::$connection;
	}
	
	/**
	 * Retorna uma nova conexão com o banco de dados
	 * @param string $exclusive
	 * @throws \Exception
	 */
	public static function createConnection(string $configKey = 'database'){
	    $config = System::get($configKey);
	    if($config == null){
	        http_response_code(500);
	        echo "[zion] Nenhuma configuração de banco encontrada, verifique se o arquivo de configuração foi criado corretamente";
	        exit();
	    }
	    
	    $driverOptions = array(
	        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
	        \PDO::ATTR_TIMEOUT => 10, // 300
	        //\PDO::ATTR_PERSISTENT => true
	    );
	    $strConnection = "";
	    
	    // configurações pré
	    switch(strtolower($config["DBMS"])){
	        case "mysql":
	            $strConnection = "mysql:host=".$config["host"].";port=".$config["port"].";dbname=".$config["schema"].";charset=utf8";
	            $driverOptions[\PDO::MYSQL_ATTR_LOCAL_INFILE] = true;
	            break;
	        case "mssql":
	        case "sqlserver":
	            $strConnection = "dblib:host=".$config["host"].":".$config["port"].";dbname=".$config["schema"];
	            break;
	        case "oracle":
	            $strConnection = "OCI:dbname=".$config["schema"].";charset=UTF-8";
	            break;
	    }
	    
	    if($strConnection == ""){
	        throw new \Exception("Nenhum driver encontrado para o DBMS '".$config["DBMS"]."'");
	    }
	    
	    $pdo = null;
	    try {
	        $pdo = new PDO($strConnection,$config["user"],$config["password"],$driverOptions);
	    }catch(PDOException $e){
	        switch(strtolower($config["DBMS"])){
	            case "mysql":
	                break;
	            case "mssql":
	            case "sqlserver":
	                if($e->getCode() == 20009){
	                    throw new Exception("Erro em conectar no banco, verifique se ele esta rodando e acessível");
	                }
	                break;
	        }
	        throw new Exception("Erro em conectar no banco de dados: ".$e->getMessage());
	    }
	    
	    // configurações pós
	    switch(strtolower($config["DBMS"])){
	        case "mysql":
	            // configurações obrigatórias, a não ser que você configure direto no banco
	            $pdo->query("SET @@time_zone = '-3:00'");
	            
	            // tudo é em UTF8 para não ter problemas com qualquer tipo de caracter
	            //$pdo->query("SET NAMES 'utf8'");
	            break;
	        case "mssql":
	        case "sqlserver":
	            $pdo->query("SET DATEFORMAT ymd");
	            break;
	    }
	    
	    return $pdo;
	}
	
	public static function getDAO(PDO $db = null,$tableName="",$className=""){
	    $DBMS = "";
	    
	    // detectando DBMS
	    if($db == null){
	        $config = System::get("database");
	        $DBMS = strtolower($config["DBMS"]);
	    }else{
	        $dsn = strtolower($db->dsn);
	        if(strpos($dsn,"mysql") !== false){
	            $DBMS = "mysql";
	        }elseif(strpos($dsn,"dblib") !== false){
	            $DBMS = "mssql";
	        }
	        
	        if($dsn == ""){
	            throw new Exception("DSN vazio");
	        }
	    }
	    
	    if($DBMS == ""){
	        throw new Exception("DBMS não encontrado");
	    }
	    
	    // obtendo DAO de acordo com o DBMS
	    if($DBMS == "mysql"){
	        $dao = new MySQLDAO($db,$tableName,$className);
	    }elseif($DBMS == "mssql"){
	        $dao = new MSSQLDAO($db,$tableName,$className);
	    }else{
	        throw new Exception("DAO indisponível para o DBMS (".$dsn.")");
	    }
	    
	    return $dao;
	}
	
	public static function redirectToHTTPS(){
	    if(\zion\ENV == "PRD" AND $_SERVER["HTTPS"] == "off"){
	        $location = 'https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
	        header('HTTP/1.1 301 Moved Permanently');
	        header('Location: '.$location);
	        exit();
	    }
	}
}
?>