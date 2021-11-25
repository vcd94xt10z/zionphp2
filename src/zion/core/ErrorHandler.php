<?php
namespace zion\core;

use Exception;
use zion\orm\ObjectVO;
use zion\utils\TextFormatter;
use zion\utils\HTTPUtils;

/**
 * @author Vinicius
 */
class ErrorHandler {
	public static $showErrors = false;

    /**
     * Todas as exceções serão redirecionadas para este método
     * @param Exception $e
     */
    public static function handleException($e){
        HTTPUtils::status(500);
        HTTPUtils::sendHeadersNoCache();
        $message = "";
        
        if (!self::$showErrors) {
            $message .= "Sistema indisponível no momento, já registramos o problema e estaremos corrigindo assim que possível.\n"; 
            $message .= "Você pode atualizar a página ou tentar mais tarde.\n";
            $message .= "Se o problema persistir, contate o administrador.\n";
        }else{
            $message = $e->getFile()." on ".$e->getLine()." [".get_class($e)."]: ".$e->getMessage();
        }
        
        http_response_code(500);
		echo $message;
        exit();
    }
    
	/**
	 * Todos os erros serão redirecionados para este método
	 */
	public static function handleError($errno, $errstr, $errfile, $errline){
		$type_str = "";
		switch($errno){
			case 1: $type_str = 'ERROR'; break;
			case 2: $type_str = 'WARNING'; break;
			case 4: $type_str = 'PARSE'; break;
			case 8: $type_str = 'NOTICE'; break;
			case 16: $type_str = 'CORE_ERROR'; break;
			case 32: $type_str = 'CORE_WARNING'; break;
			case 64: $type_str = 'COMPILE_ERROR'; break;
			case 128: $type_str = 'COMPILE_WARNING'; break;
			case 256: $type_str = 'USER_ERROR'; break;
			case 512: $type_str = 'USER_WARNING'; break;
			case 1024: $type_str = 'USER_NOTICE'; break;
			case 2048: $type_str = 'STRICT'; break;
			case 4096: $type_str = 'RECOVERABLE_ERROR'; break;
			case 8192: $type_str = 'DEPRECATED'; break;
			case 16384: $type_str = 'USER_DEPRECATED'; break;
		}

		// se for NOTICE, nem faz log e nem exibe mensagem para o usuário
		if($type_str == "NOTICE"){
			return true;
		}
		
		$begin = floatval($_SERVER["REQUEST_TIME"]);
		$end   = microtime(true);
		
		$data = array();
		$data["type"]        = "php-error";
		$data["created"]     = new \DateTime();
		$data["duration"]    = round($end - $begin,2);
		$data["http_ipaddr"] = $_SERVER["REMOTE_ADDR"];
		$data["http_method"] = $_SERVER["REQUEST_METHOD"];
		$data["http_uri"]    = $_SERVER["REQUEST_URI"];
		$data["level"]       = $type_str;
		$data["code"]        = $errno;
		$data["message"]     = $errstr;
		$data["file"]        = $errfile;
		$data["line"]        = $errline;
		$data["stack"]       = "";
		$data["input"]       = "";
		
		// warning só vai para o log, a execução deve continuar normalmente
		if($type_str == "WARNING"){
			//return true; // desativado
		}
		
		http_response_code(500);
		HTTPUtils::sendHeadersNoCache();
		echo "[".$type_str."](".$errno.") ".$errstr." ".$errfile.":".$errline;
		
		// colocando exit porque senão o php pode continuar executando a página
		// depois do erro (tipo um warning) e passando novamente neste método
		// replicando a página de erro para o usuário.
		exit();

		// http://php.net/manual/pt_BR/function.set-error-handler.php
		/* Don't execute PHP internal error handler */
		return true;
	}

	/**
	 * Exibe uma exceção de uma forma amigável
	 * @param Exception $e
	 */
	public static function showErrorException(\Throwable $e){
	    $code = file($e->getFile(),FILE_IGNORE_NEW_LINES);
	    $buffer = "";
	    $i=1;

	    $beginLine = max($e->getLine()-4,0);
	    $finalLine = min($e->getLine()+4,sizeof($code));

	    foreach($code AS $line){
	        if($i >= $beginLine AND $i <= $finalLine){
	            $number = str_pad($i, 2,"0",STR_PAD_LEFT);

	            if($e->getLine() == $i){
	                $buffer .= $number." <span style='color: #f00'>".$line."</span>\n";
	            }else{
	                $buffer .= $number." ".$line."\n";
	            }
	        }
	        $i++;
	    }

	    echo "<h1>".$e->getMessage()."</h1>";
	    echo "<hr>";
	    echo "<pre style='font-size:12px !important'><code>".$buffer."</code></pre>";
	    echo "<hr>";
	    echo "<div>".$e->getFile()." [".$e->getLine()."]</div>";
	}
}
?>
