<?php
/**
 * AttendanceRuleService
 * ======================
 * Menyimpan & menentukan ATURAN BISNIS presensi sesuai
 * RANCANGAN_PERUBAHAN_ICLABS_WEB_V3 poin 1 (Attendance/Presensi System
 * Update):
 *
 *   - Jam masuk default      : 08:00
 *   - Jam pulang minimal     : 16:00
 *   - Jika asisten memiliki jadwal_asisten yang berlangsung pada tanggal
 *     tersebut dengan jam mulai LEBIH PAGI dari 08:00 (mis. 07:00/07:15),
 *     batas keterlambatan mengikuti jam jadwal tersebut.
 *
 * Service ini TIDAK menulis ke database — murni menghitung/menentukan
 * nilai aturan, dipakai oleh AttendanceAutoService.
 */
class AttendanceRuleService
{
    /** Jam masuk default (HH:MM:SS) bila tidak ada jadwal yang lebih pagi. */
    const DEFAULT_CHECK_IN_DEADLINE = '08:00:00';

    /** Jam pulang minimal (HH:MM:SS) — untuk flag "pulang lebih awal", tidak memblokir. */
    const MINIMUM_CHECK_OUT_TIME = '16:00:00';

    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    /**
     * Batas waktu masuk efektif untuk seorang asisten pada tanggal tertentu.
     *
     * Default 08:00:00. Jika asisten memiliki jadwal_asisten yang berlaku
     * pada tanggal tersebut dengan start_time LEBIH PAGI, maka start_time
     * jadwal tersebut yang dipakai sebagai batas keterlambatan.
     *
     * @param  int    $idProfil
     * @param  string $date     Format 'Y-m-d'
     * @return string           Format 'H:i:s'
     */
    public function getEffectiveCheckInDeadline($idProfil, $date)
    {
        $scheduleStart = $this->getEarliestAssistantScheduleStart($idProfil, $date);

        if ($scheduleStart !== null && $scheduleStart < self::DEFAULT_CHECK_IN_DEADLINE) {
            return $scheduleStart;
        }

        return self::DEFAULT_CHECK_IN_DEADLINE;
    }

    /**
     * Jam pulang minimal (konstan, sesuai aturan saat ini belum ada
     * override per jadwal seperti jam masuk).
     *
     * @return string Format 'H:i:s'
     */
    public function getMinimumCheckOutTime()
    {
        return self::MINIMUM_CHECK_OUT_TIME;
    }

    /**
     * Cari jam mulai (start_time) PALING PAGI dari jadwal_asisten milik
     * $idProfil yang berlaku/berlangsung pada $date.
     *
     * Mendukung ketiga model_perulangan:
     *  - 'sekali'   : jadwal satu kali pada tanggal = $date.
     *  - 'mingguan' : berulang setiap hari `hari` (1=Senin..7=Minggu,
     *                 mengikuti PHP date('N')), aktif sejak `tanggal`
     *                 sampai `tanggal_selesai` (atau seterusnya jika NULL).
     *  - 'rentang'  : berlangsung setiap hari dari `tanggal` s/d
     *                 `tanggal_selesai`.
     *
     * @param  int    $idProfil
     * @param  string $date     Format 'Y-m-d'
     * @return string|null      Format 'H:i:s', atau null jika tidak ada jadwal.
     */
    private function getEarliestAssistantScheduleStart($idProfil, $date)
    {
        // 1 (Senin) .. 7 (Minggu) — konsisten dengan data jadwal_asisten yang ada
        // (contoh: hari=5 untuk jadwal hari Jumat).
        $dayOfWeek = (int) date('N', strtotime($date));

        $sql = "SELECT MIN(start_time) as earliest
                FROM jadwal_asisten
                WHERE id_profil = :pid
                AND (
                    (model_perulangan = 'sekali' AND tanggal = :date1)
                    OR (
                        model_perulangan = 'mingguan'
                        AND hari = :dow
                        AND tanggal <= :date2
                        AND (tanggal_selesai IS NULL OR tanggal_selesai >= :date3)
                    )
                    OR (
                        model_perulangan = 'rentang'
                        AND :date4 BETWEEN tanggal AND tanggal_selesai
                    )
                )";

        $this->db->query($sql);
        $this->db->bind(':pid', $idProfil);
        $this->db->bind(':date1', $date);
        $this->db->bind(':dow', $dayOfWeek);
        $this->db->bind(':date2', $date);
        $this->db->bind(':date3', $date);
        $this->db->bind(':date4', $date);

        $row = $this->db->single();

        return $row['earliest'] ?? null;
    }
}
