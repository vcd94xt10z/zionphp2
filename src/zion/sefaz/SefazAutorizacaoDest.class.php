<?
namespace zion\sefaz;

/**
 * Objeto principal para autorizar uma nota fiscal eletronica
 */
class SefazAutorizacaoDest {
    public $xNome;
    public $CPF;

    public $endereco;
    
    public function __construct(){
        $this->endereco = new SefazAutorizacaoDestEndereco();
    }
}
?>