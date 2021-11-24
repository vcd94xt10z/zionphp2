<?php
namespace zion\core;

use Exception;

/**
 * Gerencia bloqueios para um processo uma determinada tarefa não seja
 * executada por mais de um processo por vez
 * @author Vinicius Cesar Dias
 * Atenção! Classe feita baseada no exemplo do PHP http://us2.php.net/flock
 */
class Lock {
    private $fp   = null;
    private $name = null;
    private $file = null;
    private $own  = false;
    private $errorMessage = "";
    
    public function __construct(string $name){
        if($name == ""){
            throw new Exception("O nome do bloqueio é obrigatório");
        }
        
        // inicializando a pasta
        $folder = \zion\TEMP."lock".\DS;
        if(!file_exists($folder)){
            mkdir($folder,0777,true);
            if(!file_exists($folder)){
                throw new Exception("Erro em criar pasta para gerenciamento de bloqueios");
            }
        }
        
        $this->name = $name;
        $this->file = \zion\TEMP."lock/".$this->name.".lock";
    }
    
    /**
     * @return bool
     * @source https://stackoverflow.com/questions/20771824/test-if-file-is-locked
     */
    public function lock() : bool {
        // já tem o lock
        if($this->own === true){
            return true;
        }
        
        $this->errorMessage = "";
        
        // abrindo arquivo para leitura e escrita
        $this->fp = @fopen($this->file, "w+");
        
        if($this->fp === false){
            $this->errorMessage = "Erro em abrir arquivo";
            return false;
        }
        
        // verificando se outro processo obteve o lock
        $flag = false;
        if (!flock($this->fp, LOCK_EX|LOCK_NB, $flag)) {
            if ($flag) {
                // another process holds the lock
                $this->errorMessage = "Outro processo esta executando ".$this->name;
            }else{
                // couldn't lock for another reason, e.g. no such file
                $this->errorMessage = "Erro em obter lock para ".$this->name;
            }
            return false;
        }
        
        // lock obtido
        $this->own = true;
        $content = array();
        $content[] = "Data: ".date("d/m/Y H:i:s");
        $content[] = "REMOTE_ADDR: ".$_SERVER["REMOTE_ADDR"];
        $content[] = "REQUEST_URI: ".$_SERVER["REQUEST_URI"];
        $content[] = "PID: ".getmypid();
        fwrite($this->fp, implode("\n",$content));
        return true;
    }
    
    public function unlock(){
        // só libera se é o dono do lock
        if($this->own !== true){
            return;
        }
        
        // liberando o lock e fechando o arquivo
        if(is_resource($this->fp)){
            flock($this->fp, LOCK_UN);
            fclose($this->fp);
        }
        
        // deletando o arquivo
        if(file_exists($this->file)){
            @unlink($this->file);
        }
        
        // limpando tudo
        $this->fp   = null;
        $this->own  = false;
        $this->file = null;
        $this->errorMessage = "";
    }
    
    public function getErrorMessage() : string {
        return $this->errorMessage;
    }
    
    public function __destruct(){
        $this->unlock();
    }
}
?>