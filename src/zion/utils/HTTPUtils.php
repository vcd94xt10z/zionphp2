<?php
namespace zion\utils;

use Exception;
use StdClass;
use zion\core\System;

/**
 * @author Vinicius Cesar Dias
 */
class HTTPUtils {
    public static function parseURI(){
        $parts = explode("?",$_SERVER["REQUEST_URI"]);
        $uri  = $parts[0];
        $args = $parts[1];
        
        $parts = explode("/",$uri);
        
        return array(
            "url"   => $uri,
            "args"  => $args,
            "parts" => $parts
        );
    }
    
    /**
     * Informa o cliente para fazer cache
     * @param int $maxAge Tempo em segundos de cache no navegador
     * @param int $sMaxAge Tempo em segundos de cache na CDN
     */
    public static function sendCacheHeaders($maxAge,$sMaxAge){
        header("Cache-Control: max-age=".$maxAge.", s-maxage=".$sMaxAge);
    }
    
    public static function sendCacheHeadersStatic($uri=""){
        if($uri == ""){
            $uri = $_SERVER["REQUEST_URI"];
        }
        
        // diário
        if (preg_match("/(\.)(js|css|txt|json|xml)/", $uri)) {
            self::sendCacheHeaders(3600, 86401);
            return;
        }
        
        // semanal
        if (preg_match("/(\.)(webp|jpg|jpeg|gif|png|bmp|svg)/", $uri)) {
            self::sendCacheHeaders(3600, 604800);
            return;
        }
        
        // anual
        if (preg_match("/(\.)(woff|woff2|ttf)/", $uri)) {
            self::sendCacheHeaders(3600, 86400);
            return;
        }
        
        // padrão (diário)
        self::sendCacheHeaders(3600, 86400);
    }
    
    /**
     * Informa o cliente para não fazer cache
     */
    public static function sendHeadersNoCache(){
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
    }
    
    public static function parseCacheControl($header){
        $cacheControl = array();
        $matches = null;
        preg_match_all('#([a-zA-Z][a-zA-Z_-]*)\s*(?:=(?:"([^"]*)"|([^ \t",;]*)))?#', $header, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $cacheControl[strtolower($match[1])] = isset($match[2]) && $match[2] ? $match[2] : (isset($match[3]) ? $match[3] : true);
        }
        return $cacheControl;
    }
    
    public static function curl2($url, $method = "GET", $data = null, $options = null) {
        if (!function_exists("curl_init")) {
            throw new Exception("A biblioteca curl não esta disponível", -1);
        }
        
        if ($data === null) {
            $data = array();
        }
        
        if (!is_array($options)) {
            $options = array();
        }
        
        // opções default
        if (empty($options)) {
            $options[CURLOPT_TIMEOUT] = 60;
            $options[CURLOPT_CONNECTTIMEOUT] = 30;
            $options[CURLOPT_USERAGENT] = "php";
        }
        
        $ch = curl_init();
        if ($ch === false) {
            throw new Exception("Não foi possível initializar curl (curl_init), verifique se a URL " . $url . " esta acessível", -2);
        }
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 30);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        
        // ignora erros de ssl
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        
        // setando opções definidas pelo usuário
        foreach ($options AS $key => $value) {
            curl_setopt($ch, $key, $value);
        }
        
        // método da requisição
        switch ($method) {
            case "POST":
                curl_setopt($ch, CURLOPT_POST, 1);
                break;
            case "GET":
                curl_setopt($ch, CURLOPT_POSTFIELDS, null);
                curl_setopt($ch, CURLOPT_POST, false);
                curl_setopt($ch, CURLOPT_HTTPGET, true);
                break;
            case "HEAD":
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "HEAD");
                curl_setopt($ch, CURLOPT_NOBODY, true);
                break;
            default:
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
                break;
        }
        
        // dados do corpo da requisição
        // a função http_build_query já codifica os campos
        // Atenção! Essa função tem que ser testada com binarios e upload de arquivos
        if ($data !== null) {
            if (is_array($data)) {
                $fieldsString = http_build_query($data);
                if (!empty($data)) {
                    // necessário para que o outro lado entenda que os parâmetros estão codificados
                    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                        'Content-Type: application/x-www-form-urlencoded',
                        'Cache-Control: no-cache'
                    ));
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $fieldsString);
                }
            } elseif (is_string($data) AND trim($data) != "") {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            }
        }
        
        $response = curl_exec($ch);
        
        if ($response === false) {
            $errorCode = intval(curl_errno($ch));
            $errorList = array(
                1 => "Protocolo desconhecido",
                3 => "URL incorreta",
                5 => "Host do proxy não encontrado",
                6 => "Host não encontrado",
                7 => "Erro em conectar no host ou proxy",
                9 => "Acesso negado",
                22 => "Erro na requisição",
                26 => "Erro na leitura",
                27 => "Falta de memória",
                28 => "Timeout",
                47 => "Limite de redirecionamento atingido",
                55 => "Erro de rede no envio de dados",
                56 => "Erro de rede na leitura de dados",
            );
            $errorMessage = $errorList[$errorCode];
            if (mb_strlen($errorMessage) <= 0) {
                $errorMessage = "Erro desconhecido em executar curl, verifique se a URL " . $url . " esta acessível";
            }
            
            // concatenando informações adicionais
            $errorMessage = "[" . $errorCode . "][" . $url . "] " . $errorMessage;
            
            throw new Exception($errorMessage, $errorCode);
        }
        $curlInfo = curl_getinfo($ch);
        
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headersRaw = substr($response, 0, $header_size);
        $body = substr($response, $header_size);
        
        curl_close($ch);
        
        // organizando
        $lastHeaders = null;
        $headers = array();
        $level1List = explode("\r\n\r\n",$headersRaw);
        foreach($level1List AS $level1Content){
            $request = array();
            if($level1Content == ""){
                continue;
            }
            
            $lines = explode("\r\n",$level1Content);
            foreach($lines AS $line){
                list ($key, $value) = explode(":",$line,2);
                
                $request[] = array(
                    "key" => trim($key),
                    "value" => trim($value)
                );
            }
            
            $lastHeaders = $request;
            $headers[] = $request;
        }
        
        $obj = new StdClass();
        $obj->headersRaw  = $headersRaw;
        $obj->headers     = $headers;
        $obj->lastHeaders = $lastHeaders;
        $obj->body        = $body;
        $obj->curlInfo    = $curlInfo;
        return $obj;
    }
    
    public static function curl($url, $method, $data = null, $options = null, &$curlInfo=null){
        if(!function_exists("curl_init")){
            throw new Exception("A biblioteca curl não esta disponível",-1);
        }
        
        if(!is_array($data)){
            $data = array();
        }
        if(!is_array($options)){
            $options = array();
        }
        
        // opções default
        if(sizeof($options) <= 0){
            $options[CURLOPT_TIMEOUT] = 60;
            $options[CURLOPT_CONNECTTIMEOUT] = 30;
            $options[CURLOPT_USERAGENT] = "php";
        }
        
        $ch = curl_init();
        if($ch === false){
            throw new Exception("Não foi possível initializar curl (curl_init), verifique se a URL ".$url." esta acessível",-2);
        }
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 30);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        
        $parts = parse_url($url);
        $ip = gethostbyname($parts["host"]);
        
        // suporte a proxy (só usa se for endereço da internet)
        $proxy = System::get("proxy");
        if($proxy["enabled"] AND $ip != "127.0.0.1"){
            curl_setopt($ch, CURLOPT_PROXY, $proxy["host"]);
            curl_setopt($ch, CURLOPT_PROXYPORT, $proxy["port"]);
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy["user"].":".$proxy["password"]);
            curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
            curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC);
        }
        
        // ignora erros de ssl
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, \zion\DEFAULT_CURLOPT_SSL_VERIFYHOST);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, \zion\DEFAULT_CURLOPT_SSL_VERIFYPEER);
        
        // setando opções definidas pelo usuário
        foreach($options AS $key => $value){
            curl_setopt($ch, $key, $value);
        }
        
        // campos POST
        // aplicando urlencode nos valores
        $fields = array();
        foreach($data AS $key => $value){
            if(is_string($value)){
                $fields[$key] = urlencode($value);
            }else{
                $fields[$key] = $value;
            }
        }
        
        // comentando jeito antigo por causa de upload de arquivos
        //$fieldsString = implode("&",$fields);
        
        $fieldsString = $fields;
        if(sizeof($fields) > 0){
            curl_setopt($ch,CURLOPT_POST, count($fields));
            curl_setopt($ch,CURLOPT_POSTFIELDS, $fieldsString);
        }
        
        $response = curl_exec($ch);
        if($response === false){
            $errorCode = intval(curl_errno($ch));
            $errorList = array(
                1 => "Protocolo desconhecido",
                3 => "URL incorreta",
                5 => "Host do proxy não encontrado",
                6 => "Host não encontrado",
                7 => "Erro em conectar no host ou proxy",
                9 => "Acesso negado",
                22 => "Erro na requisição",
                26 => "Erro na leitura",
                27 => "Falta de memória",
                28 => "Timeout",
                47 => "Limite de redirecionamento atingido",
                55 => "Erro de rede no envio de dados",
                56 => "Erro de rede na leitura de dados",
            );
            $errorMessage = $errorList[$errorCode];
            if(mb_strlen($errorMessage) <= 0){
                $errorMessage = "Erro desconhecido em executar curl, verifique se a URL ".$url." esta acessível";
            }
            
            // concatenando informações adicionais
            $errorMessage = "[".$errorCode."][".$url."] ".$errorMessage;
            
            throw new Exception($errorMessage,$errorCode);
        }
        $curlInfo = curl_getinfo($ch);
        
        curl_close($ch);
        return $response;
    }
    
    public static function status($status,$reason=""){
        if($reason == ""){
            $messages = array(
                100	=> "Continue",
                101	=> "Switching Protocols",
                102	=> "Processing",
                103	=> "Early Hints",
                
                200	=> "OK",
                201	=> "Created",
                202	=> "Accepted",
                203	=> "Non-Authoritative Information",
                204	=> "No Content",
                205	=> "Reset Content",
                206	=> "Partial Content",
                207	=> "Multi-Status",
                208	=> "Already Reported",
                226	=> "IM Used",
                
                300	=> "Multiple Choices",
                301	=> "Moved Permanently",
                302	=> "Found",
                303	=> "See Other",
                304	=> "Not Modified",
                305	=> "Use Proxy",
                307	=> "Temporary Redirect",
                308	=> "Permanent Redirect",
                
                400	=> "Bad Request",
                401	=> "Unauthorized",
                402	=> "Payment Required",
                403	=> "Forbidden",
                404	=> "Not Found",
                405	=> "Method Not Allowed",
                406	=> "Not Acceptable",
                407	=> "Proxy Authentication Required",
                408	=> "Request Timeout",
                409	=> "Conflict",
                410	=> "Gone",
                411	=> "Length Required",
                412	=> "Precondition Failed",
                413	=> "Payload Too Large",
                414	=> "URI Too Long",
                415	=> "Unsupported Media Type",
                416	=> "Range Not Satisfiable",
                417	=> "Expectation Failed",
                421	=> "Misdirected Request",
                422	=> "Unprocessable Entity",
                423	=> "Locked",
                424	=> "Failed Dependency",
                425	=> "Too Early",
                426	=> "Upgrade Required",
                428	=> "Precondition Required",
                429	=> "Too Many Requests",
                431	=> "Request Header Fields Too Large",
                451	=> "Unavailable For Legal Reasons",
                
                500	=> "Internal Server Error",
                501	=> "Not Implemented",
                502	=> "Bad Gateway",
                503	=> "Service Unavailable",
                504	=> "Gateway Timeout",
                505	=> "HTTP Version Not Supported",
                506	=> "Variant Also Negotiates",
                507	=> "Insufficient Storage",
                508	=> "Loop Detected",
                510	=> "Not Extended",
                511	=> "Network Authentication Required"
            );
            
            if(array_key_exists($status, $messages)){
                $reason = $messages[$status];
            }
        }
        
        header("HTTP/1.1 ".$status." ".$reason);
    }
    
    public static function addRandomParam($url) : string {
        if(strpos($url,"?") === false){
            $url .= "?r=".date("YmdHis");
        }else{
            $url .= "&r=".date("YmdHis");
        }
        return $url;
    }
    
    /**
     * Tenta Obter o IP real do cliente
     * @return string
     * @see http://rubsphp.blogspot.com.br/2010/12/obter-o-ip-do-cliente.html
     */
    public static function getClientIP()
    {
        if (array_key_exists('HTTP_CLIENT_IP', $_SERVER)) {
            $ip = trim($_SERVER['HTTP_CLIENT_IP']);
            if (Validation::isIPv4($ip)) {
                return $ip;
            }
        }
        if (array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER)) {
            $ip = trim($_SERVER['HTTP_X_FORWARDED_FOR']);
            if (Validation::isIPv4($ip)) {
                return $ip;
            } elseif (mb_strpos($ip, ',') !== false) {
                $ips = explode(',', $ip);
                foreach ($ips as $ip) {
                    $ip = trim($ip);
                    if (Validation::isIPv4($ip)) {
                        return $ip;
                    }
                }
            } elseif (mb_strpos($ip, ';') !== false) {
                $ips = explode(';', $ip);
                foreach ($ips as $ip) {
                    $ip = trim($ip);
                    if (Validation::isIPv4($ip)) {
                        return $ip;
                    }
                }
            }
        }
        if (array_key_exists('REMOTE_ADDR', $_SERVER)) {
            $ip = trim($_SERVER['REMOTE_ADDR']);
            if (Validation::isIPv4($ip)) {
                return $ip;
            }
        }
        
        return '0.0.0.0';
    }
    
    /**
     * @see https://stackoverflow.com/questions/5483851/manually-parse-raw-multipart-form-data-data-with-php
     * @see https://gist.github.com/cwhsu1984/3419584ad31ce12d2ad5fed6155702e2
     * @return mixed[]
     * @supress
     */
    public static function parsePost(){
        $a_data = [];
        
        // read incoming data
        $input = file_get_contents('php://input');
        // grab multipart boundary from content type header
        $matches = null;
        preg_match('/boundary=(.*)$/', $_SERVER['CONTENT_TYPE'], $matches);
        // content type is probably regular form-encoded
        if (!count($matches))
        {
            // we expect regular puts to containt a query string containing data
            parse_str(urldecode($input), $a_data);
            return $a_data;
        }
        $boundary = $matches[1];
        // split content by boundary and get rid of last -- element
        $a_blocks = preg_split("/-+$boundary/", $input);
        array_pop($a_blocks);
        $keyValueStr = '';
        // loop data blocks
        foreach ($a_blocks AS $block)
        {
            if (empty($block))
                continue;
                // you'll have to var_dump $block to understand this and maybe replace \n or \r with a visibile char
                // parse uploaded files
                if (strpos($block, 'application/octet-stream') !== FALSE)
                {
                    // match "name", then everything after "stream" (optional) except for prepending newlines
                    preg_match("/name=\"([^\"]*)\".*stream[\n|\r]+([^\n\r].*)?$/s", $block, $matches);
                    $a_data['files'][$matches[1]] = $matches[2];
                }
                // parse all other fields
                else
                {
                    // match "name" and optional value in between newline sequences
                    preg_match('/name=\"([^\"]*)\"[\n|\r]+([^\n\r].*)?\r$/s', $block, $matches);
                    $keyValueStr .= $matches[1]."=".$matches[2]."&";
                }
        }
        $keyValueArr = [];
        parse_str($keyValueStr, $keyValueArr);
        return array_merge($a_data, $keyValueArr);
    }
}
?>