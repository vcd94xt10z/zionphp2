<?php
namespace zion\orm;

use zion\core\Session;
use zion\core\System;
use zion\utils\TimeCounter;

/**
 * @author Vinicius Cesar Dias
 */
class PDO extends \PDO {
    public $dsn = "";
    public static $enableSQLHistory = false;
    public static $enableSQLLog     = false;
    public static $sqlHistory       = array();
    public static $lastSQL = "";
    
    public function __construct($dsn, $username, $passwd, array $options){
        parent::__construct($dsn,$username,$passwd,$options);
        $this->dsn = $dsn;
    }
    
    public function prepare($statement,$driver_options = array()){
        self::$lastSQL = $statement;
        System::set("pdo-lastsql",$statement);
        $this->sendToLog($statement);
        return parent::prepare($statement,$driver_options);
    }
    
	public function query($sql){
		$e = null;
		$errorMessage = "";
		$result = false;
		
		if(self::$enableSQLHistory){
		    System::add("pdo-query",$sql);
		}
		
		self::$lastSQL = $sql;
		System::set("pdo-lastsql",$sql);
		
		TimeCounter::start("query");
		try {
		    if(self::$enableSQLHistory){
		        self::$sqlHistory[] = $sql;
		    }
		    
		    $this->sendToLog($sql);
		    
			$result = parent::query($sql);
		}catch(\Exception $e){
		    $errorMessage = $e->getMessage();
		}
		TimeCounter::stop("query");
		
		if(Session::get("trace") == 1){
			Session::add("traceSQL",array(
				"sql"       => $sql,
				"errorMessage" => $errorMessage,
				"type"      => "query",
				"result"    => ($result !== false)?1:0,
				"created"   => TimeCounter::begin("query"),
				"duration"  => TimeCounter::duration("query")
			));
		}

		if($e != null){
			throw $e;
		}

		return $result;
	}

	public function exec($sql){
		$e = null;
		$errorMessage = "";
		$result = false;
		
		if(self::$enableSQLHistory){
		    System::add("pdo-exec",$sql);
		}
		
		self::$lastSQL = $sql;
		System::set("pdo-lastsql",$sql);
		
		TimeCounter::start("exec");
		try {
		    if(self::$enableSQLHistory){
		        self::$sqlHistory[] = $sql;
		    }
		    
		    $this->sendToLog($sql);
		    
			$result = parent::exec($sql);
		}catch(\Exception $e){
		    $errorMessage = $e->getMessage();
		}
		TimeCounter::stop("exec");

		if(Session::get("trace") == 1){
			Session::add("traceSQL",array(
				"sql"       => $sql,
				"errorMessage" => $errorMessage,
				"type"      => "update",
				"result"    => ($result !== false)?1:0,
				"created"   => TimeCounter::begin("exec"),
				"duration"  => TimeCounter::duration("exec")
			));
		}
		
		if($e != null){
			throw $e;
		}

		return $result;
	}
	
	public function sendToLog($sql){
	    if(!self::$enableSQLLog){
	        return;
	    }
	    
	    $file = \zion\APP_ROOT."log/pdo.log";
	    $f = fopen($file,"a+");
	    if($f === false){
	        return;
	    }
	    
	    fwrite($f,$sql."\n");
	    fclose($f);
	}
}
?>