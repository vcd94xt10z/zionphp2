<?php
namespace zion\utils;

/**
 * @author Vinicius Cesar Dias
 */
class StringUtils {
    /**
     * Extrai os campos de uma string
     * @param string $key
     * @return array
     */
    public static function extractFieldsFromPattern(string $text,string $startDelimiter, string $endDelimiter) : array {
        $buffer = "";
        $output = [];
        $bufferOpen = false;
        $chars = str_split($text,1);
        
        foreach($chars AS $char){
            if($char == $startDelimiter){
                $buffer = "";
                $bufferOpen = true;
                continue;
            }
            
            if($char == $endDelimiter){
                $bufferOpen = false;
                $output[] = $buffer;
                $buffer = "";
                continue;
            }
            
            if($bufferOpen){
                $buffer .= $char;
            }
        }
        
        return $output;
    }
    
    /**
     * Retorna uma string randômica
     * @param int $length
     * @return string
     */
    public static function randomString(int $length){
        $characters = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $string = "";
        
        for ($p = 0; $p < $length; $p++) {
            $string .= $characters[random_int(0, strlen($characters))];
        }
        
        return $string;
    }
    
    /**
     * Converte um string oriunda de um textarea para array
     * @param string $content
     * @param string $type
     * @return array
     */
    public static function convertTextAreaLinesToArray(string $content, $type = "string") {
        if ($content != "") {
            $content = explode("\n", $content);
        } else {
            $content = array();
        }

        $output = array();
        foreach ($content AS $value) {
            if ($type == "int") {
                $value2 = intval(preg_replace("/[^0-9]/", "", $value));
                if($value2 > 0){
                    $output[] = $value2;
                }
            }elseif ($type == "numeric") {
                $value2 = preg_replace("/[^0-9]/", "", $value);
                if($value2 != ""){
                    $output[] = $value2;
                }
            } else {
                $value2 = preg_replace("/[^0-9a-zA-Z\#\-\_\s ]/", "", trim($value));
                if($value2 != ""){
                    $output[] = $value2;
                }
            }
        }
        return $output;
    }
    
    /**
     * Verifica se a string começa com
     * @param string $haystack
     * @param string $needle
     * @return boolean
     */
    public static function startsWith(string $haystack,string $needle) {
        $length = strlen($needle);
        return (substr($haystack, 0, $length) === $needle);
    }
    
    /**
     * Verifica se a string termina com
     * @param string $haystack
     * @param string $needle
     * @return boolean
     */
    public static function endsWith(string $haystack,string $needle) {
        $length = strlen($needle);
        if ($length == 0) {
            return true;
        }
        return (substr($haystack, -$length) === $needle);
    }
    
    /**
     * Remove os caracteres acentuados e coloca o seu equivalente sem acento
     * @param string $string
     * @return string
     */
    public static function acentRemove(string $string): string {
        $table = array(
            'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Æ' => 'A',
            'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E',
            'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I',
            'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ø' => 'O',
            'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U',
            'Ç' => 'C',
            'Ñ' => 'N',
            'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'a',
            'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
            'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
            'ð' => 'o', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ø' => 'o',
            'ù' => 'u', 'ú' => 'u', 'û' => 'u',
            'ç' => 'c',
            'ñ' => 'n'
        );

        return strtr($string, $table);
    }
}
?>