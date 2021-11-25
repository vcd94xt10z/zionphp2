<?php
namespace zion\mail;

/**
 * Dados de um e-mail para ser enviado
 * @author Vinicius Cesar Dias
 */
class InputMail extends Mail {
	private $parts;
	private $eml;
	
	public function __construct(){
		parent::__construct();
		$this->parts = array();
	}
	
	public function getRecipientsString($type=""){
		$output = $this->getRecipients($type);
		$output2 = array();
		foreach($output AS $obj){
			$output2[] = $obj->toString();
		}
		return implode(";",$output2);
	}
	
	public function setParts(array $parts){
		$this->parts = $parts;
	}

	public function getParts(){
		return $this->parts;
	}
	
	public function addPart(MailPart $obj){
		$this->parts[] = $obj;
	}
	
	public function getEML(){
		return $this->eml;
	}
	public function setEML($eml){
		$this->eml = $eml;
	}
	
	public function getAttachments(){
		$output = array();
		foreach($this->parts AS $part){
			if(mb_strlen($part->getName()) > 0){
				$output[] = EMLParser::createAttachmentByPart($part);
			}
		}
		return $output;
	}
	
	public function getAttachmentByHash($hash){
		foreach($this->parts AS $part){
			if(hash("sha256", $part->getContent()) == $hash){
				return EMLParser::createAttachmentByPart($part);
			}
		}
		return null;
	}
	
	public function getBody($contentType="text/html"){
		foreach($this->parts AS $part){
			if($part->getContentType() == $contentType){
				return $part->getContent();
			}
		}
		return "";
	}
	
	/**
	 * Procura o corpo principal do e-mail
	 */
	public function getMainBody(){
		$bodyHTML = $this->getBodyHTML();
		$bodyText = $this->getBodyText();
		
		if(mb_strlen($bodyHTML) > 0){
			return $bodyHTML;
		}
		if(mb_strlen($bodyText) > 0){
			return $bodyText;
		}
		return "";
	}
	
	public function getBodyHTML(){
		return $this->getBody("text/html");
	}
	
	public function getBodyText(){
		$content = $this->getBody("plain/text");
		if($content == ""){
			return $this->getBody("text/plain");
		}
		return "";
	}
}
?>