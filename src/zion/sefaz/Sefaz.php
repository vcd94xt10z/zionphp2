<?php
namespace zion\sefaz;

use PDO;
use DateTime;
use Exception;
use StdClass;

use \zion\core\System;

use NFePHP\NFe\Tools;
use NFePHP\Common\Certificate;
use NFePHP\NFe\Common\Standardize;
use NFePHP\NFe\Complements;
use NFePHP\Common\Soap\SoapCurl;
use NFePHP\Common\Soap\SoapFake;

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

    private $tpAmb;

    private $erros = [];
    
    public function __construct(SefazConfig $config){
        if($config == null){
            throw new Exception("Configuração ausente");
        }

        $envList = array("HOM","PRD");
        if(!in_array($config->ENV,$envList)){
            throw new Exception("Ambiente inválido, use ".implode(", ",$envList));
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

        if($config->certificadoArquivo != ""){
            $config->certificadoConteudo = file_get_contents($config->certificadoArquivo);
            if($config->certificadoConteudo == ""){
                throw new Exception("Certificado A1 (PFX) inválido");
            }
        }

        $this->tpAmb = 2; // HOM
        if($config->ENV == "PRD"){
            $this->tpAmb = 1; // PRD
        }

        $this->config = $config;
    }

    public function atorInteressado(stdClass $obj){
        $now = new DateTime();

        $config = [
            "atualizacao" => $now->format("Y-m-d H:i:s"),
            "tpAmb"       => $this->tpAmb,
            "razaosocial" => $this->config->emit->xNome,
            "siglaUF"     => $this->config->emit->endereco->UF,
            "cnpj"        => $this->config->emit->CNPJ,
            "schemes"     => "PL_009_V4",
            "versao"      => "4.00",
            "tokenIBPT"   => "AAAAAAA",
            "CSC"         => $this->config->CSC,
            "CSCid"       => $this->config->CSCToken,
            "proxyConf" => [
                "proxyIp" => "",
                "proxyPort" => "",
                "proxyUser" => "",
                "proxyPass" => ""
            ]
        ];
        
        $configJson = json_encode($config);
        
        //carrega o conteudo do certificado.
        $content = $this->config->certificadoConteudo;
        
        try {
            //intancia a classe tools
            $tools = new Tools($configJson, Certificate::readPfx($content, $this->config->certificadoSenha));
            //seta o modelo para 55
            $tools->model(self::getNFeModel($obj->chave));
            
            $soap = new SoapFake();
            $soap->disableCertValidation();
            $tools->loadSoapClass($soap);
        
            $std = new \stdClass();
            $std->chNFe = $obj->chave; //chave de 44 digitos da nota do fornecedor
            $std->tpAutor = $obj->autor; //1-emitente 2-destinatário 3-transportador indica quem está incluindo ou removendo atores
            $std->verAplic = '1.2.3'; //versão da aplicação que está gerando o evento
            $std->nSeqEvento = 1; //numero sequencial do evento, incrementar ao incluir outros ou remover
            $std->tpAutorizacao = 1; //0-não autorizo ou 1-autorizo
            $std->CNPJ = $obj->CNPJ;
            $std->CPF = $obj->CPF;
            
            $response = $tools->sefazAtorInteressado($std);
            
            $fake = \NFePHP\NFe\Common\FakePretty::prettyPrint($response);
            //header('Content-type: text/plain; charset=UTF-8');
            return $fake;
        } catch (\Exception $e) {
            throw new Exception("Erro ao registrar ator interessado: ".$e->getMessage());
        }
    }

    public function comprovanteEntrega(StdClass $obj){
        $now = new DateTime();

        $config = [
            "atualizacao" => $now->format("Y-m-d H:i:s"),
            "tpAmb"       => $this->tpAmb,
            "razaosocial" => $this->config->emit->xNome,
            "siglaUF"     => $this->config->emit->endereco->UF,
            "cnpj"        => $this->config->emit->CNPJ,
            "schemes"     => "PL_009_V4",
            "versao"      => "4.00",
            "tokenIBPT"   => "AAAAAAA",
            "CSC"         => $this->config->CSC,
            "CSCid"       => $this->config->CSCToken,
            "proxyConf" => [
                "proxyIp" => "",
                "proxyPort" => "",
                "proxyUser" => "",
                "proxyPass" => ""
            ]   
        ];
        
        //monta o config.json
        $configJson = json_encode($config);
        
        //carrega o conteudo do certificado.
        $content = $this->config->certificadoConteudo;
        
        try {
            //intancia a classe tools
            $tools = new Tools($configJson, Certificate::readPfx($content, $this->config->certificadoSenha));
            //seta o modelo para 55
            $tools->model(self::getNFeModel($obj->chave));
            
            $soap = new SoapFake();
            $soap->disableCertValidation();
            $tools->loadSoapClass($soap);
        
            $std = new \stdClass();
            $std->chNFe = $obj->chave; //chave de 44 digitos da nota do fornecedor
            $std->imagem = $obj->imagem; // aqui pode ser colocada uma imagem ou uma string que fará parte do hash 
            $std->nSeqEvento = 1;
            $std->verAplic = '1.2.3'; //versão da aplicação que está gerando o evento
            $std->data_recebimento = $now->format("Y-m-d")."T".$now->format("H:i:s").'-03:00'; //data de recebimento
            $std->documento_recebedor = $obj->documento_recebedor; //numero do documento do recebedor
            $std->nome_recebedor = $obj->nome_recebedor;
            $std->latitude = $obj->latitude;
            $std->longitude = $obj->longitude;
            $std->cancelar = false;
            
            $response = $tools->sefazComprovanteEntrega($std);
            
            $fake = \NFePHP\NFe\Common\FakePretty::prettyPrint($response);
            //header('Content-type: text/plain; charset=UTF-8');
            return $fake;
        } catch (\Exception $e) {
            throw new Exception("Erro ao gerar comprovante de entrega: ".$e->getMessage());
        }
    }

    public function status($nfeModel,$uf){
        $now = new DateTime();

        $config = [
            "atualizacao" => $now->format("Y-m-d H:i:s"),
            "tpAmb"       => $this->tpAmb,
            "razaosocial" => $this->config->emit->xNome,
            "siglaUF"     => $this->config->emit->endereco->UF,
            "cnpj"        => $this->config->emit->CNPJ,
            "schemes"     => "PL_009_V4",
            "versao"      => "4.00",
            "tokenIBPT"   => "AAAAAAA",
            "CSC"         => $this->config->CSC,
            "CSCid"       => $this->config->CSCToken,
            "proxyConf" => [
                "proxyIp" => "",
                "proxyPort" => "",
                "proxyUser" => "",
                "proxyPass" => ""
            ]   
        ];

        //monta o config.json
        $configJson = json_encode($config);
        
        //carrega o conteudo do certificado.
        $content = $this->config->certificadoConteudo;
        
        //intancia a classe tools
        $tools = new Tools($configJson, Certificate::readPfx($content, $this->config->certificadoSenha));
        $tools->model($nfeModel);
        
        //sempre que ativar a contingência pela primeira vez essa informação deverá ser 
        //gravada na base de dados ou em um arquivo para uso posterior, até que a mesma seja 
        //desativada pelo usuário, essa informação não é persistida automaticamente e depende 
        //de ser gravada pelo ERP
        //NOTA: esse retorno da função é um JSON
        $contingencia = $tools->contingency->activate('SP', 'Teste apenas');
        
        //e se necessário carregada novamente quando a classe for instanciada,
        //obtendo a string da contingência em json e passando para a classe
        //$tools->contingency->load($contingencia);
        
        //Se não for passada a sigla do estado, o status será obtido com o modo de
        //contingência, se este estiver ativo ou seja SVCRS ou SVCAN, usando a sigla 
        //contida no config.json
        $responseXML = $tools->sefazStatus();
        
        //Se for passada a sigla do estado, o status será buscado diretamente 
        //no autorizador indcado pela sigla do estado, dessa forma ignorando
        //a contingência
        //$response = $tools->sefazStatus('SP');
        
        $stdCl = new Standardize($responseXML);
        return $stdCl;
    }

    public function cancela($chave,$xJust,$nProt){
        // validações
        if($chave == ""){
            throw new Exception("Chave de acesso vazia");
        }

        if(strlen($chave) <> 44){
            throw new Exception("Chave de acesso deve ter 44 digitos");
        }

        $now = new DateTime();

        $config = [
            "atualizacao" => $now->format("Y-m-d H:i:s"),
            "tpAmb"       => $this->tpAmb,
            "razaosocial" => $this->config->emit->xNome,
            "siglaUF"     => $this->config->emit->endereco->UF,
            "cnpj"        => $this->config->emit->CNPJ,
            "schemes"     => "PL_009_V4",
            "versao"      => "4.00",
            "tokenIBPT"   => "AAAAAAA",
            "CSC"         => $this->config->CSC,
            "CSCid"       => $this->config->CSCToken,
            "proxyConf" => [
                "proxyIp" => "",
                "proxyPort" => "",
                "proxyUser" => "",
                "proxyPass" => ""
            ]   
        ];

        // monta o config.json
        $configJson = json_encode($config);
        
        //carrega o conteudo do certificado.
        $content = $this->config->certificadoConteudo;
        
        try {
            $tools = new Tools($configJson, Certificate::readPfx($content, $this->config->certificadoSenha));
            $tools->model(self::getNFeModel($chave));
            
            $response = $tools->sefazCancela($chave, $xJust, $nProt);
            
            //você pode padronizar os dados de retorno atraves da classe abaixo
            //de forma a facilitar a extração dos dados do XML
            //NOTA: mas lembre-se que esse XML muitas vezes será necessário, 
            //      quando houver a necessidade de protocolos
            $stdCl = new Standardize($response);
            //nesse caso $std irá conter uma representação em stdClass do XML retornado
            $std = $stdCl->toStd();
            //nesse caso o $arr irá conter uma representação em array do XML retornado
            $arr = $stdCl->toArray();
            //nesse caso o $json irá conter uma representação em JSON do XML retornado
            $json = $stdCl->toJson();
            
            //verifique se o evento foi processado
            if ($std->cStat != 128) {
                //houve alguma falha e o evento não foi processado
                throw new Exception("Erro ao cancelar, código Sefaz ".$std->cStat);
            } else {
                $cStat = $std->retEvento->infEvento->cStat;
                if ($cStat == '101' || $cStat == '135' || $cStat == '155' ) {
                    //SUCESSO PROTOCOLAR A SOLICITAÇÂO ANTES DE GUARDAR
                    $xml = Complements::toAuthorize($tools->lastRequest, $response);
                    //grave o XML protocolado e prossiga com outras tarefas de seu aplicativo
                } else {
                    //houve alguma falha no evento 
                    throw new Exception("Erro ao cancelar, código Sefaz ".$std->cStat);
                }
            }    
        } catch (\Exception $e) {
            throw new Exception("Erro ao cancelar: ".$e->getMessage());
        }
    }

    public static function getNFeModel($chave){
        $chave = preg_replace("[^0-9]","",$chave);
        if(strlen($chave) <> '44'){
            return;
        }

        return substr($chave,20,2);
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