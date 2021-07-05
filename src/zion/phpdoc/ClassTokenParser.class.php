<?php
namespace zion\phpdoc;

use Exception;

/*
 * Classe feita com base em outra classe baixada do seguinte link 
 * https://github.com/hugowetterberg/php-class-parser
 * Data 15/09/2015 14:30
 */
/**
 * Classe para interpretar classes e interfaces em arquivos.
 * @author Vinicius Cesar Dias
 */
class ClassTokenParser {
	public $debug = false;
	public $classList = array();
	public $doc = ""; // documentação
	public $inheritanceMod = ""; // (abstract|final)
	public $accessMod = ""; // (public|private|protected)
	public $staticMod = ""; // (static|);
	public $functionArgType = "";
	public $bufferValue = "";
	public $bufferValueEnable = false;
	
	public function getClassList($index=null){
		if($index !== null){
			return $this->classList[$index];
		}
		return $this->classList;
	}
	
	public function setClassData($key,$value){
		$i = sizeof($this->classList) - 1;
		if($i < 0){
			throw new Exception("Indice i negativo");
		}
		
		$this->classList[$i][$key] = $value;
	}
	
	public function addClassData($key,$value){
		$i = sizeof($this->classList) - 1;
		if($i < 0){
			throw new Exception("Indice i negativo");
		}
		
		$this->classList[$i][$key][] = $value;
	}
	
	public function setConstantData($key,$value){
		$i = sizeof($this->classList) - 1;
		if($i < 0){
			throw new Exception("Indice i negativo");
		}
		
		$j = sizeof($this->classList[$i]["constants"]) - 1;
		if($j < 0){
			throw new Exception("Indice j negativo");
		}
		
		$this->classList[$i]["constants"][$j][$key] = $value;
		$this->classList[$i]["constants"][$j]["valueSet"] = 1;
	}
	
	public function constantExists($name){
		$i = sizeof($this->classList) - 1;
		if($i < 0){
			throw new Exception("Indice i negativo");
		}
		foreach($this->classList[$i]["constants"] AS $const){
			if($const["name"] == $name){
				return true;
			}
		}
		return false;
	}
	
	public function addConstant($name){
		$i = sizeof($this->classList) - 1;
		if($i < 0){
			throw new Exception("Indice i negativo");
		}
		$this->classList[$i]["constants"][] = array(
			"name" => $name,
			"value" => null,
			"doc" => $this->doc,
			"comment" => $this->comment,
			"valueSet" => 0
		);
		
		$this->doc = "";
		$this->comment = "";
	}
	
	public function addClassAttribute($attribute){
		$i = sizeof($this->classList) - 1;
		if($i < 0){
			throw new Exception("Indice i negativo");
		}
		$this->classList[$i]["attributes"][] = array(
			"name" => $attribute,
			"hasDefaultValue" => 0,
			"accessMod" => $this->accessMod,
			"staticMod" => $this->staticMod,
			"doc" => $this->doc,
			"comment" => $this->comment,
			"defaultValue" => null,
			"sign" => ""
		);
		
		$this->accessMod = "";
		$this->staticMod = "";
		$this->doc = "";
		$this->comment = "";
	}
	
	public function setDefaultValueClassAttribute($value){
		$i = sizeof($this->classList) - 1;
		if($i < 0){
			throw new Exception("Indice i negativo");
		}
		
		$j = sizeof($this->classList[$i]["attributes"]) - 1;
		if($j < 0){
			throw new Exception("Indice j negativo");
		}
		
		$this->classList[$i]["attributes"][$j]["hasDefaultValue"] = 1;
		if($value === true){
			$value = "true";
		}else if($value === false){
			$value = "false";
		}
		if($value === null){
			$value = "null";
		}
		
		$this->classList[$i]["attributes"][$j]["defaultValue"] = $value;
	}
	
	public function setDefaultValueFunctionArgs($value){
		$i = sizeof($this->classList) - 1;
		if($i < 0){
			throw new Exception("Indice i negativo");
		}
		
		$j = sizeof($this->classList[$i]["functions"]) - 1;
		if($j < 0){
			throw new Exception("Indice j negativo");
		}
		
		$k = sizeof($this->classList[$i]["functions"][$j]["args"])-1;
		$this->classList[$i]["functions"][$j]["args"][$k]["hasDefaultValue"] = 1;
		$this->classList[$i]["functions"][$j]["args"][$k]["defaultValue"] = $value;
	}
	
	public function getClassData($key){
		$i = sizeof($this->classList) - 1;
		if($i < 0){
			throw new Exception("Indice i negativo");
		}
		return $this->classList[$i][$key];
	}
	
	public function setFunctionData($key,$value){
		$i = sizeof($this->classList) - 1;
		if($i < 0){
			throw new Exception("Indice i negativo");
		}
		
		$j = sizeof($this->classList[$i]["functions"]) - 1;
		if($j < 0){
			throw new Exception("Indice j negativo");
		}
		$this->classList[$i]["functions"][$j][$key] = $value;
	}
	
	public function getFunctionData($key){
		$i = sizeof($this->classList) - 1;
		if($i < 0){
			throw new Exception("Indice i negativo");
		}
		
		$j = sizeof($this->classList[$i]["functions"]) - 1;
		if($j < 0){
			throw new Exception("Indice j negativo");
		}
		
		return $this->classList[$i]["functions"][$j][$key];
	}
	
	public function addEmptyFunctionArg($name){
		$i = sizeof($this->classList) - 1;
		if($i < 0){
			throw new Exception("Indice i negativo");
		}
		
		$j = sizeof($this->classList[$i]["functions"]) - 1;
		if($j < 0){
			throw new Exception("Indice j negativo");
		}
		
		$this->classList[$i]["functions"][$j]["args"][] = array(
			"name" => $name,
			"type" => $this->functionArgType,
			"hasDefaultValue" => 0,
			"defaultValue" => null
		);
		
		$this->functionArgType = "";
	}
	
	public function addEmptyFunction(){
		$i = sizeof($this->classList) - 1;
		if($i < 0){
			throw new Exception("Indice i negativo");
		}
		
		$this->classList[$i]["functions"][] = array(
			"name" => "",
			"doc" => $this->doc,
			"comment" => $this->comment,
			"inheritanceMod" => $this->inheritanceMod,
			"accessMod" => $this->accessMod,
			"staticMod" => $this->staticMod,
			"args" => array(),
			"sign" => ""
		);
		
		$this->doc = "";
		$this->comment = "";
		$this->accessMod = "";
		$this->inheritanceMod = "";
		$this->staticMod = "";
	}
	
	public function addEmptyClass($type="class"){
		$this->classList[] = array(
			"name" => "",
			"type" => $type,
			"doc" => $this->doc,
			"comment" => $this->comment,
			"accessMod" => $this->accessMod,
			"inheritanceMod" => $this->inheritanceMod,
			"extends" => "",
			"implements" => array(),
			"constants" => array(),
			"attributes" => array(),
			"functions" => array(),
			"construct" => null,
			"destruct" => null,
		);
		
		$this->doc = "";
		$this->comment = "";
		$this->inheritanceMod = "";
	}
	
	public function parseDoc($doc){
		$text = array();
		$annotations = array();
		
		// unificando quebras de linha
		$breakLineChar = '\n';
		$doc = str_replace("\r\n",$breakLineChar,$doc);
		$doc = str_replace('\r\n',$breakLineChar,$doc);
		$doc = str_replace("\n",$breakLineChar,$doc);
		$doc = str_replace("\r",$breakLineChar,$doc);
		$doc = str_replace('\r',$breakLineChar,$doc);
		
		$docList = explode($breakLineChar,$doc);
		foreach($docList AS $line){
			$line = trim($line);
			
			// removendo caracteres especificos de comentário
			if(mb_strpos($line,"/**") === 0){
				$line = mb_substr($line,3,mb_strlen($line));
			}else if(mb_strpos($line,"*/") === 0){
				$line = mb_substr($line,2,mb_strlen($line));
			}else if(mb_strpos($line,"*") === 0){
				$line = mb_substr($line,1,mb_strlen($line));
			}
			$line = trim($line);
			
			// verificando se é annotation ou texto
			if(mb_strpos($line,"@") === 0){
				//$i1 = mb_strpos($line,"@");
				$i2 = mb_strpos($line," ");
				
				if($i2 !== false){
					$key = mb_substr($line,1,$i2);
					$value = trim(mb_substr($line,$i2,mb_strlen($line)));
					$annotations[$key] = $value;
				}
			}else{
				if($line != ""){
					$text[] = $line;	
				}	
			}
		}
		
		// separando texto curto e texto completo
		$fullText = implode("\n",$text);
		$fullText = trim($fullText,"\n");
		$fullText = trim($fullText,"\r\n");
		$fullText = trim($fullText,'\n');
		$fullText = trim($fullText,'\r\n');
		
		$shortText = $fullText;
		
		$firstDot = mb_strpos($fullText,". ");
		if($firstDot !== false){
			$shortText = mb_substr($fullText,0,$firstDot);	
		}
		
		return array(
			"fullText" => $fullText,
			"shortText" => $shortText,
			"annotations" => $annotations,
		);
	}
	
	public function buildFunctionSign($func,$full=false){
		$buffer = array();
		if($full){
			if($func["inheritanceMod"] != ""){
				$buffer[] = $func["inheritanceMod"];
			}
			if($func["accessMod"] != ""){
				$buffer[] = $func["accessMod"];
			}
		}
		$buffer[] = $func["name"];
		
		$tmp = array();
		foreach($func["args"] AS $arg){
			if($arg["hasDefaultValue"] == 1){
				$tmp[] = $arg["name"]." = ".$arg["defaultValue"];
			}else{
				$tmp[] = $arg["name"];
			}
		}
		$buffer[] = "(".implode(", ",$tmp).")";
		return implode(" ",$buffer);
	}
	
	public function buildAttributeSign($attr){
		$buffer = array();
		if($attr["accessMod"] != ""){
			$buffer[] = $attr["accessMod"];
		}
		if($attr["hasDefaultValue"] == 1){
			$buffer[] = $attr["name"]." = ".$attr["defaultValue"];
		}else{
			$buffer[] = $attr["name"];
		}
		return implode(" ",$buffer);
	}
	
	public function buildClassSign($class){
		$buffer = array();
		
		if($class["accessMod"] != ""){
			$buffer[] = $class["accessMod"];
		}
		
		$buffer[] = $class["type"];
		$buffer[] = $class["name"];
		
		if($class["extends"] != ""){
			$buffer[] = "extends ".$class["extends"];
		}		
		if(sizeof($class["implements"]) > 0){
			$buffer[] = "implements ".implode(", ",$class["implements"]);
		}
		return implode(" ",$buffer);
	}
	
	/**
	 * Organiza os dados
	 */
	public function organize(){
		for($i=0;$i<sizeof($this->classList);$i++){
			$this->classList[$i]["doc"] = $this->parseDoc($this->classList[$i]["doc"]);
			$this->classList[$i]["sign"] = $this->buildClassSign($this->classList[$i]);
			
			// constantes
			for($j=0;$j<sizeof($this->classList[$i]["constants"]);$j++){
				$this->classList[$i]["constants"][$j]["doc"] = $this->parseDoc($this->classList[$i]["constants"][$j]["doc"]);
			}
			
			// atributos
			for($j=0;$j<sizeof($this->classList[$i]["attributes"]);$j++){
				$this->classList[$i]["attributes"][$j]["sign"] = $this->buildAttributeSign($this->classList[$i]["attributes"][$j]);
				$this->classList[$i]["attributes"][$j]["doc"] = $this->parseDoc($this->classList[$i]["attributes"][$j]["doc"]);
			}
			
			// métodos
			for($j=0;$j<sizeof($this->classList[$i]["functions"]);$j++){
				$this->classList[$i]["functions"][$j]["sign"] = $this->buildFunctionSign($this->classList[$i]["functions"][$j]);
				$this->classList[$i]["functions"][$j]["doc"] = $this->parseDoc($this->classList[$i]["functions"][$j]["doc"]);
				
				$name = $this->classList[$i]["functions"][$j]["name"];
				if($name == "__construct"){
					$this->classList[$i]["construct"] = $this->classList[$i]["functions"][$j];
					$this->classList[$i]["functions"][$j] = null;
				}else if($name == "__destruct"){
					$this->classList[$i]["destruct"] = $this->classList[$i]["functions"][$j];
					$this->classList[$i]["functions"][$j] = null;
				}
			}
			
			// removendo funções null no array de funções por causa do construtor e destrutor
			$this->classList[$i]["functions"] = array_filter($this->classList[$i]["functions"]);
		}
	}
	
	public function parse($file){
		/*
		 * Indica onde o ponteiro está
		 * global = Contexto global do PHP, fora de qualquer classe, função, if,switch, while, for, foreach etc
		 * class = Dentro de uma classe
		 * extends
		 * implements
		 * classConst = Dentro de uma constante
		 * classAttr = Dentro de um atributo
		 * function = Dentro de um método
		 * functionArg = Argumento de método
		 */
		$pointer = "global";
		
		/*
		 * Profundidade, a cada nível que você entra dentro de uma classe, método, if etc um nível é adicionado e quando sai
		 * é removido. Esta classe só trabalha no nível 0 e 1 porque o objetivo é extrair informações de classe e métodos
		 */
		$depthCURLY = 0;
		$depthPARENTHESIS = 0;
		
		$tokenList = token_get_all(file_get_contents($file));
		foreach($tokenList AS $tokenItem){
			if($this->debug){
				echo "<hr>";
				echo "Pointer = ".$pointer."<br>";
				echo "Depth CURLY = ".$depthCURLY."<br>";
				echo "Depth PARENTHESIS = ".$depthPARENTHESIS."<br>";
				echo "Token = ".token_name($tokenItem[0])."<br>";
				echo "Buffer Ativo = ".$this->bufferValueEnable."<br>";
				echo "Buffer = ".$this->bufferValue."<br>";
				var_dump($tokenItem);
			}
			
			if(is_array($tokenItem)){
				// considerando esse token para não que a profundidade fique correta
				if($tokenItem[0] == T_CURLY_OPEN){
					$depthCURLY++;
				}
				
				// Só considera tokens que estão no nivel 0 (contexto global) e 1 (dentro da classe)
				if($depthCURLY <= 1){
					if($this->bufferValueEnable){
						// só não considera espaços em branco
						//if($tokenItem[0] != T_WHITESPACE){
							$this->bufferValue .= $tokenItem[1];	
						//}
					}else{
						switch($tokenItem[0]){
						case T_DOC_COMMENT:
							$this->doc = $tokenItem[1];
							break;
						case T_COMMENT:
							$this->comment = $tokenItem[1];
							break;
						case T_ABSTRACT:
						case T_FINAL:
							$this->inheritanceMod = $tokenItem[1];
							break;
						case T_PUBLIC:
						case T_PRIVATE:
						case T_PROTECTED:
							$this->accessMod = str_replace("t_","",mb_strtolower(token_name($tokenItem[0])));
							break;
						case T_STATIC:
							$this->staticMod = "static";
							break;
						case T_VARIABLE:
							if($pointer == "class"){
								$pointer = "classAttr";
								$this->addClassAttribute($tokenItem[1]);
							}else if($pointer == "function"){
								$this->setFunctionData("name",$tokenItem[1]);
							}else if($pointer == "functionArg"){
								$this->addEmptyFunctionArg($tokenItem[1]);
							}
							break;
						case T_CONST:
							if($pointer == "class"){
								$pointer = "classConst";
							}
							break;
						case T_CLASS:
							$this->addEmptyClass();
							$pointer = "class";
							break;
						case T_INTERFACE:
							$this->addEmptyClass("interface");
							$pointer = "class";
							break;
						case T_FUNCTION:
							$this->addEmptyFunction();
							$pointer = "function";
							break;
						case T_EXTENDS:
		          		case T_IMPLEMENTS:
		          			$pointer = $tokenItem[1];
		          			break;
						case T_STRING:
						case T_CONSTANT_ENCAPSED_STRING:
						case T_LNUMBER:
						case T_ARRAY:
						case T_DNUMBER:
							if($pointer == "extends" || $pointer == "implements"){
								if($pointer == "extends"){
									$this->setClassData($pointer,$tokenItem[1]);
								}else{
									$this->addClassData($pointer,$tokenItem[1]);
								}
							}else if($pointer == "function"){
								$this->setFunctionData("name",$tokenItem[1]);
								
								// o nome da função é o ultimo token antes dos parâmetros
								$pointer = "functionArg";
							}else if($pointer == "functionArg"){
								$this->functionArgType = $tokenItem[1];
							}else if($pointer == "class"){
								if($this->getClassData("name") == ""){
									$this->setClassData("name",$tokenItem[1]);
								}
							}else if($pointer == "classConst"){
								$this->addConstant($tokenItem[1]);
							}
							break;		
						}
					}
				}
			}else{
				$oldDepthCURLY = $depthCURLY;
				switch ($tokenItem){
				case '{':
					$depthCURLY++;
					break;
				case '}':
					$depthCURLY--;
					break;
				case '(':
					$depthPARENTHESIS++;
					break;
				case ')':
					$depthPARENTHESIS--;
					break;
				}
				
				// ajustando o ponteiro para classe após um extends e implements
				if($oldDepthCURLY == 0 && $depthCURLY == 1){
					$pointer = "class";
				}
				
				// volta o ponteiro para classe após o 1 nível
				if($depthCURLY > 1){
					$pointer = "class";
				}
				
				// só considera no nível da classe
				if($depthCURLY == 1){
					if($pointer == "functionArg"){
						// virgula é o separador de argumento na função e o fechamento de parenteses é o final da função no nível 1
						if($tokenItem == ',' || ($tokenItem == ')' && $depthPARENTHESIS == 0)){
							//echo "--------- TOKEN ".$tokenItem."| depthPARENTHESIS = ".$depthPARENTHESIS."<br>";
							if($this->bufferValue != ""){
								$this->setDefaultValueFunctionArgs($this->bufferValue);
							}
							$this->bufferValue = "";
							$this->bufferValueEnable = false;
						}
					}
					
					// desativando buffer de valor
					if($tokenItem == ';'){
						// salvando buffer se ele foi ativo
						if($this->bufferValueEnable){
							switch($pointer){
							case "classConst":
								$this->setConstantData("value",$this->bufferValue);
								break;
							case "classAttr":
								$this->setDefaultValueClassAttribute($this->bufferValue);
								break;							
							}
						}						
						$pointer = "class"; // voltando ponteiro para classe
						$this->bufferValue = ""; // apagando buffer
						$this->bufferValueEnable = false; // desabilitando buffer
					}else if($tokenItem == '='){
						$this->bufferValueEnable = true;
					}else{
						if($this->bufferValueEnable){
							$this->bufferValue .= $tokenItem;
						}
					}
				}
			}
		}
		
		if($this->debug){
			print "<pre>";
			print_r($this->classList);
			print "</pre>";
			exit();
		}
		
		$this->organize();
	}
}
?>