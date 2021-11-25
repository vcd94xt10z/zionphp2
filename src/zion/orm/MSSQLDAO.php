<?php
namespace zion\orm;

use DateTime;
use PDO;
use Exception;
use zion\core\System;

/**
 * @author Vinicius Cesar Dias
 */
class MSSQLDAO extends AbstractDAO {
    public function __construct(PDO $db = null, $tableName = "", $className = ""){
        parent::__construct($db,$tableName,$className);
        $this->DBMS = "MSSQL";
    }
    
    public function addDelimiters(string $reservedWord): string {
        if(strpos($reservedWord,".") !== false){
            return $reservedWord;
        }
        return "[".$reservedWord."]";
    }
    
    public function getNextId(PDO $db, string $name): int {
        // primeira tentativa
        $sql = "SELECT NEXT VALUE FOR ".$this->addDelimiters($name);
        $result = array();
        try {
            $result = $this->queryAndFetch($db, $sql);
        }catch(Exception $e){
        }
        
        if(sizeof($result) == 1){
            return $result[0]->get("computed");
        }
        
        // sequencia não existe, criando
        $sql = "CREATE SEQUENCE ".$this->addDelimiters($name)."
                 START WITH 1
             INCREMENT BY 1;";
        $this->exec($db,$sql);
        
        // segunda tentativa
        $sql = "SELECT NEXT VALUE FOR ".$this->addDelimiters($name);
        $result = $this->queryAndFetch($db, $sql);
        
        if(sizeof($result) == 1){
            return $result[0]->get("computed");
        }
        
        return -1;
    }
    
    public function loadMetadata(PDO $db, string $tableName): array {
        // verificando se há no cache
        if(array_key_exists($tableName,self::$metadataCache)){
            return self::$metadataCache[$tableName];
        }
        
        $sql = "SELECT
                     -- columns / data types
                        c.name AS column_name
                        ,c.column_id
                        ,SCHEMA_NAME(t.schema_id) AS type_schema
                        ,t.name AS type_name
                        ,c.max_length
                        ,c.precision
                        ,c.scale
                        ,c.is_nullable
                    -- primary key / indexes
                    ,i.name AS index_name
                        ,is_identity
                    ,i.is_primary_key
                    -- foreign key
                        ,f.name AS foreign_key_name
                       ,OBJECT_NAME (f.referenced_object_id) AS referenced_object
                       ,COL_NAME(fc.referenced_object_id, fc.referenced_column_id) AS referenced_column_name
                FROM sys.columns AS c 
                INNER JOIN sys.types AS t
                ON c.user_type_id=t.user_type_id
                LEFT OUTER JOIN sys.index_columns AS ic
                ON ic.object_id = c.object_id
                AND c.column_id = ic.column_id
                LEFT OUTER JOIN sys.indexes AS i
                ON i.object_id = ic.object_id
                AND i.index_id = ic.index_id
                LEFT OUTER JOIN sys.foreign_key_columns AS fc
                ON fc.parent_object_id = c.object_id
                AND COL_NAME(fc.parent_object_id, fc.parent_column_id) = c.name
                LEFT OUTER JOIN sys.foreign_keys AS f
                ON f.parent_object_id = c.object_id
                AND fc.constraint_object_id = f.object_id
                WHERE c.object_id = OBJECT_ID('".$tableName."')
                ORDER BY c.column_id;";
        
        $query = $db->query($sql);
        if($query === false){
            throw new Exception("Erro em obter metadados (".$tableName.")");
        }
        $output = array();
        while($raw = $query->fetchObject()){
            $obj = new MetadataField();
            $obj->name = $raw->column_name;
            $obj->databaseType = strtolower(preg_replace("/[^a-zA-Z]/","",$raw->type_name));
            $obj->size = intval(preg_replace("/[^0-9]/","",$raw->max_length));
            $obj->isRequired = ($raw->is_nullable == 1)?false:true;
            $obj->isPK = ($raw->is_primary_key == 1)?true:false;
            $obj->defaultValue = "";
            $obj->comment = "";
            
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
                case "decimal":
                case "real":
                    $obj->nativeType = "double";
                    break;
                case "date":
                    $obj->nativeType = "date";
                    break;
                case "datetime":
                    $obj->nativeType = "datetime";
                    break;
                case "boolean":
                    $obj->nativeType = "boolean";
                    break;
                case "blob":
                case "binary":
                    $obj->nativeType = "binary";
                    break;
                default:
                    $obj->nativeType = "string";
                    break;
            }
            
            $output[$raw->column_name] = $obj;
        }
        
        // salvando no cache
        self::$metadataCache[$tableName] = $output;
        
        return $output;
    }
    
    public function parseFilter(Filter $filter = null): string {
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
                if (strpos($f["name"],".") !== false) {
                    $name = str_replace(array("[","]"),"",$f["name"]);
                    $name = explode(".",$name);
                    $name = $name[1];
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
                        switch ($type) {
                            case "int":
                                $bufferCond = $this->addDelimiters($f["name"])." ".$op." (".$f["value1"].")";
                                break;
                            default:
                                $inValues = explode(",",$f["value1"]);
                                foreach ($inValues as $inValue) {
                                    $inValue = addslashes($inValue);
                                }
                                $inValues = "'".implode("','",$inValues)."'";
                                $bufferCond = $this->addDelimiters($f["name"])." ".$op." (".$inValues.")";
                                break;
                        }
                    } elseif (in_array($f["operator"],array("RE","NR"))) {
                        $expValues = explode("|",$f["value1"]);
                        foreach ($expValues as $expValue) {
                            $expValue = addslashes($expValue);
                        }
                        $expValues = "'".implode("|",$expValues)."'";
                        $bufferCond = $this->addDelimiters($f["name"])." ".$op." ".$expValues;
                    } else {
                        switch ($type) {
                            case "int":
                                $bufferCond = $this->addDelimiters($f["name"])." ".$op." ".$f["value1"]." AND ".$f["value2"];
                                break;
                            case "date":
                                $value1 = $f["value1"]->format($dbConfig["dateTime"]);
                                $value2 = $f["value2"]->format($dbConfig["dateTime"]);
                                $bufferCond = $this->addDelimiters($f["name"])." ".$op." '".$value1."' AND '".$value2."'";
                                break;
                            case "datetime":
                                $value1 = $f["value1"]->format($dbConfig["dateTimeFormat"]);
                                $value2 = $f["value2"]->format($dbConfig["dateTimeFormat"]);
                                $bufferCond = $this->addDelimiters($f["name"])." ".$op." '".$value1."' AND '".$value2."'";
                                break;
                            default:
                                $bufferCond = $this->addDelimiters($f["name"])." ".$op." '".addslashes($f["value1"])."' AND '".addslashes($f["value2"])."'";
                                break;
                        }
                    }
                } elseif (in_array($f["operator"],array("NU","NN"))) {
                    if ($f["operator"] == "NU") {
                        $bufferCond = $this->addDelimiters($f["name"])." IS NULL";
                    } else {
                        $bufferCond = $this->addDelimiters($f["name"])." IS NOT NULL";
                    }
                } else {
                    $op = $f["operator"];
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
                        case SQL::STARTS:
                            $op = "LIKE";
                            $v1 = addslashes($v1)."%";
                            break;
                        case SQL::ENDS:
                            $op = "LIKE";
                            $v1 = "%".addslashes($v1);
                            break;
                    }
                    
                    $bufferCond = $this->addDelimiters($f["name"])." ".$op." '".$v1."'";
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
        
        if (sizeof($bufferWHERE) > 0) {
            if ($filter->getGroupMap() != "") {
                $where = $filter->getGroupMap();
                
                foreach ($filter->getGroupList() AS $group) {
                    $where = str_replace(":".$group.":",$bufferWHERE[$group],$where);
                }
                
                $sql .= " WHERE ".$where;
            } else {
                $sql .= " WHERE ".implode(" OR ",$bufferWHERE);
            }
        }
        
        // order by
        $buffer = array();
        foreach ($filter->getSortList() AS $s) {
            $buffer[] = $s["name"]." ".$s["order"];
        }
        
        if (sizeof($buffer) > 0) {
            $sql .= " ORDER BY ".implode(", ",$buffer);
        }
        
        if (count($filter->getGroupByList()) > 0) {
            $sql .= " GROUP BY ".implode(", ",$filter->getGroupByList());
        }
        
        return $sql;
    }
    
    public function increase(PDO $db, string $field, array $keys, int $quantity = 1): int {
        throw new Exception("Método increase não implementado");
        //return -1;
    }

    public function decrease(PDO $db, string $field, array $keys, int $quantity = 1): int {
        throw new Exception("Método decrease não implementado");
        //return -1;
    }
}
?>