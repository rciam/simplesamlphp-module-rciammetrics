<?php

namespace SimpleSAML\Module\rciammetrics;

/**
 * Proxy Statistics utilities.
 *
 * @author Nicolas Liampotis <nliam@grnet.gr>
 */

class Utils
{

    /**
     * Retrieve original IP address of client.
     *
     * @return string
     */
    public static function getClientIpAddress():string
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

    /**
     * Generate a UUIDv4
     *
     * @return string
     */
    public static function generateUUIDv4(): string
    {
        // Generate 16 random bytes (128 bits)
        $data = random_bytes(16);

        // Set the version (4) and variant bits
        $data[6] = chr((ord($data[6]) & 0x0F) | 0x40); // version 4
        $data[8] = chr((ord($data[8]) & 0x3F) | 0x80); // variant 2 (RFC 4122)

        // Convert binary UUID to a human-readable string
        $uuidString = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));

        return $uuidString;
    }

}
