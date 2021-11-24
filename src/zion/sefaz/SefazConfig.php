<?
namespace zion\sefaz;

class SefazConfig {
    public $CSC;
    public $CSCToken;
    public $certificadoArquivo;
    public $certificadoConteudo;
    public $certificadoSenha;
    public $emit;
    public $responsavelTecnico;

    public function __construct(){
        $this->emit = new SefazAutorizacaoEmit();
        $this->responsavelTecnico = new SefazAutorizacaoInfRespTec();
    }
}
?>