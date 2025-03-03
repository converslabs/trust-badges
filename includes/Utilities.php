<?php
namespace TrustBadges;

class Utilities {
    /**
     * Sanitize and validate IP address
     */
    public static function sanitize_ip_address($ip) {
        $ip = filter_var($ip, FILTER_VALIDATE_IP);
        return $ip ?: '0.0.0.0';
    }

    /**
     * Sanitize and validate nonce
     */
    public static function sanitize_nonce($nonce) {
        return preg_replace('/[^a-zA-Z0-9]/', '', $nonce);
    }

    /**
     * Get client IP address safely
     */
    public static function get_client_ip() {
        $ip_sources = array(
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        );

        foreach ($ip_sources as $key) {
            if (array_key_exists($key, $_SERVER)) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP)) {
                        return $ip;
                    }
                }
            }
        }

        return '0.0.0.0';
    }
}
