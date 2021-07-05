<?php 
namespace zion\net;

/**
 * @author Vinicius
 * @since 27/05/2019
 */
class TCP {
    /**
     * Verifica se o ip informado é local ou de internet
     * @param string $ip
     * @return boolean
     */
    public static function isPrivateIP($ip) {
        if($ip == "localhost" || strpos($ip, "127.0.0.") === 0) {
            return true;
        }
        
        $ip = ip2long($ip);
        $net_a = ip2long('10.255.255.255') >> 24;
        $net_b = ip2long('172.31.255.255') >> 20;
        $net_c = ip2long('192.168.255.255') >> 16;
        
        return $ip >> 24 === $net_a || $ip >> 20 === $net_b || $ip >> 16 === $net_c;
    }
}
?>