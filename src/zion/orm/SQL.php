<?php
namespace zion\orm;

/**
 * @author Vinicius
 */
class SQL {
    // operadores de 1 campo
    const EQUAL                 = "EQ";
    const NOT_EQUAL             = "NE";
    const GREATER_THAN          = "GT";
    const GREATER_THAN_OR_EQUAL = "GE";
    const LESS_THAN             = "LT";
    const LESS_THAN_OR_EQUAL    = "LE";
    const IS_NULL               = "NU";
    const IS_NOT_NULL           = "NN";
    const IN                    = "IN";
    const NOT_IN                = "NI";
    const LIKE                  = "LI";
    const NOT_LIKE              = "NL";
    const REGEXP                = "RE";
    const NOT_REGEXP            = "NR";
    
    // operadores de 2 campos
    const BETWEEN               = "BT";
    const NOT_BETWEEN           = "NB";
    
    // customizados
    const CONTAINS              = "CS";
    const NOT_CONTAINS          = "NC";
    const STARTS                = "ST";
    const NOT_STARTS            = "NS";
    const ENDS                  = "ED";
    const NOT_ENDS              = "NZ";
    
    // ordenação
    const ASC                   = "ASC";
    const DESC                  = "DESC";
    
    // DBMS
    const MYSQL                 = "MySQL";
    const MSSQL                 = "SQL Server";
    const ORACLE                = "Oracle";
    const DB2                   = "DB2";
    const POSTGRE               = "POSTGRE";
    
    // operadores logicos
    const AND                   = "AND";
    const OR                    = "OR";
    
    public static function toMySQL($code){
        $tab = array(
            SQL::EQUAL                  => "=",
            SQL::NOT_EQUAL              => "<>",
            SQL::GREATER_THAN           => ">",
            SQL::GREATER_THAN_OR_EQUAL  => ">=",
            SQL::LESS_THAN              => "<",
            SQL::LESS_THAN_OR_EQUAL     => "<=",
            SQL::IS_NULL                => "IS NULL",
            SQL::IS_NOT_NULL            => "IS NOT NULL",
            SQL::IN                     => "IN",
            SQL::NOT_IN                 => "NOT IN",
            SQL::BETWEEN                => "BETWEEN",
            SQL::NOT_BETWEEN            => "NOT BETWEEN",
            SQL::REGEXP                 => "REGEXP",
            SQL::NOT_REGEXP             => "NOT REGEXP",
            SQL::LIKE                   => "LIKE",
            SQL::NOT_LIKE               => "NOT LIKE"
        );
        
        if(array_key_exists($code, $tab)){
            return $tab[$code];
        }
        
        // se o operador não for previsto, retorna a mesma entrada
        return $code;
    }
}
?>