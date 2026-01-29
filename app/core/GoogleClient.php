<?php
class GoogleClient {
    // GANTI DENGAN KREDENSIAL DARI GOOGLE CONSOLE ANDA
    private $client_id = '923620749646-fpi8v56rmjqevgct6vqp0k5i9tmgp8nu.apps.googleusercontent.com';
    private $client_secret = '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';
    private $redirect_uri = 'http://localhost/Code/Project_ICLabs/iclabs_v2/public/google/callback';

    private $token_url = 'https://oauth2.googleapis.com/token';
    private $auth_url = 'https://accounts.google.com/o/oauth2/auth';
    private $api_base = 'https://www.googleapis.com/calendar/v3';

    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    // 1. Generate URL Login Google
    public function getAuthUrl() {
        $params = [
            'client_id' => $this->client_id,
            'redirect_uri' => $this->redirect_uri,
            'response_type' => 'code',
            'scope' => 'https://www.googleapis.com/auth/calendar',
            'access_type' => 'offline', // Penting untuk dapat Refresh Token
            'prompt' => 'consent'
        ];
        return $this->auth_url . '?' . http_build_query($params);
    }

    // 2. Tukar Kode dengan Token (Saat Callback)
    public function authenticate($code) {
        $params = [
            'code' => $code,
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'redirect_uri' => $this->redirect_uri,
            'grant_type' => 'authorization_code'
        ];
        return $this->makeRequest($this->token_url, $params, 'POST');
    }

    // 3. Ambil Access Token User dari DB (Auto Refresh jika expired)
    public function getValidAccessToken($userId) {
        $this->db->query("SELECT * FROM user_google_token WHERE id_user = :uid");
        $this->db->bind(':uid', $userId);
        $tokenData = $this->db->single();

        if (!$tokenData) return null;

        // Cek Expired (Token Google biasanya valid 1 jam/3600 detik)
        // Kita kurangi 60 detik untuk buffer aman
        $expiryTime = strtotime($tokenData['created_at']) + $tokenData['expires_in'] - 60;
        
        if (time() >= $expiryTime) {
            return $this->refreshToken($userId, $tokenData['refresh_token']);
        }

        return $tokenData['access_token'];
    }

    // 4. Refresh Token Baru
    private function refreshToken($userId, $refreshToken) {
        $params = [
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token'
        ];
        
        $response = $this->makeRequest($this->token_url, $params, 'POST');
        
        if (isset($response['access_token'])) {
            // Update DB
            $this->db->query("UPDATE user_google_token SET access_token = :at, expires_in = :exp, created_at = NOW() WHERE id_user = :uid");
            $this->db->bind(':at', $response['access_token']);
            $this->db->bind(':exp', $response['expires_in']);
            $this->db->bind(':uid', $userId);
            $this->db->execute();
            return $response['access_token'];
        }
        return null;
    }

    // 5. Membuat Event di Google Calendar
    public function createEvent($accessToken, $eventData) {
        $url = $this->api_base . '/calendars/primary/events';
        return $this->makeRequest($url, $eventData, 'POST', $accessToken);
    }

    // 6. Update Event
    public function updateEvent($accessToken, $eventId, $eventData) {
        if(empty($eventId)) return false;
        $url = $this->api_base . '/calendars/primary/events/' . $eventId;
        return $this->makeRequest($url, $eventData, 'PUT', $accessToken);
    }

    // 7. Delete Event
    public function deleteEvent($accessToken, $eventId) {
        if(empty($eventId)) return false;
        $url = $this->api_base . '/calendars/primary/events/' . $eventId;
        return $this->makeRequest($url, [], 'DELETE', $accessToken);
    }

    // Helper cURL
    private function makeRequest($url, $params, $method = 'GET', $accessToken = null) {
        $ch = curl_init();
        
        $headers = ['Content-Type: application/json'];
        if ($accessToken) $headers[] = 'Authorization: Bearer ' . $accessToken;

        $opts = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers
        ];

        if ($method == 'POST' || $method == 'PUT') {
            $opts[CURLOPT_CUSTOMREQUEST] = $method;
            $opts[CURLOPT_POSTFIELDS] = ($accessToken) ? json_encode($params) : http_build_query($params);
            if(!$accessToken) $opts[CURLOPT_HTTPHEADER] = ['Content-Type: application/x-www-form-urlencoded'];
        } elseif ($method == 'DELETE') {
            $opts[CURLOPT_CUSTOMREQUEST] = 'DELETE';
        }

        curl_setopt_array($ch, $opts);
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $response = json_decode($result, true);
        
        // Return null for DELETE 204 No Content (Success)
        if ($method == 'DELETE' && $httpCode == 204) return true;
        
        return $response;
    }
}