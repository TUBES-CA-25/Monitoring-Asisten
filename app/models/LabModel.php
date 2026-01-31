<?php
class LabModel {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function getAllLabs() {
        $this->db->query("SELECT * FROM lab ORDER BY nama_lab ASC");
        return $this->db->resultSet();
    }

    public function getLabById($id) {
        $this->db->query("SELECT * FROM lab WHERE id_lab = :id");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }
}
?>