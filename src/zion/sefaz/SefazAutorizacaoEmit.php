<?
namespace zion\sefaz;

class SefazAutorizacaoEmit {
    public $CNPJ;
    public $IE;
    public $xNome;
    public $CRT;
    
    public $endereco;
    
    public function __construct(){
        $this->endereco = new SefazAutorizacaoEmitEndereco();
    }
}
?>