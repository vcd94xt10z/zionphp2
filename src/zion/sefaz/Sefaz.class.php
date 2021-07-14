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

    private $erros = [];
    
    public function __construct(SefazConfig $config){
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

        if($config->responsavelTecnico == null){
            throw new Exception("Dados do responsável tecnico ausente");
        }

        $this->config = $config;
    }

    public function validaAutoriza(SefazAutorizacao $obj){
        if($obj->dest == null){
            throw new Exception("Dados do destinatário vazio");
        }
    }

    public function autoriza(SefazAutorizacao $obj, $idLote){
        $this->validaAutoriza($obj);

        $now = new DateTime();

        $nfe = new \NFePHP\NFe\Make();

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
        $std->cMunFG   = $this->config->emit->endereco->cMun;
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
        $std->xNome = $this->config->emit->xNome;
        $std->CRT   = $this->config->emit->CRT;
        $nfe->tagemit($std);

        $std = new \stdClass();
        $std->xLgr    = $this->config->emit->endereco->xLgr;
        $std->nro     = $this->config->emit->endereco->nro;
        $std->xBairro = $this->config->emit->endereco->xBairro;
        $std->cMun    = $this->config->emit->endereco->cMun;
        $std->xMun    = $this->config->emit->endereco->xMun;
        $std->UF      = $this->config->emit->endereco->UF;
        $std->CEP     = $this->config->emit->endereco->CEP;
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
        $std->xLgr    = $obj->dest->endereco->xLgr;
        $std->nro     = $obj->dest->endereco->nro;
        $std->xBairro = $obj->dest->endereco->xBairro;
        $std->cMun    = $obj->dest->endereco->cMun;
        $std->xMun    = $obj->dest->endereco->xMun;
        $std->UF      = $obj->dest->endereco->UF;
        $std->CEP     = $obj->dest->endereco->CEP;
        $std->cPais   = '1058';
        $std->xPais   = 'BRASIL';
        $nfe->tagenderDest($std);

        $ICMSTot = new StdClass();
        $ICMSTot->vBC   = 0;
        $ICMSTot->vICMS = 0;
        $ICMSTot->vProd = 0;
        $ICMSTot->vNF   = 0;

        $itemId = 1;
        foreach($obj->produto AS $prod){
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
        $std->modFrete = $obj->transp->modFrete;
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
        foreach($obj->dup AS $dup){
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
        foreach($obj->detPag AS $pag){
            $std         = new \stdClass();
            $std->tPag   = '01'; // ?
            $std->vPag   = $pag->vPag;
            $std->indPag = $pag->indPag;
            $nfe->tagdetPag($std);
            $pagid++;
        }

        // responsável tecnico
        $std = new stdClass();
        $std->CNPJ     = $this->config->responsavelTecnico->CNPJ;
        $std->xContato = $this->config->responsavelTecnico->xContato;
        $std->email    = $this->config->responsavelTecnico->email;
        $std->fone     = $this->config->responsavelTecnico->fone;
        $std->CSRT     = $this->config->responsavelTecnico->CSRT;
        $std->idCSRT   = $this->config->responsavelTecnico->idCSRT;
        $nfe->taginfRespTec($std);

        try {
            $xml = $nfe->getXML();
        }catch(Exception $e){
            $this->erros = $nfe->getErrors();
            throw new Exception("Erro em gerar XML, verifique os erros");
        }
        
        $config = [
            "atualizacao" => $now->format("Y-m-d H:i:s"),
            "tpAmb"       => $obj->tpAmb,
            "razaosocial" => $this->config->emit->xNome,
            "siglaUF"     => $this->config->emit->endereco->UF,
            "cnpj"        => $this->config->emit->CNPJ,
            "schemes"     => "PL_008i2",
            "versao"      => "4.00",
            "tokenIBPT"   => "AAAAAAA",
            "CSC"         => $this->config->CSC,
            "CSCid"       => $this->config->CSCToken
        ];
        $configJson = json_encode($config);

        $certificadoDigital = $this->config->certificadoConteudo;
        if($this->config->certificadoArquivo != ""){
            $certificadoDigital = file_get_contents($this->config->certificadoArquivo);
        }

        $tools = new \NFePHP\NFe\Tools($configJson, \NFePHP\Common\Certificate::readPfx($certificadoDigital, $this->config->certificadoSenha));
        $tools->model($obj->mod); // modelo

        $output = new StdClass();
        $output->chave          = "";
        $output->xmlAssinado    = null;
        $output->retornoLote    = null;
        $output->protocolo      = null;
        $output->xmlProtocolado = null;

        $output->xmlAssinado = $tools->signNFe($xml);

        $resp = $tools->sefazEnviaLote([$output->xmlAssinado], $idLote);

        $st = new \NFePHP\NFe\Common\Standardize();
        $output->retornoLote = $st->toStd($resp);

        if ($output->retornoLote->cStat != 103) {
           return $output;
        }

        $recibo = $output->retornoLote->infRec->nRec;
        $protocolo = $tools->seFazConsultaRecibo($recibo);
        $output->protocolo = $st->toStd($protocolo);
        $output->chave = $output->protocolo->protNFe->infProt->chNFe;

        // juntando as tags de autorização ao XML assinado
        $req = $output->xmlAssinado;
        $res = $protocolo;

        try {
            $output->xmlProtocolado = \NFePHP\NFe\Complements::toAuthorize($req, $res);
        } catch (\Exception $e) {
            throw new Exception("Erro ao juntar protocolo ao XML: " . $e->getMessage());
        }

        return $output;
    }

    public function getErros(){
        return $this->erros;
    }
}
?>