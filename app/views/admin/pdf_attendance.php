<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Presensi - ICLABS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root { --primary: #4f46e5; --bg-web: #f3f4f6; }
        * { box-sizing: border-box; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg-web); margin: 0; padding: 0; display: flex; flex-direction: column; align-items: center; min-height: 100vh; padding-bottom: 100px; }
        .paper { background: white; width: 100%; max-width: 210mm; min-height: 297mm; margin-top: 40px; padding: 40px; border-radius: 12px; position: relative; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #e5e7eb; padding-bottom: 20px; }
        .header h1 { margin: 0; font-size: 20px; text-transform: uppercase; color: #111; }
        .meta-container { display: flex; justify-content: center; gap: 15px; margin-top: 15px; flex-wrap: wrap; }
        .meta-badge { background: #f9fafb; padding: 6px 14px; border-radius: 6px; border: 1px solid #e5e7eb; font-size: 11px; color: #374151; font-weight: 600; }
        
        table { width: 100%; border-collapse: collapse; font-size: 11px; margin-top: 10px; }
        th { background: #f3f4f6; padding: 10px; text-align: left; border: 1px solid #e5e7eb; font-weight: 700; color: #374151; }
        td { padding: 8px 10px; border: 1px solid #e5e7eb; color: #333; }
        tr:nth-child(even) { background-color: #fcfcfc; }
        
        .badge { padding: 3px 8px; border-radius: 4px; font-weight: 700; font-size: 9px; text-transform: uppercase; display: inline-block; min-width: 50px; text-align: center; }
        .badge-hadir { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .badge-izin { background: #dbeafe; color: #1e40af; border: 1px solid #bfdbfe; }
        .badge-sakit { background: #fef9c3; color: #854d0e; border: 1px solid #fde047; }
        .badge-alpha { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        
        .fab-container { position: fixed; bottom: 30px; left: 50%; transform: translateX(-50%); display: flex; gap: 15px; background: rgba(255,255,255,0.9); padding: 10px 20px; border-radius: 20px; box-shadow: 0 10px 40px rgba(0,0,0,0.15); }
        .btn { padding: 10px 20px; border-radius: 10px; font-size: 13px; font-weight: 600; text-decoration: none; border: none; cursor: pointer; display: flex; align-items: center; gap: 8px; }
        .btn-print { background: #4f46e5; color: white; }
        .btn-back { background: #f3f4f6; color: #374151; }
        @media print { .fab-container { display: none; } body { background: white; margin: 0; } .paper { margin: 0; padding: 20px; border-radius: 0; box-shadow: none; } }
    </style>
</head>
<body>

    <div class="paper">
        <div class="header">
            <h1>Laporan Kehadiran Asisten</h1>
            <p style="margin:5px 0 0; font-size:12px; color:#666;">Laboratorium Informatika & Komputer - ICLABS</p>
            
            <div class="meta-container">
                <div class="meta-badge">
                    <i class="far fa-calendar-alt"></i> 
                    <?= date('d M Y', strtotime($start_date)) ?> &mdash; <?= date('d M Y', strtotime($end_date)) ?>
                </div>
                <div class="meta-badge">
                    <i class="far fa-user"></i> 
                    <?= htmlspecialchars($assistant_name) ?>
                </div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 5%; text-align: center;">No</th>
                    <th style="width: 15%;">Tanggal</th>
                    <th style="width: 25%;">Nama Asisten</th>
                    <th style="width: 15%;">NIM</th>
                    <th style="width: 15%; text-align: center;">Masuk</th>
                    <th style="width: 15%; text-align: center;">Pulang</th>
                    <th style="width: 10%; text-align: center;">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1;
                if(!empty($attendance_list)): foreach($attendance_list as $row): 
                    $statusClass = match($row['status']) {
                        'Hadir' => 'badge-hadir',
                        'Izin' => 'badge-izin',
                        'Sakit' => 'badge-sakit',
                        'Alpha' => 'badge-alpha',
                        default => ''
                    };
                    $statusText = $row['status'] == '-' ? '-' : $row['status'];
                ?>
                <tr>
                    <td style="text-align: center;"><?= $no++ ?></td>
                    <td><?= date('d/m/Y', strtotime($row['tanggal'])) ?></td>
                    <td style="font-weight: 600;"><?= htmlspecialchars($row['name']) ?></td>
                    <td><?= $row['nim'] ?? '-' ?></td>
                    <td style="text-align: center; font-family: monospace;"><?= $row['waktu_presensi'] ? date('H:i', strtotime($row['waktu_presensi'])) : '-' ?></td>
                    <td style="text-align: center; font-family: monospace;"><?= $row['waktu_pulang'] ? date('H:i', strtotime($row['waktu_pulang'])) : '-' ?></td>
                    <td style="text-align: center;">
                        <?php if($statusText != '-'): ?>
                            <span class="badge <?= $statusClass ?>"><?= $statusText ?></span>
                        <?php else: ?>
                            <span style="color:#ccc;">-</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; else: ?>
                <tr>
                    <td colspan="7" style="text-align: center; padding: 30px; font-style: italic; color: #999;">Tidak ada data.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div style="margin-top: 40px; text-align: right; font-size: 10px; color: #888;">
            Dicetak pada: <?= date('d F Y H:i:s') ?> <br>
            Oleh: <?= $_SESSION['name'] ?? 'Admin' ?>
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