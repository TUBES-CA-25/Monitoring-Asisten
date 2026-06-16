<?php
require_once '../app/core/ApiResponse.php';
require_once '../app/api/AuthApi.php';

class DeviceApi {
    private $conn;
    private $table_device = 'device';

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    /**
     * POST /api/devices/register
     * Register new device
     * Request: {
     *   "device_id": "unique_device_id",
     *   "device_name": "iPhone 12",
     *   "device_type": "ios|android|web",
     *   "fcm_token": "firebase_cloud_messaging_token"
     * }
     */
    public function register() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            ApiResponse::error('Method not allowed', 405);
        }

        $payload = AuthApi::validateToken();
        $profilId = $payload['profil_id'];

        $data = json_decode(file_get_contents("php://input"), true);

        // Validation
        $errors = [];
        if (empty($data['device_id'])) $errors['device_id'] = 'Device ID required';
        if (empty($data['device_name'])) $errors['device_name'] = 'Device name required';
        if (empty($data['device_type'])) $errors['device_type'] = 'Device type required';
        if (empty($data['fcm_token'])) $errors['fcm_token'] = 'FCM token required';

        if (!empty($errors)) {
            ApiResponse::error('Validation failed', 400, $errors);
        }

        // Validate device type
        if (!in_array($data['device_type'], ['ios', 'android', 'web'])) {
            ApiResponse::error('Device type hanya ios, android, atau web', 400);
        }

        try {
            // Check if device already registered
            $checkQuery = "SELECT id_device FROM {$this->table_device}
                          WHERE id_profil = :pid AND device_id = :device_id";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->execute([':pid' => $profilId, ':device_id' => $data['device_id']]);
            $existingDevice = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if ($existingDevice) {
                // Update existing device
                $updateQuery = "UPDATE {$this->table_device}
                               SET fcm_token = :fcm_token, device_name = :device_name, 
                                   is_active = 1, last_updated = NOW()
                               WHERE id_profil = :pid AND device_id = :device_id";

                $updateStmt = $this->conn->prepare($updateQuery);
                $updateStmt->execute([
                    ':pid' => $profilId,
                    ':device_id' => $data['device_id'],
                    ':fcm_token' => $data['fcm_token'],
                    ':device_name' => $data['device_name']
                ]);

                ApiResponse::success(null, 'Device updated successfully', 200);
            } else {
                // Insert new device
                $insertQuery = "INSERT INTO {$this->table_device}
                               (id_profil, device_id, device_name, device_type, fcm_token, is_active, registered_at, last_updated)
                               VALUES (:pid, :device_id, :device_name, :device_type, :fcm_token, 1, NOW(), NOW())";

                $insertStmt = $this->conn->prepare($insertQuery);
                $result = $insertStmt->execute([
                    ':pid' => $profilId,
                    ':device_id' => $data['device_id'],
                    ':device_name' => $data['device_name'],
                    ':device_type' => $data['device_type'],
                    ':fcm_token' => $data['fcm_token']
                ]);

                if ($result) {
                    $responseData = [
                        'id_device' => $this->conn->lastInsertId(),
                        'device_id' => $data['device_id'],
                        'device_name' => $data['device_name'],
                        'device_type' => $data['device_type'],
                        'is_active' => true,
                        'registered_at' => date('Y-m-d H:i:s')
                    ];
                    ApiResponse::success($responseData, 'Device registered successfully', 201);
                } else {
                    ApiResponse::error('Failed to register device', 500);
                }
            }
        } catch (PDOException $e) {
            ApiResponse::error('Database error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/devices/list
     * Get all registered devices for current user
     */
    public function getlist() {
        $payload = AuthApi::validateToken();
        $profilId = $payload['profil_id'];

        try {
            $query = "SELECT 
                        id_device,
                        device_id,
                        device_name,
                        device_type,
                        is_active,
                        registered_at,
                        last_updated,
                        CASE 
                            WHEN last_updated >= DATE_SUB(NOW(), INTERVAL 1 HOUR) THEN 'Online'
                            WHEN last_updated >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 'Idle'
                            ELSE 'Offline'
                        END as status
                      FROM {$this->table_device}
                      WHERE id_profil = :pid
                      ORDER BY last_updated DESC";

            $stmt = $this->conn->prepare($query);
            $stmt->execute([':pid' => $profilId]);
            $devices = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get summary
            $onlineCount = 0;
            $idleCount = 0;
            $offlineCount = 0;

            foreach ($devices as $device) {
                if ($device['status'] === 'Online') $onlineCount++;
                elseif ($device['status'] === 'Idle') $idleCount++;
                else $offlineCount++;
            }

            $responseData = [
                'devices' => $devices,
                'summary' => [
                    'total_devices' => count($devices),
                    'active_devices' => $onlineCount,
                    'idle_devices' => $idleCount,
                    'offline_devices' => $offlineCount
                ]
            ];

            ApiResponse::success($responseData, 'Devices list retrieved', 200);
        } catch (PDOException $e) {
            ApiResponse::error('Database error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * DELETE /api/devices/{id}
     * Unregister device
     */
    public function delete($id = null) {
        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            ApiResponse::error('Method not allowed', 405);
        }

        $payload = AuthApi::validateToken();
        $profilId = $payload['profil_id'];

        if (!$id) {
            ApiResponse::error('Device ID required', 400);
        }

        try {
            $query = "DELETE FROM {$this->table_device}
                     WHERE id_device = :id AND id_profil = :pid";

            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([':id' => $id, ':pid' => $profilId]);

            if ($result && $stmt->rowCount() > 0) {
                ApiResponse::success(null, 'Device unregistered', 200);
            } else {
                ApiResponse::error('Device not found', 404);
            }
        } catch (PDOException $e) {
            ApiResponse::error('Database error: ' . $e->getMessage(), 500);
        }
    }
}
?>
