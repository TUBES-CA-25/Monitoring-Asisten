<?php
$start_date = $start_date ?? date('Y-m-d');
$end_date = $end_date ?? date('Y-m-d');
$report_title_name = $report_title_name ?? 'Semua Asisten';
$summary_data = $summary_data ?? [];
$css = $css ?? 'admin/pdf_attendance.css';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Rekapitulasi Kehadiran</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/<?= $css ?>">
</head>
<body>
    <div class="paper">
        <div class="header">
            <h1>Laporan Rekapitulasi Kehadiran</h1>
            <p>Laboratorium Informatika & Komputer - ICLABS</p>
            
            <div class="meta-container">
                <div class="meta-badge">
                    <i class="far fa-calendar-alt"></i> 
                    Periode: <?= date('d M Y', strtotime($start_date)) ?> &mdash; <?= date('d M Y', strtotime($end_date)) ?>
                </div>
                <div class="meta-badge">
                    <i class="far fa-user"></i> 
                    Asisten: <?= htmlspecialchars($report_title_name) ?>
                </div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th class="text-center" style="width: 5%;">No</th>
                    <th>Nama Asisten</th>
                    <th style="width: 15%;">NIM</th>
                    <th style="width: 15%;">Jabatan</th>
                    <th class="text-center" style="width: 8%;">Masuk</th>
                    <th class="text-center" style="width: 8%;">Pulang</th>
                    <th class="text-center" style="width: 10%;">Hadir</th>
                    <th class="text-center" style="width: 10%;">Izin</th>
                    <th class="text-center" style="width: 10%;">Alpa</th>
                    <th class="text-center" style="width: 12%;">Total Kehadiran</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($summary_data)): ?>
                    <tr>
                        <td colspan="10" class="text-center" style="padding: 20px; font-style: italic; color: #999;">Tidak ada data untuk ditampilkan pada rentang tanggal ini.</td>
                    </tr>
                <?php else: ?>
                    <?php $no = 1; ?>
                    <?php foreach ($summary_data as $row): ?>
                        <tr>
                            <td class="text-center"><?= $no++ ?></td>
                            <td style="font-weight: 600;"><?= htmlspecialchars($row['name']) ?></td>
                            <td><?= htmlspecialchars($row['nim'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($row['position'] ?? 'Asisten') ?></td>
                            <td class="text-center font-mono"><?= $row['total_masuk'] ?></td>
                            <td class="text-center font-mono"><?= $row['total_pulang'] ?></td>
                            <td class="text-center"><?= $row['total_hadir'] ?> Hari</td>
                            <td class="text-center"><?= $row['total_izin'] ?> Hari</td>
                            <td class="text-center"><?= $row['total_alpa'] ?> Hari</td>
                            <td class="text-center" style="font-weight: 600;"><?= $row['total_hadir'] ?> Hari</td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <div style="margin-top: 40px; text-align: right; font-size: 10px; color: #888;">
            Dicetak pada: <?= date('d F Y H:i:s') ?> <br>
            Oleh: <?= htmlspecialchars($_SESSION['name'] ?? 'Admin', ENT_QUOTES, 'UTF-8') ?>
        </div>

        <div class="fab-container">
            <?php $roleLink = strtolower(str_replace(' ', '', $_SESSION['role'])); ?>
            <a href="<?= BASE_URL ?>/<?= $roleLink ?>/monitorAttendance" class="btn btn-back">
                <i class="fas fa-arrow-left"></i> Dashboard
            </a>
            <button onclick="window.print()" class="btn btn-print">
                <i class="fas fa-print"></i> Cetak PDF
            </button>
        </div>
    </div>
</body>
</html>