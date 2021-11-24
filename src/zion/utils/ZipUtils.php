<?php
namespace zion\utils;

use ZipArchive;
use Exception;

/**
 * @author Vinicius Cesar Dias
 */
class ZipUtils {
    public static function extractFile($fileZip,$folder){
        $zip = new ZipArchive();
        if ($zip->open($fileZip) === TRUE) {
            $zip->extractTo($folder);
            $zip->close();
        } else {
            throw new Exception("Erro ao extrair zip");
        }
    }
}
?>