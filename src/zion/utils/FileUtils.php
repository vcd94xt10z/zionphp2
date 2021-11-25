<?php
namespace zion\utils;

use ZipArchive;
use Exception;

/**
 * @author Vinicius Cesar Dias
 */
class FileUtils {
    /**
     * Faz download de um arquivo para o disco a partir de uma URL
     * @param string $url
     * @param string $file
     * @return boolean
     */
    public static function downloadToDisk($url,$file){
        $content = file_get_contents($url);
        $f = fopen($file,"w");
        if($f === false){
            return false;
        }
        
        $bytes = fwrite($f,$content);
        fclose($f);
        
        return ($bytes !== false);
    }
    
    /**
     * Conta quantas linhas um arquivo tem
     * @param string $file
     * @return number
     */
    public static function countLines($file){
        $linecount = 0;
        $handle = fopen($file, "r");
        while(!feof($handle)){
            fgets($handle);
            $linecount++;
        }
        fclose($handle);
        return $linecount;
    }
    
    /**
     * Conta quantos arquivo tem dentro de um diretório recursivamente
     * @param string $folder
     * @param string $extension
     * @return number
     */
	public static function getFolderFilesCount($folder,$extension=null){
		$folder = rtrim($folder,\DS).\DS;
		$files = scandir($folder);
		$total = 0;
		foreach($files AS $filename){
			if($filename == "." || $filename == ".."){
				continue;
			}
			
			$file = $folder.$filename;
			if(is_file($file)){
				$total += 1;
			}else{
				$total += self::getFolderFilesCount($file,$extension);
			}
		}
		return $total;
	}
	
	/**
	 * @SuppressErrors
	 * @SuppressWarnings
	 */
	public static function getFolderSize($folder){
		if(ServerUtils::getSOName() == "Windows"){
			$obj = new \COM ( 'scripting.filesystemobject' );
			if ( is_object ( $obj ) ){
				$ref = $obj->getfolder ( $folder );
				$size = $ref->size;
				$obj = null;
				return $size;
			}
			return -1;
		}else{
		    throw new Exception("Não implementado para o SO ".ServerUtils::getSOName());
		}
	}
	
	/**
	 * Verifica se o diretório esta vazio
	 * @param string $dir
	 * @return boolean
	 */
	public static function isEmptyFolder($dir){
		if (!is_readable($dir)) return null;
		$handle = opendir($dir);
		while (false !== ($entry = readdir($handle))) {
			if ($entry != "." && $entry != "..") {
				return false;
			}
		}
		return true;
	}
	
	/** 
	 * A mesma função do file_exists mas ignorando case
	 */
	public static function exists(&$filepath){
		$basename = strtolower(basename($filepath));
		$folder = rtrim(dirname($filepath).\DS).\DS;
		$files = scandir($folder);
		foreach($files AS $filename){
			if($filename == "." || $filename == ".."){
				continue;
			}
			if(strtolower($filename) == $basename){
				// atualizando nome correto do arquivo
				$filepath = $folder.$filename;
				
				return true;
			}			
		}
		return false;
	}
	
	/**
	 * Verifica se pode deletar um arquivo
	 * @param string $file
	 * @return boolean
	 */
	public static function canDelete($file){
		$result = false;
		if(is_file($file)){
			$fh = @fopen($file, "r+");
			if($fh){
				$result = true;
				fclose($fh);
			}
		}
		return $result;
	}
	
	/**
	 * Deleta somente os arquivos do diretório informado, ignorando sub diretórios
	 * @param string $folder
	 * @return bool
	 */
	public static function deleteFolderFiles(string $folder) : bool {
	    $folder = trim($folder);
	    $invalid = array("",".","..","/");
	    
	    // diretório inválido
	    if(in_array($folder,$invalid)){
	        return false;
	    }
	    
	    // não é diretório ou não existe
	    if(!is_dir($folder) OR !file_exists($folder)){
	        return false;
	    }
	    
	    // corrigindo path
	    $folder = rtrim($folder,\DS).\DS;
	    
	    // obtendo arquivos e diretórios
	    $files = scandir($folder);
	    
	    // o diretório esta vazio, removendo-o
	    if(sizeof($files) == 0){
	        return true;
	    }
	    
	    foreach($files AS $filename){
	        if($filename == "." OR $filename == ".."){
	            continue;
	        }
	        
	        $file = $folder.$filename;
	        if(!is_dir($file)){
	            unlink($file);
	        }
	    }
	    return true;
	}
	
	/**
	 * Remove um diretório e todo seu conteúdo
	 */
	public static function deleteFolder($folder,array &$log = null){
		$folder = trim($folder);
		$invalid = array("",".","..","/");
		
		// diretório inválido
		if(in_array($folder,$invalid)){
			return false;
		}
		
		// não é diretório ou não existe
		if(!is_dir($folder) OR !file_exists($folder)){
			return false;
		}
		
		// corrigindo path
		$folder = rtrim($folder,\DS).\DS;
		
		// obtendo arquivos e diretórios
		$files = scandir($folder);
		
		// o diretório esta vazio, removendo-o
		if(sizeof($files) == 0){
			$log[] = "rmdir ".$folder;
			return rmdir($folder);
		}
		
		foreach($files AS $filename){
			if($filename == "." OR $filename == ".."){
				continue;
			}
			
			$file = $folder.$filename;
			if(is_dir($file)){
				self::deleteFolder($file,$log);
			}else{
				$log[] = "unlink ".$file;
				unlink($file);
			}			
		}
		
		$log[] = "rmdir ".$folder;
		return @rmdir($folder);
	}
	
	/**
	 * Lista um diretório recursivamente
	 * @param $rootFolder
	 * @param array $allFiles
	 */
	public static function listFilesRecursively($rootFolder,array &$allFiles,array $ignoreFiles=array(),array $ignoreFilesAbs=array()){
		if($rootFolder == ""){
			$rootFolder = "/";
		}
		$rootFolder = rtrim($rootFolder,\DS).\DS;
		
		if(!is_dir($rootFolder) OR !file_exists($rootFolder)){
			return;
		}
		
		$files = scandir($rootFolder);
		foreach($files AS $filename){
			if(in_array($filename,$ignoreFiles)){
				continue;
			}
			if($filename == "." OR $filename == ".."){
				continue;
			}
			
			$file = $rootFolder.$filename;
			
			if(in_array($file,$ignoreFilesAbs)){
				continue;
			}
			
			if(!is_dir($file)){
				$allFiles[] = $file;
			}else{
				$allFiles[] = $file;
				self::listFilesRecursively($file,$allFiles,$ignoreFiles,$ignoreFilesAbs);
			}
		}
	}
	
	/**
	 * Cria diretórios em massa
	 * @param array $folders
	 * @param number $mask
	 */
	public static function bulkFolderCreator(array $folders,$mask=0777){
		foreach($folders AS $folder){
			if(!file_exists($folder)){
				mkdir($folder,$mask);
			}
		}
	}
	
	/**
	 * Retorna a extensão de um arquivo
	 * @param string $name
	 * @return string
	 */
    public static function getExtension($name){
    	// ultima indice do ponto
    	$index = strrpos($name,".");
    	if($index === false){
    		return "";
    	}
    	$tmp = explode(".",$name);
    	return $tmp[sizeof($tmp)-1];
    }
    
    /**
     * Zipa um arquivo
     * @param string $file
     * @param string $fileZIP
     * @param string $internalZipFile
     * @return boolean
     */
    public static function zipSingleFile($file,$fileZIP,$internalZipFile=null){
    	if($internalZipFile == null){
    		$internalZipFile = basename($file);
    	}
    	
    	$zip = new ZipArchive();
		if ($zip->open($fileZIP, ZipArchive::CREATE)!==TRUE) {
    		return false;
		}
		$zip->addFile($file,$internalZipFile);
		$zip->close();
		return true;
    }
    
    /**
     * Extrai o conteúdo de um arquivo zip em um diretório
     * @param string $zipFile
     * @param string $folder
     */
    public static function unzipFile(string $zipFile,string $folder){
        if(!file_exists($zipFile)){
            throw new Exception("O arquivo ".$zipFile." não existe");
        }
        $filesize = filesize($zipFile);
        if($filesize <= 0){
            throw new Exception("O arquivo ".$zipFile." tem tamanho zero");
        }
        if(!is_dir($folder)){
            throw new Exception("O diretório informado não é um diretório");
        }
        
        $zip = new ZipArchive();
        $res = $zip->open($zipFile);
        if ($res !== true) {
            throw new Exception("Erro em abrir arquivo zip");
        }
        $zip->extractTo($folder);
        $zip->close();
    }
    
    /**
     * Monta uma lista de arquivos e diretórios
     * @param string $folder
     * @param array $allFiles
     * @return string[]
     */
    public static function buildFileListRecursively($folder,&$allFiles=null){
		if(!is_array($allFiles)){
			$allFiles = array();
		}
		$folder = realpath($folder).DIRECTORY_SEPARATOR;
		$files = scandir($folder);
		foreach ($files AS $filename){
			if($filename == "." || $filename == ".."){
				continue;
			}
			$file = $folder.$filename;
			if(is_file($file)){
				$allFiles[] = $file;
			}else{
				$allFiles[] = $file;
				self::buildFileListRecursively($file,$allFiles);
			}
		}
		return $allFiles;
	}
    
    /**
	 * Zipa tudo que tiver dentro do diretório (diretórios, arquivos e sub diretórios recursivamente)
	 */
	public static function zipDirectoryMultiLevel($inputDir,&$zipFile,&$zip=null,$rootFolder=null){
		$inputDir = realpath($inputDir).DIRECTORY_SEPARATOR;
		$zip = new ZipArchive();
		if ($zip->open($zipFile, ZIPARCHIVE::CREATE) !== TRUE) {
			throw new Exception ("Could not open archive");
		}
		$files = self::buildFileListRecursively($inputDir);
		foreach ($files AS $file){
			// ATENÇÃO! No zip deve ter um padrão de separador de diretórios, unificando
			// para ficar / tanto no Linux, como no Windows e outros sistemas, evitando problemas
			$folderZip = str_replace($inputDir,"",$file);
			$folderZip = str_replace("\\","/",$folderZip);
			
			if(is_file($file)){
				$zip->addFile($file,$folderZip);
			}else{
				$zip->addEmptyDir($folderZip);
			}
		}
		$zip->close();
	}
	
	/**
	 * Faz o download de qualquer arquivo
	 */
	public static function download($file,$name=null,$cache=false){
		// validação
		if(!file_exists($file)){
			return;
		}
		if($name == null){
			$name = basename($file);
		}
		
		// detectando content type
		$extension = pathinfo($file, PATHINFO_EXTENSION);		
		$contentType = self::getContentType($extension);
		
		if(!$cache){
			header("Cache-Control: no-store, no-cache, max-age=0");
			header("Pragma: no-cache");
		}
		
		header("X-Download-Options: open "); // For IE8
		header("X-Content-Type-Options: sniff"); // For IE8
		header("Content-Description: File Transfer");
		header("Content-Disposition: attachment; filename=".$name);
		header("Content-Type: ".$contentType);
		if(self::isBinaryFileByExtension($extension)){
			header("Content-Transfer-Encoding: binary");
		}
		header("Content-Length: ".filesize ($file));
		readfile($file);
	}
	
	/**
	 * Carrega o arquivo como plugin no navegador do cliente
	 */
	public static function inline($file,$name="",$cache=false){
		// validação
		if(!is_file($file)){
			return;
		}
		if($name == null){
			$name = basename($file);
		}
		
		// detectando content type
		$extension = pathinfo($file, PATHINFO_EXTENSION);
		if($extension == ""){
			// extraindo extensão
			$extension = self::getExtension($name);
		}
		
		$contentType = self::getContentType($extension);
		if($contentType == ""){
			$contentType = "application/octet-stream";
		}
		
		if(!$cache){
			header("Cache-Control: no-store, no-cache, max-age=0");
			header("Pragma: no-cache");
		}
		
		header("X-Download-Options: noopen "); // For IE8
		header("X-Content-Type-Options: nosniff"); // For IE8
		header("Content-Description: File Transfer");
		header("Content-Type: ".$contentType);
		if(self::isBinaryFileByExtension($extension)){
			header("Content-Transfer-Encoding: binary");
		}
		header("Content-disposition: inline; filename=".$name);
		header("Content-Length: ".filesize ($file));
		readfile($file);
	}
	
	/**
	 * Identifica se o arquivo é binario pela extensão
	 */
	public static function isBinaryFileByExtension($ext){
		$text = array("txt","log","sql","query","php","php5","jsp","css","js","cs","xml","asp","aspx");
		$binary = array (
			"bin","exe","pif",
			"doc","xls",".ppt","docx","xlsx",".pptx",
			"rtf","pdf","xps",
			"img","jpg","jpeg","png","gif","bmp",
			"class","com","dll"
		);
		if(in_array($ext,$text)){
			return false;
		}
		if(in_array($ext,$binary)){
			return true;
		}
		return true;
	}
	
	/**
	 * Retorna a lista de contents type mais
	 * importantes / utilizados
	 */
	public static function getContentTypeList(){
		$list = array (
			"evy"=>"application/envoy",
			"fif"=>"application/fractals",
			"spl"=>"application/futuresplash",
			"hta"=>"application/hta",
			"acx"=>"application/internet-property-stream",
			"hqx"=>"application/mac-binhex40",
			"doc"=>"application/msword",
			"dot"=>"application/msword",
			"*"=>"application/octet-stream",
			"bin"=>"application/octet-stream",
			"class"=>"application/octet-stream",
			"dms"=>"application/octet-stream",
			"exe"=>"application/octet-stream",
			"lha"=>"application/octet-stream",
			"lzh"=>"application/octet-stream",
			"oda"=>"application/oda",
			"axs"=>"application/olescript",
			"pdf"=>"application/pdf",
			"prf"=>"application/pics-rules",
			"p10"=>"application/pkcs10",
			"crl"=>"application/pkix-crl",
			"ai"=>"application/postscript",
			"eps"=>"application/postscript",
			"ps"=>"application/postscript",
			"rtf"=>"application/rtf",
			"setpay"=>"application/set-payment-initiation",
			"setreg"=>"application/set-registration-initiation",
			"xla"=>"application/vnd.ms-excel",
			"xlc"=>"application/vnd.ms-excel",
			"xlm"=>"application/vnd.ms-excel",
			"xls"=>"application/vnd.ms-excel",
			"xlt"=>"application/vnd.ms-excel",
			"xlw"=>"application/vnd.ms-excel",
			"msg"=>"application/vnd.ms-outlook",
			"sst"=>"application/vnd.ms-pkicertstore",
			"cat"=>"application/vnd.ms-pkiseccat",
			"stl"=>"application/vnd.ms-pkistl",
			"pot"=>"application/vnd.ms-powerpoint",
			"pps"=>"application/vnd.ms-powerpoint",
			"ppt"=>"application/vnd.ms-powerpoint",
			"mpp"=>"application/vnd.ms-project",
			"wcm"=>"application/vnd.ms-works",
			"wdb"=>"application/vnd.ms-works",
			"wks"=>"application/vnd.ms-works",
			"wps"=>"application/vnd.ms-works",
			"hlp"=>"application/winhlp",
			"bcpio"=>"application/x-bcpio",
			"cdf"=>"application/x-cdf",
			"z"=>"application/x-compress",
			"tgz"=>"application/x-compressed",
			"cpio"=>"application/x-cpio",
			"csh"=>"application/x-csh",
			"dcr"=>"application/x-director",
			"dir"=>"application/x-director",
			"dxr"=>"application/x-director",
			"dvi"=>"application/x-dvi",
			"gtar"=>"application/x-gtar",
			"gz"=>"application/x-gzip",
			"hdf"=>"application/x-hdf",
			"ins"=>"application/x-internet-signup",
			"isp"=>"application/x-internet-signup",
			"iii"=>"application/x-iphone",
			"js"=>"application/x-javascript",
			"latex"=>"application/x-latex",
			"mdb"=>"application/x-msaccess",
			"crd"=>"application/x-mscardfile",
			"clp"=>"application/x-msclip",
			"dll"=>"application/x-msdownload",
			"m13"=>"application/x-msmediaview",
			"m14"=>"application/x-msmediaview",
			"mvb"=>"application/x-msmediaview",
			"wmf"=>"application/x-msmetafile",
			"mny"=>"application/x-msmoney",
			"pub"=>"application/x-mspublisher",
			"scd"=>"application/x-msschedule",
			"trm"=>"application/x-msterminal",
			"wri"=>"application/x-mswrite",
			"cdf"=>"application/x-netcdf",
			"nc"=>"application/x-netcdf",
			"pma"=>"application/x-perfmon",
			"pmc"=>"application/x-perfmon",
			"pml"=>"application/x-perfmon",
			"pmr"=>"application/x-perfmon",
			"pmw"=>"application/x-perfmon",
			"p12"=>"application/x-pkcs12",
			"pfx"=>"application/x-pkcs12",
			"p7b"=>"application/x-pkcs7-certificates",
			"spc"=>"application/x-pkcs7-certificates",
			"p7r"=>"application/x-pkcs7-certreqresp",
			"p7c"=>"application/x-pkcs7-mime",
			"p7m"=>"application/x-pkcs7-mime",
			"p7s"=>"application/x-pkcs7-signature",
			"sh"=>"application/x-sh",
			"shar"=>"application/x-shar",
			"swf"=>"application/x-shockwave-flash",
			"sit"=>"application/x-stuffit",
			"sv4cpio"=>"application/x-sv4cpio",
			"sv4crc"=>"application/x-sv4crc",
			"tar"=>"application/x-tar",
			"tcl"=>"application/x-tcl",
			"tex"=>"application/x-tex",
			"texi"=>"application/x-texinfo",
			"texinfo"=>"application/x-texinfo",
			"roff"=>"application/x-troff",
			"t"=>"application/x-troff",
			"tr"=>"application/x-troff",
			"man"=>"application/x-troff-man",
			"me"=>"application/x-troff-me",
			"ms"=>"application/x-troff-ms",
			"ustar"=>"application/x-ustar",
			"src"=>"application/x-wais-source",
			"cer"=>"application/x-x509-ca-cert",
			"crt"=>"application/x-x509-ca-cert",
			"der"=>"application/x-x509-ca-cert",
			"pko"=>"application/ynd.ms-pkipko",
			"zip"=>"application/zip",
			"au"=>"audio/basic",
			"snd"=>"audio/basic",
			"mid"=>"audio/mid",
			"rmi"=>"audio/mid",
			"mp3"=>"audio/mpeg",
			"aif"=>"audio/x-aiff",
			"aifc"=>"audio/x-aiff",
			"aiff"=>"audio/x-aiff",
			"m3u"=>"audio/x-mpegurl",
			"ra"=>"audio/x-pn-realaudio",
			"ram"=>"audio/x-pn-realaudio",
			"wav"=>"audio/x-wav",
			"bmp"=>"image/bmp",
			"cod"=>"image/cis-cod",
			"gif"=>"image/gif",
			"ief"=>"image/ief",
			"jpe"=>"image/jpeg",
			"jpeg"=>"image/jpeg",
			"jpg"=>"image/jpeg",
			"png"=>"image/png",
			"jfif"=>"image/pipeg",
			"svg"=>"image/svg+xml",
			"xml"=>"text/xml",
			"tif"=>"image/tiff",
			"tiff"=>"image/tiff",
			"ras"=>"image/x-cmu-raster",
			"cmx"=>"image/x-cmx",
			"ico"=>"image/x-icon",
			"pnm"=>"image/x-portable-anymap",
			"pbm"=>"image/x-portable-bitmap",
			"pgm"=>"image/x-portable-graymap",
			"ppm"=>"image/x-portable-pixmap",
			"rgb"=>"image/x-rgb",
			"xbm"=>"image/x-xbitmap",
			"xpm"=>"image/x-xpixmap",
			"xwd"=>"image/x-xwindowdump",
			"mht"=>"message/rfc822",
			"mhtml"=>"message/rfc822",
			"nws"=>"message/rfc822",
			"css"=>"text/css",
			"323"=>"text/h323",
			"htm"=>"text/html",
			"html"=>"text/html",
			"stm"=>"text/html",
			"uls"=>"text/iuls",
			"bas"=>"text/plain",
			"c"=>"text/plain",
			"h"=>"text/plain",
			"txt"=>"text/plain",			
			"rtx"=>"text/richtext",
			"sct"=>"text/scriptlet",
			"tsv"=>"text/tab-separated-values",
			"htt"=>"text/webviewhtml",
			"htc"=>"text/x-component",
			"etx"=>"text/x-setext",
			"vcf"=>"text/x-vcard",
			"mp2"=>"video/mpeg",
			"mpa"=>"video/mpeg",
			"mpe"=>"video/mpeg",
			"mpeg"=>"video/mpeg",
			"mpg"=>"video/mpeg",
			"mpv2"=>"video/mpeg",
			"mov"=>"video/quicktime",
			"qt"=>"video/quicktime",
			"lsf"=>"video/x-la-asf",
			"lsx"=>"video/x-la-asf",
			"asf"=>"video/x-ms-asf",
			"asr"=>"video/x-ms-asf",
			"asx"=>"video/x-ms-asf",
			"avi"=>"video/x-msvideo",
			"movie"=>"video/x-sgi-movie",
			"flr"=>"x-world/x-vrml",
			"vrml"=>"x-world/x-vrml",
			"wrl"=>"x-world/x-vrml",
			"wrz"=>"x-world/x-vrml",
			"xaf"=>"x-world/x-vrml",
			"xof"=>"x-world/x-vrml",
			"hfv"=>"text/plain"
		);
				
		return $list;
	}
	
	/**
	 * Retorna o tipo de conteúdo a partir do arquivo
	 * @param string $file
	 * @return string
	 */
	public static function getContentTypeByFile($file){
	    $basename = basename($file);
	    
	    // detectando content type
	    $extension = pathinfo($basename, PATHINFO_EXTENSION);
	    if($extension == ""){
	        // extraindo extensão
	        $extension = self::getExtension($basename);
	    }
	    
	    $contentType = self::getContentType($extension);
	    if($contentType == ""){
	        $contentType = "application/octet-stream";
	    }
	    
	    return $contentType;
	}
	
	/**
	 * Retorna o ContentType da extensão
	 */
	public static function getContentType($ext){
		if($ext == null){
			return "";
		}
		$ext = strtolower($ext);
		$list = self::getContentTypeList();
		if(array_key_exists($ext,$list)){
			return $list[$ext];
		}
		return "";
	}
}
?>