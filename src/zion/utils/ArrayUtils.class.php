<?php 
namespace zion\utils;

class ArrayUtils {
    /**
     * Ordena um array de objetos
     * @param array $data
     * @param array $sortCriteria
     * @param bool $caseInSensitive
     * @return array
     */
    public static function multiSortObject(array $data, array $sortCriteria, bool $caseInSensitive = true) : array {
        // convertendo array de objetos em array de arrays
        $data2 = array();
        foreach($data AS $obj){
            $data2[] = json_decode(json_encode($obj),true);
        }
        $data = null;
        $result = self::multiSort($data2, $sortCriteria,$caseInSensitive);
        
        // convertendo array de arrays em array de objetos
        $result2 = array();
        foreach($result AS $row){
            $result2[] = json_decode(json_encode($row));
        }
        $result = null;
        
        return $result2;
    }
    
    /**
     * Ordena array multi-dimensional
     * Fonte exemplos do link http://php.net/manual/pt_BR/function.array-multisort.php
     * @param array $data
     * @param array $sortCriteria
     * Exemplo $sortCriteria = array('nome' => array(SORT_DESC, SORT_REGULAR),'idade' => array(SORT_DESC, SORT_NUMERIC));
     * @param bool $caseInSensitive
     * @return array
     */
    public static function multiSort(array $data, array $sortCriteria, bool $caseInSensitive = true) : array {
        if( !is_array($data) || !is_array($sortCriteria)){
            throw new \Exception("multiSort: Argumento inválido");
        }
        if(sizeof($data) <= 0){
            return $data;
        }
        
        $args = array();
        $i = 0;
        foreach($sortCriteria as $sortColumn => $sortAttributes){
            $colList = array();
            foreach ($data as $key => $row){
                $convertToLower = $caseInSensitive && (in_array(SORT_STRING, $sortAttributes) || in_array(SORT_REGULAR, $sortAttributes));
                $rowData = $convertToLower ? strtolower($row[$sortColumn]) : $row[$sortColumn];
                $colLists[$sortColumn][$key] = $rowData;
            }
            $args[] = &$colLists[$sortColumn];
            
            foreach($sortAttributes as $sortAttribute){
                $tmp[$i] = $sortAttribute;
                $args[] = &$tmp[$i];
                $i++;
            }
        }
        $args[] = &$data;
        call_user_func_array('array_multisort', $args);
        return end($args);
    }
}
?>