<?php
namespace zion\utils;

use Exception;

/**
 * @author Vinicius
 */
class ImageUtils {
    /**
     * Otimiza as imagens de um upload
     * @param array $files
     * @param boolean $lossy
     */
	public static function optimizeUploadFiles($lossy=false) {
		// upload multiplo
		if(is_array($_FILES["tmp_name"])){
			for($i = 0;$i < sizeof($_FILES["tmp_name"]);$i++) {
				$fileSource = $fileDestiny = $_FILES["tmp_name"][$i];
				self::optimizeImage($fileSource, $fileDestiny, $lossy);
				$_FILES["size"][$i] = filesize($_FILES["tmp_name"][$i]);
			}
		}else{
			// upload simples
			$fileSource = $fileDestiny = $_FILES["tmp_name"];
			self::optimizeImage($fileSource, $fileDestiny, $lossy);
			$_FILES["size"] = filesize($fileDestiny);
		}
	}
	
	/**
	 * Otimiza uma imagem
	 */
	public static function optimizeImage($fileSource, $fileDestiny, $lossy=false, $provider="default") {
		$sourceMime = self::getMimeImage($fileSource);
		if(!in_array($sourceMime, array("image/jpeg", "image/png", "image/gif"))) {
			return;
		}
		
		if($provider == "default") {
			if($sourceMime == "image/png") {
				$provider = "pngquant";
			}else if($sourceMime == "image/jpeg") {
				$provider = "jpegoptim";
			}else {
				$provider = "gd";
			}
		}
		
		$sameFiles = false;
		if($fileSource == $fileDestiny) {
			$sameFiles = true;
			$fileDestiny = tempnam(sys_get_temp_dir(), "Tux").".tmp";
		}else{
			if(file_exists($fileDestiny)) {
				unlink($fileDestiny);
			}
		}
		
		switch(strtolower($provider)) {
			case "pngquant":
				self::optimizePngquant($fileSource, $fileDestiny, $lossy);
				break;
			case "jpegoptim":
				self::optimizeJpegoptim($fileSource, $fileDestiny, $lossy);
				break;
			case "gd":
				self::optimizeGD($fileSource, $fileDestiny);
				break;
			default:
				throw new Exception("Biblioteca não implementada");
				break;
		}
		
		if(!file_exists($fileDestiny)) {
			throw new Exception("Falha em otimizar arquivo");
		}
		
		clearstatcache();
		$sourceSize = filesize($fileSource);
		$destinySize = filesize($fileDestiny);
		
		if($destinySize >= $sourceSize) {
			unlink($fileDestiny);
			copy($fileSource, $fileDestiny);
		}
		
		if($sameFiles) {
			unlink($fileSource);
			copy($fileDestiny, $fileSource);
			unlink($fileDestiny);
		}
	}
	
	/**
	 * Otimiza utilizando a biblioteca pngquant
	 * @param string $fileSource
	 * @param string $fileDestiny
	 * @param boolean $lossy
	 */
	public static function optimizePngquant($fileSource, $fileDestiny, $lossy=false) {
		$max_quality = 85;
		$min_quality = 60;
		$options = "--force";
		if($lossy) {
			$options = "--force --quality=$min_quality-$max_quality";
		}
		
		$comm = "pngquant $options - < ".escapeshellarg($fileSource)." >> ".escapeshellarg($fileDestiny);
		shell_exec($comm);
	}
	
	/**
	 * Otimiza utilizando a biblioteca jpegoptim
	 * @param string $fileSource
	 * @param string $fileDestiny
	 * @param boolean $lossy
	 */
	public static function optimizeJpegoptim($fileSource, $fileDestiny, $lossy=false) {
		$max_quality = 85;
		$options = "--force --strip-all --all-progressive";
		if($lossy) {
			$options = "--force --strip-all --all-progressive --m=$max_quality";
		}
		
		$comm = "jpegoptim $options --stdout $fileSource > $fileDestiny";
		shell_exec($comm);
	}
	
	/**
	 * Otimiza utilizando a biblioteca GD
	 * @param string $fileSource
	 * @param string $fileDestiny
	 */
	public static function optimizeGD($fileSource,$fileDestiny) {
		$info = getimagesize($fileSource);
		
		switch($info["mime"]) {
		case "image/jpeg":
			$image = imagecreatefromjpeg($fileSource);
			$quality = 20; // (0-100) 0 = max compression
			imagejpeg($image, $fileDestiny, $quality);
			break;
		case "image/png":
			$image = imagecreatefrompng($fileSource);
			$quality = 7; // (0-9) 9 = max compression
			imagepng($image, $fileDestiny, $quality);
			break;
		case "image/gif":
			$image = imagecreatefromgif($fileSource);
			imagegif($image, $fileDestiny);
			break;
		}
		
		if($image !== null) {
			imagedestroy($image);
		}
	}
	
	/**
	 * Retorna o mime da imagem
	 * @param string $file
	 * @return mixed
	 */
	public static function getMimeImage($file) {
		if($file == ""){
			return "";
		}
		$info = getimagesize($file);
		return $info["mime"];
	}
	
	/**
	 * Retorna a extensão de um nome de arquivo
	 * @param string $name
	 * @return string
	 */
	public static function getExtensionByName($name) {
		$index = strrpos($name,".");
		if($index === false) return "";
		$tmp = explode(".",$name);
		return $tmp[sizeof($tmp)-1];
	}
}
?>