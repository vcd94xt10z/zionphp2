<?php
namespace zion\sefaz;

use PDO;
use DateTime;
use Exception;
use StdClass;

use \zion\core\System;

class SefazTest {
/**
     * Retorna um objeto que pode ser usado para teste em
     * ambiente de homologação
     *
     * @return void
     */
    public static function getAutorizaObjTestNFCePF(){
        $obj = new SefazAutorizacao();

        // dados gerais
        $obj->cUF      = 41; // PR
        $obj->cNF      = random_int(10000000,99999999); // Código Numérico que compõe a Chave de Acesso da NF-e
        $obj->natOp    = "VENDA";
        $obj->mod      = SefazAutorizacao::MOD_CUPOM_FISCAL_ELETRONICO;
        $obj->nNF      = 10001;   // Número da Nota fiscal
        $obj->serie    = 1;       // Série
        $obj->indPag   = SefazAutorizacao::INDPAG_PAGAMENTO_A_VISTA;
        $obj->tpNF     = SefazAutorizacao::TPNF_SAIDA;
        $obj->idDest   = SefazAutorizacao::IDDEST_OPERACAO_INTERNA;
        $obj->tpImp    = SefazAutorizacao::TPIMP_DANFE_NFCE;
        $obj->tpEmis   = SefazAutorizacao::TPEMIS_EMISSAO_NORMAL;
        $obj->tpAmb    = SefazAutorizacao::TPAMB_HOMOLOGACAO;
        $obj->finNFe   = SefazAutorizacao::FINNFE_NFE_NORMAL;
        $obj->indFinal = SefazAutorizacao::INDFINAL_CONSUMIDOR_FINAL;
        $obj->indPres  = SefazAutorizacao::INDPRES_OPERACAO_PRESENCIAL;
        $obj->procEmi  = SefazAutorizacao::PROCEMI_EMISSAO_NFE_APLICATIVO_CONTRIBUINTE;
        
        // dados do destinatário
        $obj->dest        = new SefazAutorizacaoDest();
        $obj->dest->xNome = "José da Silva";
        $obj->dest->CPF   = "43632355940";

        $obj->dest->endereco->xLgr = "Rua da Canafístula";
        $obj->dest->endereco->nro  = "132";
        $obj->dest->endereco->xBairro = "Jardim São Rafael";
        $obj->dest->endereco->xMun    = "Londrina";
        $obj->dest->endereco->cMun    = "4113700";
        $obj->dest->endereco->UF      = "PR";
        $obj->dest->endereco->CEP     = "86035294";

        // produtos
        $prod = new SefazAutorizacaoProduto();
        $prod->cProd    = '1';
        $prod->xProd    = 'PRODUTO TESTE';
        $prod->NCM      = '39100019';
        $prod->CFOP     = '5102';
        $prod->cEAN     = '7898932740209';
        $prod->cEANTrib = '7898932740209';
        $prod->uCom     = 'PC';
        $prod->qCom     = 1;
        $prod->vUnCom   = 16.09;
        $prod->vProd    = 16.09;
        $prod->uTrib    = 'PC';
        $prod->qTrib    = 1;
        $prod->vUnTrib  = 16.09;
        $prod->indTot   = 1;

        $prod->vTotTrib = 16.09;

        // ICMS
        $prod->ICMS->item  = 1;
        $prod->ICMS->orig  = 0;
        $prod->ICMS->CST   = '00';
        $prod->ICMS->modBC = 0;
        $prod->ICMS->vBC   = 16.90;
        $prod->ICMS->pICMS = 18;
        $prod->ICMS->vICMS = 3.04;

        $obj->produto[] = $prod;

        // frete
        $obj->transp->modFrete = SefazAutorizacaoTransp::MODFRETE_SEM_FRETE;

        // volumes
        $obj->vol->item = 1;
        $obj->vol->esp   = 'Caixa';
        $obj->vol->marca = 'Generico';
        $obj->vol->nVol  = 1;
        $obj->vol->qVol  = 1;
        $obj->vol->pesoL = 1;
        $obj->vol->pesoB = 1;

        // dados da fatura
        $obj->fat->nFat  = 1;
        $obj->fat->vOrig = 16.09;
        $obj->fat->vLiq  = 16.09;

        // duplicatas
        $dup        = new \stdClass();
        $dup->nDup  = '1';
        $dup->dVenc = date("Y-m-d");
        $dup->vDup  = 16.09;
        $obj->dup[] = $dup;

        // troco
        $obj->pag->vTroco = 0;

        // pagamento
        $pag           = new SefazAutorizacaoDetPag();
        $pag->tPag     = '01'; // ?
        $pag->vPag     = 16.09;
        $pag->indPag   = 0; // /0= Pagamento à Vista 1= Pagamento à Prazo
        $obj->detPag[] = $pag;

        return $obj;
    }
}
?>