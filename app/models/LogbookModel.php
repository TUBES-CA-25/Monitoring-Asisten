<?php
class LogbookModel {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function getAllWithUserInfo() {
        $sql = "SELECT l.id_logbook as id, l.detail_aktivitas as activity_detail,
                       pr.tanggal as date, pr.waktu_presensi as time_in, pr.waktu_pulang as time_out,
                       p.nama as user_name, p.id_user, l.id_presensi
                FROM logbook l
                JOIN profile p ON l.id_profil = p.id_profil
                JOIN presensi pr ON l.id_presensi = pr.id_presensi
                ORDER BY pr.tanggal DESC";

        $this->db->query($sql);
        return $this->db->resultSet();
    }

    public function countTotal() {
        $this->db->query("SELECT COUNT(*) as total FROM logbook");
        return $this->db->single()['total'] ?? 0;
    }

    public function getUserLogbookHistory($userId) {
        $this->db->query("SELECT id_profil FROM profile WHERE id_user = :uid");
        $this->db->bind(':uid', $userId);
        $res = $this->db->single();
        $pid = $res['id_profil'] ?? null;
        if (!$pid) return [];

        $sql = "SELECT pr.tanggal as date, pr.waktu_presensi as time_in, pr.waktu_pulang as time_out, l.detail_aktivitas as activity
                FROM presensi pr
                LEFT JOIN logbook l ON pr.id_presensi = l.id_presensi
                WHERE pr.id_profil = :pid
                ORDER BY pr.tanggal DESC";

        $this->db->query($sql);
        $this->db->bind(':pid', $pid);
        return $this->db->resultSet();
    }

    public function saveLogbook($data) {
        $this->db->query("SELECT id_profil FROM profile WHERE id_user = :uid");
        $this->db->bind(':uid', $data['user_id']);
        $resP = $this->db->single();
        $pId = $resP['id_profil'] ?? false;
        if (!$pId) return false;

        $this->db->query("SELECT id_presensi FROM presensi WHERE id_profil = :pid AND tanggal = :date");
        $this->db->bind(':pid', $pId);
        $this->db->bind(':date', $data['date']);
        $presensi = $this->db->single();
        if (!$presensi) return false;
        $idPresensi = $presensi['id_presensi'];

        $this->db->query("SELECT id_logbook FROM logbook WHERE id_presensi = :idp");
        $this->db->bind(':idp', $idPresensi);

        if ($this->db->rowCount() > 0) {
            $sql = "UPDATE logbook SET detail_aktivitas = :act WHERE id_presensi = :idp";
            $this->db->query($sql);
            $this->db->bind(':idp', $idPresensi);
            $this->db->bind(':act', $data['activity']);
        } else {
            $sql = "INSERT INTO logbook (id_profil, id_presensi, detail_aktivitas, is_verified) VALUES (:pid, :idp, :act, 0)";
            $this->db->query($sql);
            $this->db->bind(':pid', $pId);
            $this->db->bind(':idp', $idPresensi);
            $this->db->bind(':act', $data['activity']);
        }

        return $this->db->execute();
    }

    public function getAllLogs() {
        return $this->getAllWithUserInfo();
    }

    public function getLogsByUserIdForAdmin($userId) {
        $this->db->query("SELECT id_profil FROM profile WHERE id_user = :uid");
        $this->db->bind(':uid', $userId);
        $pid = $this->db->single()['id_profil'] ?? null;
        if(!$pid) return [];

        $sql = "SELECT pr.id_presensi, l.id_logbook, pr.tanggal as date, pr.waktu_presensi as time_in, pr.waktu_pulang as time_out, l.detail_aktivitas as activity
                FROM presensi pr
                LEFT JOIN logbook l ON pr.id_presensi = l.id_presensi
                WHERE pr.id_profil = :pid
                ORDER BY pr.tanggal DESC";

        $this->db->query($sql);
        $this->db->bind(':pid', $pid);
        return $this->db->resultSet();
    }

    public function saveLogAdmin($data) {
        try {
            $this->db->getConnection()->beginTransaction();

            $this->db->query("SELECT id_profil FROM profile WHERE id_user = :uid");
            $this->db->bind(':uid', $data['user_id']);
            $pid = $this->db->single()['id_profil'] ?? null;
            if(!$pid) return false;

            $this->db->query("SELECT id_presensi FROM presensi WHERE id_profil = :pid AND tanggal = :date");
            $this->db->bind(':pid', $pid);
            $this->db->bind(':date', $data['date']);
            $presensi = $this->db->single();

            if ($presensi) {
                $idPresensi = $presensi['id_presensi'];
                $sqlUpdPres = "UPDATE presensi SET waktu_presensi = :tin, waktu_pulang = :tout WHERE id_presensi = :idp";
                $this->db->query($sqlUpdPres);
                $this->db->bind(':tin', $data['time_in']);
                $this->db->bind(':tout', !empty($data['time_out']) ? $data['time_out'] : null);
                $this->db->bind(':idp', $idPresensi);
                $this->db->execute();
            } else {
                $sqlInsPres = "INSERT INTO presensi (id_profil, tanggal, waktu_presensi, waktu_pulang, status, foto_presensi) 
                               VALUES (:pid, :date, :tin, :tout, 'Hadir', 'admin_manual.jpg')";
                $this->db->query($sqlInsPres);
                $this->db->bind(':pid', $pid);
                $this->db->bind(':date', $data['date']);
                $this->db->bind(':tin', $data['time_in']);
                $this->db->bind(':tout', !empty($data['time_out']) ? $data['time_out'] : null);
                $this->db->execute();
                $idPresensi = $this->db->getConnection()->lastInsertId();
            }

            $this->db->query("SELECT id_logbook FROM logbook WHERE id_presensi = :idp");
            $this->db->bind(':idp', $idPresensi);

            if ($this->db->rowCount() > 0) {
                $sqlLog = "UPDATE logbook SET detail_aktivitas = :act WHERE id_presensi = :idp";
                $this->db->query($sqlLog);
            } else {
                $sqlLog = "INSERT INTO logbook (id_profil, id_presensi, detail_aktivitas, is_verified) 
                           VALUES (:pid, :idp, :act, 1)";
                $this->db->query($sqlLog);
                $this->db->bind(':pid', $pid);
            }

            $this->db->bind(':idp', $idPresensi);
            $this->db->bind(':act', $data['activity']);
            $this->db->execute();

            $this->db->getConnection()->commit();
            return true;
        } catch (Exception $e) {
            $this->db->getConnection()->rollBack();
            return false;
        }
    }

    public function deleteLogAdmin($idLogbook) {
        $this->db->query("DELETE FROM logbook WHERE id_logbook = :id");
        $this->db->bind(':id', $idLogbook);
        return $this->db->execute();
    }
}