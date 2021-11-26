<?php
namespace zion\session;

use Exception;
use zion\utils\FileUtils;

// Atenção! Classe em desenvolvimento, ainda não liberada para uso!

class SessionFile extends AbstractSession {
    protected $folder = "/tmp/";

    public function __construct(array $config = []){
        parent::__construct($config);
        $this->createSessionCookieIfNotExists();
    }

    public function set(string $key,$value){
        $this->load();
        parent::set($key,$value);
        $this->write();
    }

    /**
     * Destroi a sessão
     * @return void
     */
    public function destroy(){
        parent::destroy();
        $this->write();

        $file = $this->getFile();
        if(FileUtils::canDelete($file)){
            unlink($file);
        }
    }

    /**
     * Retorna o caminho completo do arquivo
     */
    private function getFile(){
        return $this->folder.$this->sessionKey."-".$this->id.".session";
    }

    private function load(){
        if(!$this->hasValidCookie()){
            return;
        }
        
        $file = $this->getFile();
        if(file_exists($file)){
            $content = unserialize(file_get_contents($file));
            if(is_array($content)){
                $this->data = $content["data"];
                $this->info = $content["info"];
                $content = null;
            }else{
                // o arquivo existe mas seu conteúdo é inválido, deletando-o
                if(FileUtils::canDelete($file)){
                    unlink($file);
                }
            }
            $content = null;
        }else{
            // o cookie existe mas o arquivo não. Nesse caso o info precisa ser inicializado!
            $this->info = $this->createInfo();
        }
    }

    /**
     * Atualiza a sessão no disco
     */
    private function write(){
        $content = array(
            "data" => $this->data,
            "info" => $this->info
        );
        
        // verifica se há dados na sessão, se não tiver, não há necessidade de gravar um arquivo
        if(sizeof($content["data"]) <= 0){
            return;
        }
        
        if(sizeof($content["info"]) <= 0){
            throw new Exception("Erro ao gravar sessão, há data mas não info");
        }
        
        $file = $this->getFile();
        $f = @fopen($file,"w");
        if($f !== false){
            fwrite($f,serialize($content));
            fclose($f);
        }
    }
}
?>