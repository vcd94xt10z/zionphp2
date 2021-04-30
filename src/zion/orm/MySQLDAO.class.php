<?php
namespace zion\orm;

use PDO;
use Exception;
use PDOException;
use DateTime;

use zion\core\System;

/**
 * @author Vinicius Cesar Dias
 */
class MySQLDAO extends AbstractDAO {
    public function __construct($db = null, $tableName = "", $className = ""){
        parent::__construct($db,$tableName,$className);
        $this->DBMS = "MYSQL";
    }
    
    public function addDelimiters(string $reservedWord) : string {
        if(strpos($reservedWord,".") !== false){
            return $reservedWord;
        }
        return "`".$reservedWord."`";
    }
    
    public function getNextId(PDO $db, string $name, array $options = []) : int {
        if($options["strategy"] == "sequence"){
            throw new Exception("MySQL->getNextId(): Sequence não implementado");
        }else{
            $sql = "SELECT zion_nextval('".$name."') AS `nextval`";
            $query = $db->query($sql);
            if($query === false){
                return -1;
            }
            $raw = $query->fetchObject();
            if($raw === false){
                return -1;
            }
            $id = intval($raw->nextval);
        }
        return $id;
    }
    
    public function loadMetadata(PDO $db,string $tableName) : array {
        // verificando se há no cache
        if(array_key_exists($tableName,self::$metadataCache)){
            return self::$metadataCache[$tableName];
        }
        
        // consultando indices
        $sql = "SHOW INDEX FROM `".$tableName."`";
        $query = $db->query($sql);
        if($query === false){
            throw new Exception("Erro em obter metadados (".$tableName.")");
        }
        $uniqueIndexes = array();
        while($raw = $query->fetchObject()){
            // somente indices únicos que não sejam PK
            if($raw->Non_unique === 0 AND $raw->Key_name != "PRIMARY"){
                $uniqueIndexes[] = $raw->Column_name;
            }
        }
        
        $sql = "SHOW FULL COLUMNS FROM `".$tableName."`";
        $query = $db->query($sql);
        if($query === false){
            throw new Exception("Erro em obter metadados (".$tableName.")");
        }
        $output = array();
        while($raw = $query->fetchObject()){
            $obj = new MetadataField();
            $obj->name = $raw->Field;
            $obj->databaseType = strtolower(preg_replace("/[^a-zA-Z]/","",$raw->Type));
            $obj->size = intval(preg_replace("/[^0-9]/","",$raw->Type));
            $obj->isRequired = ($raw->Null == "YES")?false:true;
            $obj->isPK = ($raw->Key == "PRI")?true:false;
            $obj->defaultValue = $raw->Default;
            $obj->comment = $raw->Comment;
            $obj->isUK = in_array($raw->Field,$uniqueIndexes);
                
            if($obj->size <= 0){
                $obj->size = 1;
            }
            
            switch($obj->databaseType){
            case "integer":
            case "smallint":
            case "int":
            case "long":
            case "bigint":
                $obj->nativeType = "integer";
                break;
            case "double":
            case "float":
                $obj->nativeType = "double";
                break;
            case "date":
                $obj->nativeType = "date";
                break;
            case "datetime":
                $obj->nativeType = "datetime";
                break;
            case "boolean":
            case "tinyint":
            case "tiny":
                $obj->nativeType = "boolean";
                break;
            case "blob":
                $obj->nativeType = "binary";
                break;
            default:
                $obj->nativeType = "string";
                break;
            }
            
            $output[$raw->Field] = $obj;
        }
        
        // salvando no cache
        self::$metadataCache[$tableName] = $output;
        
        return $output;
    }
    
    public function increase(PDO $db,string $field,array $keys,int $quantity=1) : int {
        $field = $this->addDelimiters($field);
        
        $sql  = "UPDATE ".$this->addDelimiters($this->tableName);
        $sql .= "   SET ".$field." = COALESCE(".$field.",0) + {$quantity}";
        $sql .= $this->parseKeysWhere($keys);
        
        return $this->exec($db, $sql);
    }
    
    public function decrease(PDO $db,string $field,array $keys,int $quantity=1) : int {
        $field = $this->addDelimiters($field);
        
        $sql  = "UPDATE ".$this->addDelimiters($this->tableName);
        $sql .= "   SET ".$field." = COALESCE(".$field.",0) - {$quantity}";
        $sql .= $this->parseKeysWhere($keys);
        
        return $this->exec($db, $sql);
    }
    
    public function parseFilter(Filter $filter = null) : string {
	    $sql = "";
	    
		if (!($filter instanceof Filter)) {
		    return $sql;
		}
		
		$dbConfig = System::get("database");
		
		// where
		$bufferWHERE = array();
		
		foreach($filter->getFilterList() AS $group => $conditionList) {
			$buffer = array();
			
			foreach ($conditionList AS $f) {
				$bufferCond = "";
				
				$name = $f["name"];
				$sep = "`";
				if (strpos($f["name"],".") !== false) {
				    $name = str_replace("`","",$f["name"]);
				    $name = explode(".",$name);
				    $name = $name[1];
				    $sep = "";
				}
				
				// regra geral
				$type = strtolower(gettype($f["value1"]));
				
				// exceções
				if($f["value1"] instanceof DateTime){
				    $type = "datetime";
				}
				
				// operadores que usam dois campos
				if (in_array($f["operator"],array("IN","NI","BT","NB","RE","NR"))) {
					$op = SQL::toMySQL($f["operator"]);
					
					if ($f["operator"] == "IN" OR $f["operator"] == "NI") {
					    if($type == "string"){
					        $bufferCond = $sep.$f["name"].$sep." ".$op." (".$f["value1"].")";
					    }else if(is_array($f["value1"])){
					        $list1 = [];
					        foreach($f["value1"] AS $k1 => $v1){
					            $type1 = strtolower(gettype($v1));
					            if($type1 instanceof DateTime){
					                $type1 = "datetime";
					            }
					            
					            switch($type1){
					            case "datetime":
					                $list1[] = "'".$v1->format("Y-m-d")."'";
					                break;
					            case "string":
					                $list1[] = "'".addslashes($v1)."'";
					                break;
					            default:
					                $list1[] = addslashes($v1);
					                break;
					            }
					        }
					        
					        $inValues = implode(",",$list1);
					        $bufferCond = $sep.$f["name"].$sep." ".$op." (".$inValues.")";
					    }
					} elseif (in_array($f["operator"],array("RE","NR"))) {
					    $expValues = explode("|",$f["value1"]);
					    foreach ($expValues as $expValue) {
					        $expValue = addslashes($expValue);
					    }
					    $expValues = "'".implode("|",$expValues)."'";
					    $bufferCond = $sep.$f["name"].$sep." ".$op." ".$expValues;
					} else {
						switch ($type) {
							case "int":
								$bufferCond = $sep.$f["name"].$sep." ".$op." ".$f["value1"]." AND ".$f["value2"];
								break;
							case "date":
							    $value1 = $f["value1"]->format($dbConfig["dateTime"]);
							    $value2 = $f["value2"]->format($dbConfig["dateTime"]);
							    $bufferCond = $sep.$f["name"].$sep." ".$op." '".$value1."' AND '".$value2."'";
							    break;
							case "datetime":
							    $value1 = $f["value1"]->format($dbConfig["dateTimeFormat"]);
							    $value2 = $f["value2"]->format($dbConfig["dateTimeFormat"]);
							    $bufferCond = $sep.$f["name"].$sep." ".$op." '".$value1."' AND '".$value2."'";
							    break;
							default:
								$bufferCond = $sep.$f["name"].$sep." ".$op." '".addslashes($f["value1"])."' AND '".addslashes($f["value2"])."'";
								break;
						}
					}
				} elseif (in_array($f["operator"],array("NU","NN"))) {
					if ($f["operator"] == "NU") {
						$bufferCond = $sep.$f["name"].$sep." IS NULL";
					} else {
						$bufferCond = $sep.$f["name"].$sep." IS NOT NULL";
					}
				} else {
					$op = SQL::toMySQL($f["operator"]);
					$v1 = $f["value1"];
					
					switch ($type) {
					case "date":
						if ($v1 instanceof DateTime) {
							$v1 = $v1->format($dbConfig["dateFormat"]);
						}
						break;
					case "datetime":
						if ($v1 instanceof DateTime) {
							$v1 = $v1->format($dbConfig["dateTimeFormat"]);
						}
						break;
					}
					
					switch ($f["operator"]) {
					case SQL::CONTAINS:
						$op = "LIKE";
						$v1 = "%".addslashes($v1)."%"; 
						break;
					case SQL::NOT_CONTAINS:
					    $op = "NOT LIKE";
					    $v1 = "%".addslashes($v1)."%";
					    break;
					case SQL::STARTS:
						$op = "LIKE";
						$v1 = addslashes($v1)."%"; 
						break;
					case SQL::NOT_STARTS:
					    $op = "NOT LIKE";
					    $v1 = addslashes($v1)."%";
					    break;
					case SQL::ENDS:
						$op = "LIKE";
						$v1 = "%".addslashes($v1); 
						break;
					case SQL::NOT_ENDS:
					    $op = "NOT LIKE";
					    $v1 = "%".addslashes($v1);
					    break;
					}
					
					$bufferCond = $sep.$f["name"].$sep." ".$op." ".$this->addStringDelimiter($v1);
				}
				
				if (sizeof($buffer) == 0) {
					$buffer[] = $bufferCond;
				} else {
					$buffer[] = $f['oplogic']." ".$bufferCond;
				}
			}
			
			if (sizeof($buffer) > 0) {
				$bufferWHERE[$group] = "(".implode(" ",$buffer).")";
			}
		}
		
		// filtro nativo
		if($filter->getNativeFilter() != ""){
		    $bufferWHERE[] = $filter->getNativeFilter();
		}
		
		if (sizeof($bufferWHERE) > 0) {
			if ($filter->getGroupMap() != "") {
				$where = $filter->getGroupMap();
				
				foreach ($filter->getGroupList() AS $group) {
					$where = str_replace(":".$group.":",$bufferWHERE[$group],$where);
				}
				
				$sql .= " WHERE ".$where;
			} else {
				$sql .= " WHERE ".implode(" AND ",$bufferWHERE);
			}
		}
		
		// order by
		$buffer = array();
		foreach ($filter->getSortList() AS $s) {
			// uso de funções
			if (strpos($s["name"],"(") !== false) {
				$buffer[] = $s["name"]." ".$s["order"];
			} else {
				$buffer[] = "".$s["name"]." ".$s["order"];
			}
		}
		
		if (count($filter->getGroupByList()) > 0) {
		    $sql .= " GROUP BY ".implode(", ",$filter->getGroupByList());
		}
		
		if (sizeof($buffer) > 0) {
			$sql .= " ORDER BY ".implode(", ",$buffer);
		}
		
		// limit
		if ($filter->getLimit() > 0) {
			$sql .= " LIMIT ".$filter->getLimit();
		}
		
		// offset
		if ($filter->getOffset() > 0) {
		    $sql .= " OFFSET ".$filter->getOffset();
		}
		
		return $sql;
	}
	
	public function throwException(Exception $e,array $errorInfo){
	    if(!self::$translateError){
	        throw $e;
	    }
	    
	    $msg = $e->getMessage();
	    
	    switch ($errorInfo[1]) {
        case 1364:
            $msg = "Atenção: Um campo obrigatório não foi preenchido ou não foi informado";
            break;
        case 1054:
            $msg = "Atenção: o sistema está consultando uma coluna inexistente no banco de dados.";
            break;
        case 1452:
            $msg = "O cadastro esta referenciando uma registro em outra tabela que não existe";
            break;
        case 1062:
            $msg = "O cadastro esta com alguma chave duplicada (primária ou unica)";
            break;
	    }
	    
	    throw new PDOException($msg,$errorInfo[1],$e->getPrevious());
	}
	
	/**
	 * Salva um conteúdo direto para um arquivo
	 * @param $db
	 * @param array $keys
	 * @param $fieldName
	 * @param $fp resource Ponteiro do arquivo fopen()
	 * @throws Exception
	 */
	public function saveFileToDisk(PDO $db,array $keys,string $fieldName,$fp){
	    if (sizeof($keys) <= 0) {
	        throw new Exception("MySQLDAO::saveFileToDisk(): Chave vazia");
	    }
	    
	    $sql = "SELECT `".$fieldName."`
				  FROM `".$this->tableName."`";
	    $sql .= $this->parseKeysWhere($keys);
	    $sql .= " LIMIT 1";
	    
	    try {
	        $stmt = $db->prepare($sql);
	        $stmt->bindColumn(1, $fp, \PDO::PARAM_LOB);
	        $stmt->execute();
	        $stmt->fetch(\PDO::FETCH_BOUND);
	        $stmt->closeCursor();
	        $stmt = null;
	    } catch (PDOException $e) {
	        $this->throwException($e,$db->errorInfo());
	    }
	}
	
	/**
	 * Alternativa para a função saveFileToDisk() porque ela simplesmente não funciona no momento
	 * @param $db
	 * @param array $keys
	 * @param $fieldName
	 * @param $fp
	 * @throws Exception
	 */
	public function saveFileToDisk2(PDO $db,array $keys,string $fieldName,$fp){
	    if (sizeof($keys) <= 0) {
	        throw new Exception("MySQLDAO::saveFileToDisk2(): Chave vazia");
	    }
	    
	    $sql = "SELECT ".$this->addDelimiters($fieldName)." AS blobdata
				  FROM ".$this->addDelimiters($this->tableName);
	    $sql .= $this->parseKeysWhere($keys);
	    $sql .= " LIMIT 1";
	    
	    try {
	        $query = $db->query($sql);
	    } catch (PDOException $e) {
	        $this->throwException($e,$db->errorInfo());
	    }
	    
	    $raw = $query->fetchObject();
	    fwrite($fp,$raw->blobdata);
	    $raw = null;
	    $query = null;
	}
}
?>