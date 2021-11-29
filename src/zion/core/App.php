<?php
namespace zion\core;

use zion\utils\FileUtils;
use zion\utils\HTTPUtils;

/**
 * @author Vinicius Cesar Dias
 */
class App {
    /**
     * Mapea as rotas padrões para os módulos
     */
    public static function route(){
        $uri = explode("/",$_SERVER["REQUEST_URI"]);
        
        // URI modulos app
        if(strpos($_SERVER["REQUEST_URI"],"/app/mod/") === 0){
            if($uri[4] == "view"){
                $file = \zion\APP_ROOT."public".str_replace("/mod/","/modules/",$_SERVER["REQUEST_URI"]);
                $file = explode("?",$file);
                $file = $file[0];
                
                if(file_exists($file)){
                    FileUtils::inline($file);
                    exit();
                }
                
                HTTPUtils::status(404);
                echo "Página não encontrada";
                exit();
            }
            
            $module     = preg_replace("[^a-zA-Z0-9]", "", $uri[3]);
            $controller = preg_replace("[^a-zA-Z0-9]", "", $uri[4]);
            $action     = explode("?", $uri[5]);
            $action     = preg_replace("[^a-zA-Z0-9]", "", $action[0]);
            
            $className   = $controller."Controller";
            $classNameNS = "\\app\\mod\\".$module."\\controller\\".$controller."Controller";
            $classFile   = $_SERVER["DOCUMENT_ROOT"]."/modules/".$module."/controller/".$className.".php";
            
            if(file_exists($classFile)) {
                require_once($classFile);
                $ctrl = new $classNameNS();
                
                $methodName = "action".ucfirst($action);
                if(method_exists($ctrl, $methodName)){
                    $ctrl->$methodName();
                    exit();
                }
            }
        }
        
        // URI modulos app (curta)
        if(strpos($_SERVER["REQUEST_URI"],"/mod/") === 0){
            // antigamente aqui tinha um código para carregar arquivos estáticos dentro da pasta view
            // porém, isso não estava sendo vantajoso pois as regras do usuário em um arquivo .htaccess
            // por exemplo, não eram aplicadas pois o arquivo não existia no caminho informado
            if(sizeof($uri) >= 5){
                $module     = preg_replace("[^a-zA-Z0-9]", "", $uri[2]);
                $controller = preg_replace("[^a-zA-Z0-9]", "", $uri[3]);
                $action     = explode("?", $uri[4]);
                $action     = preg_replace("[^a-zA-Z0-9]", "", $action[0]);
                
                $className   = $controller."Controller";
                $classNameNS = "\\app\\mod\\".$module."\\controller\\".$controller."Controller";
                $classFile   = $_SERVER["DOCUMENT_ROOT"]."/modules/".$module."/controller/".$className.".php";
                
                if(file_exists($classFile)) {
                    require_once($classFile);
                    $ctrl = new $classNameNS();
                    
                    $methodName = "action".ucfirst($action);
                    if(method_exists($ctrl, $methodName)){
                        $ctrl->$methodName();
                        exit();
                    }
                }
            }
        }
        
        // URI no padrão rest
        if(strpos($_SERVER["REQUEST_URI"],"/rest/") === 0){
            $uri = explode("/", $_SERVER["REQUEST_URI"]);
            if(sizeof($uri) < 4){
                HTTPUtils::status(400);
                HTTPUtils::sendHeadersNoCache();
                echo "Padrão de URI Rest inválido (".sizeof($uri).")";
                exit();
            }
            
            if(!in_array($_SERVER["REQUEST_METHOD"],array("GET","POST","PUT","DELETE","FILTER"))){
                HTTPUtils::status(400);
                HTTPUtils::sendHeadersNoCache();
                echo "Método Rest inválido";
                exit();
            }
            
            // controle
            $module     = preg_replace("[^a-z0-9\_]", "", strtolower($uri[2]));
            $controller = preg_replace("[^a-zA-Z0-9]", "", $uri[3]);
            
            $className   = $controller."Controller";
            $classNameNS = "\\mod\\".$module."\\controller\\".$controller."Controller";
            $classFile   = \zion\APP_ROOT."public/modules/".$module."/controller/".$className.".php";
            
            if(file_exists($classFile)) {
                require_once($classFile);
                $ctrl = new $classNameNS();
                
                $methodName = "rest";
                if(method_exists($ctrl, $methodName)){
                    $ctrl->$methodName();
                    exit();
                }
            }
            
            HTTPUtils::status(404);
            HTTPUtils::sendHeadersNoCache();
            exit();
        }
    }
}
?>
