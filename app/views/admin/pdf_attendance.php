<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Presensi - <?= $date ?></title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* --- RESET & BASE STYLES --- */
        :root {
            --primary: #4f46e5; /* Indigo 600 */
            --primary-hover: #4338ca; /* Indigo 700 */
            --bg-web: #f3f4f6;
            --paper-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        * { box-sizing: border-box; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-web);
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 100vh;
            padding-bottom: 100px; /* Space for floating bar */
        }

        /* --- KERTAS LAPORAN (WEB VIEW) --- */
        .paper {
            background: white;
            width: 100%;
            max-width: 210mm; /* Lebar A4 */
            min-height: 297mm; /* Tinggi A4 */
            margin-top: 40px;
            padding: 40px;
            border-radius: 12px;
            box-shadow: var(--paper-shadow);
            position: relative;
            animation: slideUp 0.8s cubic-bezier(0.16, 1, 0.3, 1);
        }

        /* --- HEADER LAPORAN --- */
        .header {
            text-align: center;
            margin-bottom: 40px;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 20px;
        }
        .header h1 { 
            margin: 0; 
            font-size: 24px; 
            font-weight: 800; 
            color: #111827; 
            text-transform: uppercase; 
            letter-spacing: 0.05em;
        }
        .header p { 
            margin: 5px 0 0; 
            font-size: 14px; 
            color: #6b7280; 
            font-weight: 500; 
        }
        .date-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 15px;
            background: #f3f4f6;
            padding: 6px 16px;
            border-radius: 99px;
            font-size: 13px;
            font-weight: 600;
            color: #374151;
            border: 1px solid #e5e7eb;
        }

        /* --- TABEL DATA --- */
        .table-container {
            width: 100%;
            overflow-x: auto; /* Agar bisa scroll di HP */
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 10px; 
            font-size: 12px; 
        }
        th { 
            background-color: #f9fafb; 
            color: #374151; 
            font-weight: 700; 
            text-transform: uppercase; 
            padding: 12px 10px; 
            border: 1px solid #e5e7eb; 
            text-align: left;
        }
        td { 
            padding: 12px 10px; 
            border: 1px solid #e5e7eb; 
            color: #1f2937; 
            vertical-align: middle;
        }
        tr:nth-child(even) { background-color: #fcfcfc; }
        tr:hover { background-color: #f0fdfa; }

        /* Status Badge */
        .badge {
            padding: 4px 10px;
            border-radius: 6px;
            font-weight: 700;
            font-size: 10px;
            text-transform: uppercase;
            display: inline-block;
            letter-spacing: 0.025em;
        }
        .badge-hadir { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        .badge-absen { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }

        /* Footer */
        .footer { 
            margin-top: 50px; 
            text-align: right; 
            font-size: 11px; 
            color: #9ca3af; 
            font-style: italic; 
        }

        /* --- FLOATING ACTION BAR (NAVIGASI) --- */
        .fab-container {
            position: fixed;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 15px;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(12px);
            padding: 10px 20px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            border: 1px solid rgba(255,255,255,0.5);
            z-index: 1000;
            animation: floatUp 1s ease-out 0.5s backwards;
        }

        .btn {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 24px;
            border-radius: 14px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: none;
            cursor: pointer;
            white-space: nowrap;
        }

        .btn-back {
            background-color: #f3f4f6;
            color: #4b5563;
        }
        .btn-back:hover {
            background-color: #e5e7eb;
            color: #1f2937;
            transform: translateY(-2px);
        }

        .btn-print {
            background: linear-gradient(135deg, var(--primary), var(--primary-hover));
            color: white;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
        }
        .btn-print:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(79, 70, 229, 0.4);
        }
        .btn-print:active { transform: translateY(0); }

        /* --- ANIMATIONS --- */
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(40px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes floatUp {
            from { opacity: 0; transform: translate(-50%, 20px); }
            to { opacity: 1; transform: translate(-50%, 0); }
        }

        /* --- RESPONSIVE MEDIA QUERIES --- */
        @media (max-width: 768px) {
            .paper {
                width: 95%;
                margin-top: 20px;
                padding: 25px;
                min-height: auto;
            }
            .header h1 { font-size: 20px; }
            .fab-container {
                width: 90%;
                justify-content: center;
                bottom: 20px;
                padding: 10px;
            }
            .btn { padding: 12px 15px; font-size: 13px; flex: 1; justify-content: center; }
            .btn span { display: none; } /* Hide text on small screens if needed, or keep it */
            .btn span { display: inline; } /* Keep text for clarity */
        }

        /* --- PRINT STYLES (KETIKA DICETAK/SAVED PDF) --- */
        @media print {
            @page { margin: 0; size: auto; }
            body { 
                background: white; 
                padding: 0; 
                margin: 0; 
                display: block; 
            }
            .paper {
                box-shadow: none;
                margin: 0;
                padding: 40px;
                max-width: 100%;
                width: 100%;
                border-radius: 0;
                animation: none;
            }
            .fab-container, .no-print { display: none !important; }
            
            /* Hapus background warna tabel agar hemat tinta */
            tr:nth-child(even), tr:hover { background-color: transparent !important; }
            .date-badge { border: 1px solid #ccc; background: none; }
            
            /* Pastikan border tabel hitam jelas */
            table, th, td { border: 1px solid #000 !important; }
            
            /* Status tetap berwarna atau jadi grayscale tergantung setting printer user */
            .badge { border: 1px solid #000; color: #000; background: none; }
        }
    </style>
</head>
<body>

    <div class="paper">
        <div class="header">
            <h1>Laporan Kehadiran Asisten</h1>
            <p>Laboratorium Informatika & Komputer - ICLABS</p>
            
            <div class="date-badge">
                <i class="far fa-calendar-alt"></i> 
                <span><?= date('d F Y', strtotime($date)) ?></span>
            </div>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th style="width: 5%; text-align: center;">No</th>
                        <th style="width: 25%;">Nama Asisten</th>
                        <th style="width: 15%;">NIM / ID</th>
                        <th style="width: 15%;">Jabatan</th>
                        <th style="width: 15%; text-align: center;">Jam Masuk</th>
                        <th style="width: 15%; text-align: center;">Jam Pulang</th>
                        <th style="width: 10%; text-align: center;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    if(!empty($attendance_list)): foreach($attendance_list as $row): 
                    ?>
                    <tr>
                        <td style="text-align: center; font-weight: bold; color: #6b7280;"><?= $no++ ?></td>
                        <td style="font-weight: 600;"><?= htmlspecialchars($row['name']) ?></td>
                        <td style="font-family: monospace; font-size: 11px;"><?= $row['nim'] ?? '-' ?></td>
                        <td><?= $row['position'] ?? 'Anggota' ?></td>
                        <td style="text-align: center; font-family: monospace; font-weight: 600;">
                            <?= $row['check_in_time'] ? date('H:i', strtotime($row['check_in_time'])) : '-' ?>
                        </td>
                        <td style="text-align: center; font-family: monospace; font-weight: 600;">
                            <?= $row['check_out_time'] ? date('H:i', strtotime($row['check_out_time'])) : '-' ?>
                        </td>
                        <td style="text-align: center;">
                            <span class="badge <?= $row['status'] == 'Hadir' ? 'badge-hadir' : 'badge-absen' ?>">
                                <?= $row['status'] ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 40px; color: #9ca3af; font-style: italic;">
                            <i class="fas fa-inbox" style="font-size: 24px; margin-bottom: 10px; display: block;"></i>
                            Tidak ada data presensi pada tanggal ini.
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="footer">
            <p>Dicetak otomatis oleh Sistem ICLABS pada <?= date('d/m/Y H:i:s') ?></p>
            <p>Admin: <?= $_SESSION['name'] ?? 'System' ?></p>
        </div>
    </div>

    <div class="fab-container">
        <a href="<?= BASE_URL ?>/admin/monitorAttendance" class="btn btn-back">
            <i class="fas fa-arrow-left"></i> 
            <span>Dashboard</span>
        </a>
        
        <button onclick="window.print()" class="btn btn-print">
            <i class="fas fa-print"></i> 
            <span>Cetak / Simpan PDF</span>
        </button>
    </div>

</body>
</html>