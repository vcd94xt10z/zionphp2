<?php
namespace zion\mail;

/**
 * @author Vinicius Cesar Dias
 */
class MailAddress {
    const TYPE_TO  = "TO";
    const TYPE_CC  = "CC";
    const TYPE_BCC = "BCC";
    const TYPE_RPL = "RPL";
    
	private $name;
	private $email;
	private $type;
	
	public static $typeList = array(
		"TO"  => "Destinatário",
		"CC"  => "Com cópia",
		"BCC" => "Com cópia oculta",		
		"RPL" => "Resposta"		
	);

	public function __construct($email="",$name="",$type='TO'){
		$this->setEmail($email);
		$this->setName($name);
		$this->setType($type);
	}

	public function setName($name){
		$this->name = $name;
	}

	public function getName(){
		return $this->name;
	}

	public function setEmail($email){
		$this->email = $email;
	}

	public function getEmail(){
		return $this->email;
	}

	public function setType($type){
		$keys = array_keys(self::$typeList);
		if(in_array($type,$keys)){
			$this->type = $type;
		}
	}

	public function getType(){
		return $this->type;
	}

	public function toString(){
		if($this->getName() == ""){
			return $this->getEmail();
		}
		return $this->getName()." (".$this->getEmail().")";
	}
}
?>