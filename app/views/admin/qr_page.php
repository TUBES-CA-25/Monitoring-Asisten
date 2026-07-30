<?php /* app/views/admin/qr_page.php — Halaman QR Presensi (Toggle Design) */ ?>

<style>
@keyframes qr-pulse-in  { 0%,100%{box-shadow:0 0 0 0 rgba(37,99,235,.4),0 0 0 0 rgba(8,145,178,.25)} 50%{box-shadow:0 0 0 20px rgba(37,99,235,0),0 0 0 36px rgba(8,145,178,0)} }
@keyframes qr-pulse-out { 0%,100%{box-shadow:0 0 0 0 rgba(234,88,12,.4)} 50%{box-shadow:0 0 0 20px rgba(234,88,12,0)} }
.qr-pulse-in  { animation: qr-pulse-in  2.6s ease-in-out infinite; }
.qr-pulse-out { animation: qr-pulse-out 2.6s ease-in-out infinite; }
@keyframes qr-fadein { from{opacity:0;transform:scale(.9)} to{opacity:1;transform:scale(1)} }
.qr-fadein { animation: qr-fadein .3s ease-out; }
.qr-header { background: linear-gradient(110deg,#1d4ed8 0%,#0891b2 100%); position:relative; overflow:hidden; }
/* Toggle pill */
.toggle-pill { transition: all .25s cubic-bezier(.4,0,.2,1); }
</style>

<!-- [DIUBAH] Di bawah breakpoint lg (termasuk display kiosk kecil
     480×320), tampilan TIDAK berubah sama sekali dari desain semula
     (kolom tunggal, vertikal center, max-w-sm). Mulai lg: (laptop/desktop)
     container mengikuti konvensi ukuran halaman menu admin lainnya
     (max-w-7xl, space-y-6, grid 12 kolom) - latar tetap PUTIH seperti
     menu lain (bukan galaksi Milky Way, dilepas lagi setelah review). -->
<div class="min-h-[calc(100vh-4rem)] flex flex-col items-center justify-center p-4 md:p-6
            lg:block lg:min-h-0 lg:p-0 lg:max-w-7xl lg:mx-auto lg:space-y-6 lg:pb-12">

  <!-- Header -->
  <div class="w-full max-w-sm lg:max-w-none mb-4 lg:mb-0">
    <div class="bg-gradient-to-r from-blue-600 to-cyan-500 circuit-pattern relative overflow-hidden rounded-2xl lg:rounded-3xl px-5 py-4 lg:p-8 text-white shadow-lg lg:shadow-xl lg:shadow-blue-500/20">
      <div class="flex items-center justify-between gap-3">
        <div>
          <h1 class="text-lg lg:text-3xl font-extrabold leading-tight tracking-tight">QR Presensi</h1>
          <p class="text-blue-100 text-[11px] lg:text-sm mt-0.5 lg:mt-2">Token berubah setiap 3 menit atau setelah di-scan.</p>
        </div>
        <!-- Jam Live (menggantikan tombol Kembali) -->
        <div class="text-right bg-white/10 px-3 py-2 lg:p-3 rounded-xl lg:rounded-2xl backdrop-blur-sm border border-white/20 shrink-0">
          <p class="text-[9px] lg:text-[10px] font-bold text-blue-100 uppercase tracking-widest mb-0.5 lg:mb-1">Waktu Sistem</p>
          <p id="liveDate" class="text-sm lg:text-xl font-bold font-mono leading-tight"><?= date('d F Y') ?></p>
          <p class="text-xs lg:text-sm font-mono mt-0.5 lg:mt-1">
            <span id="liveTime" class="bg-blue-900/30 px-1.5 py-0.5 rounded text-[11px] lg:text-xs"><?= date('H:i:s') ?></span>
            <span class="text-blue-200 text-[9px] lg:text-xs"> WITA</span>
          </p>
        </div>
      </div>
    </div>
  </div>

  <!-- [BARU] Grid 2 kolom mulai lg: kiri = toggle+QR, kanan = panduan &
       peringatan (mengisi ruang kosong dengan konten informatif, latar
       tetap kartu putih standar - konsisten dengan halaman menu lain). -->
  <div class="w-full max-w-sm lg:max-w-none grid grid-cols-1 lg:grid-cols-12 lg:gap-6 lg:mt-6">

    <!-- Kolom kiri: toggle + kartu QR -->
    <div class="lg:col-span-5">
      <!-- Toggle switch: Masuk / Pulang -->
      <div class="w-full mb-5">
        <div class="flex bg-gray-100 rounded-2xl p-1 relative" id="modeToggle">
          <!-- active pill -->
          <div id="togglePill" class="toggle-pill absolute top-1 left-1 w-[calc(50%-4px)] h-[calc(100%-8px)] rounded-xl bg-gradient-to-r from-blue-600 to-cyan-500 shadow-md shadow-blue-300/40 z-0"></div>
          <button id="btnModeIn" onclick="setMode('in')"
                  class="relative z-10 flex-1 flex items-center justify-center gap-2 py-2.5 lg:py-3.5 rounded-xl text-sm lg:text-base font-bold transition text-white">
            <i class="fas fa-sign-in-alt text-xs lg:text-sm"></i> QR Masuk
          </button>
          <button id="btnModeOut" onclick="setMode('out')"
                  class="relative z-10 flex-1 flex items-center justify-center gap-2 py-2.5 lg:py-3.5 rounded-xl text-sm lg:text-base font-bold transition text-gray-500">
            <i class="fas fa-sign-out-alt text-xs lg:text-sm"></i> QR Pulang
          </button>
        </div>
      </div>

      <!-- QR Display Card -->
      <div class="w-full">
        <div id="qrCard" class="bg-white rounded-3xl shadow-xl border border-gray-100 overflow-hidden">

          <!-- Card header (warna berubah sesuai mode) -->
          <div id="qrCardHeader" class="bg-gradient-to-r from-blue-600 to-cyan-500 px-5 py-3.5 lg:px-7 lg:py-5 flex items-center gap-3">
            <div id="qrCardIcon" class="w-9 h-9 lg:w-11 lg:h-11 rounded-xl bg-white/20 flex items-center justify-center shrink-0">
              <i class="fas fa-sign-in-alt text-white lg:text-lg"></i>
            </div>
            <div id="qrCardTitle" class="text-white">
              <h2 class="font-extrabold leading-tight text-base lg:text-xl">QR Masuk (Check-In)</h2>
              <p class="text-blue-100 text-[11px] lg:text-sm">Scan untuk absen masuk</p>
            </div>
            <span id="qrStatus" class="ml-auto inline-flex items-center gap-1.5 text-[10px] lg:text-xs font-bold bg-white/15 text-white border border-white/20 px-2.5 py-1 rounded-full">
              <span class="w-1.5 h-1.5 bg-green-400 rounded-full animate-pulse"></span> Aktif
            </span>
          </div>

          <!-- [DIUBAH] QR Code area — ukuran QR sekarang FLEKSIBEL, mengikuti
               lebar frame container-nya sendiri (qrWrap: w-full dibatasi
               max-width per breakpoint), bukan pixel tetap. qr_page.js
               mengukur lebar #qrBox yang sebenarnya lalu me-render ulang
               QR persis di resolusi itu, supaya QR selalu pas & tajam
               di dalam bingkainya. -->
          <div class="flex flex-col items-center justify-center p-6 lg:p-8 gap-4">
            <div id="qrWrap" class="qr-pulse-in rounded-2xl border-4 border-blue-200 p-3 lg:p-4 bg-white qr-fadein w-full max-w-[288px] lg:max-w-[360px] mx-auto">
              <div id="qrBox" class="w-full aspect-square"></div>
            </div>
            <button onclick="forceRefreshQR()"
                    class="flex items-center gap-2 text-xs lg:text-sm font-bold text-gray-400 hover:text-blue-600 transition px-3 py-1.5 rounded-lg hover:bg-blue-50">
              <i class="fas fa-rotate-right"></i> Generate Ulang
            </button>
          </div>
        </div>

        <!-- Info singkat -->
        <p class="text-center text-[11px] lg:text-sm text-gray-400 mt-4 px-2">
          QR hanya bisa dipakai <strong>satu kali</strong> per asisten — otomatis berubah setelah di-scan.
        </p>
      </div>
    </div>

    <!-- Kolom kanan: panduan & peringatan — HANYA tampil mulai breakpoint
         lg, mengisi ruang kosong di layar laptop/desktop dengan konten
         yang relevan (bukan sekadar dekorasi). -->
    <div class="hidden lg:flex lg:col-span-7 flex-col gap-6">

      <!-- Cara kerja -->
      <div class="bg-white rounded-3xl shadow-sm border border-gray-200 p-6">
        <p class="text-[10px] font-bold text-blue-500 uppercase tracking-widest mb-1">Cara Kerja</p>
        <h3 class="text-lg font-extrabold text-gray-800 mb-4">Alur Presensi via QR</h3>
        <div class="grid grid-cols-2 gap-4">
          <div class="flex items-start gap-3">
            <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center font-extrabold text-sm shrink-0">1</div>
            <div>
              <p class="font-bold text-sm text-gray-700">Buka kamera</p>
              <p class="text-gray-400 text-xs mt-0.5">Lewat menu Scan QR di dashboard asisten.</p>
            </div>
          </div>
          <div class="flex items-start gap-3">
            <div class="w-8 h-8 rounded-lg bg-cyan-50 text-cyan-600 flex items-center justify-center font-extrabold text-sm shrink-0">2</div>
            <div>
              <p class="font-bold text-sm text-gray-700">Arahkan ke QR ini</p>
              <p class="text-gray-400 text-xs mt-0.5">Pilih QR Masuk/Pulang sesuai keperluan.</p>
            </div>
          </div>
          <div class="flex items-start gap-3">
            <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center font-extrabold text-sm shrink-0">3</div>
            <div>
              <p class="font-bold text-sm text-gray-700">Verifikasi wajah</p>
              <p class="text-gray-400 text-xs mt-0.5">Foto singkat untuk konfirmasi kehadiran.</p>
            </div>
          </div>
          <div class="flex items-start gap-3">
            <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center font-extrabold text-sm shrink-0">4</div>
            <div>
              <p class="font-bold text-sm text-gray-700">Tercatat otomatis</p>
              <p class="text-gray-400 text-xs mt-0.5">Langsung terlihat di dashboard admin & kepala lab.</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Peringatan / catatan penting -->
      <div class="bg-amber-50 border border-amber-200 rounded-3xl p-6">
        <div class="flex items-center gap-2 mb-3">
          <i class="fas fa-triangle-exclamation text-amber-500"></i>
          <h3 class="text-sm font-extrabold text-amber-700 uppercase tracking-wide">Peringatan & Catatan Penting</h3>
        </div>
        <ul class="space-y-2.5 text-xs text-amber-700">
          <li class="flex items-start gap-2">
            <i class="fas fa-circle text-[4px] mt-1.5 shrink-0"></i>
            <span>Jangan bagikan tangkapan layar QR ini — QR hanya untuk dipindai langsung di layar ini.</span>
          </li>
          <li class="flex items-start gap-2">
            <i class="fas fa-circle text-[4px] mt-1.5 shrink-0"></i>
            <span>Token berubah otomatis tiap 3 menit atau segera setelah dipindai — QR lama tidak berlaku lagi.</span>
          </li>
          <li class="flex items-start gap-2">
            <i class="fas fa-circle text-[4px] mt-1.5 shrink-0"></i>
            <span>Pastikan perangkat asisten terhubung internet & mengizinkan akses kamera + lokasi.</span>
          </li>
          <li class="flex items-start gap-2">
            <i class="fas fa-circle text-[4px] mt-1.5 shrink-0"></i>
            <span>Gunakan tombol <strong>Generate Ulang</strong> jika QR tidak terbaca atau dicurigai bermasalah.</span>
          </li>
        </ul>
      </div>
    </div>

  </div>

</div><!-- /flex container -->
