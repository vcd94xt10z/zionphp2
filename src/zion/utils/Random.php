<?php 
namespace zion\utils;

use Exception;

/**
 * @author Vinicius Cesar Dias
 */
class Random {
    public static function strongPassword($length=8,$lettersMin=2,$lettersMai=2,$numbers=2,$specials=2){
        $password     = array();
        $numberAcc    = 0;
        $letterMinAcc = 0;
        $letterMaiAcc = 0;
        $specialAcc   = 0;
        $specialList  = str_split("!@#$%&*()",1);
        
        $total = $lettersMin + $lettersMai + $numbers + $specials;
        if($total <> $length){
            throw new Exception("O comprimento da senha deve ser igual a soma das combinações");
        }
        
        for($i=0;$i<$length;$i++){
            $randNumber    = chr(random_int(48,57));  // numeros
            $randLetterMin = chr(random_int(97,122)); // letras minusculas
            $randLetterMai = chr(random_int(65,90));  // letras maiusculas
            $randSpecial   = $specialList[array_rand($specialList)]; // especiais
            
            if($numberAcc <= $numbers){
                $password[] = $randNumber;
                $numberAcc++;
            }
            
            if($letterMinAcc <= $lettersMin){
                $password[] = $randLetterMin;
                $letterMinAcc++;
            }
            
            if($letterMaiAcc <= $lettersMai){
                $password[] = $randLetterMai;
                $letterMaiAcc++;
            }
            
            if($specialAcc <= $specials){
                $password[] = $randSpecial;
                $specialAcc++;
            }
            
            if(sizeof($password) == $length){
                break;
            }
        }
        
        shuffle($password);
        
        return implode("",$password);
    }
    
    /**
     * Generate a random string, using a cryptographically secure
     * pseudorandom number generator (random_int)
     *
     * For PHP 7, random_int is a PHP core function
     * For PHP 5.x, depends on https://github.com/paragonie/random_compat
     *
     * @link https://stackoverflow.com/questions/6101956/generating-a-random-password-in-php
     * @param int $length      How many characters do we want?
     * @param string $keyspace A string of all possible characters
     *                         to select from
     * @return string
     */
    public static function password1($length,$keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ') {
        $str = '';
        $max = mb_strlen($keyspace, '8bit') - 1;
        if ($max < 1) {
            throw new Exception('$keyspace must be at least two characters long');
        }
        
        for ($i = 0; $i < $length; ++$i) {
            $str .= $keyspace[random_int(0, $max)];
        }
        return $str;
    }
    
    public static function phone(){
    }
    
    public static function email(){
        $a = "";
        
        // array of possible top-level domains
        $tlds = array("com", "net", "gov", "org", "edu", "biz", "info");
        
        // string of possible characters
        $char = "0123456789abcdefghijklmnopqrstuvwxyz";
            
        // choose random lengths for the username ($ulen) and the domain ($dlen)
        $ulen = random_int(5, 10);
        $dlen = random_int(7, 17);
        
        // get $ulen random entries from the list of possible characters
        // these make up the username (to the left of the @)
        for ($i = 1; $i <= $ulen; $i++) {
            $a .= substr($char, random_int(0, strlen($char)), 1);
        }
        
        // wouldn't work so well without this
        $a .= "@";
        
        // now get $dlen entries from the list of possible characters
        // this is the domain name (to the right of the @, excluding the tld)
        for ($i = 1; $i <= $dlen; $i++) {
            $a .= substr($char, random_int(0, strlen($char)), 1);
        }
        
        // need a dot to separate the domain from the tld
        $a .= ".";
        
        // finally, pick a random top-level domain and stick it on the end
        $a .= $tlds[random_int(0, (sizeof($tlds)-1))];
        
        return $a;
    }
    
    /**
     * @see https://gist.github.com/acfreitas/fb7465c33156ec144513 
     */
    public static function cnpj($mascara = true) {
        $n1 = random_int(0, 9);
        $n2 = random_int(0, 9);
        $n3 = random_int(0, 9);
        $n4 = random_int(0, 9);
        $n5 = random_int(0, 9);
        $n6 = random_int(0, 9);
        $n7 = random_int(0, 9);
        $n8 = random_int(0, 9);
        $n9 = 0;
        $n10 = 0;
        $n11 = 0;
        $n12 = 1;
        $d1 = $n12 * 2 + $n11 * 3 + $n10 * 4 + $n9 * 5 + $n8 * 6 + $n7 * 7 + $n6 * 8 + $n5 * 9 + $n4 * 2 + $n3 * 3 + $n2 * 4 + $n1 * 5;
        $d1 = 11 - (self::mod($d1, 11) );
        if ($d1 >= 10) {
            $d1 = 0;
        }
        $d2 = $d1 * 2 + $n12 * 3 + $n11 * 4 + $n10 * 5 + $n9 * 6 + $n8 * 7 + $n7 * 8 + $n6 * 9 + $n5 * 2 + $n4 * 3 + $n3 * 4 + $n2 * 5 + $n1 * 6;
        $d2 = 11 - (self::mod($d2, 11) );
        if ($d2 >= 10) {
            $d2 = 0;
        }
        $retorno = '';
        if ($mascara == 1) {
            $retorno = '' . $n1 . $n2 . "." . $n3 . $n4 . $n5 . "." . $n6 . $n7 . $n8 . "/" . $n9 . $n10 . $n11 . $n12 . "-" . $d1 . $d2;
        } else {
            $retorno = '' . $n1 . $n2 . $n3 . $n4 . $n5 . $n6 . $n7 . $n8 . $n9 . $n10 . $n11 . $n12 . $d1 . $d2;
        }
        return $retorno;
    }
    
    /**
     * @see https://gist.github.com/acfreitas/fb7465c33156ec144513
     */
    public static function cpf($mascara = true) {
        $n1 = random_int(0, 9);
        $n2 = random_int(0, 9);
        $n3 = random_int(0, 9);
        $n4 = random_int(0, 9);
        $n5 = random_int(0, 9);
        $n6 = random_int(0, 9);
        $n7 = random_int(0, 9);
        $n8 = random_int(0, 9);
        $n9 = random_int(0, 9);
        $d1 = $n9 * 2 + $n8 * 3 + $n7 * 4 + $n6 * 5 + $n5 * 6 + $n4 * 7 + $n3 * 8 + $n2 * 9 + $n1 * 10;
        $d1 = 11 - (self::mod($d1, 11) );
        if ($d1 >= 10) {
            $d1 = 0;
        }
        $d2 = $d1 * 2 + $n9 * 3 + $n8 * 4 + $n7 * 5 + $n6 * 6 + $n5 * 7 + $n4 * 8 + $n3 * 9 + $n2 * 10 + $n1 * 11;
        $d2 = 11 - (self::mod($d2, 11) );
        if ($d2 >= 10) {
            $d2 = 0;
        }
        $retorno = '';
        if ($mascara == 1) {
            $retorno = '' . $n1 . $n2 . $n3 . "." . $n4 . $n5 . $n6 . "." . $n7 . $n8 . $n9 . "-" . $d1 . $d2;
        } else {
            $retorno = '' . $n1 . $n2 . $n3 . $n4 . $n5 . $n6 . $n7 . $n8 . $n9 . $d1 . $d2;
        }
        return $retorno;
    }
    
    /**
     * @param int $dividendo
     * @param int $divisor
     * @return int
     */
    private static function mod($dividendo, $divisor) {
        return round($dividendo - (floor($dividendo / $divisor) * $divisor));
    }
}
?>