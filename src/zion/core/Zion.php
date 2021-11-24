<?php
namespace zion\core;

use Exception;
use zion\utils\FileUtils;
use zion\mod\welcome\controller\WelcomeController;
use zion\utils\HTTPUtils;
use zion\security\ACL;
use zion\utils\DiffUtils;
use zion\core\Page;

/**
 * @author Vinicius Cesar Dias
 */
class Zion {
    public static function getModules(){
        $modules = array();
        $files = scandir(\zion\ROOT."modules".\DS);
        foreach($files AS $filename){
            if(in_array($filename,array(".",".."))){
                continue;
            }
            $modules[] = $filename;
        }
        sort($modules);
        return $modules;
    }
    
    /**
     * Coloque essa chamada no crontab do Linux
     * crontab -e
     * 0 * * * * /usr/bin/wget -O - -q -t 1 http://<hostname>/zion/crontab > /dev/null 2>&1
     */
    public static function crontab(){
        // estatisticas de banco
        echo "Atualizando estatisticas de banco<br>\n";
        try {
            $className = "\zion\mod\core\controller\UserController";
            $ctrl = new $className();
            $ctrl->actionCollectHistdata();
        }catch(Exception $e){
        }
        
        // apagando cache antigo
        echo "Apagando cache antigo<br>\n";
        try {
            $className = "\zion\cache\FileCache";
            $ctrl = new $className([]);
            $ctrl->clean();
        }catch(Exception $e){
        }
        
        echo "Importando log de erros<br>\n";
        try {
            \zion\core\ErrorHandler::importLogToDatabase();
        }catch(Exception $e){
        }
    }
    
    /**
     * Chamar esse método caso utilize arquivos de frontend de modulos
     */
    public static function route(){
        System::routeToController();
        
        // framework / bibliotecas frontend
        $isFrontEndLib = false;
        
        if(strpos($_SERVER["REQUEST_URI"],"/zion/lib/") === 0 ||
           strpos($_SERVER["REQUEST_URI"],"/lib/") === 0){
           $isFrontEndLib = true;
        }
        
        if($isFrontEndLib){
            $uri = $_SERVER["REQUEST_URI"];
            
            if(strpos($_SERVER["REQUEST_URI"],"/zion/lib/") === 0){
                $uri = str_replace("/zion/lib/","/",$uri);
            }elseif(strpos($_SERVER["REQUEST_URI"],"/lib/") === 0){
                $uri = str_replace("/lib/","/",$uri);
            }
            
            $file = \zion\ROOT."frontend".$uri;
            
            $file = explode("?",$file);
            $file = $file[0];
            
            if(file_exists($file)){
                $contentType = FileUtils::getContentTypeByFile($file);
                header("Content-Type: ".$contentType);
                HTTPUtils::sendCacheHeadersStatic();
                readfile($file);
            }else{
                HTTPUtils::status(404);
                HTTPUtils::sendHeadersNoCache();
            }
            exit();
        }
        
        // a partir daqui, o prefixo /zion/ é obrigatório
        if(strpos($_SERVER["REQUEST_URI"],"/zion/") !== 0){
            return;
        }
        
        Page::loadLibs(array(
            "jquery"    => 3,
            "bootstrap" => 4
        ));
        
        if(strpos($_SERVER["REQUEST_URI"],"/zion/doc/") === 0){
            require(\zion\ROOT."docs/index.html");
            exit();
        }
        
        if(strpos($_SERVER["REQUEST_URI"],"/zion/crontab") === 0){
            self::crontab();
            exit();
        }
        
        $zion = System::get("zion");
        
        $zionuriEnabled = true;
        if($zion["enableURI"] === 0){
            $zionuriEnabled = false;
        }
        
        // diff
        if(strpos($_SERVER["REQUEST_URI"],"/zion/diff/") === 0){
            DiffUtils::sendObjectList();
            exit();
        }
        
        // home
        if($zionuriEnabled AND $_SERVER["REQUEST_URI"] == "/zion/"){
            $ctrl = new WelcomeController();
            $ctrl->actionHome();
            exit();
        }
        
        // exceções
        if($zionuriEnabled){
            $user = Session::get("user");
            
            $checkACL = true;
            if(self::isFreeURI() OR ($user != null AND $user->perfil == "admin")){
               $checkACL = false;
            }
            
            if($checkACL){
                $acl = ACL::getObject();
                if($acl === null){
                    HTTPUtils::status(403);
                    echo "Acesso negado, erro em verificar regras de acesso";
                    exit();
                }
                
                if($acl->get("status") == "SOL"){
                    HTTPUtils::status(403);
                    echo "Acesso negado, sua solicitação já foi registrada para análise";
                    exit();
                }
                
                if($acl->get("status") == "NEG"){
                    HTTPUtils::status(403);
                    echo "Acesso negado, sua solicitado foi bloqueada pela administração do sistema";
                    exit();
                }
            }
        }
        
        if($zionuriEnabled AND strpos($_SERVER["REQUEST_URI"],"/zion/rest/") === 0){
            $uri = explode("/", $_SERVER["REQUEST_URI"]);
            if(sizeof($uri) < 5){
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
            $module     = preg_replace("[^a-z0-9\_]", "", strtolower($uri[3]));
            $controller = preg_replace("[^a-zA-Z0-9]", "", $uri[4]);
            
            $className   = $controller."Controller";
            $classNameNS = "\\zion\\mod\\".$module."\\controller\\".$controller."Controller";
            $classFile   = \zion\ROOT."modules/".$module."/controller/".$className.".class.php";
            
            System::set("module",$module);
            
            if(file_exists($classFile)) {
                require($classFile);
                $ctrl = new $classNameNS();
                
                $methodName = "rest";
                if(method_exists($ctrl, $methodName)){
                    self::checkSession();
                    $ctrl->$methodName();
                    exit();
                }
            }
            
            HTTPUtils::status(404);
            HTTPUtils::sendHeadersNoCache();
            exit();
        }
        
        if($zionuriEnabled AND strpos($_SERVER["REQUEST_URI"],"/zion/mod/") === 0){
            $uri = explode("/", $_SERVER["REQUEST_URI"]);
            
            if(sizeof($uri) == 5 AND $uri[4] == "") {
                $module = preg_replace("[^a-z0-9\_]", "", strtolower($uri[3]));
                $file = \zion\ROOT."modules/".$module."/index.php";
                if(file_exists($file)){
                    require($file);
                    exit();
                }
            }
            
            if(sizeof($uri) < 6) {
                HTTPUtils::status(404);
                echo "Página não encontrada";
                exit();
            }
            
            $module = preg_replace("[^a-z0-9\_]", "", strtolower($uri[3]));
            
            System::set("module",$module);
            
            // padrão de view
            if($uri[4] == "view"){
                $file = \zion\ROOT.str_replace("/zion/mod/","/modules/",$_SERVER["REQUEST_URI"]);
                $file = explode("?",$file);
                $file = $file[0];
                
                if(file_exists($file)){
                    FileUtils::inline($file);
                    exit();
                }
                
                HTTPUtils::status(404);
                echo "Página não encontrada";
                exit();
            }else{
                // padrão de controle
                $controller = preg_replace("[^a-zA-Z0-9]", "", $uri[4]);
                $action     = explode("?", $uri[5]);
                $action     = preg_replace("[^a-zA-Z0-9]", "", $action[0]);
                
                $className   = $controller."Controller";
                $classNameNS = "\\zion\\mod\\".$module."\\controller\\".$controller."Controller";
                $classFile   = \zion\ROOT."modules/".$module."/controller/".$className.".class.php";
                
                if(file_exists($classFile)){
                    require($classFile);
                    $ctrl = new $classNameNS();
                    
                    $methodName = "action".ucfirst($action);
                    if(method_exists($ctrl, $methodName)){
                        self::checkSession();
                        $ctrl->$methodName();
                        exit();
                    }
                }
            }
        }
        
        // só exibe 404 quando o zionuri esta habilitado pois o usuário
        // pode querer utilizar a uri zion. Porém as bibliotecas frontend /zion/lib/
        // sempre ficam disponíveis!
        if($zionuriEnabled){
            HTTPUtils::status(404);
            echo "Página não encontrada";
            exit();
        }
    }
    
    public static function checkSession(){
        // verificando se o usuário esta logado
        if(!self::isFreeURI()){
            $user = Session::get("user");
            if($user == null){
                HTTPUtils::status(401);
                echo "Autenticação requerida";
                exit();
            }
        }
    }
    
    public static function isFreeURI(){
        $freeURIs = array(
            "/zion/crontab",
            "/zion/mod/core/view/",
            "/zion/mod/core/User/loginform",
            "/zion/mod/core/User/login",
            "/zion/mod/core/User/logout",
            "/zion/mod/core/User/createAdminUser",
            "/zion/mod/welcome/",
            "/zion/mod/monitor/Object/monitor",
            "/zion/mod/monitor/Object/crontab",
            "/zion/mod/monitor/Object/changeSound",
            "/zion/mod/monitor/Object/getNotifications",
            "/zion/mod/monitor/Object/getAudio",
            "/zion/mod/monitor/view/",
            "/zion/mod/waf/WAF/checkServerConfig"
        );
        
        foreach($freeURIs AS $uri){
            if(strpos($_SERVER["REQUEST_URI"],$uri) === 0){
                return true;
            }
        }
        return false;
    }
}