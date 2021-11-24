<?php
namespace zion\utils;

use Exception;
use DateTime;
use StdClass;

/**
 * @author Vinicius Cesar Dias
 */
class CertUtils {
    public static function getPFXInfo($content,$password){
        $obj = new StdClass();
        $obj->validFrom     = null;
        $obj->validTo       = null;
        $obj->subject       = new StdClass();
        $obj->subject->CN   = "";
        $obj->issuer        = new StdClass();
        $obj->issuer->CN    = "";
        $obj->daysToExpire  = 0;
        $obj->raw           = null;
        $obj->error         = "";

        $x509certdata = null;
        if (!openssl_pkcs12_read($content, $x509certdata, $password)) {
            $obj->error = "Erro ao ler certificado PFX (openssl_pkcs12_read)";
            return $obj;
        }

        $CertPriv = openssl_x509_parse(openssl_x509_read($x509certdata['cert']));

        $obj->validFrom     = new DateTime(date(DATE_RFC2822,$CertPriv['validFrom_time_t']));
        $obj->validTo       = new DateTime(date(DATE_RFC2822,$CertPriv['validTo_time_t']));
        $obj->subject->CN  = $CertPriv["subject"]["CN"];
        $obj->issuer->CN   = $CertPriv["issuer"]["CN"];
        $obj->daysToExpire = round(DateTimeUtils::getSecondsDiff($obj->validTo,new DateTime()) / 86400,2);
        $obj->raw          = $CertPriv;

        return $obj;
    }
}
?>