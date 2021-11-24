<?
namespace zion\sefaz;

/**
 * Objeto principal para autorizar uma nota fiscal eletronica
 */
class SefazAutorizacao {
    const MOD_CONHECIMENTO_AEREO            = '10';
    const MOD_CONHECIMENTO_FERROVIARIO      = '11';
    const MOD_CONHECIMENTO_FLUVIAL          = '09';
    const MOD_CONHECIMENTO_RODOVIARIO       = '08';
    const MOD_NF_MODELO_1_1A                = '01';
    const MOD_NF_ENERGIA_ELETRICA           = '06';
    const MOD_NF_ENTRADA                    = '03';
    const MOD_NF_SERVICO_DE_COMUNICACAO     = '21';
    const MOD_NF_SERVICO_DE_TELECOMUNICACAO = '22';
    const MOD_NF_SERVICO_DE_TRANSPORTES     = '07';
    const MOD_NF_CONSUMO                    = '02';
    const MOD_NF_PRODUCAO                   = '04';
    const MOD_NF_MODELO_55                  = '55';
    const MOD_CTE_MODELO_57                 = '57';
    const MOD_CTE_MODELO_67                 = '67';
    const MOD_NFE_PARA_ENERGIA              = '66';
    const MOD_CUPOM_FISCAL_ELETRONICO       = '65';

    const INDPAG_PAGAMENTO_A_VISTA           = 0;
    const INDPAG_PAGAMENTO_A_PRAZO           = 1;
    const INDPAG_OUTROS                      = 2;

    const TPNF_ENTRADA                       = 0;
    const TPNF_SAIDA                         = 1;

    const IDDEST_OPERACAO_INTERNA            = 1;
    const IDDEST_OPERACAO_INTERESTADUAL      = 2;

    const TPIMP_SEM_GERACAO_DANFE            = 0;
    const TPIMP_DANFE_NORMAL_RETRATO         = 1;
    const TPIMP_DANFE_NORMAL_PAISAGEM        = 2;
    const TPIMP_DANFE_SIMPLIFICADO           = 3;
    const TPIMP_DANFE_NFCE                   = 4;
    const TPIMP_DANFE_NFCE_EM_MSG_ELETRONICA = 5;

    const TPEMIS_EMISSAO_NORMAL      = 1;
    const TPEMIS_CONTINGENCIA_FS_IA  = 2;
    const TPEMIS_CONTINGENCIA_SCAN   = 3;
    const TPEMIS_CONTINGENCIA_DPEC   = 4;
    const TPEMIS_CONTINGENCIA_FS_DA  = 5;
    const TPEMIS_CONTINGENCIA_SVC_AN = 6;
    const TPEMIS_CONTINGENCIA_SVC_RS = 7;

    const TPAMB_PRODUCAO    = 1;
    const TPAMB_HOMOLOGACAO = 2;

    const FINNFE_NFE_NORMAL              = 1;
    const FINNFE_NFE_COMPLEMENTAR        = 2;
    const FINNFE_NFE_DE_AJUSTE           = 3;
    const FINNFE_DEVOLUCAO_DE_MERCADORIA = 4;

    const INDFINAL_CONSUMIDOR_NORMAL = 0;
    const INDFINAL_CONSUMIDOR_FINAL  = 1;

    const INDPRES_NAO_SE_APLICA                           = 0;
    const INDPRES_OPERACAO_PRESENCIAL                     = 1;
    const INDPRES_OPERACAO_NAO_PRESENCIAL_INTERNET        = 2;
    const INDPRES_OPERACAO_NAO_PRESENCIAL_TELEATENDIMENTO = 3;
    const INDPRES_NFCE_OPERACAO_ENTREGA_DOMICILIO         = 4;
    const INDPRES_OPERACAO_NAO_PRESENCIAL_OUTROS          = 9;

    const PROCEMI_EMISSAO_NFE_APLICATIVO_CONTRIBUINTE = 0;
    const PROCEMI_EMISSAO_NFE_AVULSA_PELO_FISCO       = 1;
    const PROCEMI_EMISSAO_NFE_AVULSA_PELO_CONTRIB     = 2;
    const PROCEMI_EMISSAO_NFE_PELO_CONTRIB_APP_FISCO  = 3;

    const CRT_SIMPLES_NACIONAL                             = 1;
    const CRT_SIMPLES_NACIONAL_EXCESSO_SUBLIMITE_REC_BRUTA = 2;
    const CRT_REGIME_NORMAL                                = 3;

    public $cUF;
    public $cNF;
    public $natOp;
    public $indPag;
    public $mod;
    public $serie;
    public $nNF;
    public $dhEmi;
    public $dhSaiEnt;
    public $tpNF;
    public $idDest;
    public $cMunFG;
    public $tpImp;
    public $tpEmis;
    public $cDV;
    public $tpAmb;
    public $finNFe;
    public $indFinal;
    public $indPres;
    public $procEmi;
    public $verProc;

    public $dest;
    public $detPag = [];
    public $dup = [];
    public $fat;
    public $pag;
    public $produto = [];
    public $transp;
    public $vol;

    public function __construct(){
        $this->dest   = new SefazAutorizacaoDest();
        $this->fat    = new SefazAutorizacaoFat();
        $this->pag    = new SefazAutorizacaoPag();
        $this->transp = new SefazAutorizacaoTransp();
        $this->vol    = new SefazAutorizacaoVol();
    }
}
?>