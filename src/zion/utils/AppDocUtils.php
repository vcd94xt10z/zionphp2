<?php
namespace zion\utils;

use DateTime;
use ReflectionClass;

/**
 * @author Vinicius
 */
class AppDocUtils {
	public static function getFileInfo($file){
		$info = array();
		$info["name"] = basename($file);
		$info["size"] = filesize($file);
		$info["updated"] = new DateTime(date("Y-m-d H:i:s", filemtime($file)));
		
		$data = file_get_contents($file);
		$info["lines"] = substr_count ($data,PHP_EOL);
		$info["chars"] = mb_strlen($data);
		$data = null;
		return $info;
	}
	
	public static function getClassDoc($file){
		require_once($file);
		$className = str_replace(".class.php","",basename($file));
		$obj = new ReflectionClass($className);
		return $obj;
	}
	
	public static function scanPackages($folder,$isRoot=true){
	    if(!is_dir($folder)){
	        return array();
	    }
	    
		$files = scandir($folder);
    	$packageList = array();
    	$classList = array();
    	$acceptRootFiles = array(
    		"public"
    	);
    	foreach($files AS $filename){
    		if($filename == "." || $filename == ".."){
    			continue;
    		}
    		
    		if($isRoot){
    			if(!in_array($filename,$acceptRootFiles)){
    				//continue;
    			}
    		}
    		
    		// padrão
    		$item = array(
    			"name" => $filename,
				"type" => "package",
				"classCounter" => 0,
				"packageCounter" => 0
    		);
    		
    		$file = $folder.$filename;
    		if(is_dir($file)){
    			$item["type"] = "package";
    			$subfiles = scandir($file);
    			foreach($subfiles AS $subfilename){
    				$subfile = $file."/".$subfilename;
    				if(is_dir($subfile)){
    					$item["packageCounter"]++;
    				}else if(mb_strpos($subfilename,".php") !== false){
    					$item["classCounter"]++;
    				}
    			}
    			$packageList[] = $item;
    		}else if(mb_strpos($filename,".php") !== false){    			
    			$item["type"] = "class";
    			$classList[] = $item;
    		}else{
    			continue;
    		}
    	}
    	return array_merge($packageList,$classList);
    }
	
    public static function getShortMethodInfo($m){
    	$part1 = array();
		if($m->isStatic()){
			$part1[] = "<span style='color:#4682B4'>static</span>";
		}
		
		// categoria 1
		if($m->isFinal()){
			$part1[] = "final";
		}else if($m->isAbstract()){
			$part1[] = "abstract";
		}
		
		// modificador
		if($m->isPublic()){
			$part1[] = "<span style='color:#333'>public</span>";
		}else if($m->isPrivate()){
			$part1[] = "<span style='color:#f00'>private</span>";
		}else if($m->isProtected()){
			$part1[] = "<span style='color:#00a'>protected</span>";
		}
		
		$part2 = array();
		$part2[] = "<span style='color:#000'>".$m->getName()."</span>";
		$args = array();
		foreach($m->getParameters() AS $p){
			$pTemp = "";
			if($p->isPassedByReference()){
				$pTemp .= "&";
			}
			$pTemp .= "\$".$p->getName();
			if($p->isOptional()){
				if(is_string($p->getDefaultValue())){
					$pTemp .= " = \"".$p->getDefaultValue()."\"";	
				}else{
					$pTemp .= " = ".$p->getDefaultValue();
				}
			}
			$args[] = $pTemp;
		}
		$part2[] = "(".implode(", ",$args).")";
		
		$comment = "";
		$lines = explode(PHP_EOL,$m->getDocComment());
		$anotations = array();
		foreach($lines AS $line){
			$line = trim($line);
			$line = trim($line,"/**");
			$line = trim($line,"*");
			$line = trim($line,"*/");
			$line = trim($line);
			
			if(mb_strpos($line,"@") === 0){
				$anotations[] = $line;
			}else{
				$comment .= $line." ";
			}
		}
		
		return array(
			"modtype" => implode(" ",$part1),
			"sign" => implode(" ",$part2),
			"comment" => $comment,
			"anotations" => $anotations,
		);
    }
}
?>