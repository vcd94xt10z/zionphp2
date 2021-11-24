<?
namespace zion\sefaz;

class SefazAutorizacaoProduto {
    public $cProd;
    public $xProd;
    public $NCM;
    public $CFOP;
    public $cEAN;
    public $cEANTrib;
    public $uCom;
    public $qCom;
    public $vUnCom;
    public $vProd;
    public $uTrib;
    public $qTrib;
    public $vUnTrib;
    public $indTot;
    public $vTotTrib;
    public $ICMS;

    public function __construct(){
        $this->ICMS = new SefazAutorizacaoProdutoICMS();
    }
}
?>