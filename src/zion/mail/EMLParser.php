<?php
namespace zion\mail;

require_once(\zion\ROOT."src/unknow/mail/rfc822_addresses.php");
require_once(\zion\ROOT."src/unknow/mail/mime_parser.php");

/**
 * @author Vinicius Cesar Dias
 */
class EMLParser {
	private function __construct(){
	}
	
	public static function parse($emlContent){
		$mime = new \mime_parser_class();
		$mime->ignore_syntax_errors = 1;
		$parameters = array(
			'Data'=> $emlContent,
		);
		$decoded = array();
		$mime->Decode($parameters, $decoded);
		
		$obj = new InputMail();
		
		// procurando dados
		for($i=0,$sizeLoop=sizeof($decoded);$i<$sizeLoop;$i++){
			$obj->setDate($decoded[$i]['Headers']['date:']);
		
			//get the name and email of the sender
			$fromName = $decoded[$i]['ExtractedAddresses']['from:'][0]['name'];
			$fromEmail = $decoded[$i]['ExtractedAddresses']['from:'][0]['address'];
			$obj->setFrom(new MailAddress($fromEmail,$fromName));
			
			//get the name and email of the recipient
			$toEmail = $decoded[$i]['ExtractedAddresses']['to:'][0]['address'];
			$toName = $decoded[$i]['ExtractedAddresses']['to:'][0]['name'];
			$obj->addRecipient(new MailAddress($toEmail,$toName,"TO"));

			//get the subject
			$subject = $decoded[$i]['Headers']['subject:'];
			
			$subjectElem = imap_mime_header_decode($subject);
			if(sizeof($subjectElem) > 0 && mb_strlen($subjectElem[0]->text) > 0){
				$subject = $subjectElem[0]->text;
			}
			$obj->setSubject($subject);
			
			$removeChars = array('<','>');

			//get the message id
			$obj->addHeader("messageID",str_replace($removeChars,'',$decoded[$i]['Headers']['message-id:']));

			//get the reply id
			$obj->addHeader("replyToID",str_replace($removeChars,'',$decoded[$i]['Headers']['in-reply-to:']));
			
			self::parsePartRecursively($obj,$decoded[$i]["Parts"]);
			
			// buscando dados do cabeçalho
			if(mb_substr($decoded[$i]['Headers']['content-type:'],0,mb_strlen('text/html')) == 'text/html' && isset($decoded[$i]['Body'])){
				$partObj = new MailPart();
				$partObj->setContentType("text/html");
				$partObj->setContent($decoded[$i]['Body']);
				$obj->addPart($partObj);
			}
			if(mb_substr($decoded[$i]['Headers']['content-type:'],0,mb_strlen('text/plain')) == 'text/plain' && isset($decoded[$i]['Body'])){
				$partObj = new MailPart();
				$partObj->setContentType("text/plain");
				$partObj->setContent($decoded[$i]['Body']);
				$obj->addPart($partObj);
			}
		}
		return $obj;
	}
	
	private static function parsePartRecursively(InputMail &$obj, array $partArray){
		for($j=0,$sizeArray=sizeof($partArray);$j<$sizeArray;$j++){
			$part = $partArray[$j];
			if(sizeof($part["Parts"]) > 0){
				self::parsePartRecursively($obj,$part["Parts"]);
			}
			
			// extraindo conteúdo
			$contentTypeFull = trim($part["Headers"]["content-type:"]);
			$extra = array(
				"contentType" => "",
				"name" => "",
				"charset" => "",
				"boundary" => ""
			);
			
			$split = explode(";",$contentTypeFull);
			foreach($split AS $piece){
				$index = mb_strpos($piece,"=");
				if($index !== false){
					$key = trim(mb_substr($piece,0,$index));
					$value = trim(mb_substr($piece,$index+1,mb_strlen($piece)));
					if(in_array($key,array("name","charset","boundary"))){
						$extra[$key] = $value;
					}
				}else{
					$extra["contentType"] = $piece;
				}
			}
			
			$extra["name"] = preg_replace("([^\w\s\d\-_~,;:\.\[\]\(\]]|[\.]{2,})", '', $extra["name"]);
			
			$partObj = new MailPart();
			$partObj->setName($extra["name"]);
			$partObj->setContentType($extra["contentType"]);
			$partObj->setBoundary($extra["boundary"]);
			$partObj->setContent($part["Body"]);
			$partObj->setContentId(str_replace(array("<",">"),"",$part["Headers"]["content-id:"]));
			
			$obj->addPart($partObj);
		}
	}
	
	public static function createAttachmentByPart(MailPart $part){
		$attach = new MailAttachment();
		$attach->setName($part->getName());
		$attach->setContent($part->getContent());
		$attach->setContentType($part->getContentType());
		$attach->setContentId($part->getContentId());
		return $attach;
	}
	
	public static function linkBodyEmbeddedElements($body,array $attachments){
		foreach($attachments AS $attach){
			$contentId = $attach->getContentId();
			$data = "data:image/png;base64,".base64_encode($attach->getContent());
			$body = str_replace("cid:".$contentId,$data,$body);
		}
		return $body;
	}
	
	public static function getImageLink(MailAttachment $attach){
		return "data:image/png;base64,".base64_encode($attach->getContent());;
	}
	
	public static function download(MailAttachment $attach){
		header("Content-Type: ".$attach->getContentType());
		header("Content-Disposition: attachment; filename=\"".basename($attach->getName())."\";" );
		header("Content-Transfer-Encoding: binary");
		echo $attach->getContent();
		exit();
	}
}
?>