<?php 
namespace zion\mail;

/**
 * Dados de um e-mail para ser enviado
 * @author Vinicius
 */
class OutputMail extends Mail {
    public $body;
    public $bodyContentType = "text/html";
    public $attachmentFileList = [];
    public $embeddedImageList = [];
    
    public function __construct(){
        parent::__construct();
    }
    
    public function getRecipientsString(){
        $list = array();
        foreach($this->recipients AS $rec){
            if($rec->getName() != ""){
                $list[] = "[{$rec->getType()}] ".$rec->getName()." (".$rec->getEmail().")";
            }else{
                $list[] = "[{$rec->getType()}] ".$rec->getEmail();
            }
        }
        return implode(", ",$list);
    }
    
    public function addAttachment($attachment){
        $this->attachmentFileList[] = $attachment;
    }
    
    public function addEmbeddedImageList($image){
        $this->embeddedImageList[] = $image;
    }
}
?>