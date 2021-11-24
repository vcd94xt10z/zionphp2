<?php 
namespace zion\utils;

/**
 * @author Vinicius
 * @since 08/06/2021
 */
class Math {
    /**
     * https://stackoverflow.com/questions/12277945/php-how-do-i-round-down-to-two-decimal-places
     * @param unknown $decimal
     * @param unknown $precision
     * @return number
     */
    public static function roundDown($decimal, $precision){
        $sign = $decimal > 0 ? 1 : -1;
        $base = pow(10, $precision);
        return floor(abs($decimal) * $base) / $base * $sign;
    }
    
    /**
     * Calcula o valor da parcela com juros
     * https://guerrati.wordpress.com/2017/01/19/php-funcao-para-calcular-price/
     * @param unknown $Valor
     * @param unknown $Parcelas
     * @param unknown $Juros
     * @return string
     */
    public static function price($Valor, $Parcelas, $Juros) {
        $Juros = bcdiv($Juros,100,15);
        $E=1.0;
        $cont=1.0;
        
        for($k=1;$k<=$Parcelas;$k++)
        {
            $cont= bcmul($cont,bcadd($Juros,1,15),15);
            $E=bcadd($E,$cont,15);
        }
        $E=bcsub($E,$cont,15);
        
        $Valor = bcmul($Valor,$cont,15);
        return Math::roundDown(floatval(bcdiv($Valor,$E,15)),2);
    }
}
?>