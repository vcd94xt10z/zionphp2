<?php
namespace zion\orm;

use zion\utils\TextFormatter;

/**
 * @author Vinicius Cesar Dias
 */
class Filter {
    public static $optionsOP = [
        // operadores de 1 campo
        "EQ"   => "igual",
        "NE"   => "diferente",
        "GT"   => "maior que",
        "GE"   => "maior ou igual a",
        "LT"   => "menor que",
        "LE"   => "menor ou igual a",
        "NU"   => "vazio",
        "NN"   => "preenchido",
        "IN"   => "na lista",
        "NI"   => "não na lista",
        "RE"   => "expressão regular",
        
        // operadores de 2 campos
        "BT"   => "entre",
        "NB"   => "não entre",
        
        // customizados
        SQL::CONTAINS     => "contém",
        SQL::NOT_CONTAINS => "não contém",
        SQL::STARTS       => "começa com",
        SQL::NOT_STARTS   => "não começa com",
        SQL::ENDS         => "termina com",
        SQL::NOT_ENDS     => "não termina com"
    ];
    
    protected $filterList = [];
    protected $sortList = [];
    protected $offset = 0;
    protected $limit = 0;
    protected $groupMap = "";
    protected $groupByList = [];
    protected $nativeCondition = "";
    
    public function __construct(array $keys = array()) {
        foreach($keys AS $key => $value){
            $this->eq($key,$value);
        }
    }
    
    /**
     * Permite que envie uma condição sql nativa na clausula WHERE
     * @param string $nativeSQL
     */
    public function native(string $nativeSQL) {
        $this->nativeCondition = $nativeSQL;
    }
    
    public function getNativeFilter() : string {
        return $this->nativeCondition;
    }
    
    public function eq($name, $value, $group = "default", $oplogic = "AND") {
        $this->addFilter($name, SQL::EQUAL, $value, null, $group, $oplogic);
    }
    
    public function ne($name, $value, $group = "default", $oplogic = "AND") {
        $this->addFilter($name, SQL::NOT_EQUAL, $value, null, $group, $oplogic);
    }
    
    public function lt($name, $value, $group = "default", $oplogic = "AND") {
        $this->addFilter($name, SQL::LESS_THAN, $value, null, $group, $oplogic);
    }

    public function le($name, $value, $group = "default", $oplogic = "AND") {
        $this->addFilter($name, SQL::LESS_THAN_OR_EQUAL, $value, null, $group, $oplogic);
    }

    public function gt($name, $value, $group = "default", $oplogic = "AND") {
        $this->addFilter($name, SQL::GREATER_THAN, $value, null, $group, $oplogic);
    }
    
    public function ge($name, $value, $group = "default", $oplogic = "AND") {
        $this->addFilter($name, SQL::GREATER_THAN_OR_EQUAL, $value, null, $group, $oplogic);
    }    
    
    public function nu($name, $group = "default", $oplogic = "AND") {
        $this->addFilter($name, SQL::IS_NULL, null, null, $group, $oplogic);
    }

    public function nn($name, $group = "default", $oplogic = "AND") {
        $this->addFilter($name, SQL::IS_NOT_NULL, null, null, $group, $oplogic);
    }
    
    public function bt($name, $value1, $value2, $group = "default", $oplogic = "AND") {
        $this->addFilter($name, SQL::BETWEEN, $value1, $value2, $group, $oplogic);
    }
    
    public function nb($name, $value1, $value2, $group = "default", $oplogic = "AND") {
        $this->addFilter($name, SQL::NOT_BETWEEN, $value1, $value2, $group, $oplogic);
    }

    public function in($name, $list, $group = "default", $oplogic = "AND") {
        //if (is_array($list)) {
        //    $list = implode(",", $list);
        //}
        $this->addFilter($name, SQL::IN, $list, null, $group, $oplogic);
    }

    public function ni($name, $list, $group = "default", $oplogic = "AND") {
        if (is_array($list)) {
            $list = implode(",", $list);
        }
        $this->addFilter($name, SQL::NOT_IN, $list, null, $group, $oplogic);
    }
    
    public function re($name, $list, $group = "default", $oplogic = "AND") {
        if (is_array($list)) {
            $list = implode("|", $list);
        }
        $this->addFilter($name, SQL::REGEXP, $list, null, $group, $oplogic);
    }
    
    public function nr($name, $list, $group = "default", $oplogic = "AND") {
        if (is_array($list)) {
            $list = implode("|", $list);
        }
        $this->addFilter($name, SQL::NOT_REGEXP, $list, null, $group, $oplogic);
    }
    
    public function starts($name,$value,$group = "default", $oplogic = "AND"){
        $this->addFilter($name,SQL::LIKE,$value."%",null,$group,$oplogic);
    }
    
    public function notStarts($name,$value,$group = "default", $oplogic = "AND"){
        $this->addFilter($name,SQL::NOT_LIKE,$value."%",null,$group,$oplogic);
    }
    
    public function ends($name,$value,$group = "default", $oplogic = "AND"){
        $this->addFilter($name,SQL::LIKE,"%".$value,null,$group,$oplogic);
    }
    
    public function notEnds($name,$value,$group = "default", $oplogic = "AND"){
        $this->addFilter($name,SQL::NOT_LIKE,"%".$value,null,$group,$oplogic);
    }
    
    public function contains($name,$value,$group = "default", $oplogic = "AND"){
        $this->addFilter($name,SQL::LIKE,"%".$value."%",null,$group,$oplogic);
    }
    
    public function notContains($name,$value,$group = "default", $oplogic = "AND"){
        $this->addFilter($name,SQL::NOT_LIKE,"%".$value."%",null,$group,$oplogic);
    }
    
    /**
     * Adiciona um filtro (WHERE)
     * @param group agrupa as condições de filtro
     */
    public function addFilter(string $name, string $operator, $value1 = null, $value2 = null, $group = "default", $oplogic = "AND") {
        $this->filterList[$group][] = [
            "name"     => $name,
            "operator" => $operator,
            "value1"   => $value1,
            "value2"   => $value2,
            "oplogic"  => $oplogic
        ];
    }
    
    public function addFilterField(string $name,string $type,array $field,string $group = "default", string $oplogic = "AND"){
        // se o operador não for informado e nenhum valor for informado, então não filtra nada
        if($field["operator"] == "" AND $field["low"] == ""){
            return;
        }
        
        // quando não informado, o padrão é %like%
        if($field["operator"] == ""){
            $field["operator"] = "LIKE";
        }
        
        $this->filterList[$group][] = [
            "name"     => $name,
            "operator" => $field["operator"],
            "value1"   => TextFormatter::parse($type, $field["low"]),
            "value2"   => TextFormatter::parse($type, $field["high"]),
            "oplogic"  => $oplogic
        ];
    }
    
    public function getFilterList() {
        return $this->filterList;
    }
    
    /**
     * Ordena o resultado
     * @param string $name
     * @param string $direction
     */
    public function sort(string $name, string $direction) {
        if($name == ""){
            return;
        }
        
        $direction = strtoupper($direction);
        if(!in_array($direction,array("ASC","DESC"))){
            $direction = "ASC";
        }
        
        $this->sortList[] = [
            "name" => $name,
            "order" => $direction
        ];
    }
    
    public function addSort(string $name, string $direction) {
        $this->sort($name, $direction);
    }
    
    public function sortList() {
        return $this->sortList;
    }
    
    public function getSortList() {
        return $this->sortList;
    }
    
    public function limit($limit) {
        $this->limit = $limit;
    }
    
    public function setLimit($limit) {
        $this->limit = $limit;
    }
    
    public function getLimit() {
        return $this->limit;
    }
    
    public function offset($offset) {
        $this->offset = $offset;
    }
    
    public function setOffset($offset) {
        $this->offset = $offset;
    }
    
    public function getOffset() {
        return $this->offset;
    }

    public function clear() {
        $this->filterList  = array();
        $this->sortList   = array();
        $this->offset     = 0;
        $this->limit      = 0;
        $this->groupMap   = "";
        $this->groupByList = array();
        $this->nativeCondition = "";
    }
    
    public static function getOperators() : array {
        return self::$optionsOP;
    }
    
    /**
     * Define um mapa de grupo nas condições WHERE
     * Exemplo: (:g1: AND :g2:) OR (:g3: OR :g4:) ...
     */
    public function setGroupMap($map) {
        $this->groupMap = $map;
    }
    
    public function getGroupMap() {
        return $this->groupMap;
    }
    
    public function getGroupList() {
        return array_keys($this->filterList);
    }
    
    public function setGroupByList($groupByList) {
        $this->groupByList = $groupByList;
    }
    
    public function getGroupByList() {
        return $this->groupByList;
    }
    
    public function addGroupBy($groupBy) {
        $this->groupByList[] = $groupBy;
    }
}
?>
