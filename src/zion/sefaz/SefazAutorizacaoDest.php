<?
namespace zion\sefaz;

class SefazAutorizacaoDest {
    public $xNome;
    public $CPF;
    public $endereco;
    
    public function __construct(){
        $this->endereco = new SefazAutorizacaoDestEndereco();
    }
}
?>