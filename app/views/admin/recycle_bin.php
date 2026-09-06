<?php /* app/views/admin/recycle_bin.php — Recycle Bin Presensi */ ?>

<div class="max-w-7xl mx-auto space-y-6 animate-enter pb-12">

  <!-- Header -->
  <div class="bg-gradient-to-r from-blue-600 to-cyan-500 circuit-pattern relative overflow-hidden rounded-3xl p-6 md:p-8 text-white shadow-xl shadow-blue-500/20">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <!-- <p class="text-blue-100 text-[10px] font-bold uppercase tracking-widest mb-1">Admin — Manajemen Data</p> -->
        <h1 class="text-2xl font-extrabold flex items-center gap-2"></i> Restore Data Presensi</h1>
        <p class="text-blue-100 text-sm mt-1">Data yang di-reset tersimpan di sini sebelum dihapus permanen.</p>
      </div>
      <!-- Jam Live (menggantikan tombol Kembali) -->
      <div class="shrink-0 text-right bg-white/10 px-4 py-3 rounded-2xl backdrop-blur-sm border border-white/20">
        <p class="text-[9px] font-bold text-blue-100 uppercase tracking-widest mb-1">Waktu Sistem</p>
        <h2 id="liveDate" class="text-lg font-bold font-mono leading-tight"><?= date('d F Y') ?></h2>
        <p class="text-sm font-mono mt-0.5">
          <span id="liveTime" class="bg-blue-900/30 px-2 py-0.5 rounded"><?= date('H:i:s') ?></span>
          <span class="text-blue-200 text-xs"> WITA</span>
        </p>
      </div>
    </div>
  </div>

  <!-- Filter bar -->
  <form method="GET" action="<?= BASE_URL ?>/admin/recycleBin"
        class="bg-gradient-to-r from-blue-600 to-cyan-500 circuit-pattern rounded-2xl border border-gray-200 shadow-sm px-4 py-3 flex flex-wrap gap-3 items-center">

    <select name="scope" onchange="this.form.submit()"
            class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-200 bg-white">
      <option value=""          <?= empty($filter['scope'])     ? 'selected' : '' ?>>Semua Tipe</option>
      <option value="all"       <?= $filter['scope']==='all'    ? 'selected' : '' ?>>Reset Keseluruhan</option>
      <option value="single"    <?= $filter['scope']==='single' ? 'selected' : '' ?>>Reset Per Asisten</option>
    </select>

    <?php if (($filter['scope'] ?? '') === 'single' && !empty($assistant_list)): ?>
    <select name="pid" onchange="this.form.submit()"
            class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-200 bg-white min-w-[180px]">
      <option value="">— Semua Asisten —</option>
      <?php
        $jabatanGroups = [];
        foreach ($assistant_list as $a) $jabatanGroups[$a['jabatan_asisten'] ?? 'Lainnya'][] = $a;
        foreach ($jabatanGroups as $jabatan => $list):
      ?>
      <optgroup label="<?= htmlspecialchars($jabatan) ?>">
        <?php foreach ($list as $a): ?>
        <?php $hPid = HashHelper::encode((int)$a['id_profil']); ?>
        <option value="<?= $hPid ?>" <?= ($filter['id_profil'] == $a['id_profil']) ? 'selected' : '' ?>>
          <?= htmlspecialchars($a['nama_asisten']) ?>
        </option>
        <?php endforeach; ?>
      </optgroup>
      <?php endforeach; ?>
    </select>
    <?php endif; ?>

    <?php if (!empty($filter['scope']) || !empty($filter['id_profil'])): ?>
    <a href="<?= BASE_URL ?>/admin/recycleBin" class="text-xs text-white hover:text-red-500 transition flex items-center gap-1">
      <i class="fas fa-times-circle"></i> Reset Filter
    </a>
    <?php endif; ?>

    <!-- [BARU] Pencarian nama (instan, client-side) + filter jabatan &
         angkatan - terpisah dari scope/pid di atas (yang server-side/reload)
         supaya nama langsung ter-filter saat mengetik tanpa reload. -->
    <div class="relative flex-1 min-w-[180px]">
      <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-300 text-xs"></i>
      <input type="text" id="binSearchInput" placeholder="Cari nama asisten..." autocomplete="off"
             class="w-full border border-gray-200 rounded-xl pl-8 pr-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-200 bg-white">
    </div>

    <!-- [DIUBAH] Opsi jabatan & angkatan diambil dari data asisten di
         database ($jabatan_list/$angkatan_list dari AttendanceModel, sama
         seperti filter di Kelola Pengguna) - BUKAN diturunkan dari entri
         recycle bin yang sedang tampil, supaya dropdown selalu lengkap
         walau bin kosong atau sedang terfilter scope/pid. -->
    <select id="binJabatanFilter" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-200 bg-white">
      <option value="">Semua Jabatan</option>
      <?php foreach (($jabatan_list ?? []) as $j): ?>
      <option value="<?= htmlspecialchars($j, ENT_QUOTES) ?>"><?= htmlspecialchars($j) ?></option>
      <?php endforeach; ?>
    </select>

    <select id="binAngkatanFilter" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-200 bg-white">
      <option value="">Semua Angkatan</option>
      <?php foreach (($angkatan_list ?? []) as $ang): ?>
      <option value="<?= htmlspecialchars($ang, ENT_QUOTES) ?>"><?= htmlspecialchars($ang) ?></option>
      <?php endforeach; ?>
    </select>

    <span class="ml-auto text-xs text-white"><span id="binVisibleCount"><?= count($bin_entries) ?></span> entri</span>
  </form>

  <!-- Empty state (tidak ada entri sama sekali) -->
  <?php if (empty($bin_entries)): ?>
    <div class="bg-white rounded-3xl border border-gray-200 shadow-sm p-16 text-center text-gray-400">
      <i class="fas fa-trash-restore-alt text-5xl mb-4 opacity-20"></i>
      <p class="font-bold text-lg mb-1 text-gray-500">Restore Data Kosong</p>
      <p class="text-sm">Belum ada data presensi yang di-reset.</p>
    </div>

  <?php else: ?>
  <div class="bg-white rounded-3xl border border-gray-200 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-left text-sm">
        <thead class="bg-gray-50 border-b border-gray-100 text-[11px] font-bold text-gray-400 uppercase tracking-wide">
          <tr>
            <th class="px-5 py-3">Asisten / Keterangan</th>
            <th class="px-4 py-3">Tipe</th>
            <th class="px-4 py-3">Rentang Data</th>
            <th class="px-4 py-3">Tanggal Reset</th>
            <th class="px-4 py-3 text-center">Data</th>
            <th class="px-4 py-3 text-center">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100" id="binTableBody">
          <?php foreach ($bin_entries as $b):
            $hBin = HashHelper::encode((int)$b['id_bin']); ?>
          <tr class="hover:bg-gray-50/70 transition group" id="bin-row-<?= $hBin ?>"
              data-name="<?= strtolower(htmlspecialchars($b['nama_asisten'] ?? '', ENT_QUOTES)) ?>"
              data-jabatan="<?= htmlspecialchars($b['jabatan_asisten'] ?? '', ENT_QUOTES) ?>"
              data-angkatan="<?= htmlspecialchars($b['angkatan_asisten'] ?? '', ENT_QUOTES) ?>">

            <!-- Nama -->
            <td class="px-5 py-3.5">
              <div class="font-semibold text-gray-800 text-sm"><?= htmlspecialchars($b['nama_asisten'] ?? '—') ?></div>
              <?php if (!empty($b['jabatan_asisten'])): ?>
              <div class="text-[10px] text-gray-400 font-bold uppercase tracking-wide mt-0.5"><?= htmlspecialchars($b['jabatan_asisten']) ?></div>
              <?php endif; ?>
            </td>

            <!-- Tipe -->
            <td class="px-4 py-3.5">
              <?php if ($b['reset_scope'] === 'all'): ?>
              <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-red-50 text-red-600 text-[11px] font-bold border border-red-100">
                <i class="fas fa-users text-[9px]"></i> Semua
              </span>
              <?php else: ?>
              <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-orange-50 text-orange-600 text-[11px] font-bold border border-orange-100">
                <i class="fas fa-user text-[9px]"></i> Asisten
              </span>
              <?php endif; ?>
            </td>

            <!-- Rentang -->
            <td class="px-4 py-3.5 text-xs text-gray-500 font-mono">
              <?php
                $ds = $b['date_data_start'] ? date('d/m/Y', strtotime($b['date_data_start'])) : '—';
                $de = $b['date_data_end']   ? date('d/m/Y', strtotime($b['date_data_end']))   : '—';
                echo $ds . ' s/d ' . $de;
              ?>
            </td>

            <!-- Tanggal Reset -->
            <td class="px-4 py-3.5 text-xs text-gray-500 whitespace-nowrap">
              <?= date('d M Y', strtotime($b['date_reset'])) ?><br>
              <span class="text-gray-400"><?= date('H:i', strtotime($b['date_reset'])) ?> WIB</span>
            </td>

            <!-- Jumlah data -->
            <td class="px-4 py-3.5 text-center">
              <div class="text-xs text-gray-600">
                <span class="font-bold"><?= $b['jumlah_presensi'] ?></span>
                <span class="text-gray-400"> presensi</span><br>
                <span class="font-bold"><?= $b['jumlah_logbook'] ?></span>
                <span class="text-gray-400"> logbook</span>
              </div>
            </td>

            <!-- Aksi — ikon saja dengan tooltip -->
            <td class="px-4 py-3.5 text-center">
              <div class="flex items-center justify-center gap-1">

                <a href="<?= BASE_URL ?>/admin/recycleBinDownload?id=<?= $hBin ?>"
                   title="Unduh data (CSV/ZIP)"
                   class="w-8 h-8 flex items-center justify-center rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 border border-blue-100 transition">
                  <i class="fas fa-download text-xs"></i>
                </a>

                <button onclick="confirmBinAction('restore', '<?= $hBin ?>', '<?= htmlspecialchars($b['nama_asisten'], ENT_QUOTES) ?>')"
                        title="Pulihkan data ke presensi"
                        class="w-8 h-8 flex items-center justify-center rounded-lg bg-green-50 text-green-600 hover:bg-green-100 border border-green-100 transition">
                  <i class="fas fa-trash-restore-alt text-xs"></i>
                </button>

                <button onclick="confirmBinAction('delete', '<?= $hBin ?>', '<?= htmlspecialchars($b['nama_asisten'], ENT_QUOTES) ?>')"
                        title="Hapus permanen"
                        class="w-8 h-8 flex items-center justify-center rounded-lg bg-red-50 text-red-500 hover:bg-red-100 border border-red-100 transition">
                  <i class="fas fa-trash-alt text-xs"></i>
                </button>

              </div>
            </td>

          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <!-- [BARU] Ditampilkan JS saat pencarian/filter tidak cocok dengan baris manapun -->
      <div id="binNoFilterResult" class="hidden text-center text-gray-400 py-14">
        <i class="fas fa-search text-3xl mb-3 opacity-30"></i>
        <p class="text-sm">Tidak ada entri yang cocok dengan pencarian/filter.</p>
      </div>
    </div>
  </div>
  <?php endif; ?>

</div><!-- /max-w -->

<!-- Modal konfirmasi -->
<div id="binActionModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
  <div class="absolute inset-0 bg-gray-900/70 backdrop-blur-sm" onclick="closeBinModal()"></div>
  <div class="relative bg-white w-full max-w-md rounded-3xl shadow-2xl overflow-hidden z-10">
    <div id="binModalHeader" class="px-6 py-5 text-white">
      <h3 id="binModalTitle"    class="font-extrabold text-lg"></h3>
      <p  id="binModalSubtitle" class="text-sm opacity-75 mt-0.5"></p>
    </div>
    <div class="p-6 space-y-4">
      <p id="binModalBody" class="text-sm text-gray-600"></p>

      <div id="binConflictWarn" class="hidden bg-yellow-50 border border-yellow-200 rounded-xl p-3 text-xs text-yellow-700">
        <p class="font-bold mb-1"><i class="fas fa-exclamation-triangle mr-1"></i> Konflik data ditemukan:</p>
        <ul id="binConflictList" class="list-disc ml-4 space-y-0.5"></ul>
        <p class="mt-2 text-yellow-600">Tanggal-tanggal tersebut sudah memiliki presensi baru dan dilewati saat pemulihan.</p>
      </div>

      <div class="flex gap-3 pt-1">
        <button onclick="closeBinModal()" class="flex-1 py-2.5 rounded-xl bg-gray-100 text-gray-600 font-bold text-sm hover:bg-gray-200 transition">Batal</button>
        <button id="binActionBtn" class="flex-1 py-2.5 rounded-xl text-white font-bold text-sm transition" onclick="executeBinAction()">Lanjutkan</button>
      </div>
    </div>
  </div>
</div>
