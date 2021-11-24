<?
namespace zion\sefaz;

class SefazAutorizacaoTransp {
    const MODFRETE_POR_CONTA_DO_EMITENTE  = 0;
    const MODFRETE_POR_CONTA_DO_DEST_REM  = 1;
    const MODFRETE_POR_CONTA_DE_TERCEIROS = 2;
    const MODFRETE_SEM_FRETE              = 9;
    
    public $modFrete;
}
?>