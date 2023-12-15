<?php

namespace SimpleSAML\Module\proxystatistics;

/**
 * Proxy Statistics utilities.
 *
 * @author Nicolas Liampotis <nliam@grnet.gr>
 */

class Utils
{

    /**
     * Retrieve original IP address of client.
     */
    public function getClientIpAddress():string
    {

        $ip_keys = [
            // Providers
            'HTTP_CF_CONNECTING_IP',    // Cloudflare
            'HTTP_INCAP_CLIENT_IP',     // Incapsula
            'HTTP_X_CLUSTER_CLIENT_IP', // RackSpace
            'HTTP_TRUE_CLIENT_IP',      // Akamai
            // Proxies
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_CLIENT_IP',
            'HTTP_X_REAL_IP',
            'HTTP_FORWARDED',
            'HTTP_FORWARDED_FOR',
            // Standard fallback
            'REMOTE_ADDR',
        ];

        foreach ($ip_keys as $key) {
            if(!isset($_SERVER[$key])) {
                continue;
            }
            $value = $_SERVER[$key];
            if (!empty($value) && is_string($value)) {
                foreach (explode(',', $value) as $ip) {
                    // trim for safety measures
                    $ip = trim($ip);
                    // attempt to validate IP
                    if (
                        filter_var($ip, FILTER_VALIDATE_IP,
                            FILTER_FLAG_NO_RES_RANGE
                        )
                    ) {
                        return $ip;
                    }
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'];
    }
}
