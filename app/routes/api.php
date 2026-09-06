<?php
// app/routes/api.php

return function($url) {
    if (strpos($url, 'logbook/delete') !== false) {
        $api = new LogbookApi();
        $api->delete();
        exit;
    }

    $apiUrl = str_replace('api/', '', $url);
    $urlParts = explode('/', trim($apiUrl, '/'));
    
    // ==================== AUTH ====================
    if ($urlParts[0] === 'auth') {
        $api = new AuthApi();
        
        if ($urlParts[1] === 'login') {
            $api->login();
            exit;
        } elseif ($urlParts[1] === 'refresh') {
            $api->refresh();
            exit;
        } elseif ($urlParts[1] === 'validate') {
            $api->validateToken();
            exit;
        }
    }

    // ==================== USER ====================
    if ($urlParts[0] === 'user') {
        $api = new UserApi();
        
        if ($urlParts[1] === 'profile') {
            $api->profile();
            exit;
        } elseif ($urlParts[1] === 'update') {
            $api->update();
            exit;
        }
        elseif ($urlParts[1] === 'change-password') {
            $api->changePassword();
            exit;
        }
    }

    // ==================== ATTENDANCE ====================
    if ($urlParts[0] === 'attendance') {
        $api = new AttendanceApi();
        
        if ($urlParts[1] === 'clock-in') {
            $api->clockIn();
            exit;
        } elseif ($urlParts[1] === 'clock-out') {
            $api->clockOut();
            exit;
        } elseif ($urlParts[1] === 'today') {
            $api->today();
            exit;
        } elseif ($urlParts[1] === 'history') {
            $api->history();
            exit;
        } elseif ($urlParts[1] === 'stats') {
            $api->stats();
            exit;
        } elseif ($urlParts[1] === 'photo' && isset($urlParts[2])) {
            $api->getPhoto($urlParts[2]);
            exit;
        } elseif ($urlParts[1] === 'search') {
            $api->search();
            exit;
        } elseif ($urlParts[1] === 'export') {
            $api->exportReport();
            exit;
        }
    }
    
    // ==================== SCHEDULE / JADWAL ====================
    if ($urlParts[0] === 'schedule') {
        $api = new ScheduleApi();

        if (isset($urlParts[1]) && $urlParts[1] === 'add') {
            $api->add();
            exit;
        } elseif (isset($urlParts[1]) && $urlParts[1] === 'week') {
            $api->week();
            exit;
        } elseif (isset($urlParts[1]) && $urlParts[1] === 'detail' && isset($urlParts[2])) {
            $api->detail($urlParts[2]);
            exit;
        } elseif (isset($urlParts[1]) && $urlParts[1] === 'kuliah') {
            $api->getKuliah();
            exit;
        } 
        // 🚀 BARU & KUNCI UTAMA: Daftarkan rute formOptions untuk auto-fill data web admin
        elseif (isset($urlParts[1]) && $urlParts[1] === 'formOptions') {
            $api->getFormOptions();
            exit;
        } 
        elseif (isset($urlParts[1]) && $urlParts[1] === 'asisten') {
            $api->getAsisten();
            exit;
        } elseif (isset($urlParts[1]) && $urlParts[1] === 'lab') {
            $api->getLab();
            exit;
        } elseif (isset($urlParts[1]) && $urlParts[1] === 'delete') {
            $api->delete();
            exit;
        }elseif (isset($urlParts[1]) && $urlParts[1] === 'piket') {
            $api->getPiket();
            exit;
        }
    }

    if (strpos($_SERVER['REQUEST_URI'], 'logbook/update') !== false) {
        $api = new LogbookApi();
        $api->update();
        exit;
    }

    if (strpos($_SERVER['REQUEST_URI'], 'logbook/delete') !== false) {
        $api = new LogbookApi();
        $api->delete();
        exit;
    }

    // ==================== LOGBOOK (FIXED & MATCHED WITH FLUTTER BODY JSON) ====================
    if ($urlParts[0] === 'logbook') {
        $api = new LogbookApi();
        
        if ($urlParts[1] === 'getlist') {
            $api->getlist();
            exit;
        }
        // [DIHAPUS - Tahap 13 V3] Rute detail/create/search/export dihapus
        // bersamaan dengan method-nya di LogbookApi (lihat komentar di sana)
        // - tidak dipakai aplikasi mobile & mereferensikan kolom yang tidak
        // ada di skema `logbook`.
        elseif ($urlParts[1] === 'update') {
            $api->update();
            exit;
        } 
        // 🚀 SOLUSI HAPUS: Arahkan rute logbook/delete dengan benar tanpa interupsi bypass
        elseif ($urlParts[1] === 'delete') {
            $api->delete();
            exit;
        }
    }

    // ==================== IZIN (LEAVE) ====================
    if ($urlParts[0] === 'izin') {
        $api = new IzinApi();
        
        if ($urlParts[1] === 'list') {
            $api->getlist();
            exit;
        } elseif ($urlParts[1] === 'create') {
            $api->create();
            exit;
        } elseif ($urlParts[1] === 'detail' && isset($urlParts[2])) {
            $api->detail($urlParts[2]);
            exit;
        } elseif ($urlParts[1] === 'update' && isset($urlParts[2])) {
            $api->update($urlParts[2]);
            exit;
        } elseif ($urlParts[1] === 'cancel' && isset($urlParts[2])) {
            $api->cancel($urlParts[2]);
            exit;
        } elseif ($urlParts[1] === 'approve' && isset($urlParts[2])) {
            $api->approve($urlParts[2]);
            exit;
        } elseif ($urlParts[1] === 'reject' && isset($urlParts[2])) {
            $api->reject($urlParts[2]);
            exit;
        } elseif ($urlParts[1] === 'admin' && $urlParts[2] === 'pending') {
            $api->getPendingForAdmin();
            exit;
        }
    }

    // ==================== QR CODE ====================
    if ($urlParts[0] === 'qr') {
        $api = new QrApi();
        
        if ($urlParts[1] === 'generate') {
            $api->generate();
            exit;
        } elseif ($urlParts[1] === 'scan') {
            $api->scan();
            exit;
        } elseif ($urlParts[1] === 'validate') {
            $api->validate();
            exit;
        }
    }

    // ==================== DASHBOARD ====================
    if ($urlParts[0] === 'dashboard') {
        $api = new DashboardApi();
        
        if ($urlParts[1] === 'summary') {
            $api->summary();
            exit;
        } elseif ($urlParts[1] === 'upcoming-schedule') {
            $api->upcomingSchedule();
            exit;
        } elseif ($urlParts[1] === 'upcoming-schedule-mobile') {
            $api->upcomingScheduleMobile();
            exit;
        }
    }

    // ==================== NOTIFICATION ====================
    if ($urlParts[0] === 'notification') {
        $api = new NotificationApi();
        
        if ($urlParts[1] === 'list') {
            $api->getlist();
            exit;
        } elseif ($urlParts[1] === 'mark-read' && isset($urlParts[2])) {
            $api->markRead($urlParts[2]);
            exit;
        } elseif ($urlParts[1] === 'delete' && isset($urlParts[2])) {
            $api->delete($urlParts[2]);
            exit;
        }
    }

    // ==================== STATISTICS ====================
    if ($urlParts[0] === 'stats') {
        $api = new StatsApi();
        
        if ($urlParts[1] === 'attendance-monthly') {
            $api->attendanceMonthly();
            exit;
        } elseif ($urlParts[1] === 'attendance-yearly') {
            $api->attendanceYearly();
            exit;
        } elseif ($urlParts[1] === 'punctuality') {
            $api->punctuality();
            exit;
        }
    }

    // ==================== DEVICE ====================
    if ($urlParts[0] === 'devices') {
        $api = new DeviceApi();
        
        if ($urlParts[1] === 'register') {
            $api->register();
            exit;
        } elseif ($urlParts[1] === 'list') {
            $api->getlist();
            exit;
        } elseif ($urlParts[1] === 'delete' && isset($urlParts[2])) {
            $api->delete($urlParts[2]);
            exit;
        }
    }

    // ==================== DEFAULT 404 ====================
    ApiResponse::error('API Route Not Found', 404);
    exit;
};