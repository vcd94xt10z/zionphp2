<?php
namespace zion\sefaz;

use PDO;
use DateTime;
use Exception;
use StdClass;

use \zion\core\System;

/**
 * Links úteis
 * https://github.com/nfephp-org/sped-nfe/blob/master/docs/Make.md
 * https://imasters.com.br/back-end/emitindo-nfe-com-php
 * 
 * Dependencias
 * composer require nfephp-org/sped-nfe
 */
class Sefaz {
    // configurações para se comunicar com o Sefaz
    private $config = null;
    
    public function __construct(stdClass $config){
        if($config == null){
            throw new Exception("Configuração ausente");
        }

        if($config->CSC == null){
            throw new Exception("CSC ausente");
        }

        if($config->CSCToken == null){
            throw new Exception("CSC Token ausente");
        }

        if($config->certificadoArquivo == null OR $config->certificadoConteudo = ""){
            throw new Exception("Certificado A1 (PFX) inválido");
        }

        if($config->certificadoSenha == null){
            throw new Exception("Senha do certificado ausente");
        }

        if($config->emit == null){
            throw new Exception("Empresa ausente");
        }

        if($config->emit->cnpj == null){
            throw new Exception("CNPJ ausente");
        }

        if($config->emit->ie == null){
            throw new Exception("Inscrição estadual ausente");
        }

        if($config->emit->razaoSocial == null){
            throw new Exception("Razão social ausente");
        }

        if($config->emit->CRT == null){
            throw new Exception("CRT ausente");
        }

        if($config->emit->endereco == null){
            throw new Exception("Endereço ausente");
        }

        if($config->emit->endereco->rua == null){
            throw new Exception("Rua ausente");
        }

        if($config->emit->endereco->numero == null){
            throw new Exception("Número da rua ausente");
        }

        if($config->emit->endereco->bairro == null){
            throw new Exception("Bairro ausente");
        }

        if($config->emit->endereco->cidade == null){
            throw new Exception("Nome da cidade ausente");
        }

        if($config->emit->endereco->cidadeIBGE == null){
            throw new Exception("Código do IBGE da cidade ausente");
        }

        if($config->emit->endereco->uf == null){
            throw new Exception("UF ausente");
        }

        if($config->emit->endereco->cep == null){
            throw new Exception("CEP ausente");
        }

        if($config->responsavelTecnico == null){
            throw new Exception("Dados do responsável tecnico ausente");
        }

        if($config->responsavelTecnico->nome == null){
            throw new Exception("Nome do responsável tecnico ausente");
        }

        if($config->responsavelTecnico->cnpj == null){
            throw new Exception("CNPJ do responsável tecnico ausente");
        }

        if($config->responsavelTecnico->email == null){
            throw new Exception("E-mail do responsável tecnico ausente");
        }

        if($config->responsavelTecnico->telefone == null){
            throw new Exception("Telefone do responsável tecnico ausente");
        }

        if($config->responsavelTecnico->CSRT == null){
            throw new Exception("CSRT do responsável tecnico ausente");
        }

        if($config->responsavelTecnico->CSRTToken == null){
            throw new Exception("CSRT Token do responsável tecnico ausente");
        }

        $this->config = $config;
    }

    /**
     * Retorna um objeto que pode ser usado para teste em
     * ambiente de homologação
     *
     * @return void
     */
    public function getAutorizaObjTestNFCePF(){
        $obj = new SefazAutorizacao();

        // dados gerais
        $obj->cUF      = 41; // PR
        $obj->cNF      = rand(10000000,99999999); // Código Numérico que compõe a Chave de Acesso da NF-e
        $obj->natOp    = "VENDA";
        $obj->mod      = SefazAutorizacao::MOD_CUPOM_FISCAL_ELETRONICO;
        $obj->cNF      = 10001;   // Número da Nota fiscal
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
        $obj->dest       = new SefazAutorizacaoDest();
        $obj->dest->nome = "Cliente Teste";
        $obj->dest->CPF  = "";

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
        $prod->vUnTrib  = 16.90;
        $prod->indTot   = 1;

        $prod->vTotTrib = 16.09;

        // ICMS
        $prod->ICMS->CST   = '00';
        $prod->ICMS->modBC = 0;
        $prod->ICMS->vBC   = 16.09;
        $prod->ICMS->pICMS = 18;
        $prod->ICMS->vICMS = 3.04;

        $obj->produto[] = $prod;

        // frete
        $obj->transp->modFrete = SefazAutorizacaoTransp::MODFRETE_SEM_FRETE;

        // volumes
        $obj->vol->esp   = 'Caixa';
        $obj->vol->marca = '';
        $obj->vol->nVol  = 1;
        $obj->vol->pesoL = 1;
        $obj->vol->pesoB = 1;

        // dados da fatura
        $obj->fat->nFat  = 1;
        $obj->fat->vOrig = 16.09;
        $obj->fat->vLiq  = 16.09;

        // duplicatas
        $dup        = new \stdClass();
        $dup->nDup  = $dup->nDup;
        $dup->dVenc = $dup->dVenc;
        $dup->vDup  = $dup->vDup;
        $obj->dup[] = $dup;

        // troco
        $obj->pag->vTroco = 0;

        // pagamento
        $pag = new SefazAutorizacaoDetPag();
        $pag->tPag = '01'; // ?
        $pag->vPag = 16.09;
        $pag->indPag = 0; // /0= Pagamento à Vista 1= Pagamento à Prazo
        $obj->detPag[] = $pag;

        return $obj;
    }

    public function validaAutoriza(SefazAutorizacao $obj){
        if($obj->dest == null){
            throw new Exception("Dados do destinatário vazio");
        }
    }

    public function autoriza(SefazAutorizacao $obj, $idLote){
        $this->validaAutoriza($obj);

        $now = new DateTime();

        $nfe = new NFePHP\NFe\Make();

        $std = new \stdClass();
        $std->versao = '4.00';
        $nfe->taginfNFe($std);

        $std = new \stdClass();
        $std->cUF      = $obj->cUF;
        $std->cNF      = $obj->cNF;
        $std->natOp    = $obj->natOp;
        $std->indPag   = $obj->indPag;
        $std->mod      = $obj->mod;
        $std->serie    = $obj->serie;
        $std->nNF      = $obj->nNF;
        $std->dhEmi    = $now->format("Y-m-d")."T".$now->format("H:i:s").'-03:00';
        $std->dhSaiEnt = $now->format("Y-m-d")."T".$now->format("H:i:s").'-03:00';
        $std->tpNF     = $obj->tpNF;
        $std->idDest   = $obj->idDest;
        $std->cMunFG   = $this->config->emit->endereco->cidadeIBGE;
        $std->tpImp    = $obj->tpImp;
        $std->tpEmis   = $obj->tpEmis;
        $std->cDV      = 0; // Digito Verificador
        $std->tpAmb    = $obj->tpAmb;
        $std->finNFe   = $obj->finNFe;
        $std->indFinal = $obj->indFinal;
        $std->indPres  = $obj->indPres;
        $std->procEmi  = $obj->procEmi;
        $std->verProc  = 1; // versão do aplicativo emissor
        $nfe->tagide($std);

        $std = new \stdClass();
        $std->CNPJ  = $this->config->emit->CNPJ;
        $std->IE    = $this->config->emit->IE;
        $std->xNome = $this->config->emit->razaoSocial;
        $std->CRT   = $this->config->emit->CRT;
        $nfe->tagemit($std);

        $std = new \stdClass();
        $std->xLgr    = $this->config->emit->endereco->rua;
        $std->nro     = $this->config->emit->endereco->numero;
        $std->xBairro = $this->config->emit->endereco->bairro;
        $std->cMun    = $this->config->emit->endereco->cidadeIBGE;
        $std->xMun    = $this->config->emit->endereco->cidade;
        $std->UF      = $this->config->emit->endereco->uf;
        $std->CEP     = $this->config->emit->endereco->cep;
        $std->cPais   = '1058';
        $std->xPais   = 'BRASIL';
        $nfe->tagenderEmit($std);

        $std = new \stdClass();
        $std->xNome = $obj->dest->nome;
        //$std->indIEDest = 1;
        //$std->IE = '';
        //$std->CNPJ = '';
        $std->CPF = $obj->dest->CPF;
        $nfe->tagdest($std);

        $std = new \stdClass();
        $std->xLgr    = $obj->dest->endereco->rua;
        $std->nro     = $obj->dest->endereco->numero;
        $std->xBairro = $obj->dest->endereco->bairro;
        $std->cMun    = $obj->dest->endereco->cidadeIBGE;
        $std->xMun    = $obj->dest->endereco->cidade;
        $std->UF      = $obj->dest->endereco->uf;
        $std->CEP     = $obj->dest->endereco->cep;
        $std->cPais   = '1058';
        $std->xPais   = 'BRASIL';
        $nfe->tagenderDest($std);

        $ICMSTot = new StdClass();
        $ICMSTot->vBC   = 0;
        $ICMSTot->vICMS = 0;
        $ICMSTot->vProd = 0;
        $ICMSTot->vNF   = 0;

        $itemId = 1;
        foreach($obj->productList AS $prod){
            $std = new \stdClass();
            $std->item     = $itemId;
            $std->cProd    = $prod->cProd;
            $std->xProd    = $prod->xProd;
            $std->NCM      = $prod->NCM;
            $std->CFOP     = $prod->CFOP;
            $std->cEAN     = $prod->cEAN;
            $std->cEANTrib = $prod->cEANTrib;
            $std->uCom     = $prod->uCom;
            $std->qCom     = $prod->qCom;
            $std->vUnCom   = $prod->vUnCom;
            $std->vProd    = $prod->vProd;
            $std->uTrib    = $prod->uTrib;
            $std->qTrib    = $prod->qTrib;
            $std->vUnTrib  = $prod->vUnTrib;
            $std->indTot   = $prod->indTot;
            $nfe->tagprod($std);

            $std = new \stdClass();
            $std->item     = $itemId;
            $std->vTotTrib = $prod->vTotTrib;
            $nfe->tagimposto($std);

            if($prod->ICMS != null){
                $std        = new \stdClass();
                $std->item  = $itemId;
                $std->orig  = 0;
                $std->CST   = $prod->ICMS->CST;
                $std->modBC = 0;
                $std->vBC   = $prod->ICMS->vBC;
                $std->pICMS = $prod->ICMS->pICMS;
                $std->vICMS = $prod->ICMS->vICMS;
                $nfe->tagICMS($std);

                // totais 
                $ICMSTot->vBC   += $prod->ICMS->vBC;
                $ICMSTot->vICMS += $prod->ICMS->vICMS;
                $ICMSTot->vProd += $prod->vProd;
                $ICMSTot->vNF    = $ICMSTot->vProd;
            }

            /*
            $std = new \stdClass();
            $std->item = 1;
            $std->cEnq = '999';
            $std->CST = '50';
            $std->vIPI = 0;
            $std->vBC = 0;
            $std->pIPI = 0;
            $nfe->tagIPI($std);
            */

            /*
            $std = new \stdClass();
            $std->item = 1;
            $std->CST = '01';
            $std->vBC = 16.9;
            $std->pPIS = 10;
            $std->vPIS = 0.28;
            $nfe->tagPIS($std);
            */

            /*
            $std = new \stdClass();
            $std->item = 1;
            $std->CST = '01';
            $std->vCOFINS = 1.28;
            $std->vBC = 16.9;
            $std->pCOFINS = 10;
            $nfe->tagCOFINSST($std);
            */

            $itemId++;
        }

        $std = new \stdClass();
        $std->vBC        = $ICMSTot->vBC;
        $std->vICMS      = $ICMSTot->vICMS;
        $std->vICMSDeson = 0;
        $std->vBCST      = 0.00;
        $std->vST        = 0.00;
        $std->vProd      = $ICMSTot->vProd;
        $std->vFrete     = 0.00;
        $std->vSeg       = 0.00;
        $std->vDesc      = 0.00;
        $std->vII        = 0.00;
        $std->vIPI       = 0.00;
        $std->vPIS       = 0.00;
        $std->vCOFINS    = 0.00;
        $std->vOutro     = 0.00;
        $std->vNF        = $ICMSTot->vNF;
        $std->vTotTrib   = 0.00;
        $nfe->tagICMSTot($std);

        $std = new \stdClass();
        $std->modFrete = $obj->modFrete;
        $nfe->tagtransp($std);

        // volumes transportados
        $std = new \stdClass();
        $std->item  = 1; // id do volume
        $std->qVol  = 1;
        $std->esp   = $obj->vol->esp;
        $std->marca = $obj->vol->marca;
        $std->nVol  = $obj->vol->nVol;
        $std->pesoL = $obj->vol->pesoL;
        $std->pesoB = $obj->vol->pesoB;
        $nfe->tagvol($std);

        // dados da fatura
        $std = new \stdClass();
        $std->nFat  = $obj->fat->nFat;
        $std->vOrig = $obj->fat->vOrig;
        $std->vLiq  = $obj->fat->vLiq;
        $nfe->tagfat($std);

        // duplicatas
        foreach($obj->dupList AS $dup){
            $std        = new \stdClass();
            $std->nDup  = $dup->nDup;
            $std->dVenc = $dup->dVenc;
            $std->vDup  = $dup->vDup;
            $nfe->tagdup($std);
        }

        $std = new \stdClass();
        $std->vTroco = $obj->vTroco;
        $nfe->tagpag($std);

        // forma de pagamento
        $pagid = 1;
        foreach($obj->detPagList AS $pag){
            $std = new \stdClass();
            $std->tPag = '01'; // ?
            $std->vPag = $pag->vPag;
            $std->indPag = $pag->indPag;
            $nfe->tagdetPag($std);
            $pagid++;
        }

        // responsável tecnico
        $std = new stdClass();
        $std->CNPJ     = $this->config->responsavelTecnico->cnpj;
        $std->xContato = $this->config->responsavelTecnico->nome;
        $std->email    = $this->config->responsavelTecnico->email;
        $std->fone     = $this->config->responsavelTecnico->telefone;
        $std->CSRT     = $this->config->responsavelTecnico->CSRT;
        $std->idCSRT   = $this->config->responsavelTecnico->CSRTToken;
        $nfe->taginfRespTec($std);

        $xml = $nfe->getXML();

        $config = [
            "atualizacao" => $now->format("Y-m-d H:i:s"),
            "tpAmb"       => $obj->tpAmb,
            "razaosocial" => $this->config->emit->razaoSocial,
            "siglaUF"     => $this->config->emit->endereco->uf,
            "cnpj"        => $this->config->emit->cnpj,
            "schemes"     => "PL_008i2",
            "versao"      => "4.00",
            "tokenIBPT"   => "AAAAAAA",
            "CSC"         => $this->config->csc,
            "CSCid"       => $this->config->cscIdFull
        ];
        $configJson = json_encode($config);

        $certificadoDigital = $this->config->certificadoConteudo;
        if($this->config->certificadoArquivo != ""){
            $certificadoDigital = file_get_contents($this->config->certificadoArquivo);
        }

        $tools = new NFePHP\NFe\Tools($configJson, NFePHP\Common\Certificate::readPfx($certificadoDigital, $this->config->certificadoSenha));
        $tools->model($obj->mod); // modelo

        $output = new StdClass();
        $output->xmlAssinado = null;
        $output->retornoLote = null;

        $output->xmlAssinado = $tools->signNFe($xml);

        $resp = $tools->sefazEnviaLote([$output->xmlAssinado], $idLote);

        $st = new NFePHP\NFe\Common\Standardize();
        $output->retornoLote = $st->toStd($resp);

        return $output;
    }
}
?>