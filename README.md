# Sistem Monitoring Asisten

![PHP](https://img.shields.io/badge/Backend-PHP%20Native%20MVC-blue)
![MySQL](https://img.shields.io/badge/Database-MySQL-orange)
![Tailwind](https://img.shields.io/badge/Frontend-TailwindCSS-06B6D4)

**Sistem Monitoring Asisten** adalah aplikasi berbasis web yang dirancang untuk memanajemen operasional Laboratorium Komputer. Sistem ini menangani presensi asisten (QR Code & Selfie), penjadwalan terintegrasi Google Calendar, pengelolaan logbook harian, serta monitoring kinerja asisten secara *real-time*.

Dibangun menggunakan arsitektur **MVC (Model-View-Controller)** murni dengan PHP Native tanpa framework, menjadikannya ringan, cepat, dan mudah dikustomisasi.

---

## Fitur Unggulan

### Keamanan & Autentikasi
- **Role-Based Access Control (RBAC):** Login terpisah untuk Admin, Kepala Lab, dan User (Asisten).
- **Secure Login:** Password hashing (`password_verify`) dan proteksi sesi.
- **Error Handling:** Halaman error kustom (403, 404, 500) untuk keamanan UX.

### Presensi & Jadwal
- **Smart Attendance:** Check-in menggunakan scan **QR Code** yang merotasi token setiap 3 jam + Validasi lokasi.
- **Anti-Fraud Selfie:** Bukti kehadiran wajib foto selfie dengan *watermark* waktu server (anti galeri lama).
- **Google Calendar Sync:** Sinkronisasi jadwal lab otomatis ke akun Google Calendar asisten.
- **Real Alpha Calculation:** Algoritma cerdas yang menghitung ketidakhadiran berdasarkan kalender akademik (bukan sekadar hitung baris database).

### Dashboard & Monitoring
- **Admin Dashboard:** Grafik statistik kehadiran (Harian/Mingguan), manajemen user, dan ekspor laporan (PDF/CSV).
- **Kepala Lab View:** Mode "Read-Only" untuk memantau kinerja asisten, melihat logbook, dan statistik tanpa risiko mengubah data.
- **Leaderboard:** Gamifikasi ranking asisten berdasarkan kerajinan dan kedisiplinan.

### Administrasi
- **Digital Logbook:** Pencatatan aktivitas harian asisten yang terintegrasi dengan jam kerja.
- **Image Cropping:** Fitur potong foto profil otomatis (1:1) saat upload.
- **Responsive UI:** Tampilan sidebar adaptif dan mobile-friendly menggunakan TailwindCSS.

---

## 📂 Struktur Folder (MVC)

Berikut adalah struktur direktori proyek berdasarkan arsitektur MVC yang digunakan:

```text
iclabs_v2/
├── app/
│   ├── config/
│   │   └── config.php
│   ├── controllers/
│   │   ├── AdminController.php
│   │   ├── AuthController.php
│   │   ├── KepalaLabController.php
│   │   ├── UserController.php
│   │   └── ...
│   ├── core/
│   │   ├── App.php    
│   │   ├── Controller.php 
│   │   ├── Database.php  
│   │   └── GoogleClient.php 
│   ├── models/        
│   │   ├── UserModel.php
│   │   ├── AttendanceModel.php
│   │   ├── ScheduleModel.php
│   │   └── ...
│   └── views/             
│       ├── admin/      
│       ├── user/          
│       ├── kepalalab/  
│       ├── auth/      
│       ├── layout/      
│       └── errors/  
├── public/              
│   ├── css/
│   ├── js/
│   ├── uploads/        
│   └── index.php       
└── vendor/
```
---
## LINK [FLOWCHART](https://app.diagrams.net/?src=about#G1UnRBj-WlMVfPQSxdHRfsSCjT8DFKX0oz#%7B%22pageId%22%3A%22hbGYuj2Pi25B7b6xom5o%22%7D)
## LINK [ERD](https://app.diagrams.net/?src=about#G1CLHcQsatMvsAwDfnKUA-MYQ1wnAP3Ldv#%7B%22pageId%22%3A%22xvckEQKs8oghxl2EZW2P%22%7D)
## LINK [WIREFRAME](https://www.figma.com/design/tiJHZlwOKuYbbNkx7Aqb76/web-anti-ninja?node-id=0-1&m=dev&t=6QWWBSl43Ls9HrYw-1)
---
##KELOMPOK 4
- Firly Anastasya Hafid
- Nurfajri Mukmin Saputra
- Muhammad Nur Fuad
