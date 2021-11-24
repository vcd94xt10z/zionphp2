<?php
namespace zion\mail;

/**
 * @author Vinicius Cesar Dias
 */
class MailPart {
	protected $contentType;
	protected $content;
	protected $name;
	protected $boundary;
	protected $contentId;
	
	public function __construct(){
	}

	public function setContentType($contentType){
		$this->contentType = $contentType;
	}

	public function getContentType(){
		return $this->contentType;
	}

	public function setContent($content){
		$this->content = $content;
	}

	public function getContent(){
		return $this->content;
	}

	public function setName($name){
		$this->name = $name;
	}

	public function getName(){
		return $this->name;
	}
	
	public function setBoundary($data){
		$this->boundary = $data;
	}
	
	public function getBoundary(){
		return $this->boundary;
	}
	
	public function setContentId($data){
		$this->contentId = trim($data);
	}
	
	public function getContentId(){
		return $this->contentId;
	}
	
	public function toArray(){
		$data = array();
		$data["contentType"] = $this->getContentType();
		//$data["content"] = $this->getContent();
		$data["name"] = $this->getName();
		$data["boundary"] = $this->getBoundary();
		$data["contentId"] = $this->getContentId();
		return $data;
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