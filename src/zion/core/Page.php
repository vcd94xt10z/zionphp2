<?php 
namespace zion\core;

/**
 * @author Vinicius 
 */
class Page {
    /**
     * Título da página
     * @var string
     */
    private static $title = "Sem titulo";
    
    private static $showHeader = true;
    
    private static $showFooter = true;
    
    /**
     * Arquivo a ser incluído
     * @var string
     */
    private static $include = "";
    
    /**
     * Metatags
     * @var string
     */
    private static $meta = array(
        "robots" => "noindex"
    );
    
    /**
     * Cache da página
     * @var array
     */
    private static $cacheControl = array(
        "max-age"  => 0,
        "s-maxage" => 0
    );
    
    /**
     * Navegação
     * @var array
     */
    private static $breadcrumbs = array();
    
    /**
     * Dados utilizados na view
     * @var array
     */
    private static $data = array(
        "js" => [],
        "css" => []
    );
    
    public static function set($key,$value){
        self::$data[$key] = $value;
    }
    
    public static function get($key){
        return self::$data[$key];
    }
    
    public static function add($key,$value){
        self::$data[$key][] = $value;
    }
    
    /**
     * Define o título da página
     * @param string $title
     */
    public static function setTitle($title){
        self::$title = $title;
    }
    
    public static function getTitle(){
        return self::$title;
    }
    
    public static function showHeader($bool = null){
        if($bool === null){
            return self::$showHeader;
        }
        self::$showHeader = $bool;
    }
    
    public static function showFooter($bool = null){
        if($bool === null){
            return self::$showFooter;
        }
        self::$showFooter = $bool;
    }
    
    public static function setBreadcrumbs(array $bc){
        self::$breadcrumbs = $bc;
    }
    
    public static function getBreadcrumbs(){
        return self::$breadcrumbs;
    }
    
    public static function setMeta($name,$value){
        self::$meta[$name] = $value;
    }
    
    public static function getMeta($name){
        return self::$meta[$name];
    }
    
    public static function setCacheControl($name,$value){
        self::$cacheControl[$name] = $value;
    }
    
    public static function getCacheControl($name){
        return self::$cacheControl[$name];
    }
    
    public static function setInclude($file){
        self::$include = $file;
    }
    
    public static function getInclude(){
        return self::$include;
    }
    
    public static function sendCacheControl(){
        header("Cache-Control: max-age=".self::$cacheControl["max-age"].", s-maxage=".self::$cacheControl["s-maxage"]);
    }
    
    /**
     * Remove arquivos css/js duplicados
     */
    public static function removeDuplicates(){
        self::$data["js"]  = array_unique(self::$data["js"]);
        self::$data["css"] = array_unique(self::$data["css"]);
    }
    
    public static function cssBulk(array $list){
        foreach($list AS $uri){
            self::css($uri);
        }
    }
    
    /**
     * Inclue e retorna as URIs css
     */
    public static function css($uri=null){
        if($uri === null){
            return self::$data["css"];
        }
        self::$data["css"][] = $uri;
    }
    
    public static function cssTags(){
        $lines = array();
        
        foreach(Page::css() AS $uri){
            if(is_array($uri)){
                $attrs = array();
                foreach($uri AS $key => $value){
                    $attrs[] = $key."=\"".$value."\"";
                }
                $lines[] = "<link ".implode(" ",$attrs)."/>";
            }else{
                $lines[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"{$uri}\"/>";
            }
        }
        return $lines;
    }
    
    public static function jsBulk(array $list){
        foreach($list AS $uri){
            self::js($uri);
        }
    }
    
    /**
     * Inclue e retorna as URIs js
     */
    public static function js($uri=null){
        if($uri === null){
            return self::$data["js"];
        }
        self::$data["js"][] = $uri;
    }
    
    public static function jsTags(){
        $lines = array();
        foreach(Page::js() AS $uri){
            if(is_array($uri)){
                $attrs = array();
                foreach($uri AS $key => $value){
                    $attrs[] = $key."=\"".$value."\"";
                }
                $lines[] = "<script ".implode(" ",$attrs)."></script>";
            }else{
                $lines[] = "<script type=\"text/javascript\" src=\"{$uri}\"></script>";
            }
        }
        return $lines;
    }
}
?>