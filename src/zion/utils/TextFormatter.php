<?php
namespace zion\utils;

use zion\core\System;

/**
 * Formatador de Textos
 * @author Vinicius Cesar Dias
 * @since 31/01/2019
 */
class TextFormatter {
    /**
     * Corta as casas decimais, não arredonda
     * @param float $value
     * @param int $decimalPlaces
     * @return float
     */
    public static function cutDecimal(float $value, int $decimalPlaces = 2) : float {
        return floatval(number_format($value,$decimalPlaces,".",""));
    }
    
    public static function maskCardNumber($number){
        $parts = str_split($number,4);
        $parts[2] = "####";
        return implode("",$parts);
    }
    
    public static function maskEmail($email){
        $mail_parts = explode("@", $email);
        $length = strlen($mail_parts[0]);
        $show = floor($length/2);
        $hide = $length - $show;
        $replace = str_repeat("*", $hide);
        return substr_replace ( $mail_parts[0] , $replace , $show, $hide ) . "@" . substr_replace($mail_parts[1], "**", 0, 2);
    }
    
    public static function commaValuesToArray($values){
        if(strpos($values,",") !== false){
            $values = explode(",",$values);
        }elseif($values != ""){
            $values = array($values);
        }else{
            $values = array();
        }
        return $values;
    }
    
    public static function getTypeOf($var){
        $type = gettype($var);
        
        switch($type){
            case "boolean":
                $type = "bool";
                break;
            case "integer":
                $type = "bool";
                break;
            case "double":
            case "float":
                $type = "double";
                break;
            case "object":
                $type = "object";
                break;
            default:
                $type = "string";
                break;
        }
        
        if($type == "object"){
            $type = get_class($var);
        }
        
        return strtolower($type);
    }
    
    public static function format($type,$value){
        $output = "";
        if($value === null){
            return $output;
        }
        
        if($type == "autodetect"){
            $type = gettype($type);
        }
        
        switch(strtolower($type)){
            case "int":
                $output = preg_replace("/[^0-9]/","",$value);
                break;
            case "float":
            case "double":
                $output = preg_replace("/[^0-9\.]/","",$value);
                break;
            case "time":
                $output = self::parseTime($value); // usa o mesmo método
                break;
            case "date":
                if($value instanceof \DateTime){
                    $output = $value->format(System::get("dateFormat"));
                }
                break;
            case "datetime":
                if($value instanceof \DateTime){
                    $output = $value->format(System::get("dateTimeFormat"));
                }
                break;
            case "datetime2":
                if($value instanceof \DateTime){
                    $output = $value->format(System::get("dateTime2Format"));
                }
                break;
            case "file":
                $output = trim($value);
                break;
            case "currency":
                $output = number_format($value,System::get("currencyDecimalPlaces"),
                System::get("currencyDecimalSep"),System::get("currencyThousandSep"));
                break;
            case "phone":
                $output = self::formatPhone($value);
                break;
            case "ipv4":
                $output = $value; // fazer...
                break;
            case "br-cpf":
                $output = self::formatCPF($value);
                break;
            case "br-cnpj":
                $output = self::formatCNPJ($value);
                break;
            case "br-cep":
                $output = self::formatCEP($value);
                break;
            case "answer":
                $output = ($value == "S" || $value == 1)?"Sim":"Não";
                break;
            case "status":
                $output = ($value == "A")?"Ativo":"Inativo";
                break;
            default:
                $output = $value;
                break;
        }
        return $output;
    }
    
    /**
     * 
     * @param string $text {A = Alpha, N = Numeric, S = Special}
     * @param string $type 
     */
    public static function filterString($text,$type,$maxLength=0){
        $output = "";
        
        $alpha   = "\p{L}\.\s\_\- ";
        $number  = "\p{N}";
        $special = "\@\*\#\$\_\-\%\!\¨\&\(\)\+\,\/\:";
        
        // texto, numero e especial
        if($type == "ANS"){
            $output = preg_replace("/[^".$alpha.$number.$special."]/u","", $text);
        }
        
        // texto e numero
        if($type == "AN"){
            $output = preg_replace("/[^".$alpha.$number."]/u","", $text);
        }
        
        // texto e especial
        if($type == "AS"){
            $output = preg_replace("/[^".$alpha.$special."]/u","", $text);
        }
        
        // numero e especial
        if($type == "NS"){
            $output = preg_replace("/[^".$number.$special."]/u","", $text);
        }
        
        // texto
        if($type == "A"){
            $output = preg_replace("/[^".$alpha."]/u","", $text);
        }
        
        // numero
        if($type == "N"){
            $output = preg_replace("/[^".$number."]/u","", $text);
        }
        
        // especial
        if($type == "S"){
            $output = preg_replace("/[^".$special."]/u","", $text);
        }
        
        // limitador
        if($maxLength > 0){
            if(strlen($output) > $maxLength){
                $output = substr($output,0,$maxLength);
            }
        }
        
        return $output; 
    }
    
    /**
     * Remove caracteres inválidos da site padrão do site
     * @param string $password
     * @return string
     */
    public static function filterPassword($password) : string {
        $regex = "/[^a-zA-Z0-9\s\@\*\#\_\-\.]/";
        return preg_replace($regex, "", $password);
    }
    
    /**
     * Remove caracteres inválidos do e-mail
     * @param string $email
     * @return string
     */
    public static function filterEmail($email) : string {
        $the_count = 0;
        return preg_replace("/[^a-zA-Z0-9\/:@\.\+-s]/", "", $email, -1, $the_count);
    }
    
    public static function parse(string $type,$value,$emptyNull=false){
        if($emptyNull AND $value == ""){
            $value = null;
        }
        
        if($value === null){
            return null;
        }
        
        $output = null;
        switch(strtolower($type)){
            case "int":
            case "integer":
                $temp = preg_replace("/[^0-9]/","",$value);
                $output = intval($temp);
                break;
            case "float":
            case "double":
                $temp = preg_replace("/[^0-9\.\-]/","",$value);
                $output = doubleval($temp);
                break;
            case "boolean":
                $output = ($value)?true:false;
                break;
            case "time":
                $output = self::parseTime($value);
                break;
            case "date":
            case "datetime":
                $output = self::parseDate($value);
                break;
            case "currency":
                $output = self::parseCurrency($value);
                break;
            case "phone":
                $output = preg_replace("/[^0-9]/","",$value);
                break;
            case "br-cpf":
            case "br-cnpj":
            case "br-cep":
                $output = preg_replace("/[^0-9]/","",$value);
                break;
            default:
                $output = (string)$value;
                break;
        }
        
        return $output;
    }
    
    /**
     * Formata um telefone
     */
    public static function formatPhone($phone,$nineDigits=false,$noddd=false){
        $phone = preg_replace("/[^0-9]/",'',$phone);
        $length = mb_strlen($phone);
        $output = $phone;
        
        // xxxx-xxxx
        if($length == 8){
            $tmp = str_split($phone,4);
            $output = $tmp[0]."-".$tmp[1];
        }
        
        // (dd)xxxx-xxxx
        if($length == 10){
            $tmp = str_split($phone,2);
            
            $output = "";
            if (!$noddd) {
                $output .= "(".$tmp[0].") ";
            }
            $output .= $tmp[1].$tmp[2]."-".$tmp[3].$tmp[4];
        }
        
        // (Xdd)xxxx-xxxx ou xxxx xxx xx xx
        if($length == 11){
            $output = "";
            if (mb_strpos($phone,"0800") === 0) {
                $output .= mb_substr($phone,0,4)." ".mb_substr($phone,4,3)." ".mb_substr($phone,7,4);
            } elseif ($nineDigits) {
                if (!$noddd) {
                    $output .= "(".mb_substr($phone,0,2).") ";
                }
                $output .= mb_substr($phone,2,5)."-".mb_substr($phone,7,4);
            } else {
                if (!$noddd) {
                    $output .= "(".mb_substr($phone,0,3).") ";
                }
                $output .= mb_substr($phone,3,4)."-".mb_substr($phone,8,4);
            }
        }
        
        // 55(dd)xxxx-xxxx
        if($length == 12){
            $output = "+".mb_substr($phone,0,2)." (".mb_substr($phone,2,2).") ".mb_substr($phone,4,4)."-".mb_substr($phone,8,4);
        }
        
        return $output;
    }
    
    public static function formatCPFCNPJ($num){
        if(strlen($num) == 11){
            return self::formatCPF($num);
        }
        return self::formatCNPJ($num);
    }
    
    public static function formatCPF($num){
        $num = preg_replace("/[^0-9]/",'',$num);
        $num = self::cutfill($num,11,"0");
        
        // xxx.xxx.xxx-xx
        return mb_substr($num,0,3).".".mb_substr($num,3,3).".".mb_substr($num,6,3)."-".mb_substr($num,9,2);
    }
    
    public static function formatCEP($num){
        $num = preg_replace("/[^0-9]/",'',$num);
        $num = self::cutfill($num,8,"0");
        
        // xxxxx-xxx
        return mb_substr($num,0,5)."-".mb_substr($num,5,3);
    }
    
    public static function formatCNPJ($num){
        $num = preg_replace("/[^0-9]/",'',$num);
        $num = self::cutfill($num,14,"0");
        
        // xx.xxx.xxx/xxxx-xx
        return mb_substr($num,0,2).".".mb_substr($num,2,3).".".mb_substr($num,5,3)."/".mb_substr($num,8,4)."-".mb_substr($num,12,2);
    }
    
    public static function formatDate($obj,$format=null){
        if($obj == null){
            return "";
        }
        if($format == null){
            $format = System::get("dateFormat");
        }
        
        return $obj->format($format);
    }
    
    public static function formatDateTime($obj,$format=null){
        if($obj == null){
            return "";
        }
        if($format == null){
            $format = System::get("dateTimeFormat");
        }
        return $obj->format($format);
    }
    
    public static function formatCurrency($value){
        return number_format($value,System::get("currencyDecimalPlaces"),System::get("currencyDecimalSep"),System::get("currencyThousandSep"));
    }
    
    public static function parseCurrency($value,$decimalSep=",",$thousandSep="."){
        if(is_float($value)){
            return $value;
        }
        
        // removendo caracteres que não fazem parte do preço
        $regexp = "/[^0-9\\".$decimalSep."\\".$thousandSep."\-]/";
        $value = preg_replace($regexp,"",trim($value));
        if(mb_strlen($value) <= 0){
            return 0.0;
        }
        
        // deixando apenas o separador da parte decimal
        $value = str_replace($thousandSep,"",$value);
        
        // não tem parte decimal
        if(mb_strpos($value,$decimalSep) === false){
            return floatval($value);
        }
        
        $parts = explode($decimalSep,$value);
        $intPart = $parts[0];
        $decimalPart = $parts[1];
        
        return floatval($intPart.".".$decimalPart);
    }
    
    public static function parseTime($time,$format=null){
        // formato
        if($format == null){
            $format = System::get("timeFormat");
        }
        
        $originalFormat = $format;
        
        // deixando somente numeros
        $time = preg_replace("/[^0-9]/","",$time);
        $format = preg_replace("/[^a-zA-Z]/","",$format);
        
        // extraindo valores
        $data = array(
            'H' => 0,
            'i' => 0,
            's' => 0
        );
        
        $index = 0;
        $length = 2;
        $parts = str_split($format);
        
        foreach($parts AS $char){
            $data[$char] = intval(mb_substr($time,$index,$length));
            $index += $length;
        }
        
        // padding
        $data['H'] = str_pad($data['H'],2,"0",STR_PAD_LEFT);
        $data['i'] = str_pad($data['i'],2,"0",STR_PAD_LEFT);
        $data['s'] = str_pad($data['s'],2,"0",STR_PAD_LEFT);
        
        // formatando
        $output = $originalFormat;
        $output = str_replace("H",$data["H"],$output);
        $output = str_replace("i",$data["i"],$output);
        $output = str_replace("s",$data["s"],$output);
        return $output;
    }
    
    /**
     * Interpreta uma data String já formatada para um objeto DateTime,
     * esse método serve tanto para data como data e hora
     */
    public static function parseDate($date,$format=null){
        // já é uma data
        if($date instanceof \DateTime){
            return $date;
        }
        
        // formato
        if($format == null){
            $format = System::get("dateTimeFormat");
        }
        
        // retirando separadores do formato para extrair somente
        // os caracteres que representam os dados no formato
        $format = preg_replace("/[^a-zA-Z0-9]/","",$format);
        
        // os valores da data só pode ser numeros
        $date = preg_replace("/[^0-9]/","",$date);
        
        // validando formato e valor
        if($format == "" || $date == ""){
            return null;
        }
        
        // extraindo valores
        $data = array(
            'd' => date('d'),
            'm' => date('m'),
            'Y' => date('Y'),
            'H' => date('H'),
            'i' => date('i'),
            's' => date('s')
        );
        
        $index = 0;
        $length = 2;
        $parts = str_split($format);
        foreach($parts AS $char){
            switch($char){
                case 'd':
                case 'm':
                case 'H':
                case 'i':
                case 's':
                    $length = 2;
                    break;
                case 'Y':
                    $length = 4;
                    break;
            }
            $data[$char] = intval(mb_substr($date,$index,$length));
            $index += $length;
        }
        
        // padding
        $data['d'] = str_pad($data['d'],2,"0",STR_PAD_LEFT);
        $data['m'] = str_pad($data['m'],2,"0",STR_PAD_LEFT);
        
        // criando objeto
        try {
            return new \DateTime($data['Y'].'-'.$data['m'].'-'.$data['d'].' '.$data['H'].':'.$data['i'].':'.$data['s']);
        }catch(\Exception $e){
        }
        return null;
    }
    
    /**
     * Encurta um texto
     */
    public static function shortText($text, $maxLength=20,$mode="center"){
        mb_internal_encoding("UTF-8");
        $real_length = mb_strlen($text);
        if($real_length <= $maxLength){
            return $text;
        }
        
        if($mode == "center"){
            $keep = round( $maxLength / 2 ) - 1;
            return mb_substr($text, 0, $keep) . "[…]" . mb_substr($text, -$keep);
        }
        
        // end
        return mb_substr($text,0,$maxLength)."...";
    }
    
    /**
     * Corta e preenche uma string
     */
    public static function cutfill($data,$maxLength,$padChar){
        $length = mb_strlen($data);
        if($length > $maxLength){
            $data = mb_substr($data,0,$maxLength);
        }else if($length < $maxLength){
            $data = str_pad($data,$maxLength,$padChar,STR_PAD_RIGHT);
        }
        return $data;
    }
    
    public static function removeAccents($string){
        if ( !preg_match('/[\x80-\xff]/', $string) )
            return $string;
            
        $chars = [
            // Decompositions for Latin-1 Supplement
            chr(195).chr(128) => 'A', chr(195).chr(129) => 'A',
            chr(195).chr(130) => 'A', chr(195).chr(131) => 'A',
            chr(195).chr(132) => 'A', chr(195).chr(133) => 'A',
            chr(195).chr(135) => 'C', chr(195).chr(136) => 'E',
            chr(195).chr(137) => 'E', chr(195).chr(138) => 'E',
            chr(195).chr(139) => 'E', chr(195).chr(140) => 'I',
            chr(195).chr(141) => 'I', chr(195).chr(142) => 'I',
            chr(195).chr(143) => 'I', chr(195).chr(145) => 'N',
            chr(195).chr(146) => 'O', chr(195).chr(147) => 'O',
            chr(195).chr(148) => 'O', chr(195).chr(149) => 'O',
            chr(195).chr(150) => 'O', chr(195).chr(153) => 'U',
            chr(195).chr(154) => 'U', chr(195).chr(155) => 'U',
            chr(195).chr(156) => 'U', chr(195).chr(157) => 'Y',
            chr(195).chr(159) => 's', chr(195).chr(160) => 'a',
            chr(195).chr(161) => 'a', chr(195).chr(162) => 'a',
            chr(195).chr(163) => 'a', chr(195).chr(164) => 'a',
            chr(195).chr(165) => 'a', chr(195).chr(167) => 'c',
            chr(195).chr(168) => 'e', chr(195).chr(169) => 'e',
            chr(195).chr(170) => 'e', chr(195).chr(171) => 'e',
            chr(195).chr(172) => 'i', chr(195).chr(173) => 'i',
            chr(195).chr(174) => 'i', chr(195).chr(175) => 'i',
            chr(195).chr(177) => 'n', chr(195).chr(178) => 'o',
            chr(195).chr(179) => 'o', chr(195).chr(180) => 'o',
            chr(195).chr(181) => 'o', chr(195).chr(182) => 'o',
            chr(195).chr(182) => 'o', chr(195).chr(185) => 'u',
            chr(195).chr(186) => 'u', chr(195).chr(187) => 'u',
            chr(195).chr(188) => 'u', chr(195).chr(189) => 'y',
            chr(195).chr(191) => 'y',
            // Decompositions for Latin Extended-A
            chr(196).chr(128) => 'A', chr(196).chr(129) => 'a',
            chr(196).chr(130) => 'A', chr(196).chr(131) => 'a',
            chr(196).chr(132) => 'A', chr(196).chr(133) => 'a',
            chr(196).chr(134) => 'C', chr(196).chr(135) => 'c',
            chr(196).chr(136) => 'C', chr(196).chr(137) => 'c',
            chr(196).chr(138) => 'C', chr(196).chr(139) => 'c',
            chr(196).chr(140) => 'C', chr(196).chr(141) => 'c',
            chr(196).chr(142) => 'D', chr(196).chr(143) => 'd',
            chr(196).chr(144) => 'D', chr(196).chr(145) => 'd',
            chr(196).chr(146) => 'E', chr(196).chr(147) => 'e',
            chr(196).chr(148) => 'E', chr(196).chr(149) => 'e',
            chr(196).chr(150) => 'E', chr(196).chr(151) => 'e',
            chr(196).chr(152) => 'E', chr(196).chr(153) => 'e',
            chr(196).chr(154) => 'E', chr(196).chr(155) => 'e',
            chr(196).chr(156) => 'G', chr(196).chr(157) => 'g',
            chr(196).chr(158) => 'G', chr(196).chr(159) => 'g',
            chr(196).chr(160) => 'G', chr(196).chr(161) => 'g',
            chr(196).chr(162) => 'G', chr(196).chr(163) => 'g',
            chr(196).chr(164) => 'H', chr(196).chr(165) => 'h',
            chr(196).chr(166) => 'H', chr(196).chr(167) => 'h',
            chr(196).chr(168) => 'I', chr(196).chr(169) => 'i',
            chr(196).chr(170) => 'I', chr(196).chr(171) => 'i',
            chr(196).chr(172) => 'I', chr(196).chr(173) => 'i',
            chr(196).chr(174) => 'I', chr(196).chr(175) => 'i',
            chr(196).chr(176) => 'I', chr(196).chr(177) => 'i',
            chr(196).chr(178) => 'IJ',chr(196).chr(179) => 'ij',
            chr(196).chr(180) => 'J', chr(196).chr(181) => 'j',
            chr(196).chr(182) => 'K', chr(196).chr(183) => 'k',
            chr(196).chr(184) => 'k', chr(196).chr(185) => 'L',
            chr(196).chr(186) => 'l', chr(196).chr(187) => 'L',
            chr(196).chr(188) => 'l', chr(196).chr(189) => 'L',
            chr(196).chr(190) => 'l', chr(196).chr(191) => 'L',
            chr(197).chr(128) => 'l', chr(197).chr(129) => 'L',
            chr(197).chr(130) => 'l', chr(197).chr(131) => 'N',
            chr(197).chr(132) => 'n', chr(197).chr(133) => 'N',
            chr(197).chr(134) => 'n', chr(197).chr(135) => 'N',
            chr(197).chr(136) => 'n', chr(197).chr(137) => 'N',
            chr(197).chr(138) => 'n', chr(197).chr(139) => 'N',
            chr(197).chr(140) => 'O', chr(197).chr(141) => 'o',
            chr(197).chr(142) => 'O', chr(197).chr(143) => 'o',
            chr(197).chr(144) => 'O', chr(197).chr(145) => 'o',
            chr(197).chr(146) => 'OE',chr(197).chr(147) => 'oe',
            chr(197).chr(148) => 'R',chr(197).chr(149) => 'r',
            chr(197).chr(150) => 'R',chr(197).chr(151) => 'r',
            chr(197).chr(152) => 'R',chr(197).chr(153) => 'r',
            chr(197).chr(154) => 'S',chr(197).chr(155) => 's',
            chr(197).chr(156) => 'S',chr(197).chr(157) => 's',
            chr(197).chr(158) => 'S',chr(197).chr(159) => 's',
            chr(197).chr(160) => 'S', chr(197).chr(161) => 's',
            chr(197).chr(162) => 'T', chr(197).chr(163) => 't',
            chr(197).chr(164) => 'T', chr(197).chr(165) => 't',
            chr(197).chr(166) => 'T', chr(197).chr(167) => 't',
            chr(197).chr(168) => 'U', chr(197).chr(169) => 'u',
            chr(197).chr(170) => 'U', chr(197).chr(171) => 'u',
            chr(197).chr(172) => 'U', chr(197).chr(173) => 'u',
            chr(197).chr(174) => 'U', chr(197).chr(175) => 'u',
            chr(197).chr(176) => 'U', chr(197).chr(177) => 'u',
            chr(197).chr(178) => 'U', chr(197).chr(179) => 'u',
            chr(197).chr(180) => 'W', chr(197).chr(181) => 'w',
            chr(197).chr(182) => 'Y', chr(197).chr(183) => 'y',
            chr(197).chr(184) => 'Y', chr(197).chr(185) => 'Z',
            chr(197).chr(186) => 'z', chr(197).chr(187) => 'Z',
            chr(197).chr(188) => 'z', chr(197).chr(189) => 'Z',
            chr(197).chr(190) => 'z', chr(197).chr(191) => 's'
        ];
        
        $string = strtr($string, $chars);
        
        return $string;
    }
}
?>