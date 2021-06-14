<?php
namespace zion\mail;

/**
 * @author Vinicius Cesar Dias
 */
class MailAttachment extends MailPart {
	private $path;
	private $encoding;
	
	public function __construct($path='',$name=''){
		$this->setEncoding("base64");
		$this->setContentType("application/octet-stream");
		$this->setName($name);
		$this->setPath($path);
	}
	
	public function getPath(){
		return $this->path;
	}
	
	public function setPath($path){
		$this->path = $path;
	}
	
	public function getEncoding(){
		return $this->encoding;
	}
	
	public function setEncoding($encoding){
		$this->encoding = $encoding;
	}
	
	public function toArray(){
		$output = array();
		$output["path"] = $this->getPath();
		$output["encoding"] = $this->getEncoding();
				
		$output = array_merge($output,parent::toArray());
		
		return $output;
	}
	
	public function toString(){
		$data = $this->toArray();
		$output = array();
		foreach($data AS $key => $value){
			$output[] = $key."=".$value;
		}
		return implode(",",$output);
	}
}
?>