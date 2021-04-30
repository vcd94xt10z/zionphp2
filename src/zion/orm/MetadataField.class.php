<?php
namespace zion\orm;

/**
 * @author Vinicius Cesar Dias
 */
class MetadataField {
    /**
     * Tipos nativos aceitos
     * @type array of string
     */
    public static $nativeTypes = ["string","integer","date","datetime","double","boolean","binary"];
    
    /**
     * Nome do campo
     * @var string
     */
    public $name;
    
    /**
     * Tipo nativo do banco de dados
     * @type string
     */
    public $databaseType;
    
    /**
     * Tipo nativo do PHP
     * @type string
     */
    public $nativeType;
    
    /**
     * Tamanho do campo
     * @type int
     */
    public $size;
    
    /**
     * Tamanho da parte decimal, se houver
     * @type int
     */
    public $decimal;
    
    /**
     * É obrigatório?
     * @type boolean
     */
    public $isRequired;
    
    /**
     * É chave primária? 
     * @type boolean
     */
    public $isPK;
    
    /**
     * É unico?
     * @var boolean
     */
    public $isUK;
    
    /**
     * Valor padrão
     * @type string
     */
    public $defaultValue;
    
    /**
     * Comentário
     * @type string
     */
    public $comment;
    
    public function isAI(){
        return ($this->isPK AND $this->nativeType == "integer");
    }
    
    public function getCommentField($field){
        $json = json_decode($this->comment,true);
        if(is_array($json)){
            return $json[$field];
        }
        return "";
    }
}
?>