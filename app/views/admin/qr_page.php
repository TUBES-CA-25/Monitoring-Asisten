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

<div class="min-h-[calc(100vh-4rem)] flex flex-col items-center justify-center p-4 md:p-6">

  <!-- Compact header -->
  <div class="w-full max-w-sm mb-4">
    <div class="bg-gradient-to-r from-blue-600 to-cyan-500 rounded-2xl px-5 py-4 text-white shadow-lg">
      <div class="flex items-center justify-between gap-3">
        <div>
          <!-- <p class="text-blue-100 text-[10px] font-bold uppercase tracking-widest">ICo Labs-UMI</p> -->
          <h1 class="text-lg font-extrabold leading-tight">QR Presensi</h1>
          <p class="text-blue-100 text-[11px] mt-0.5">Silahkan scan QR untuk presensi.</p>
        </div>
        <div class="text-center md:text-right bg-white/10 p-3 rounded-2xl backdrop-blur-sm border border-white/20">
            <p class="text-[10px] font-bold text-blue-100 uppercase tracking-widest mb-1">Waktu Sistem</p>
            <h2 id="liveDate" class="text-xl font-bold font-mono"><?= date('d F Y') ?></h2>
            <p class="text-sm opacity-90 font-mono mt-1">
                <span id="liveTime" class="bg-blue-900/30 px-2 py-0.5 rounded"><?= date('H:i:s') ?></span> WITA
            </p>
        </div>
        <!-- <a href="<?= BASE_URL ?>/admin/dashboard"
           class="shrink-0 flex items-center gap-1.5 bg-white/15 hover:bg-white/25 border border-white/20 text-white px-3 py-2 rounded-xl font-bold text-xs transition">
          <i class="fas fa-arrow-left text-[10px]"></i> Kembali
        </a> -->
      </div>
    </div>
  </div>

  <!-- Toggle switch: Masuk / Pulang -->
  <div class="w-full max-w-sm mb-5">
    <div class="flex bg-gray-100 rounded-2xl p-1 relative" id="modeToggle">
      <!-- active pill -->
      <div id="togglePill" class="toggle-pill absolute top-1 left-1 w-[calc(50%-4px)] h-[calc(100%-8px)] rounded-xl bg-gradient-to-r from-blue-600 to-cyan-500 shadow-md shadow-blue-300/40 z-0"></div>
      <button id="btnModeIn" onclick="setMode('in')"
              class="relative z-10 flex-1 flex items-center justify-center gap-2 py-2.5 rounded-xl text-sm font-bold transition text-white">
        <i class="fas fa-sign-in-alt text-xs"></i> QR Masuk
      </button>
      <button id="btnModeOut" onclick="setMode('out')"
              class="relative z-10 flex-1 flex items-center justify-center gap-2 py-2.5 rounded-xl text-sm font-bold transition text-gray-500">
        <i class="fas fa-sign-out-alt text-xs"></i> QR Pulang
      </button>
    </div>
  </div>

  <!-- QR Display Card -->
  <div class="w-full max-w-sm">
    <div id="qrCard" class="bg-white rounded-3xl shadow-xl border border-gray-100 overflow-hidden">

      <!-- Card header (warna berubah sesuai mode) -->
      <div id="qrCardHeader" class="bg-gradient-to-r from-blue-600 to-cyan-500 px-5 py-3.5 flex items-center gap-3">
        <div id="qrCardIcon" class="w-9 h-9 rounded-xl bg-white/20 flex items-center justify-center shrink-0">
          <i class="fas fa-sign-in-alt text-white"></i>
        </div>
        <div id="qrCardTitle" class="text-white">
          <h2 class="font-extrabold leading-tight text-base">QR Masuk (Check-In)</h2>
          <p class="text-blue-100 text-[11px]">Scan untuk absen masuk</p>
        </div>
        <span id="qrStatus" class="ml-auto inline-flex items-center gap-1.5 text-[10px] font-bold bg-white/15 text-white border border-white/20 px-2.5 py-1 rounded-full">
          <span class="w-1.5 h-1.5 bg-green-400 rounded-full animate-pulse"></span> Aktif
        </span>
      </div>

      <!-- QR Code area — ukuran tetap untuk display kecil (480×320) -->
      <div class="flex flex-col items-center justify-center p-6 gap-4">
        <div id="qrWrap" class="qr-pulse-in rounded-2xl border-4 border-blue-200 p-3 bg-white qr-fadein">
          <!-- QR diisi JS — ukuran fixed 256px agar cukup di layar 480×320 -->
          <div id="qrBox" style="width:256px;height:256px"></div>
        </div>
        <button onclick="forceRefreshQR()"
                class="flex items-center gap-2 text-xs font-bold text-gray-400 hover:text-blue-600 transition px-3 py-1.5 rounded-lg hover:bg-blue-50">
          <i class="fas fa-rotate-right"></i> Generate Ulang
        </button>
      </div>
    </div>

    <!-- Info singkat -->
    <p class="text-center text-[11px] text-gray-400 mt-4 px-2">
      QR hanya bisa dipakai <strong>satu kali</strong> per asisten — otomatis berubah setelah di-scan.
    </p>
  </div>

</div><!-- /flex container -->
