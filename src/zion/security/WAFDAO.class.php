<?php
namespace zion\security;

use Exception;
use PDO;

/**
 * WAFDAO
 * @author Vinicius
 * @since 01/02/2019
 */
class WAFDAO {
    public function putClientLocation($db,$obj) {
        $sql = "
        INSERT INTO `zion_waf_ip_location`
		(
          `ipaddr`,`type`,`continent_code`,`continent_name`,`country_code`,
		  `country_name`,`region_code`,`region_name`,`city`,`updated`
        )
		VALUES
		(
          ':ipaddr:',':type:',':continent_code:',':continent_name:',':country_code:',
		  ':country_name:',':region_code:',':region_name:',':city:',NOW()
		)";
        
        $sql = str_replace(":ipaddr:", addslashes($obj->ipaddr), $sql);
        $sql = str_replace(":type:", addslashes($obj->type), $sql);
        $sql = str_replace(":continent_code:", addslashes($obj->continent_code), $sql);
        $sql = str_replace(":continent_name:", addslashes($obj->continent_name), $sql);
        $sql = str_replace(":country_code:", addslashes($obj->country_code), $sql);
        $sql = str_replace(":country_name:", addslashes($obj->country_name), $sql);
        $sql = str_replace(":region_code:", addslashes($obj->region_code), $sql);
        $sql = str_replace(":region_name:", addslashes($obj->region_name), $sql);
        $sql = str_replace(":city:", addslashes($obj->city), $sql);
        $db->exec($sql);
    }
    
    public function getClientLocation($db,$ip) {
        $sql = "SELECT *
                  FROM `zion_waf_ip_location`
                 WHERE `ipaddr` = '".addslashes($ip)."'";
        $query = $db->query($sql);
        if($raw = $query->fetchObject()) {
            return $raw;
        }
        return null;
    }
    
    public function inBlacklist($db){
        $timeout = 3600;
        
        $sql = "SELECT *
                  FROM `zion_waf_blacklist`
                 WHERE `ipaddr` = '".$_SERVER["REMOTE_ADDR"]."'
                   AND TIMESTAMPDIFF(SECOND,`created`,NOW()) < ".$timeout;
        $query = $db->query($sql);
        $raw = $query->fetchObject();
        if($raw !== false){
            return true;
        }
        return false;
    }
    
    public function inWhitelist($db){
        $timeout = "21600"; // 6 horas
        
        $sql = "SELECT * 
                  FROM `zion_waf_whitelist`
                 WHERE (`ipaddr` = '".$_SERVER["REMOTE_ADDR"]."' AND `type` = 'S')
                    OR (`ipaddr` = '".$_SERVER["REMOTE_ADDR"]."' 
                        AND TIMESTAMPDIFF(SECOND,`updated`,NOW()) < ".$timeout." 
                        AND `type` = 'D')";
        $query = $db->query($sql);
        $raw = $query->fetchObject();
        if($raw === false OR $raw == null) {
            return false;
        }
        return true;
    }
    
    /**
     * Adiciona o usuário na blacklist e para a execução
     */
    public function addToBlacklist($db,$policy,array $params = []){
        $REMOTE_ADDR = $_SERVER["REMOTE_ADDR"];
        $HTTP_USER_AGENT = $_SERVER["HTTP_USER_AGENT"];
        $REQUEST_URI = $_SERVER["REQUEST_URI"];
        $SERVER_NAME = $_SERVER["SERVER_NAME"];
        if(sizeof($params) > 0) {
            $REMOTE_ADDR = $params["REMOTE_ADDR"];
            $HTTP_USER_AGENT = $params["HTTP_USER_AGENT"];
            $REQUEST_URI = $params["REQUEST_URI"];
            $SERVER_NAME = $params["SERVER_NAME"];
        }
        
        $sql = "INSERT INTO `zion_waf_blacklist`
    			(`ipaddr`, `created`, `user_agent`, `request_uri`, `server_name`, `hits`, `policy`, `updated`)
    			VALUES
				(':ipaddr:', NOW(), ':user_agent:', ':request_uri:', ':server_name:', 1, ':policy:', NOW())
                ON DUPLICATE KEY UPDATE
                `created` = NOW(), `request_uri` = ':request_uri:', `hits`= `hits`+1, `updated` = NOW()";
        $sql = str_replace(":ipaddr:", $REMOTE_ADDR, $sql);
        $sql = str_replace(":user_agent:", addslashes($HTTP_USER_AGENT), $sql);
        $sql = str_replace(":request_uri:", addslashes($REQUEST_URI), $sql);
        $sql = str_replace(":server_name:", addslashes($SERVER_NAME), $sql);
        $sql = str_replace(":policy:", addslashes($policy), $sql);
        
        try {
            $db->exec($sql);
        }catch(Exception $e){
        }
    }
    
    /**
     * Registra a requisição no log
     * @param PDO $db
     */
    public function log($db){
        $fields = array("USER", "HOME", "SCRIPT_NAME", "REQUEST_URI", "QUERY_STRING", "REQUEST_METHOD", "SERVER_PROTOCOL",
            "GATEWAY_INTERFACE", "REDIRECT_URL", "REMOTE_PORT", "SCRIPT_FILENAME", "SERVER_ADMIN", "CONTEXT_DOCUMENT_ROOT",
            "CONTEXT_PREFIX", "REQUEST_SCHEME", "DOCUMENT_ROOT", "REMOTE_ADDR", "SERVER_PORT", "SERVER_ADDR", "SERVER_NAME",
            "SERVER_SOFTWARE", "SERVER_SIGNATURE", "PATH", "HTTP_PRAGMA", "HTTP_COOKIE", "HTTP_ACCEPT_LANGUAGE", "HTTP_ACCEPT_ENCODING",
            "HTTP_ACCEPT", "HTTP_DNT", "HTTP_USER_AGENT", "HTTP_UPGRADE_INSECURE_REQUESTS", "HTTP_CONNECTION", "HTTP_HOST", "UNIQUE_ID",
            "REDIRECT_STATUS", "REDIRECT_UNIQUE_ID", "FCGI_ROLE", "PHP_SELF", "REQUEST_TIME_FLOAT", "REQUEST_TIME", "HTTP_REFERER", "REQUEST_BODY");
        
        $sql = "INSERT INTO `zion_waf_request_log`
                (requestid, ".implode(", ",$fields).")
                VALUES
                (null, :".implode(":, :",$fields).":)";
        
        foreach($fields AS $field) {
            if($field == "REQUEST_TIME") {
                $sql = str_replace(":".$field.":", "NOW()", $sql);
            }else if($field == "REQUEST_BODY") {
                $sql = str_replace(":".$field.":", "'".addslashes(file_get_contents("php://input"))."'", $sql);
            }else{
                $sql = str_replace(":".$field.":", "'".addslashes($_SERVER[$field])."'", $sql);
            }
        }
        
        try {
            $db->exec($sql);
        }catch(Exception $e){
        }
    }
}
?>