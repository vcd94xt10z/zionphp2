<?php
if(\zion\ENV == "PERFECTWORLD"){
    define("zion\DEFAULT_CURLOPT_SSL_VERIFYHOST",2);
    define("zion\DEFAULT_CURLOPT_SSL_VERIFYPEER",true);
}else{
    define("zion\DEFAULT_CURLOPT_SSL_VERIFYHOST",0);
    define("zion\DEFAULT_CURLOPT_SSL_VERIFYPEER",false);
}
?>