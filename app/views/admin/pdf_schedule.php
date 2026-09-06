<?php
$start_date = $start_date ?? '';
$end_date = $end_date ?? '';
$selected_assistant = $selected_assistant ?? '';
$schedule_type = $schedule_type ?? '';
$sort_by = $sort_by ?? 'hari_waktu';
$report_title_name = $report_title_name ?? 'Semua Asisten';
$schedules = $schedules ?? [];
$css = $css ?? 'admin/pdf_schedule.css';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Jadwal Asisten & Kegiatan - ICLABS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/<?= $css ?>">
</head>
<body>

    <div class="paper">
        <div class="header">
            <h1>Laporan Jadwal Asisten & Kegiatan Laboratorium</h1>
            <p style="margin:5px 0 0; font-size:12px; color:#666;">Laboratorium Informatika & Komputer - ICLABS</p>
            
            <div class="meta-container">
                <div class="meta-badge">
                    <i class="far fa-calendar-alt"></i> 
                    Dicetak: <?= date('d/m/Y H:i') ?> WITA
                </div>
                <?php if (!empty($start_date) && !empty($end_date)): ?>
                <div class="meta-badge">
                    <i class="far fa-calendar"></i> 
                    Periode: <?= date('d/m/Y', strtotime($start_date)) ?> s/d <?= date('d/m/Y', strtotime($end_date)) ?>
                </div>
                <?php endif; ?>
                <div class="meta-badge">
                    <i class="far fa-user"></i> 
                    Asisten: <?= htmlspecialchars($report_title_name) ?>
                </div>
                <?php if (!empty($schedule_type)): ?>
                <div class="meta-badge">
                    <i class="fas fa-layer-group"></i> 
                    Tipe: <?= htmlspecialchars(ucfirst($schedule_type)) ?>
                </div>
                <?php endif; ?>
                 <div class="meta-badge">
                    <i class="fas fa-sort"></i> 
                    Urut: Hari, Waktu & Asisten
                </div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 4%; text-align: center;">No</th>
                    <th style="width: 12%;">Jenis Jadwal</th>
                    <th style="width: 18%;">Nama Asisten / PIC</th>
                    <th style="width: 18%;">Kegiatan / Mata Kuliah</th>
                    <th style="width: 6%; text-align: center;">Kelas</th>
                    <th style="width: 16%;">Dosen Pengampu</th>
                    <th style="width: 8%; text-align: center;">Hari</th>
                    <th style="width: 10%; text-align: center;">Waktu</th>
                    <th style="width: 8%;">Lokasi</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1;
                $dayMap = [
                    1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 
                    5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'
                ];
                if(!empty($schedules)): foreach($schedules as $row): 
                    $typeLower = strtolower($row['type'] ?? '');
                    $badgeClass = match($typeLower) {
                        'umum' => 'badge-umum',
                        'asisten' => 'badge-asisten',
                        'piket' => 'badge-piket',
                        'kuliah' => 'badge-kuliah',
                        default => ''
                    };
                    $typeFmt = match($typeLower) {
                        'umum' => 'Umum (Lab)',
                        'asisten' => 'Asisten Lab',
                        'piket' => 'Piket',
                        'kuliah' => 'Kuliah Asisten',
                        default => ucfirst($row['type'] ?? '')
                    };
                    $dayNum = intval($row['day_of_week'] ?? 0);
                    $dayName = $dayMap[$dayNum] ?? '-';
                    
                    $timeStr = '-';
                    if (!empty($row['start_time']) && !empty($row['end_time'])) {
                        $timeStr = date('H:i', strtotime($row['start_time'])) . ' - ' . date('H:i', strtotime($row['end_time']));
                    }
                ?>
                <tr>
                    <td style="text-align: center;"><?= $no++ ?></td>
                    <td>
                        <span class="badge <?= $badgeClass ?>"><?= $typeFmt ?></span>
                    </td>
                    <td style="font-weight: 600;"><?= htmlspecialchars($row['user_name'] ?? 'Laboratorium') ?></td>
                    <td><?= htmlspecialchars($row['title'] ?? '-') ?></td>
                    <td style="text-align: center; font-weight: bold;"><?= htmlspecialchars($row['kelas'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($row['dosen'] ?? '-') ?></td>
                    <td style="text-align: center;"><?= $dayName ?></td>
                    <td style="text-align: center; font-family: monospace;"><?= $timeStr ?></td>
                    <td><?= htmlspecialchars($row['location'] ?? 'Lab') ?></td>
                </tr>
                <?php endforeach; else: ?>
                <tr>
                    <td colspan="9" style="text-align: center; padding: 30px; font-style: italic; color: #999;">Tidak ada data jadwal untuk ditampilkan.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div style="margin-top: 40px; text-align: right; font-size: 10px; color: #888;">
            Laporan ini dibuat secara otomatis oleh sistem ICLABS pada <?= date('d F Y H:i:s') ?>
        </div>
    </div>

    <div class="fab-container">
        <?php $roleLink = strtolower(str_replace(' ', '', $_SESSION['role'])); ?>
        <a href="<?= BASE_URL ?>/<?= $roleLink ?>/schedule" class="btn btn-back">
            <i class="fas fa-arrow-left"></i> Dashboard
        </a>
        <button onclick="window.print()" class="btn btn-print">
            <i class="fas fa-print"></i> Cetak PDF
        </button>
    </div>

</body>
</html>
