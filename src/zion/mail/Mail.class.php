<?php 
namespace zion\mail;

/**
 * Email
 * @author Vinicius
 */
class Mail {
    protected $headers;
    protected $date;
    protected $subject;
    protected $from;
    protected $recipients = array();
    
    public function __construct(){
        $this->headers = array();
        $this->recipients = array();
        $this->from = new MailAddress();
    }
    
    public function setHeaders(array $headers){
        $this->headers = $headers;
    }
    
    public function getHeaders(){
        return $this->headers;
    }
    
    public function addHeader($key,$value){
        $this->headers[$key] = $value;
    }
    
    public function setDate($date){
        $this->date = $date;
    }
    
    public function getDate(){
        return $this->date;
    }
    
    public function setSubject($subject){
        $this->subject = $subject;
    }
    
    public function getSubject(){
        return $this->subject;
    }
    
    public function setFrom(MailAddress $obj){
        $this->from = $obj;
    }
    
    public function getFrom(){
        return $this->from;
    }
    
    public function setRecipients(array $recipients){
        $this->recipients = $recipients;
    }
    
    public function addRecipient(MailAddress $obj){
        $this->recipients[] = $obj;
    }
    
    public function getRecipients($type=""){
        if($type == ""){
            return $this->recipients;
        }
        $output = array();
        foreach($this->recipients AS $obj){
            if($obj->getType() == $type){
                $output[] = $obj;
            }
        }
        return $output;
    }
}
?>