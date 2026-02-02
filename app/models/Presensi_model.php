<?php
class Presensi_model {
    private $db;
    public function __construct() { $this->db = new Database; }

    public function getAllAttendance() {
        $this->db->query("SELECT a.*, u.name, u.photo FROM attendance a JOIN users u ON a.user_id = u.id ORDER BY a.date DESC");
        return $this->db->resultSet();
    }
    public function getMyHistory($id) {
        $this->db->query("SELECT * FROM attendance WHERE user_id=:id ORDER BY date DESC LIMIT 5");
        $this->db->bind('id', $id);
        return $this->db->resultSet();
    }
    public function hasCheckedInToday($userId) {
        $this->db->query("SELECT * FROM attendance WHERE user_id = :uid AND date = CURDATE()");
        $this->db->bind('uid', $userId);
        return $this->db->single();
    }
    public function absenMasuk($userId) {
        $this->db->query("INSERT INTO attendance (user_id, date, check_in, status) VALUES (:uid, CURDATE(), CURTIME(), 'Hadir')");
        $this->db->bind('uid', $userId);
        $this->db->execute();
        return $this->db->rowCount();
    }
    public function absenPulang($userId, $logbook) {
        $this->db->query("UPDATE attendance SET check_out = CURTIME(), logbook = :log WHERE user_id = :uid AND date = CURDATE()");
        $this->db->bind('log', $logbook);
        $this->db->bind('uid', $userId);
        $this->db->execute();
        return $this->db->rowCount();
    }
}