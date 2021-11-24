<?php
namespace zion\utils;

/**
 * @author Vinicius Cesar Dias
 */
class Validation {
    /**
     * Verifica se uma senha é forte
     * @see https://stackoverflow.com/questions/10752862/password-strength-check-in-php
     * @return boolean
     */
    public static function isStrongPassword($password,array &$errors = array()){
        if (strlen($password) < 8) {
            $errors[] = "Senha curta";
        }
        
        if (!preg_match("#[0-9]+#", $password)) {
            $errors[] = "Senha não contém nenhum numero";
        }
        
        if (!preg_match("#[a-zA-Z]+#", $password)) {
            $errors[] = "Senha não contém nenhuma letra";
        }
        
        if( !preg_match("#\W+#", $password) ) {
            $errors[] = "Senha não contém nenhuma caracter especial";
        }
        
        return (sizeof($errors) <= 0);
    }

    public static function validatePassword($password){
        $errors = [];

        if (strlen($password) < 8) {
            $errors[] = "Senha curta, informe ao menos 8 caracteres";
        }

        if (strlen($password) > 20) {
            $errors[] = "Senha longa, informe no máximo 20 caracteres";
        }
        
        if (!preg_match("#[0-9]+#", $password)) {
            $errors[] = "Senha não contém nenhum numero";
        }
        
        if (!preg_match("#[a-z]+#", $password)) {
            $errors[] = "Senha não contém nenhuma letra minuscula";
        }

        if (!preg_match("#[A-Z]+#", $password)) {
            $errors[] = "Senha não contém nenhuma letra maiuscula";
        }
        
        $specialList = "!@#$%&*()-+";
        $specialList = str_split($specialList,1);
        $found = false;
        foreach($specialList AS $c){
            if(strpos($password,$c) !== false){
                $found = true;
                break;
            }
        }
        if(!$found){
            $errors[] = "Senha não contém nenhuma caracter especial";
        }
        
        return $errors;
    }
    
    /**
     * Verifica se é um CEP válido
     * @param string $cep
     * @return boolean
     */
    public static function isCEP($cep){
        $a = preg_replace("[^0-9]","",$cep);
        return (strlen($a) == 8);
    }
    
    /**
     * Verifica se é um CNPJ válido
     * @see https://gist.github.com/guisehn/3276302
     * @param string $cnpj
     * @return boolean
     */
    public static function isCNPJ($cnpj){
        $cnpj = preg_replace('/[^0-9]/', '', (string) $cnpj);
        // Valida tamanho
        if (strlen($cnpj) != 14)
            return false;
            // Valida primeiro dígito verificador
            for ($i = 0, $j = 5, $soma = 0; $i < 12; $i++)
            {
                $soma += $cnpj[$i] * $j;
                $j = ($j == 2) ? 9 : $j - 1;
            }
            $resto = $soma % 11;
            if ($cnpj[12] != ($resto < 2 ? 0 : 11 - $resto))
                return false;
                // Valida segundo dígito verificador
                for ($i = 0, $j = 6, $soma = 0; $i < 13; $i++)
                {
                    $soma += $cnpj[$i] * $j;
                    $j = ($j == 2) ? 9 : $j - 1;
                }
                $resto = $soma % 11;
                return $cnpj[13] == ($resto < 2 ? 0 : 11 - $resto);
    }
    
    /**
     * Verifica se é um CPF válido
     * @see https://www.geradorcpf.com/script-validar-cpf-php.htm
     * @param string $cpf
     * @return boolean
     */
    public static function isCPF($cpf){
        // Verifica se um número foi informado
        if(empty($cpf)) {
            return false;
        }
        
        // Elimina possivel mascara
        $cpf = preg_replace("/[^0-9]/", "", $cpf);
        $cpf = str_pad($cpf, 11, '0', STR_PAD_LEFT);
        
        // Verifica se o numero de digitos informados é igual a 11
        if (strlen($cpf) != 11) {
            return false;
        }
        // Verifica se nenhuma das sequências invalidas abaixo
        // foi digitada. Caso afirmativo, retorna falso
        else if ($cpf == '00000000000' ||
            $cpf == '11111111111' ||
            $cpf == '22222222222' ||
            $cpf == '33333333333' ||
            $cpf == '44444444444' ||
            $cpf == '55555555555' ||
            $cpf == '66666666666' ||
            $cpf == '77777777777' ||
            $cpf == '88888888888' ||
            $cpf == '99999999999') {
                return false;
                // Calcula os digitos verificadores para verificar se o
                // CPF é válido
         } else {
            for ($t = 9; $t < 11; $t++) {
                for ($d = 0, $c = 0; $c < $t; $c++) {
                    $d += $cpf[$c] * (($t + 1) - $c);
                }
                $d = ((10 * $d) % 11) % 10;
                if ($cpf[$c] != $d) {
                    return false;
                }
            }
            return true;
        }
    }
    
    /**
     * Verifica se é um email válido
     * @param string $email
     * @return mixed
     */
    public static function isEmail($email){
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
    
    /**
     * Valida um IP v4
     * @param string $ip: IP a ser validado
     * @return bool
     * @see http://rubsphp.blogspot.com.br/2010/12/obter-o-ip-do-cliente.html
     */
    public static function isIPv4($ip) : bool {
        // IPv4
        $vetor = explode('.', $ip);
        if (count($vetor) != 4) {
            return false;
        }
        foreach ($vetor as $i) {
            if (!is_numeric($i) || $i < 0 || $i > 255) {
                return false;
            }
        }
        return true;
    }
}
?>
