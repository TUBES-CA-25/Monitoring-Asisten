(function () {
  try {
    if (
      localStorage.getItem('sidebarState') === 'minimized' &&
      window.innerWidth >= 768
    ) {
      document.documentElement.classList.add('preload-minimized');
    }
  } catch (e) {
  }
})();

document.addEventListener('DOMContentLoaded', () => {
  initSidebarController();
});

function initSidebarController() {
  const sidebar = document.getElementById('sidebar');
  const mainContent = document.getElementById('mainContent');
  const toggleBtn = document.getElementById('toggleSidebar');
  const toggleIcon = document.getElementById('toggleIcon');
  const mobileBtn = document.getElementById('mobileMenuBtn');
  const logoHeader = document.getElementById('logoHeader');

  if (!sidebar || !mainContent) {
    return;
  }

  const textElements = document.querySelectorAll('.sidebar-text');
  const headers = document.querySelectorAll('.sidebar-header');
  const profileContainer = document.getElementById('profileContainer');
  const logoContainer = document.getElementById('logoContainer');

  let isMinimized = localStorage.getItem('sidebarState') === 'minimized';

  function updateSidebarState() {
    if (window.innerWidth < 768) {
      sidebar.classList.remove('w-20');
      sidebar.classList.add('w-64');

      if (toggleBtn) toggleBtn.classList.add('hidden');

      textElements.forEach(el => el.classList.remove('hidden', 'opacity-0'));
      headers.forEach(el => el.classList.remove('hidden', 'opacity-0'));

      if (profileContainer) {
        profileContainer.classList.add('justify-start');
        profileContainer.classList.remove('justify-center');
      }

      if (logoContainer) {
        const span = logoContainer.querySelector('span');
        if (span) span.classList.remove('hidden');
        logoContainer.classList.remove('justify-center', 'w-full');
      }

      return;
    }

    if (isMinimized) {
      sidebar.classList.remove('w-64');
      sidebar.classList.add('w-20');

      mainContent.classList.remove('md:ml-64');
      mainContent.classList.add('md:ml-20');

      if (toggleIcon) {
        toggleIcon.classList.remove('fa-chevron-left');
        toggleIcon.classList.add('fa-chevron-right');
      }

      textElements.forEach(el => el.classList.add('hidden', 'opacity-0'));
      headers.forEach(el => el.classList.add('hidden', 'opacity-0'));

      if (profileContainer) {
        profileContainer.classList.remove('justify-start');
        profileContainer.classList.add('justify-center');
      }

      if (logoContainer) {
        const span = logoContainer.querySelector('span');
        if (span) span.classList.add('hidden');
        logoContainer.classList.add('justify-center', 'w-full');
      }

      if (logoHeader) {
        logoHeader.classList.add('cursor-pointer', 'hover:bg-blue-700/50');
        logoHeader.title = 'Klik untuk memperbesar';
      }
    } else {
      sidebar.classList.add('w-64');
      sidebar.classList.remove('w-20');

      mainContent.classList.add('md:ml-64');
      mainContent.classList.remove('md:ml-20');

      if (toggleIcon) {
        toggleIcon.classList.add('fa-chevron-left');
        toggleIcon.classList.remove('fa-chevron-right');
      }

      textElements.forEach(el => el.classList.remove('hidden', 'opacity-0'));
      headers.forEach(el => el.classList.remove('hidden', 'opacity-0'));

      if (profileContainer) {
        profileContainer.classList.add('justify-start');
        profileContainer.classList.remove('justify-center');
      }

      if (logoContainer) {
        const span = logoContainer.querySelector('span');
        if (span) span.classList.remove('hidden');
        logoContainer.classList.remove('justify-center', 'w-full');
      }

      if (logoHeader) {
        logoHeader.classList.remove('cursor-pointer', 'hover:bg-blue-700/50');
        logoHeader.removeAttribute('title');
      }
    }
  }

  updateSidebarState();

  setTimeout(() => {
    document.documentElement.classList.remove('preload-minimized');
  }, 100);

  if (toggleBtn) {
    toggleBtn.addEventListener('click', e => {
      e.stopPropagation();

      isMinimized = !isMinimized;
      localStorage.setItem('sidebarState', isMinimized ? 'minimized' : 'expanded');
      updateSidebarState();
    });
  }

  if (logoHeader) {
    logoHeader.addEventListener('click', () => {
      if (isMinimized && window.innerWidth >= 768) {
        isMinimized = false;
        localStorage.setItem('sidebarState', 'expanded');
        updateSidebarState();
      }
    });
  }

  if (mobileBtn) {
    mobileBtn.addEventListener('click', e => {
      e.stopPropagation();
      sidebar.classList.toggle('-translate-x-full');
    });
  }

  window.addEventListener('resize', updateSidebarState);

  document.addEventListener('click', e => {
    if (window.innerWidth < 768) {
      const isClickInside =
        sidebar.contains(e.target) ||
        (mobileBtn && mobileBtn.contains(e.target));

      if (!isClickInside && !sidebar.classList.contains('-translate-x-full')) {
        sidebar.classList.add('-translate-x-full');
      }
    }
  });
}

// =====================================================================
// [BARU - Patch 5 V3] GLOBAL MODAL SYSTEM
// =====================================================================
// Mengganti alert()/confirm() bawaan browser dengan modal interaktif yang
// konsisten dengan tema aplikasi. Tersedia di SEMUA halaman lewat markup
// #globalAlertModal di layout/footer.php.
//
// Beberapa halaman (admin/schedule.js, user/schedule.js, dll) sudah punya
// implementasi showCustomAlert()/closeCustomAlert() SENDIRI (top-level,
// dideklarasikan setelah global.js dimuat) yang memakai modal #customAlertModal
// milik halaman tersebut - deklarasi function top-level itu akan MENIMPA
// window.showCustomAlert/window.closeCustomAlert di bawah ini untuk halaman
// tersebut, sehingga halaman yang sudah punya modal sendiri TIDAK terpengaruh
// dan tetap memakai modalnya sendiri. Versi global ini berfungsi sebagai
// fallback untuk halaman yang BELUM memiliki modal sendiri.
function showCustomAlert(type, title, message) {
  const modal = document.getElementById('globalAlertModal');
  if (!modal) {
    // Fallback terakhir jika markup global tidak ditemukan (mis. halaman
    // tanpa layout/footer, seperti halaman error/login).
    window.alert(`${title}\n\n${message}`);
    return;
  }

  const iconBg = document.getElementById('globalAlertIconBg');
  const icon = document.getElementById('globalAlertIcon');
  const btn = document.getElementById('globalAlertBtn');

  document.getElementById('globalAlertTitle').innerText = title;
  document.getElementById('globalAlertMessage').innerText = message;

  if (type === 'success') {
    iconBg.className = 'w-16 h-16 rounded-full flex items-center justify-center mb-4 bg-green-100 text-green-600';
    icon.className = 'fas fa-check text-3xl';
    btn.className = 'w-full py-3 rounded-xl font-bold text-white shadow-lg transition transform hover:scale-[1.02] bg-green-600 hover:bg-green-700 shadow-green-500/30';
  } else {
    iconBg.className = 'w-16 h-16 rounded-full flex items-center justify-center mb-4 bg-red-100 text-red-600';
    icon.className = 'fas fa-times text-3xl';
    btn.className = 'w-full py-3 rounded-xl font-bold text-white shadow-lg transition transform hover:scale-[1.02] bg-red-600 hover:bg-red-700 shadow-red-500/30';
  }

  modal.classList.remove('hidden');
  setTimeout(() => {
    document.getElementById('globalAlertBackdrop').classList.remove('opacity-0');
    document.getElementById('globalAlertContent').classList.remove('scale-90', 'opacity-0');
    document.getElementById('globalAlertContent').classList.add('scale-100', 'opacity-100');
  }, 50);
}

function closeCustomAlert() {
  const modal = document.getElementById('globalAlertModal');
  if (!modal) return;

  document.getElementById('globalAlertBackdrop').classList.add('opacity-0');
  document.getElementById('globalAlertContent').classList.remove('scale-100', 'opacity-100');
  document.getElementById('globalAlertContent').classList.add('scale-90', 'opacity-0');
  setTimeout(() => modal.classList.add('hidden'), 300);
}

// =====================================================================
// [BARU - Modul Dosen] Toggle input "Tambah Dosen Baru"
// =====================================================================
// Dipakai oleh dropdown #inputIdDosen di admin/schedule.php &
// user/schedule.php (form Jadwal Asisten / Jadwal Kuliah). Saat opsi
// "+ Tambah Dosen Baru..." (value="__new__") dipilih, tampilkan input teks
// #inputDosenBaru dan jadikan wajib diisi; selain itu sembunyikan & kosongkan.
function handleDosenChange(selectEl) {
  const newInput = document.getElementById('inputDosenBaru');
  if (!newInput) return;

  if (selectEl.value === '__new__') {
    newInput.classList.remove('hidden');
    newInput.required = true;
    newInput.focus();
  } else {
    newInput.classList.add('hidden');
    newInput.required = false;
    newInput.value = '';
  }
}
