<?php
namespace zion\orm;

use StdClass;
use DateTime;
use zion\utils\TextFormatter;

/**
 * @author Vinicius Cesar Dias
 * Objeto utilizado no mapeamento objeto-relacional
 * Design Pattern: VO (Value Object)
 */
class ObjectVO {
    public static $sortField = "";
    protected $data = array();
    
    public function __construct(array $data = array()){
        $this->data = $data;
    }
    
    public function replaceNullByEmpty(){
        foreach($this->data AS $key => $value){
            if($value === null){
                $this->data[$key] = "";
            }
        }
    }
    
    public function set($key,$value,$maxlength=0){
        if($maxlength > 0){
            $value = substr($value,0,$maxlength);
        }
    	$this->data[$key] = $value;
    }
    
    public function setAll($data){
        if($data instanceof \stdClass){
            $data = get_object_vars($data);
        }
        
        foreach($data AS $key => $value){
    	   $this->set($key,$value);
        }
    }
    
    public function getAll(){
        return $this->data;
    }
    
    public function get($key,$index=null){
    	if($index !== null){
    		return $this->data[$key][$index];
    	}
    	return $this->data[$key];
    }
    
    public function multiply(array $fields){
        $result = 0;
        $index = 1;
        foreach($fields AS $field){
            if($index == 1){
                $result =  $this->get($field);
            }else{            
                $result *= $this->get($field);
            }
            $index++;
        }
        return $result;
    }
    
    /**
     * Converte uma lista de campos para query string
     * @param array $fields
     * @return string
     */
    public function toQueryStringKeys(array $fields){
        $output = [];
        foreach($fields AS $field){
            $value = $this->data[$field];
            if($value instanceof DateTime){
                $value = TextFormatter::format("datetime",$value);
            }elseif(is_object($value)){
                $value = (string)$value;
            }
            $output[] = "keys[".$field."]=".urlencode($value);
        }
        return implode("&",$output);
    }
    
    public function concat(array $fields,$separator="-"){
        $values = array();
        foreach($fields AS $field){
            $value = $this->data[$field];
            if($value instanceof DateTime){
                $value = TextFormatter::format("datetime",$value);
            }elseif(is_object($value)){
                $value = (string)$value;
            }
            $values[] = $value;
        }
        return implode($separator,$values);
    }
    
    public function getData() : array {
        return $this->data;
    }
    
    public function unset($key){
        unset($this->data[$key]);
    }
    
    public function &getReference($key,$index=null){
        if($index !== null){
            return $this->data[$key][$index];
        }
        return $this->data[$key];
    }
    
    /**
     * Ordena um array de objetos do tipo ObjectVO
     * @param $field
     * @param string $order
     * @param array $arrayOfObjectVO
     */
    public static function sortDataBy($field,$order="ASC",array &$arrayOfObjectVO){
    	self::$sortField = $field;
    	usort($arrayOfObjectVO,array("\zion\orm\ObjectVO","sortString"));
    	
    	if($order == "DESC"){
    		$arrayOfObjectVO = array_reverse($arrayOfObjectVO);
    	}
    }
    
    public static function sortString($a, $b){
    	return strcmp($a->get(self::$sortField),$b->get(self::$sortField));
    }
    
    public function add($key,$value,$index=null){
    	if($index !== null){
    		$this->data[$key][$index] = $value;
    	}else{
    		$this->data[$key][] = $value;
    	}
    }
    
    public function append($key,$value){
    }
    
    public function prepend($key,$value){
    	$data = $this->data[$key];
    	$this->data[$key] = array();
    	$this->data[$key][] = $value;
    	
    	foreach($data AS $v){
    		$this->data[$key][] = $v;
    	}
    }
    
    public function exists($key){
    	return $this->has($key);
    }
    
    public function isAnyNull(array $keys){
        foreach($keys AS $key){
            if($this->data[$key] === null){
                return true;
            }
        }
        return false;
    }
    
    public function has($key){
    	return array_key_exists($key,$this->data);
    }
    
    public function inc($key,$amount=1){
    	$this->data[$key] += $amount;
    }
    
    public function toJSON(){
    	return json_encode($this->data);
    }
    
    public function cleanRegex(string $key,string $regex){
        $this->data[$key] = preg_replace($regex,"",$this->data[$key]);
    }
    
    public function pregReplace(string $key,string $arg1,string $arg2){
        $this->data[$key] = preg_replace($arg1,$arg2,$this->data[$key]);
    }
    
    public function cast(string $key,string $type){
        switch(strtolower($type)){
        case "int":
            $this->data[$key] = intval($this->data[$key]);
            break;
        case "float":
        case "double":
            $this->data[$key] = floatval($this->data[$key]);
            break;
        case "boolean":
        case "bool":
            $this->data[$key] = ($this->data[$key]);
            break;
        }
    }
    
    public function truncate(string $key,int $size){
        $this->data[$key] = mb_substr($this->data[$key],0,$size);
    }
    
    public function createArrayIfEmpty($key){
        $value = $this->get($key);
        if($value === null){
            $this->set($key,array());
        }
    }
    
    public function toStdClass(){
        $obj = new StdClass();
        foreach($this->data AS $key => $value){
            $obj->$key = $value;
        }
        return $obj;
    }
    
    public function toArray($fields = array()) : array {
        if(sizeof($fields) > 0){
            $output = array();
            foreach($fields AS $field){
                $output[$field] = $this->data[$field];
            }
            return $output;
        }
        return $this->data;
    }
    
    public static function toArrayRecursive(array $dataArray) : array {
        $output = array();
        foreach($dataArray AS $key => $value){
            if($value instanceof ObjectVO){
                $output[$key] = self::toArrayRecursive($value->getData());
            }elseif(is_array($value) AND $value != null){                
                $output[$key] = self::toArrayRecursive($value);
            }else{
                $output[$key] = $value;
            }
        }
        
        return $output;
    }
    
    public static function toArrayFull(array $arr){
        $list = array();
        foreach ($arr as $key => $item) {
            if ($item instanceof ObjectVO) {
                $list[$key] = ObjectVO::toArrayFull($item->getAll());
            } elseif (is_array($item)) {
                $list[$key] = ObjectVO::toArrayFull($item);
            } else {
                $list[$key] = $item;
            }
        }
        return $list;
    }
}
?>