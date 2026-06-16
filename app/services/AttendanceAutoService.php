<?php
require_once __DIR__ . '/AttendanceRuleService.php';

/**
 * AttendanceAutoService
 * ======================
 * Menghitung nilai-nilai presensi yang sebelumnya hardcode/manual, sesuai
 * RANCANGAN_PERUBAHAN_ICLABS_WEB_V3 poin 1:
 *
 *   - Status otomatis saat check-in: HADIR / TERLAMBAT (memakai kolom
 *     `presensi.status` yang sudah ada — tidak ada kolom attendance_status
 *     baru).
 *   - `late_minutes`  : menit keterlambatan saat check-in (0 jika tepat
 *     waktu/lebih awal).
 *   - `work_duration` : durasi kerja (menit) saat check-out.
 *   - Status IZIN & ALPHA TIDAK ditentukan di sini:
 *       - IZIN  -> diberikan oleh IzinModel::approve() (Modul 4).
 *       - ALPHA -> dihitung on-demand untuk asisten yang tidak check-in
 *                  pada hari kerja (mengikuti pola
 *                  UserModel::calculateRealAlpha yang sudah ada), BUKAN
 *                  lewat insert baris presensi otomatis/cron.
 *
 * Service ini TIDAK menulis ke database — murni kalkulasi, dipakai oleh
 * AttendanceModel & AttendanceApi saat clock-in/clock-out.
 */
class AttendanceAutoService
{
    private $ruleService;

    public function __construct()
    {
        $this->ruleService = new AttendanceRuleService();
    }

    /**
     * Evaluasi check-in: tentukan status (Hadir/Terlambat) & late_minutes
     * berdasarkan batas waktu masuk efektif (default 08:00, atau jadwal
     * asisten hari itu jika lebih pagi).
     *
     * @param  int    $idProfil
     * @param  string $date        Format 'Y-m-d'
     * @param  string $checkInTime Format 'H:i:s'
     * @return array               ['attendance_status' => 'Hadir'|'Terlambat', 'late_minutes' => int]
     */
    public function evaluateCheckIn($idProfil, $date, $checkInTime)
    {
        $deadline = $this->ruleService->getEffectiveCheckInDeadline($idProfil, $date);

        $diffSeconds = strtotime("$date $checkInTime") - strtotime("$date $deadline");
        $lateMinutes = $diffSeconds > 0 ? (int) ceil($diffSeconds / 60) : 0;

        return [
            'attendance_status' => $lateMinutes > 0 ? 'Terlambat' : 'Hadir',
            'late_minutes'      => $lateMinutes,
        ];
    }

    /**
     * Hitung durasi kerja (menit) antara jam masuk & jam pulang.
     *
     * @param  string      $date         Format 'Y-m-d'
     * @param  string|null $checkInTime  Format 'H:i:s'
     * @param  string      $checkOutTime Format 'H:i:s'
     * @return int|null    Durasi dalam menit, atau null jika jam masuk tidak ada.
     */
    public function calculateWorkDuration($date, $checkInTime, $checkOutTime)
    {
        if (empty($checkInTime)) {
            return null;
        }

        $diffSeconds = strtotime("$date $checkOutTime") - strtotime("$date $checkInTime");

        return $diffSeconds > 0 ? (int) round($diffSeconds / 60) : 0;
    }

    /**
     * Apakah jam check-out ini di bawah batas minimal (16:00)? Hanya
     * informasi/flag untuk ditampilkan ke pengguna — TIDAK memblokir
     * proses check-out (sesuai kesepakatan: asisten tetap bisa pulang
     * lebih awal jika ada keperluan mendesak).
     *
     * @param  string $checkOutTime Format 'H:i:s'
     * @return bool
     */
    public function isEarlyCheckout($checkOutTime)
    {
        return $checkOutTime < $this->ruleService->getMinimumCheckOutTime();
    }
}
