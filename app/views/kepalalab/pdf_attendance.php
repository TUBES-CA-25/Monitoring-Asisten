<?php
$start_date = $start_date ?? date('Y-m-d');
$end_date = $end_date ?? date('Y-m-d');
$assistant_name = $assistant_name ?? 'Semua Asisten';
$summary_data = $summary_data ?? [];
$css = $css ?? 'kepalalab/pdf_attendance.css';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Presensi - ICLABS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/<?= $css ?>">
</head>
<body>


    <div class="paper">
        <div class="header">
            <h1>Laporan Rekapitulasi Kehadiran Asisten</h1>
            <p style="margin:5px 0 0; font-size:12px; color:#666;">Laboratorium Informatika & Komputer - ICLABS</p>
            
            <div class="meta-container">
                <div class="meta-badge">
                    <i class="far fa-calendar-alt"></i> 
                    <?= date('d M Y', strtotime($start_date)) ?> &mdash; <?= date('d M Y', strtotime($end_date)) ?>
                </div>
                <div class="meta-badge">
                    <i class="far fa-user"></i> 
                    Asisten: <?= htmlspecialchars($assistant_name) ?>
                </div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 5%; text-align: center;">No</th>
                    <th>Nama Asisten</th>
                    <th style="width: 15%;">NIM</th>
                    <th style="width: 15%;">Jabatan</th>
                    <th style="width: 8%; text-align: center;">Masuk</th>
                    <th style="width: 8%; text-align: center;">Pulang</th>
                    <th style="width: 10%; text-align: center;">Hadir</th>
                    <th style="width: 10%; text-align: center;">Izin</th>
                    <th style="width: 10%; text-align: center;">Alpa</th>
                    <th style="width: 12%; text-align: center;">Total Kehadiran</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1;
                if(!empty($summary_data)): foreach($summary_data as $row): 
                ?>
                <tr>
                    <td style="text-align: center;"><?= $no++ ?></td>
                    <td style="font-weight: 600;"><?= htmlspecialchars($row['name']) ?></td>
                    <td><?= htmlspecialchars($row['nim'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($row['position'] ?? 'Asisten') ?></td>
                    <td style="text-align: center; font-family: monospace;"><?= $row['total_masuk'] ?></td>
                    <td style="text-align: center; font-family: monospace;"><?= $row['total_pulang'] ?></td>
                    <td style="text-align: center;"><?= $row['total_hadir'] ?> Hari</td>
                    <td style="text-align: center;"><?= $row['total_izin'] ?> Hari</td>
                    <td style="text-align: center;"><?= $row['total_alpa'] ?> Hari</td>
                    <td style="text-align: center; font-weight: 600;"><?= $row['total_hadir'] ?> Hari</td>
                </tr>
                <?php endforeach; else: ?>
                <tr>
                    <td colspan="10" style="text-align: center; padding: 30px; font-style: italic; color: #999;">Tidak ada data untuk ditampilkan pada rentang tanggal ini.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div style="margin-top: 40px; text-align: right; font-size: 10px; color: #888;">
            Dicetak pada: <?= date('d F Y H:i:s') ?> <br>
            Oleh: <?= htmlspecialchars($_SESSION['name'] ?? 'Kepala Lab', ENT_QUOTES, 'UTF-8') ?>
        </div>
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

</body>
</html>