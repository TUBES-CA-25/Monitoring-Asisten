<?php

class JwtHandler {
    
    /**
     * Generate JWT Token
     * @param array $data - Data yang akan di-encode (user_id, role, dll)
     * @param int $expiration - Waktu expire dalam seconds
     * @return string JWT Token
     */
    public static function generateToken($data, $expiration = JWT_EXPIRATION) {
        // Header
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $header = rtrim(strtr(base64_encode($header), '+/', '-_'), '=');

        // Payload
        $payload = array_merge($data, [
            'iat' => time(),
            'exp' => time() + $expiration
        ]);
        $payload = rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');

        // Signature
        $signature = hash_hmac('sha256', "$header.$payload", JWT_SECRET, true);
        $signature = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

        return "$header.$payload.$signature";
    }

    /**
     * Validate & Decode JWT Token
     * @param string $token - JWT Token
     * @return array|false - Decoded payload atau false jika invalid
     */
    public static function verifyToken($token) {
        $parts = explode('.', $token);
        
        if (count($parts) !== 3) {
            return false;
        }

        list($header, $payload, $signature) = $parts;

        // Recreate signature
        $valid_signature = rtrim(strtr(base64_encode(
            hash_hmac('sha256', "$header.$payload", JWT_SECRET, true)
        ), '+/', '-_'), '=');

        // Timing-attack safe comparison (SEC-07)
        if (!hash_equals($valid_signature, $signature)) {
            return false;
        }

        // Decode payload
        $payload_decoded = json_decode(
            base64_decode(strtr($payload, '-_', '+/')),
            true
        );

        // Check expiration
        if ($payload_decoded['exp'] < time()) {
            return false; // Token expired
        }

        return $payload_decoded;
    }

    public static function getBearerToken() {
        $authHeader = null;
        
        // 1. Cek $_SERVER standar
        if (isset($_SERVER['Authorization'])) {
            $authHeader = $_SERVER['Authorization'];
        } 
        // 2. Cek HTTP_AUTHORIZATION (biasanya dari .htaccess RewriteRule)
        elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
        }
        // 3. Cek REDIRECT_HTTP_AUTHORIZATION (jika ada rewrite)
        elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }
        // 4. getallheaders (untuk lebih kompatibel dengan berbagai setup)
        elseif (function_exists('getallheaders')) {
            $headers = getallheaders();
            foreach ($headers as $name => $value) {
                if (strcasecmp($name, 'Authorization') == 0) {
                    $authHeader = $value;
                    break;
                }
            }
        }

        // Query parameter $_GET['token'] fallback dihapus (SEC-07: cegah kebocoran token ke log server)

        if ($authHeader) {
            if (preg_match('/Bearer\s+(\S+)/', $authHeader, $matches)) {
                return $matches[1];
            }
        }
        return false;
    }
}
?>