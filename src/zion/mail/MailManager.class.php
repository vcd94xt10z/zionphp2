<?php
namespace zion\mail;

use PHPMailer\PHPMailer\PHPMailer;

use Exception;

/**
 * @author Vinicius Cesar Dias
 */
class MailManager {
	private $phpMailer = null;
	private $charset = "UTF-8";

	/**
	 * Inicializa os parâmetros que o PHPMailer precisa
	 * @param array $data
	 */
	public function __construct(array $data){
    	$this->phpMailer = new PHPMailer(true);
		$this->phpMailer->CharSet = $this->charset;
		$this->phpMailer->IsSMTP();
		//$this->phpMailer->SMTPDebug = 1;
		if(array_key_exists("debug",$data) && $data["debug"] > 0){
			$this->phpMailer->SMTPDebug = $data["debug"];
		}
		$this->phpMailer->Host 	   = $data["host"];
		$this->phpMailer->SMTPAuth = $data["auth"];
		$this->phpMailer->Username = $data["user"];
		$this->phpMailer->Password = $data["password"];
		$this->phpMailer->Port     = $data["port"];
		$this->phpMailer->SMTPSecure = $data["secure"];
		$this->phpMailer->WordWrap = 100;
		//$this->phpMailer->SetLanguage("br");
		//$this->phpMailer->SMTPSecure = "tls";
		$this->phpMailer->IsHTML(true);

		$options = array();
		$options["ssl"] = array (
			"verify_peer"       => false,
			"verify_peer_name"  => false,
			"allow_self_signed" => true
		);
		$this->phpMailer->SMTPOptions = $options;
    }
    
    /**
     * Este método server apenas para demonstrar como usar esta classe
     * Copie e cole a implementação e modifique conforme necessidade
     */
    private static function howToUse(){
        $obj = new OutputMail();
        
        // assunto
        $obj->setSubject("Teste de assunto");
        
        // remetente
        $from = new MailAddress();
        $from->setName("Fulano");
        $from->setEmail("fulano@teste.com");
        $obj->setFrom($from);
        
        // destinatários
        $obj->addRecipient(new MailAddress("destino1@teste.com","Destino 1",MailAddress::TYPE_TO));
        $obj->addRecipient(new MailAddress("destino2@teste.com","Destino 2",MailAddress::TYPE_TO));
        $obj->addRecipient(new MailAddress("destino3@teste.com","Destino 3",MailAddress::TYPE_CC));
        
        // mensagem
        $obj->body = "<strong>Teste</strong>";
        $obj->bodyContentType = "text/html";
        
        // dados do smtp
        $data = array(
            "host"     => "smtp.teste.com",
            "auth"     => true,
            "user"     => "teste@teste.com",
            "password" => "123456",
            "port"     => 3333,
            "secure"   => "ssl"
        );
        
        // tentando enviar
        try {
            $mm = new MailManager($data);
            $mm->send($obj);
            echo "OK";
        }catch(Exception $e){
            echo "Erro";
            echo $e->getMessage();
        }
    }
    
    /**
     * Envia um e-mail
     * @param MailAddress $from
     * @param array $recipients
     * @param string $subject
     * @param string $body
     * @param string $bodyContentType
     * @param array $attachmentFileList
     * @param array $embeddedImageList
     * @throws Exception
     */
    public function send(OutputMail $obj){
		// colocando remetente como resposta
		$reply = new MailAddress();
		$reply->setEmail($obj->getFrom()->getEmail());
		$reply->setType("RPL");
		if($obj->getFrom()->getEmail() == ""){
			throw new Exception("E-mail do remetente vazio");
		}
		$obj->addRecipient($reply);
		$this->phpMailer->From = $this->phpMailer->Username;
		$this->phpMailer->FromName = $obj->getFrom()->getName();
		$this->phpMailer->Subject = stripslashes($obj->getSubject());
		
		$toCounter = 0;
		foreach($obj->getRecipients() AS $emailAddress){
			if($emailAddress == null || !($emailAddress instanceof MailAddress)
				|| $emailAddress->getEmail() == "" || $emailAddress->getEmail() == "sem@email"){
				continue;
			}
			switch($emailAddress->getType()){
			case "TO": // destinatário
			default:
				$this->phpMailer->AddAddress($emailAddress->getEmail(),$emailAddress->getName());
				$toCounter++;
				break;
			case "CC": // cópia
				$this->phpMailer->AddCC($emailAddress->getEmail(),$emailAddress->getName());
				break;
			case "BCC": // cópia oculta
				$this->phpMailer->AddBCC($emailAddress->getEmail(),$emailAddress->getName());
				break;
			case "RPL": // email de resposta
				$this->phpMailer->AddReplyTo($emailAddress->getEmail(),$emailAddress->getName());
				break;
			case "CNF": // confirmação de leitura
				$this->ConfirmReadingTo = $emailAddress->getEmail();
				break;
			}
		}

		// verificando se o email tem ao menos um destinatário
		if($toCounter == 0){
			throw new Exception("Nenhum destinatário informado.");
		}

		// anexos
		foreach($obj->attachmentFileList AS $attachment){
			if($attachment instanceof MailAttachment){
				$this->phpMailer->AddAttachment($attachment->getPath(), $attachment->getName(),
						"base64", $attachment->getContentType());
			}else{
				$this->phpMailer->AddAttachment($attachment);
			}
		}

		// imagens incorporadas
		foreach($obj->embeddedImageList AS $emb){
			$this->phpMailer->AddEmbeddedImage($emb["file"],$emb["cid"]);
		}

		$this->phpMailer->ContentType = $obj->bodyContentType;
		if($obj->bodyContentType == "text/html"){
			$this->phpMailer->IsHTML(true);
		}else{
			$this->phpMailer->IsHTML(false);
		}

		$this->phpMailer->Body = $obj->body;
		
		// enviando
		if(!$this->phpMailer->Send()){
			$errorMessage = $this->phpMailer->ErrorInfo;
			try {
				$this->phpMailer->SmtpClose();
			}catch(Exception $e){
			}
			throw new Exception($errorMessage);
		}
		$this->phpMailer->SmtpClose();
	}
}
?>