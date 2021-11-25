<?php
namespace zion\payment;

use Exception;
use StdClass;

/**
 * Classe criada apenas para facilitar o uso com base nos exemplos do link abaixo
 * https://github.com/renatomb/php_qrcode_pix
 * 
 * @author Vinicius
 * @since 10/06/21
 */
class PIX {
    public static function getObject(){
        $beneficiario         = new StdClass();
        $beneficiario->nome   = "";
        $beneficiario->cidade = "";
        
        $obj = new StdClass();
        $obj->chave         = "";
        $obj->valor         = 0.00;
        $obj->beneficiario  = $beneficiario;
        $obj->descricao     = "";
        $obj->identificador = "";
        return $obj;
    }
    
    public static function getBase64($pix){
        $file = tempnam(sys_get_temp_dir(), 'PHPPix');
        \QRCode::png($pix, $file,'M',5,1);
        $content = file_get_contents($file);
        if(file_exists($file)){
            @unlink($file);
        }
        return base64_encode($content);
    }
    
    /**
     * Gera o código PIX para copiar e colar no aplicativo ou internet banking.
     * Esse código pode ser usado para gerar o QRCode também 
     * @param StdClass $obj
     */
    public static function gerar(StdClass $obj){
        $px = [];
        $px[00]="01"; //Payload Format Indicator, Obrigatório, valor fixo: 01
        // Se o QR Code for para pagamento único (só puder ser utilizado uma vez), descomente a linha a seguir.
        //$px[01]="12"; //Se o valor 12 estiver presente, significa que o BR Code só pode ser utilizado uma vez.
        $px[26][00]="BR.GOV.BCB.PIX"; //Indica arranjo específico; “00” (GUI) obrigatório e valor fixo: br.gov.bcb.pix
        $px[26][01]=$obj->chave;
        if (!empty($obj->descricao)) {
            $tam_max_descr=99-(4+4+4+14+strlen($obj->chave));
            if (strlen($obj->descricao) > $tam_max_descr) {
                $descricao=substr($obj->descricao,0,$tam_max_descr);
            }
            $px[26][02]=$descricao;
        }
        $px[52]="0000"; //Merchant Category Code “0000” ou MCC ISO18245
        $px[53]="986"; //Moeda, “986” = BRL: real brasileiro - ISO4217
        $px[54]=$obj->valor;
        $px[58]="BR"; //“BR” – Código de país ISO3166-1 alpha 2
        $px[59]=$obj->beneficiario->nome; //Nome do beneficiário/recebedor. Máximo: 25 caracteres.
        $px[60]=$obj->beneficiario->cidade; //Nome cidade onde é efetuada a transação. Máximo 15 caracteres.
        $px[62][05]=$obj->identificador;
        $px[62][50][00]="BR.GOV.BCB.BRCODE"; //Payment system specific template - GUI
        $px[62][50][01]="1.0.0"; //Payment system specific template - versão
        $pix=self::montaPix($px);
        $pix.="6304"; //Adiciona o campo do CRC no fim da linha do pix.
        $pix.= self::crcChecksum($pix); //Calcula o checksum CRC16 e acrescenta ao final.
                
        return $pix;
    }
    
    /*
     # Biblioteca de funções para geração da linha do Pix copia e cola
     # cujo texto é utilizado para a geração do QRCode para recebimento
     # de pagamentos através do Pix do Banco Central.
     #
     #
     # Desenvolvido em 2020 por Renato Monteiro Batista - http://renato.ovh
     #
     # Este código pode ser copiado, modificado, redistribuído
     # inclusive comercialmente desde que mantida a refereência ao autor.
     */
    
    public static function montaPix($px){
        /*
         # Esta rotina monta o código do pix conforme o padrão EMV
         # Todas as linhas são compostas por [ID do campo][Tamanho do campo com dois dígitos][Conteúdo do campo]
         # Caso o campo possua filhos esta função age de maneira recursiva.
         #
         # Autor: Eng. Renato Monteiro Batista
         */
        $ret="";
        foreach ($px as $k => $v) {
            if (!is_array($v)) {
                if ($k == 54) { $v=number_format($v,2,'.',''); } // Formata o campo valor com 2 digitos.
                $ret.= self::c2($k).self::cpm($v).$v;
            }
            else {
                $conteudo=self::montaPix($v);
                $ret.= self::c2($k).self::cpm($conteudo).$conteudo;
            }
        }
        return $ret;
    }
    
    public static function cpm($tx){
        /*
         # Esta função auxiliar retorna a quantidade de caracteres do texto $tx com dois dígitos.
         #
         # Autor: Renato Monteiro Batista
         */
        if (strlen($tx) > 99) {
            throw new Exception("Tamanho máximo deve ser 99, inválido: $tx possui " . strlen($tx) . " caracteres.");
        }
        /*
         Não aprecio o uso de die no código, é um tanto deselegante pois envolve matar.
         Mas considerando que 99 realmente é o tamanho máximo aceitável, estou adotando-o.
         Mas aconselho que essa verificação seja feita em outras etapas do código.
         Caso não tenha entendido a problemática consulte  a página 4 do Manual de Padrões para Iniciação do Pix.
         Ou a issue 4 deste projeto: https://github.com/renatomb/php_qrcode_pix/issues/4
         */
        return self::c2(strlen($tx));
    }
    
    public static function c2($input){
        /*
         # Esta função auxiliar trata os casos onde o tamanho do campo for < 10 acrescentando o
         # dígito 0 a esquerda.
         #
         # Autor: Renato Monteiro Batista
         */
        return str_pad($input, 2, "0", STR_PAD_LEFT);
    }
    
    
    public static function crcChecksum($str) {
        /*
         # Esta função auxiliar calcula o CRC-16/CCITT-FALSE
         #
         # Autor: evilReiko (https://stackoverflow.com/users/134824/evilreiko)
         # Postada originalmente em: https://stackoverflow.com/questions/30035582/how-to-calculate-crc16-ccitt-in-php-hex
         */
        // The PHP version of the JS str.charCodeAt(i)
        function charCodeAt($str, $i) {
            return ord(substr($str, $i, 1));
        }
        
        $crc = 0xFFFF;
        $strlen = strlen($str);
        for($c = 0; $c < $strlen; $c++) {
            $crc ^= charCodeAt($str, $c) << 8;
            for($i = 0; $i < 8; $i++) {
                if($crc & 0x8000) {
                    $crc = ($crc << 1) ^ 0x1021;
                } else {
                    $crc = $crc << 1;
                }
            }
        }
        $hex = $crc & 0xFFFF;
        $hex = dechex($hex);
        $hex = strtoupper($hex);
        return $hex;
    }
}
?>