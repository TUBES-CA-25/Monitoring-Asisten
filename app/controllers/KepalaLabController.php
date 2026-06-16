<?php
require_once '../app/core/GoogleClient.php';

class KepalaLabController extends Controller {

    public function index() { $this->dashboard(); }

    public function dashboard()
    {
        $this->checkAccess(['Kepala Lab']);

        $data = [];
        $data['judul'] = 'Dashboard Pengawas';

        $userModel = $this->model('UserModel');
        $attModel  = $this->model('AttendanceModel');
        $qrModel   = $this->model('QrModel');

        $data['user'] = $userModel->getUserById($_SESSION['user_id']);

        $todayStats  = $attModel->getTodayStats();
        $totalAsisten = $userModel->countUsersByRole('User');
        $totalLate    = $attModel->countLateToday();

        $data['stats'] = [
            'hadir_today'   => (int) ($todayStats['hadir'] ?? 0),
            'izin_today'   => (int) ($todayStats['izin'] ?? 0),
            'alpa_today'   => (int) ($todayStats['alpa'] ?? 0),
            'total_asisten'=> (int) $totalAsisten,
            'total_late'   => (int) $totalLate
        ];

        $allUsers = $userModel->getAllUsers();
        $assistants = array_values(array_filter($allUsers, function ($u) {
            return isset($u['role']) && $u['role'] === 'User';
        }));

        foreach ($assistants as &$ast) {
            $pid = $ast['id_profil'] ?? null;
            if (!$pid) {
                $ast['visual_status'] = 'alpha';
                $ast['total_hadir']  = 0;
                $ast['total_izin']   = 0;
                $ast['total_alpa']  = 0;
                continue;
            }

            $statusDetail = $attModel->getTodayAttendanceDetail($pid);
            $presensi = $statusDetail['presensi'] ?? null;
            $izin     = $statusDetail['izin'] ?? null;

            if (!empty($presensi['waktu_presensi'])) {
                $ast['visual_status'] = !empty($presensi['waktu_pulang'])
                    ? 'offline_pulang'
                    : 'online';
            } elseif ($izin) {
                $ast['visual_status'] = 'izin';
            } else {
                $ast['visual_status'] = 'alpha';
            }

            $userStats = $attModel->getUserStats($pid);
            $ast['total_hadir'] = (int) ($userStats['hadir'] ?? 0);
            $ast['total_izin'] = (int) ($userStats['izin'] ?? 0);

            $createdAt  = $ast['created_at'] ?? date('Y-m-d');
            $isCompleted = (int) ($ast['is_completed'] ?? 0);

            $ast['total_alpa'] = (int) $userModel->calculateRealAlpha(
                $pid,
                $createdAt,
                $isCompleted
            );
        }
        unset($ast);

        $data['assistants'] = $assistants;

        $chartData = $attModel->getChartData();

        $data['chart_data'] = is_array($chartData)
            ? $chartData
            : [
                'daily'   => ['labels' => [], 'data' => []],
                'weekly'  => ['labels' => [], 'data' => []],
                'monthly' => ['labels' => [], 'data' => []]
            ];

        $data['qr_in']  = json_encode([
            'type'  => 'CHECK_IN',
            'token' => $qrModel->getOrGenerateToken('check_in')
        ]);

        $data['qr_out'] = json_encode([
            'type'  => 'CHECK_OUT',
            'token' => $qrModel->getOrGenerateToken('check_out')
        ]);

        $data['page_css'] = [
            ASSET_URL . '/css/kepalalab/dashboard.css'
        ];

        $data['page_js'] = [
            ASSET_URL . '/js/kepalalab/dashboard.js',
            ASSET_URL . '/js/common/assistant_performance_chart.js',
            ASSET_URL . '/js/common/assistant_search_modal.js'
        ];

        $data['js_config'] = [
            'baseUrl'     => rtrim(BASE_URL, '/'),
            'roleSegment' => strtolower(str_replace(' ', '', $_SESSION['role'])),
            'chartData'   => $data['chart_data'],
            'qrIn'        => $data['qr_in'],
            'qrOut'       => $data['qr_out']
        ];

        $this->view('layout/header', $data);
        $this->view('layout/sidebar', $data);
        $this->view('kepalalab/dashboard', $data);
        $this->view('layout/footer', $data);
    }

    /**
     * AJAX: data chart "Performa Asisten" untuk dashboard Kepala Lab — hanya
     * asisten pada laboratorium yang dipimpinnya.
     * Dipanggil oleh public/assets/js/common/assistant_performance_chart.js
     */
    public function getAssistantChartData() {
        $this->checkAccess(['Kepala Lab']);

        ob_clean();
        header('Content-Type: application/json');

        $allowed = ['kehadiran', 'jam_masuk', 'izin', 'logbook', 'jadwal', 'durasi_kerja'];
        $metric = $_GET['metric'] ?? 'kehadiran';
        if (!in_array($metric, $allowed)) {
            $metric = 'kehadiran';
        }

        $currentUser = $this->model('UserModel')->getUserById($_SESSION['user_id']);
        $idLab = $currentUser['id_lab'] ?? null;

        $rows = $this->model('UserModel')->getAssistantPerformanceData($metric, $idLab);

        echo json_encode([
            'status' => 'success',
            'metric' => $metric,
            'labels' => array_column($rows, 'name'),
            'data'   => array_column($rows, 'score')
        ]);
        exit;
    }

    public function manageUsers() {
        $this->checkAccess(['Kepala Lab']);
        
        $data['judul'] = 'Daftar Pengguna';
        $userModel = $this->model('UserModel');
        $data['user'] = $userModel->getUserById($_SESSION['user_id']);
        
        $allUsers = $userModel->getAllUsers();
        $data['users_list'] = array_filter($allUsers, function($u) {
            return $u['id'] != $_SESSION['user_id'];
        });
        
        $data['labs'] = $this->model('LabModel')->getAllLabs();

        $data['page_css'] = [
            ASSET_URL . '/css/kepalalab/users.css'
        ];

        $data['page_js'] = [
            ASSET_URL . '/js/kepalalab/users.js'
        ];

        $data['js_config'] = [
            'BASE_URL' => BASE_URL
        ];
        
        $this->view('layout/header', $data);
        $this->view('layout/sidebar', $data);
        $this->view('kepalalab/users', $data); 
        $this->view('layout/footer', $data);
    }

    public function addUser() {
        if (!isset($_SESSION['role']) || $_SESSION['role'] != 'Kepala Lab') {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            exit;
        }
        echo json_encode(['status' => 'error', 'title' => 'Akses Ditolak', 'message' => 'Kepala Lab hanya memiliki akses lihat (Read-Only).']);
    }

    public function editUser() {
        if (!isset($_SESSION['role']) || $_SESSION['role'] != 'Kepala Lab') {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            exit;
        }
        echo json_encode(['status' => 'error', 'title' => 'Akses Ditolak', 'message' => 'Kepala Lab hanya memiliki akses lihat (Read-Only).']);
    }

    public function deleteUser() {
        if (!isset($_SESSION['role']) || $_SESSION['role'] != 'Kepala Lab') {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            exit;
        }
        echo json_encode(['status' => 'error', 'title' => 'Akses Ditolak', 'message' => 'Kepala Lab hanya memiliki akses lihat (Read-Only).']);
    }

    public function schedule() {
        $this->checkAccess(['Kepala Lab']);
        
        $data['judul'] = 'Monitoring Jadwal Lab';
        $userModel = $this->model('UserModel');
        $data['user'] = $userModel->getUserById($_SESSION['user_id']);
        
        $allUsers = $userModel->getAllUsers();
        $assistants = array_filter($allUsers, fn($u) => $u['role'] == 'User');
        usort($assistants, fn($a, $b) => strcasecmp($a['nama'] ?? $a['name'] ?? '', $b['nama'] ?? $b['name'] ?? ''));
        $data['assistants'] = $assistants;
        
        // Filters
        $data['start_date'] = !empty($_GET['start_date']) ? $_GET['start_date'] : '';
        $data['end_date'] = !empty($_GET['end_date']) ? $_GET['end_date'] : '';
        $data['assistant_id'] = !empty($_GET['assistant_id']) ? $_GET['assistant_id'] : '';
        $data['schedule_type'] = !empty($_GET['schedule_type']) ? $_GET['schedule_type'] : '';
        $data['sort_by'] = !empty($_GET['sort_by']) ? $_GET['sort_by'] : 'hari_waktu';

        $schedules = $this->model('ScheduleModel')->getAllSchedules();

        // Filter by Date Range
        if (!empty($data['start_date']) && !empty($data['end_date'])) {
            $schedules = array_filter($schedules, function($row) use ($data) {
                $evtStart = $row['start_date'];
                $evtEnd = !empty($row['end_date']) ? $row['end_date'] : $evtStart;
                return ($evtStart <= $data['end_date'] && $evtEnd >= $data['start_date']);
            });
        }

        // Filter by Assistant
        if (!empty($data['assistant_id'])) {
            $schedules = array_filter($schedules, function($row) use ($data) {
                return strtolower($row['type'] ?? '') === 'umum' || (isset($row['id_profil']) && strval($row['id_profil']) === strval($data['assistant_id']));
            });
        }

        // Filter by Schedule Type
        if (!empty($data['schedule_type'])) {
            $schedules = array_filter($schedules, function($row) use ($data) {
                return strtolower($row['type'] ?? '') === strtolower($data['schedule_type']);
            });
        }

        // Sort schedules
        usort($schedules, function($a, $b) use ($data) {
            if ($data['sort_by'] === 'nama') {
                $cmp = strcasecmp($a['user_name'] ?? '', $b['user_name'] ?? '');
                if ($cmp !== 0) return $cmp;
            } elseif ($data['sort_by'] === 'jenis') {
                $typeA = strtolower($a['type'] ?? '');
                $typeB = strtolower($b['type'] ?? '');
                $cmp = strcasecmp($typeA, $typeB);
                if ($cmp !== 0) return $cmp;
            }
            $dayA = intval($a['day_of_week'] ?? 0);
            $dayB = intval($b['day_of_week'] ?? 0);
            if ($dayA !== $dayB) return $dayA - $dayB;
            
            $timeA = $a['start_time'] ?? '00:00:00';
            $timeB = $b['start_time'] ?? '00:00:00';
            return strcasecmp($timeA, $timeB);
        });

        $data['raw_schedules'] = $schedules; 
        
        $data['css'] = 'kepalalab/schedule.css';
        $data['js'] = 'kepalalab/schedule.js';

        $data['js_config'] = [
            'baseUrl'     => BASE_URL,
            'rawEvents'   => $data['raw_schedules'],
            'initialDate' => !empty($data['start_date']) ? $data['start_date'] : date('Y-m-d')
        ];
        
        $this->view('layout/header', $data);
        $this->view('layout/sidebar', $data);
        $this->view('kepalalab/schedule', $data); 
        $this->view('layout/footer', $data);
    }

    public function getFilteredSchedulesJson() {
        if (!isset($_SESSION['role']) || $_SESSION['role'] != 'Kepala Lab') {
            header('Content-Type: application/json');
            echo json_encode([]);
            exit;
        }
        
        $startDate = !empty($_GET['start_date']) ? $_GET['start_date'] : '';
        $endDate = !empty($_GET['end_date']) ? $_GET['end_date'] : '';
        $assistantId = !empty($_GET['assistant_id']) ? $_GET['assistant_id'] : '';
        $scheduleType = !empty($_GET['schedule_type']) ? $_GET['schedule_type'] : '';
        $sortBy = !empty($_GET['sort_by']) ? $_GET['sort_by'] : 'hari_waktu';

        $schModel = $this->model('ScheduleModel');
        $schedules = $schModel->getAllSchedules();

        // Filter by Date Range
        if (!empty($startDate) && !empty($endDate)) {
            $schedules = array_filter($schedules, function($row) use ($startDate, $endDate) {
                $evtStart = $row['start_date'];
                $evtEnd = !empty($row['end_date']) ? $row['end_date'] : $evtStart;
                return ($evtStart <= $endDate && $evtEnd >= $startDate);
            });
        }

        // Filter by Assistant
        if (!empty($assistantId) && $assistantId !== 'all') {
            $schedules = array_filter($schedules, function($row) use ($assistantId) {
                return strtolower($row['type'] ?? '') === 'umum' || (isset($row['id_profil']) && strval($row['id_profil']) === strval($assistantId));
            });
        }

        // Filter by Schedule Type
        if (!empty($scheduleType)) {
            $schedules = array_filter($schedules, function($row) use ($scheduleType) {
                return strtolower($row['type'] ?? '') === strtolower($scheduleType);
            });
        }

        // Sort schedules by Day, Time, Assistant Name, and Type
        usort($schedules, function($a, $b) {
            $dayA = intval($a['day_of_week'] ?? 0);
            $dayB = intval($b['day_of_week'] ?? 0);
            if ($dayA !== $dayB) return $dayA - $dayB;
            
            $timeA = $a['start_time'] ?? '00:00:00';
            $timeB = $b['start_time'] ?? '00:00:00';
            $cmpTime = strcasecmp($timeA, $timeB);
            if ($cmpTime !== 0) return $cmpTime;

            $nameA = $a['user_name'] ?? '';
            $nameB = $b['user_name'] ?? '';
            $cmpName = strcasecmp($nameA, $nameB);
            if ($cmpName !== 0) return $cmpName;

            $typeA = strtolower($a['type'] ?? '');
            $typeB = strtolower($b['type'] ?? '');
            return strcasecmp($typeA, $typeB);
        });

        $schedules = array_values($schedules);

        header('Content-Type: application/json');
        echo json_encode($schedules);
        exit;
    }

    public function addSchedule() {
        $this->checkAccess(['Kepala Lab']);
        $_SESSION['flash'] = ['type' => 'error', 'title' => 'Akses Ditolak', 'message' => 'Kepala Lab hanya memiliki akses lihat (Read-Only).'];
        header("Location: " . BASE_URL . "/kepalalab/schedule");
    }

    public function editSchedule() {
        $this->checkAccess(['Kepala Lab']);
        $_SESSION['flash'] = ['type' => 'error', 'title' => 'Akses Ditolak', 'message' => 'Kepala Lab hanya memiliki akses lihat (Read-Only).'];
        header("Location: " . BASE_URL . "/kepalalab/schedule");
    }

    public function deleteSchedule() {
        $this->checkAccess(['Kepala Lab']);
        $_SESSION['flash'] = ['type' => 'error', 'title' => 'Akses Ditolak', 'message' => 'Kepala Lab hanya memiliki akses lihat (Read-Only).'];
        header("Location: " . BASE_URL . "/kepalalab/schedule");
    }

    public function monitorAttendance() {
        $this->checkAccess(['Kepala Lab']);
        
        $data['judul'] = 'Rekap Presensi';
        $data['user'] = $this->model('UserModel')->getUserById($_SESSION['user_id']);
        $attModel = $this->model('AttendanceModel');

        $data['assistants_list'] = $attModel->getAllAssistantsList();

        $startDate = !empty($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d');
        $endDate = !empty($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
        $assistantId = !empty($_GET['assistant_id']) ? $_GET['assistant_id'] : null;

        $data['start_date'] = $startDate;
        $data['end_date'] = $endDate;
        $data['selected_assistant'] = $assistantId;

        $data['attendance_list'] = $attModel->getAttendanceRecap($startDate, $endDate, $assistantId);

        $data['page_css'] = [
            ASSET_URL . '/css/kepalalab/attendance.css'
        ];

        $data['page_js'] = [
            ASSET_URL . '/js/kepalalab/attendance.js'
        ];


        $this->view('layout/header', $data);
        $this->view('layout/sidebar', $data);
        $this->view('kepalalab/attendance', $data); 
        $this->view('layout/footer', $data);
    }

    public function exportCsv() {
        $this->checkAccess(['Kepala Lab']);
        
        $startDate = !empty($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
        $endDate = !empty($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
        $assistantId = !empty($_GET['assistant_id']) ? $_GET['assistant_id'] : null;

        $data = $this->model('AttendanceModel')->getAttendanceSummary($startDate, $endDate, $assistantId);
        $filename = "Rekap_Presensi_" . date('d-m-Y', strtotime($startDate)) . "_sd_" . date('d-m-Y', strtotime($endDate)) . ".csv";
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="'.$filename.'"');

        $output = fopen('php://output', 'w');
        // Kirim UTF-8 BOM agar Excel dapat mendeteksi encoding dan memisahkan kolom dengan benar
        fwrite($output, "\xEF\xBB\xBF");

        fputcsv($output, ['No', 'Nama Asisten', 'NIM', 'Jabatan', 'Masuk', 'Pulang', 'Hadir', 'Izin', 'Alpa', 'Total Kehadiran'], ';', '"', '');

        $no = 1;
        foreach ($data as $row) {
            fputcsv($output, [
                $no++,
                $row['name'],
                $row['nim'] ?? '-',
                $row['position'] ?? 'Asisten',
                $row['total_masuk'],
                $row['total_pulang'],
                $row['total_hadir'] . " Hari",
                $row['total_izin'] . " Hari",
                $row['total_alpa'] . " Hari",
                $row['total_hadir'] . " Hari"
            ], ';', '"', '');
        }
        fclose($output); exit;
    }

    public function exportPdf() {
        $this->checkAccess(['Kepala Lab']);
        
        $startDate = !empty($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
        $endDate = !empty($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
        $assistantId = !empty($_GET['assistant_id']) ? $_GET['assistant_id'] : null;
        
        $attModel = $this->model('AttendanceModel');
        $data['summary_data'] = $attModel->getAttendanceSummary($startDate, $endDate, $assistantId);
        
        $data['start_date'] = $startDate;
        $data['end_date'] = $endDate;
        
        $data['assistant_name'] = 'Semua Asisten';
        if($assistantId) {
            $user = $this->model('UserModel')->getUserById($assistantId);
            $data['assistant_name'] = $user['name'] ?? 'Asisten';
        }

        $data['css'] = 'kepalalab/pdf_attendance.css';

        $this->view('kepalalab/pdf_attendance', $data);
    }

    public function exportScheduleCsv() {
        $this->checkAccess(['Kepala Lab']);
        
        $startDate = !empty($_GET['start_date']) ? $_GET['start_date'] : '';
        $endDate = !empty($_GET['end_date']) ? $_GET['end_date'] : '';
        $assistantId = !empty($_GET['assistant_id']) ? $_GET['assistant_id'] : '';
        $scheduleType = !empty($_GET['schedule_type']) ? $_GET['schedule_type'] : '';
        $sortBy = !empty($_GET['sort_by']) ? $_GET['sort_by'] : 'hari_waktu';

        $schModel = $this->model('ScheduleModel');
        $schedules = $schModel->getAllSchedules();
        
        // Filter by Date Range
        if (!empty($startDate) && !empty($endDate)) {
            $schedules = array_filter($schedules, function($row) use ($startDate, $endDate) {
                $evtStart = $row['start_date'];
                $evtEnd = !empty($row['end_date']) ? $row['end_date'] : $evtStart;
                return ($evtStart <= $endDate && $evtEnd >= $startDate);
            });
        }

        // Filter by Assistant
        if (!empty($assistantId) && $assistantId !== 'all') {
            $schedules = array_filter($schedules, function($row) use ($assistantId) {
                return strtolower($row['type'] ?? '') === 'umum' || (isset($row['id_profil']) && strval($row['id_profil']) === strval($assistantId));
            });
        }

        // Filter by Schedule Type
        if (!empty($scheduleType)) {
            $schedules = array_filter($schedules, function($row) use ($scheduleType) {
                return strtolower($row['type'] ?? '') === strtolower($scheduleType);
            });
        }

        // Sort schedules by Day, Time, Assistant Name, and Type
        usort($schedules, function($a, $b) {
            $dayA = intval($a['day_of_week'] ?? 0);
            $dayB = intval($b['day_of_week'] ?? 0);
            if ($dayA !== $dayB) return $dayA - $dayB;
            
            $timeA = $a['start_time'] ?? '00:00:00';
            $timeB = $b['start_time'] ?? '00:00:00';
            $cmpTime = strcasecmp($timeA, $timeB);
            if ($cmpTime !== 0) return $cmpTime;

            $nameA = $a['user_name'] ?? '';
            $nameB = $b['user_name'] ?? '';
            $cmpName = strcasecmp($nameA, $nameB);
            if ($cmpName !== 0) return $cmpName;

            $typeA = strtolower($a['type'] ?? '');
            $typeB = strtolower($b['type'] ?? '');
            return strcasecmp($typeA, $typeB);
        });

        $filename = "Jadwal_Asisten_Lab_" . date('d-m-Y') . ".csv";
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        fwrite($output, "\xEF\xBB\xBF");
        
        fputcsv($output, ['No', 'Jenis Jadwal', 'Nama Asisten / PIC', 'Kegiatan / Mata Kuliah', 'Kelas', 'Dosen Pengampu', 'Hari', 'Waktu', 'Lokasi'], ';', '"', '');
        
        $dayMap = [
            1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 
            5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'
        ];
        
        $no = 1;
        foreach ($schedules as $row) {
            $typeFmt = match(strtolower($row['type'] ?? '')) {
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
                $timeStr = date('H:i', strtotime($row['start_time'])) . ' - ' . date('H:i', strtotime($row['end_time'])) . ' WITA';
            }
            
            fputcsv($output, [
                $no++,
                $typeFmt,
                $row['user_name'] ?? 'Laboratorium',
                $row['title'] ?? '-',
                $row['kelas'] ?? '-',
                $row['dosen'] ?? '-',
                $dayName,
                $timeStr,
                $row['location'] ?? 'Lab'
            ], ';', '"', '');
        }
        fclose($output);
        exit;
    }

    public function exportSchedulePdf() {
        $this->checkAccess(['Kepala Lab']);
        
        $startDate = !empty($_GET['start_date']) ? $_GET['start_date'] : '';
        $endDate = !empty($_GET['end_date']) ? $_GET['end_date'] : '';
        $assistantId = !empty($_GET['assistant_id']) ? $_GET['assistant_id'] : '';
        $scheduleType = !empty($_GET['schedule_type']) ? $_GET['schedule_type'] : '';
        $sortBy = !empty($_GET['sort_by']) ? $_GET['sort_by'] : 'hari_waktu';

        $schModel = $this->model('ScheduleModel');
        $schedules = $schModel->getAllSchedules();
        
        // Filter by Date Range
        if (!empty($startDate) && !empty($endDate)) {
            $schedules = array_filter($schedules, function($row) use ($startDate, $endDate) {
                $evtStart = $row['start_date'];
                $evtEnd = !empty($row['end_date']) ? $row['end_date'] : $evtStart;
                return ($evtStart <= $endDate && $evtEnd >= $startDate);
            });
        }

        // Filter by Assistant
        if (!empty($assistantId) && $assistantId !== 'all') {
            $schedules = array_filter($schedules, function($row) use ($assistantId) {
                return strtolower($row['type'] ?? '') === 'umum' || (isset($row['id_profil']) && strval($row['id_profil']) === strval($assistantId));
            });
        }

        // Filter by Schedule Type
        if (!empty($scheduleType)) {
            $schedules = array_filter($schedules, function($row) use ($scheduleType) {
                return strtolower($row['type'] ?? '') === strtolower($scheduleType);
            });
        }

        // Sort schedules by Day, Time, Assistant Name, and Type
        usort($schedules, function($a, $b) {
            $dayA = intval($a['day_of_week'] ?? 0);
            $dayB = intval($b['day_of_week'] ?? 0);
            if ($dayA !== $dayB) return $dayA - $dayB;
            
            $timeA = $a['start_time'] ?? '00:00:00';
            $timeB = $b['start_time'] ?? '00:00:00';
            $cmpTime = strcasecmp($timeA, $timeB);
            if ($cmpTime !== 0) return $cmpTime;

            $nameA = $a['user_name'] ?? '';
            $nameB = $b['user_name'] ?? '';
            $cmpName = strcasecmp($nameA, $nameB);
            if ($cmpName !== 0) return $cmpName;

            $typeA = strtolower($a['type'] ?? '');
            $typeB = strtolower($b['type'] ?? '');
            return strcasecmp($typeA, $typeB);
        });

        $data['schedules'] = $schedules;
        $data['start_date'] = $startDate;
        $data['end_date'] = $endDate;
        $data['selected_assistant'] = $assistantId;
        $data['schedule_type'] = $scheduleType;
        $data['sort_by'] = $sortBy;
        
        $data['report_title_name'] = 'Semua Asisten';
        if (!empty($assistantId) && $assistantId !== 'all') {
            $assistants = $this->model('UserModel')->getAssistantsWithProfile();
            foreach ($assistants as $ast) {
                if (strval($ast['id_profil']) === strval($assistantId)) {
                    $data['report_title_name'] = $ast['name'];
                    break;
                }
            }
        }
        
        $this->view('kepalalab/pdf_schedule', $data);
    }

    public function logbook() {
        $this->checkAccess(['Kepala Lab']);
        $data['judul'] = 'Monitoring Logbook';
        $userModel = $this->model('UserModel');
        $data['user'] = $userModel->getUserById($_SESSION['user_id']);
        
        $allUsers = $userModel->getAllUsers();
        $data['assistants'] = array_filter($allUsers, fn($u) => $u['role'] == 'User');

        $data['css'] = 'kepalalab/logbook.css';
        $data['js']  = 'kepalalab/logbook.js';

        $data['js_config'] = [
            'BASE_URL' => BASE_URL
        ];

        $this->view('layout/header', $data);
        $this->view('layout/sidebar', $data);
        $this->view('kepalalab/logbook', $data); 
        $this->view('layout/footer', $data);
    }

    public function getLogsByUser() {
        if (!isset($_SESSION['role']) || $_SESSION['role'] != 'Kepala Lab') {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            exit;
        }
        $userId = $_POST['user_id'] ?? 0;
        $logs = $this->model('LogbookModel')->getUnifiedLogbook($userId);
        echo json_encode($logs);
    }

    public function reset_logbook() {
        if (!isset($_SESSION['role']) || $_SESSION['role'] != 'Kepala Lab') {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            exit;
        }
        echo json_encode(['status' => 'error', 'message' => 'Akses Ditolak. Kepala Lab hanya memiliki akses lihat (Read-Only).']);
    }

    public function saveLogbookAdmin() {
        if (!isset($_SESSION['role']) || $_SESSION['role'] != 'Kepala Lab') {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            exit;
        }
        echo json_encode(['status' => 'error', 'message' => 'Akses Ditolak. Kepala Lab hanya memiliki akses lihat (Read-Only).']);
    }

    public function deleteLogbook() {
        if (!isset($_SESSION['role']) || $_SESSION['role'] != 'Kepala Lab') {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            exit;
        }
        echo json_encode(['status' => 'error', 'message' => 'Akses Ditolak. Kepala Lab hanya memiliki akses lihat (Read-Only).']);
    }

    public function profile() {
        $this->checkAccess(['Kepala Lab']);

        $data['judul'] = 'Profil Kepala Lab';
        $userModel = $this->model('UserModel');
        $data['user'] = $userModel->getUserById($_SESSION['user_id']);
        
        $data['is_google_connected'] = $userModel->isGoogleConnected($_SESSION['user_id']);
        $data['google_configured'] = (new GoogleClient())->isConfigured();
        $data['total_managed_users'] = $userModel->countUsersByRole('User');

        $attModel = $this->model('AttendanceModel');
        $data['chart_data'] = $attModel->getChartData(); 

        $data['demographics'] = $userModel->getDemographics();
        
        $allSchedules = $this->model('ScheduleModel')->getAllSchedules();
        $upcoming = array_filter($allSchedules, function($s) {
            return ($s['type'] == 'umum') && ($s['start_date'] >= date('Y-m-d'));
        });
        usort($upcoming, function($a, $b) { return strtotime($a['start_date']) - strtotime($b['start_date']); });
        $data['upcoming_schedules'] = array_slice($upcoming, 0, 5);
        foreach($data['upcoming_schedules'] as &$sch) {
            $sch['display_date'] = date('d M Y', strtotime($sch['start_date']));
        }
        
        $data['rankings'] = [
            'online'           => $userModel->getAssistantRankings('online'),
            'rajin'            => $userModel->getAssistantRankings('rajin'),
            'jarang'           => $userModel->getAssistantRankings('jarang'),
            'cepat'            => $userModel->getAssistantRankings('cepat'),
            'terlambat'        => $userModel->getAssistantRankings('terlambat'),
            'sering_izin'     => $userModel->getAssistantRankings('sering_izin'),
            'logbook_lengkap' => $userModel->getAssistantRankings('logbook_lengkap'),
            'logbook_singkat' => $userModel->getAssistantRankings('logbook_singkat'),
            'sibuk'            => $userModel->getAssistantRankings('sibuk'),
            'santai'          => $userModel->getAssistantRankings('santai'),
        ];

        $data['page_css'] = [
            ASSET_URL . '/css/common/profile.css'
        ];

        $data['page_js'] = [
            'https://cdn.jsdelivr.net/npm/chart.js',
            ASSET_URL . '/js/common/profile.js'
        ];

        $data['js_config'] = [
            'BASE_URL' => BASE_URL,
            'RAW_DEMOGRAPHICS' => $data['demographics'] ?? [],
            'RANKINGS' => $data['rankings'] ?? [],
            'USER_STATS' => $data['stats'] ?? null,
            'IS_USER_ROLE' => ($data['user']['role'] === 'User')
        ];

        $this->view('layout/header', $data);
        $this->view('layout/sidebar', $data);
        $this->view('common/profile', $data); 
        $this->view('layout/footer', $data);
    }

    public function editProfile() {
        $this->checkAccess(['Kepala Lab']);
        
        $data['judul'] = 'Edit Profil';
        $data['user'] = $this->model('UserModel')->getUserById($_SESSION['user_id']);

        // [PERBAIKAN] Kepala Lab hanya dapat mengakses halaman edit profil SATU
        // KALI (selama belum melengkapi profil) — sama seperti Asisten.
        if (!empty($data['user']['is_completed'])) {
            $_SESSION['google_modal'] = [
                'type' => 'locked',
                'title' => 'Profil Terkunci',
                'message' => 'Anda sudah melengkapi profil. Data tidak dapat diubah lagi. Hubungi Administrator jika terdapat kesalahan.'
            ];
            header('Location: ' . BASE_URL . '/kepalalab/profile');
            exit;
        }

        // [PERBAIKAN] Kepala Lab "Tanpa data laboratorium" pada edit profil sendiri
        // (assignment id_lab dikelola Admin lewat Manajemen Pengguna), jadi
        // common/edit_profile.php tidak menampilkan field Laboratorium untuk role
        // ini — $data['labs'] tidak diperlukan lagi.

        $data['role'] = $_SESSION['role'];
        $data['isUser'] = ($_SESSION['role'] == 'User');
        $data['isAdmin'] = ($_SESSION['role'] == 'Admin'); 

        $data['page_css'] = [
            'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css',
            ASSET_URL . '/css/common/edit_profile.css'
        ];

        $data['page_js'] = [
            'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js',
            ASSET_URL . '/js/common/edit_profile.js'
        ];

        $data['js_config'] = [
            'BASE_URL' => BASE_URL,
            'USER_ROLE' => $data['user']['role']
        ];

        $this->view('layout/header', $data);
        $this->view('layout/sidebar', $data);
        $this->view('common/edit_profile', $data);
        $this->view('layout/footer', $data);
    }

    public function updateProfile() {
        if (!isset($_SESSION['role']) || $_SESSION['role'] != 'Kepala Lab') {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            ob_clean(); header('Content-Type: application/json');
            $userModel = $this->model('UserModel');
            $currentUser = $userModel->getUserById($_SESSION['user_id']);

            // [PERBAIKAN] Kepala Lab hanya dapat melengkapi/mengubah profil SATU KALI.
            if (!empty($currentUser['is_completed'])) {
                echo json_encode(['status' => 'error', 'title' => 'Profil Terkunci', 'message' => 'Profil Anda sudah dikunci dan tidak dapat diubah lagi. Hubungi Administrator jika terdapat kesalahan.']);
                exit;
            }

            if (empty($_POST['name']) || empty($_POST['phone']) || empty($_POST['address'])) {
                echo json_encode(['status' => 'error', 'message' => 'Data wajib diisi.']); exit;
            }

            $photoName = $currentUser['photo_profile'];
            $targetDir = UPLOAD_PATH . 'profile/';
            // [BARU - Audit Validasi Foto] hanya terima JPG/JPEG/PNG, selaras
            // dengan accept="image/png, image/jpeg, image/jpg" pada input file.
            $allowedExtensions = ['jpg', 'jpeg', 'png'];

            if (!empty($_POST['cropped_image'])) {
                $dataImg = $_POST['cropped_image'];
                if (preg_match('/^data:image\/(\w+);base64,/', $dataImg, $type)) {
                    $type = strtolower($type[1]); 
                    if (!in_array($type, $allowedExtensions)) {
                        echo json_encode(['status' => 'error', 'title' => 'Format Tidak Didukung', 'message' => 'Foto harus berformat JPG, JPEG, atau PNG.']);
                        exit;
                    }
                    $dataImg = substr($dataImg, strpos($dataImg, ',') + 1);
                    $decodedData = base64_decode($dataImg);
                    if ($decodedData !== false) {
                        if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);
                        $fileName = time() . '_' . uniqid() . '.' . $type;
                        if (file_put_contents($targetDir . $fileName, $decodedData)) {
                            $photoName = $fileName;
                            $_SESSION['photo'] = $fileName;
                            if ($currentUser['photo_profile'] && $currentUser['photo_profile'] != 'default.jpg' && file_exists($targetDir . $currentUser['photo_profile'])) {
                                unlink($targetDir . $currentUser['photo_profile']);
                            }
                        }
                    }
                }
            } elseif (isset($_FILES['photo']['name']) && $_FILES['photo']['name'] != "") {
                $fileExt = strtolower(pathinfo($_FILES["photo"]["name"], PATHINFO_EXTENSION));
                if (!in_array($fileExt, $allowedExtensions)) {
                    echo json_encode(['status' => 'error', 'title' => 'Format Tidak Didukung', 'message' => 'Foto harus berformat JPG, JPEG, atau PNG.']);
                    exit;
                }
                if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);
                $fileName = time() . '_' . uniqid() . '.' . $fileExt;
                if (move_uploaded_file($_FILES["photo"]["tmp_name"], $targetDir . $fileName)) {
                    $photoName = $fileName;
                    $_SESSION['photo'] = $fileName;
                    if ($currentUser['photo_profile'] && $currentUser['photo_profile'] != 'default.jpg' && file_exists($targetDir . $currentUser['photo_profile'])) {
                        unlink($targetDir . $currentUser['photo_profile']);
                    }
                }
            }

            $isCompleted = (!empty($_POST['name']) && !empty($_POST['phone']) && !empty($_POST['address'])) ? 1 : 0;

            // [BARU - Edit Email/Password] validasi email (wajib, format,
            // & keunikan) dan password baru (opsional, minimal 8 karakter)
            // - sama seperti pada form Tambah/Edit Pengguna Admin.
            $newEmail = trim($_POST['email'] ?? '');
            if (empty($newEmail) || !filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['status' => 'error', 'title' => 'Gagal', 'message' => 'Email tidak valid.']); exit;
            }
            if ($newEmail !== $currentUser['email'] && $userModel->isEmailTaken($newEmail, $_SESSION['user_id'])) {
                echo json_encode(['status' => 'error', 'title' => 'Gagal', 'message' => 'Email sudah digunakan oleh akun lain.']); exit;
            }

            $newPassword = $_POST['password'] ?? '';
            if (!empty($newPassword) && strlen($newPassword) < 8) {
                echo json_encode(['status' => 'error', 'title' => 'Gagal', 'message' => 'Password baru minimal 8 karakter.']); exit;
            }

            // [PERBAIKAN] Per spesifikasi, Kepala Lab "Tanpa data laboratorium" pada
            // halaman edit profil sendiri — assignment id_lab dikelola Admin lewat
            // Manajemen Pengguna. Pertahankan id_lab lama agar tidak tertimpa NULL
            // hanya karena form tidak lagi mengirim 'lab_id'.
            $labId = $currentUser['id_lab'] ?? null;

            $data = [
                'id'       => $_SESSION['user_id'],
                'role'     => 'Kepala Lab',
                'name'     => $_POST['name'],
                'email'    => $newEmail,
                'password' => $newPassword,
                'nim'      => $_POST['nim'] ?? null,
                'position' => $_POST['position'] ?? 'Kepala Lab',
                'prodi'    => null,
                'phone'    => $_POST['phone'],
                'address'  => $_POST['address'],
                'gender'   => $_POST['gender'],
                'interest' => null,
                'photo'    => ($photoName != $currentUser['photo_profile']) ? $photoName : null,
                'is_completed' => $isCompleted,
                'lab_id'   => $labId
            ];

            if ($userModel->updateSelfProfile($data)) {
                $_SESSION['name'] = $_POST['name'];
                $_SESSION['jabatan'] = $_POST['position'];
                
                echo json_encode([
                    'status'   => 'success', 
                    'title'    => 'Berhasil',
                    'message'  => 'Profil berhasil diperbarui.',
                    'redirect' => BASE_URL . '/kepalalab/profile'
                ]);
            } else {
                echo json_encode(['status' => 'error', 'title' => 'Gagal', 'message' => 'Gagal memperbarui profil.']);
            }
            exit;
        }
    }

    public function getQrAjax() {
        if (!isset($_SESSION['role']) || $_SESSION['role'] != 'Kepala Lab') {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            exit;
        }
        
        $type = $_POST['type'] ?? 'check_in'; 
        $token = $this->model('QrModel')->getOrGenerateToken($type);
        
        $qrString = json_encode([
            'type' => ($type == 'check_in') ? 'CHECK_IN' : 'CHECK_OUT', 
            'token' => $token
        ]);
        
        echo json_encode(['status' => 'success', 'qr_data' => $qrString]);
    }

    public function assistantDetail($id) {
        $this->checkAccess(['Kepala Lab']);

        $userModel = $this->model('UserModel');
        $attModel = $this->model('AttendanceModel');
        $assistant = $userModel->getUserById($id);

        if (!$assistant || $assistant['role'] != 'User') {
            header("Location: " . BASE_URL . "/kepalalab/dashboard");
            exit;
        }

        $data['judul'] = 'Detail Asisten';
        $data['css'] = 'kepalalab/detail_assistant.css';
        $data['js'] = 'kepalalab/detail_assistant.js';
        $data['user'] = $userModel->getUserById($_SESSION['user_id']); 
        $data['assistant'] = $assistant; 

        $pId = $assistant['id_profil'];
        $userStats = $attModel->getUserStats($pId);
        $alpa = $userModel->calculateRealAlpha($pId, $assistant['created_at'], $assistant['is_completed']);

        $data['stats'] = [
            'hadir' => $userStats['hadir'], 
            'izin' => $userStats['izin'], 
            'alpa' => $alpa
        ];

        $data['logs'] = $this->model('LogbookModel')->getUserLogbookHistory($id);
        $data['schedules'] = $this->model('ScheduleModel')->getAllUserSchedules($id);

        $data['js_config'] = [
            'STATS' => $data['stats']
        ];

        $this->view('layout/header', $data);
        $this->view('layout/sidebar', $data);
        $this->view('kepalalab/detail_assistant', $data);
        $this->view('layout/footer', $data);
    }

    public function assistantSchedule($id) {
        $this->checkAccess(['Kepala Lab']);

        $assistant = $this->model('UserModel')->getUserById($id);
        if (!$assistant || $assistant['role'] != 'User') {
            header("Location: " . BASE_URL . "/kepalalab/dashboard");
            exit;
        }

        $data['judul'] = 'Jadwal Asisten';
        $data['user'] = $this->model('UserModel')->getUserById($_SESSION['user_id']); 
        $data['assistant'] = $assistant; 

        $rawSchedules = $this->model('ScheduleModel')->getAllUserSchedules($id);
        $finalSchedules = [];
        $now = time();
        
        $dayMap = [
            'Monday'=>1, 'Tuesday'=>2, 'Wednesday'=>3, 'Thursday'=>4, 'Friday'=>5, 'Saturday'=>6, 'Sunday'=>7,
            'Senin'=>1, 'Selasa'=>2, 'Rabu'=>3, 'Kamis'=>4, 'Jumat'=>5, 'Sabtu'=>6, 'Minggu'=>7,
            1=>1, 2=>2, 3=>3, 4=>4, 5=>5, 6=>6, 7=>7
        ];

        foreach ($rawSchedules as $sch) {
            $isWeekly = (isset($sch['model_perulangan']) && strtolower($sch['model_perulangan']) == 'mingguan');
            $startTime = $sch['start_time'];
            $endTime = $sch['end_time'] ?: '23:59:00'; 
            $targetDate = '';

            if ($isWeekly) {
                $scheduleDayNum = isset($dayMap[$sch['day_of_week']]) ? $dayMap[$sch['day_of_week']] : 0;
                
                if ($scheduleDayNum > 0) {
                    $currentDayNum = date('N'); 
                    $diff = $scheduleDayNum - $currentDayNum;
                    
                    $isTodayButPassed = ($diff == 0 && strtotime(date('Y-m-d') . ' ' . $endTime) < $now);

                    if ($diff < 0 || $isTodayButPassed) {
                        $diff += 7; 
                    }
                    
                    $targetDate = date('Y-m-d', strtotime("+$diff days"));
                } else {
                    continue;
                }
            } else {
                $targetDate = $sch['start_date'];
            }

            $scheduleEndTimestamp = strtotime("$targetDate $endTime");
            $scheduleStartTimestamp = strtotime("$targetDate $startTime");

            if ($scheduleEndTimestamp < $now) {
                continue;
            }

            $sch['sort_timestamp'] = $scheduleStartTimestamp;
            $sch['start_date'] = $targetDate; 
            $finalSchedules[] = $sch;
        }

        usort($finalSchedules, function($a, $b) {
            return $a['sort_timestamp'] - $b['sort_timestamp'];
        });

        $limit = 6; 
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $totalData = count($finalSchedules);
        $totalPages = ceil($totalData / $limit);

        if ($page < 1) $page = 1;
        if ($page > $totalPages && $totalPages > 0) $page = $totalPages;

        $offset = ($page - 1) * $limit;
        $data['schedules'] = array_slice($finalSchedules, $offset, $limit);

        $data['pagination'] = [
            'current' => $page,
            'total_pages' => $totalPages,
            'total_items' => $totalData
        ];

        $data['css'] = 'kepalalab/assistant_schedule.css';

        $this->view('layout/header', $data);
        $this->view('layout/sidebar', $data);
        $this->view('kepalalab/assistant_schedule', $data); 
        $this->view('layout/footer', $data);
    }

    
}
?>