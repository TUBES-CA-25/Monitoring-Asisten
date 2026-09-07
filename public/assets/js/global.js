/**
 * [Item 1] CSRF Auto-Inject — Global Fetch Patch
 *
 * Semua 30+ panggilan fetch() di 16 file JS tidak perlu diubah satu per satu.
 * Solusi: patch window.fetch di sini (global.js dimuat pertama kali di semua halaman)
 * agar setiap POST/PUT/PATCH/DELETE otomatis menyertakan header X-CSRF-TOKEN.
 *
 * Token dibaca dari <meta name="csrf-token"> yang disisipkan oleh header.php dan login.php.
 * Jika meta tag tidak ada (misal: halaman API), fetch berjalan normal tanpa header CSRF.
 */
(function patchFetchWithCsrf() {
    var _origFetch = window.fetch.bind(window);

    window.fetch = function (resource, init) {
        init = init || {};
        var method = ((init.method || 'GET')).toUpperCase();

        // Hanya inject untuk request yang mengubah state (bukan GET/HEAD)
        if (method !== 'GET' && method !== 'HEAD') {
            var metaTag = document.querySelector('meta[name="csrf-token"]');
            var token   = metaTag ? metaTag.getAttribute('content') : '';

            if (token) {
                // Dukung baik Headers object maupun plain object
                if (!init.headers) {
                    init.headers = { 'X-CSRF-TOKEN': token };
                } else if (typeof init.headers.set === 'function') {
                    if (!init.headers.has('X-CSRF-TOKEN')) {
                        init.headers.set('X-CSRF-TOKEN', token);
                    }
                } else if (!init.headers['X-CSRF-TOKEN'] && !init.headers['x-csrf-token']) {
                    init.headers['X-CSRF-TOKEN'] = token;
                }
            }
        }
        return _origFetch(resource, init);
    };
})();

(function () {
  try {
    // [BARU] Selain preferensi tersimpan (localStorage), layar sempit
    // (>=768 tapi <1024 - tablet/laptop kecil) SELALU dianggap minimized
    // sejak render pertama, konsisten dengan computeIsMinimized() di
    // initSidebarController() - mencegah flash "melebar dulu baru
    // menyempit" saat halaman pertama kali dimuat di lebar tsb.
    var w = window.innerWidth;
    var isNarrowDesktop = w >= 768 && w < 1024;
    if (w >= 768 && (isNarrowDesktop || localStorage.getItem('sidebarState') === 'minimized')) {
      document.documentElement.classList.add('preload-minimized');
    }
  } catch (e) {
  }
})();


// =====================================================================
// AJAX Navigation — Sidebar tetap, hanya konten utama yang diganti
// =====================================================================
// Mencegat klik pada link internal agar navigasi dilakukan via AJAX:
// 1. Fetch halaman tujuan
// 2. Ekstrak konten <main> dari response HTML
// 3. Ganti konten <main> saat ini tanpa mereload sidebar
// 4. Update URL + judul halaman via history.pushState
// 5. Muat script halaman baru, hapus script halaman lama
//
// Script halaman (page_js) sering memakai DOMContentLoaded — karena DOM
// sudah siap saat navigasi AJAX, kita patch sementara addEventListener
// agar callback DOMContentLoaded langsung dijalankan (setTimeout 0).
// =====================================================================

// Set untuk melacak src script halaman yang sedang aktif
var _iclabsActivePageScripts = new Set();

// [BARU] Library vendor (mis. FullCalendar/Chart.js dari CDN) TIDAK PERNAH
// dihapus dari set ini (berbeda dari _iclabsActivePageScripts yang di-clear
// tiap navigasi) - begitu berhasil dimuat sekali, tidak perlu di-fetch ulang
// dari CDN setiap kali pengguna bolak-balik ke halaman yang sama. Sebelumnya
// vendor_js selalu di-reload dari jaringan pada SETIAP navigasi AJAX karena
// diletakkan di <body> (bukan <head>), sehingga kalau koneksi ke CDN lambat/
// gagal (jaringan sekolah/kampus sering memblokir atau throttle CDN pihak
// ketiga), FullCalendar gagal terdefinisi dan kalender "hilang" secara
// INTERMITTEN (kadang berhasil kadang tidak) - berbeda dari bug SyntaxError
// sebelumnya yang gagal 100% konsisten.
var _iclabsLoadedVendorScripts = new Set();

function _iclabsIsVendorSrc(src) {
    try { return new URL(src, window.location.href).host !== window.location.host; }
    catch (e) { return false; }
}

// Progress bar tipis di atas halaman sebagai indikator navigasi
/* ── ICLABS Animated Loading Overlay ──────────────────────────────────
   HTML: #iclabsLoaderWrap dalam header.php
   CSS: global.css → .iclabs-orbit, .iclabs-dot, .iclabs-logo-center    */
function _iclabsNavBarShow() {
    var wrap = document.getElementById('iclabsLoaderWrap');
    if (wrap) { wrap.classList.add('active'); wrap.removeAttribute('aria-hidden'); }
}
function _iclabsNavBarProgress(pct) { /* progress via CSS orbit animation */ }
function _iclabsNavBarDone() {
    var wrap = document.getElementById('iclabsLoaderWrap');
    if (wrap) { wrap.classList.remove('active'); wrap.setAttribute('aria-hidden','true'); }
}
window.iclabsLoaderShow = _iclabsNavBarShow;
window.iclabsLoaderHide = _iclabsNavBarDone;

// URL-URL yang harus dilakukan dengan navigasi penuh (bukan AJAX)
function _iclabsSkipAjax(href) {
    if (!href) return true;
    // Protokol bukan HTTP/HTTPS
    if (!/^https?:\/\//i.test(href) && !href.startsWith('/') && !href.startsWith('./')) {
        if (href.startsWith('javascript:') || href.startsWith('#') || href.startsWith('mailto:') || href.startsWith('tel:')) return true;
    }
    try {
        var u = new URL(href, window.location.href);
        // Link eksternal
        if (u.host !== window.location.host) return true;
        var p = u.pathname;
        // Logout, PDF, download, API
        // Halaman yang butuh full page reload:
    // - Logout (clear session)
    // - PDF/export/download (response berupa file, bukan HTML)
    // - API/AJAX endpoint (return JSON, bukan HTML)
    // - scan page (butuh akses kamera; getUserMedia perlu reload)
    if (/\/(auth\/logout|pdf|exportPdf|exportCsv|recycleBinDownload|getQrAjax|resetToBin|submit_attendance|submit_logbook|check_qr_type|submit_leave|saveLogbook|manageLogbook|deleteLogbook|\/scan$|\/scan\?)/i.test(p)) return true;
        // File statis (gambar, pdf, zip, dll)
        if (/\.(pdf|zip|png|jpg|jpeg|gif|svg|webp|mp4|mp3|doc|docx|xls|xlsx)(\?|$)/i.test(p)) return true;
    } catch (e) { return true; }
    return false;
}

function _iclabsLoadPageScripts(newDoc, callback) {
    // Kumpulkan src script dari halaman baru (body: vendor_js + page_js)
    // vendor_js di-render footer.php SEBELUM page_js agar library tersedia
    var newSrcs = [];
    newDoc.querySelectorAll('body script[src]').forEach(function (s) {
        if (s.src) newSrcs.push(s.src);
    });

    // Hapus SEMUA script halaman lama dari DOM agar re-eksekusi saat halaman
    // berikutnya memuatnya. Penting untuk script yang menyimpan referensi DOM
    // (mis. assistant_search_modal.js) agar tidak memakai ref DOM lama setelah
    // AJAX swap.
    _iclabsActivePageScripts.forEach(function (src) {
        var el = Array.from(document.querySelectorAll('script[src]')).find(function(s){ return s.src === src; });
        if (el) el.remove();
    });
    _iclabsActivePageScripts.clear();

    // Perbarui APP_CONFIG dari halaman baru
    // Footer.php menyisipkan window.APP_CONFIG = {...} sebagai inline script
    // di luar #mainContent. Kita baca dari newDoc dan jalankan ulang.
    // [PENTING] TIDAK boleh pakai `new Function(txt)()` / eval — CSP situs ini
    // (script-src tanpa 'unsafe-eval') memblokirnya, dan errornya sebelumnya
    // tertelan oleh catch kosong sehingga window.APP_CONFIG TIDAK PERNAH
    // ter-update lagi setelah navigasi AJAX pertama. Akibatnya semua chart/
    // kalender di halaman berikutnya memakai data halaman SEBELUMNYA (kosong/
    // salah) — chart tidak tampil, kalender tidak muncul, dst.
    // Solusi: buat elemen <script> DOM asli dan sisipkan ke document — ini
    // diizinkan oleh 'unsafe-inline' pada CSP script-src (sama seperti script
    // inline biasa), tidak melalui eval sama sekali.
    newDoc.querySelectorAll('script:not([src])').forEach(function (s) {
        var txt = s.textContent || '';
        if (txt.indexOf('APP_CONFIG') !== -1 || txt.indexOf('LOCKOUT_QUOTES') !== -1) {
            var inlineScript = document.createElement('script');
            inlineScript.textContent = txt;
            document.body.appendChild(inlineScript);
            inlineScript.remove();
        }
    });

    // Muat script baru yang belum ada
    // Catat script baru yang perlu dimuat (semua page_js + vendor_js dari halaman baru)
    // _iclabsActivePageScripts sudah di-clear sebelumnya, jadi ini SELALU fresh.
    // [BARU] vendor script (CDN, cross-origin) yang sudah pernah berhasil dimuat
    // TIDAK di-fetch ulang - library-nya (mis. window.FullCalendar) tetap ada di
    // memori walau elemen <script>-nya lama, jadi aman dilewati.
    var headSrcs = Array.from(document.head.querySelectorAll('script[src]')).map(function(s){ return s.src; });
    var toLoad = newSrcs.filter(function (src) {
        if (headSrcs.includes(src)) return false;
        if (_iclabsIsVendorSrc(src) && _iclabsLoadedVendorScripts.has(src)) return false;
        return true;
    });

    if (!toLoad.length) { if (callback) callback(); return; }

    // Patch sementara document.addEventListener agar DOMContentLoaded
    // langsung dijalankan (DOM sudah siap saat navigasi AJAX)
    var _origDocAEL = document.addEventListener.bind(document);
    document.addEventListener = function (type, handler, opts) {
        if (type === 'DOMContentLoaded') {
            setTimeout(function () {
                try { handler.call(document, new Event('DOMContentLoaded')); } catch (e) {}
            }, 0);
            return;
        }
        return _origDocAEL(type, handler, opts);
    };

    var idx = 0;
    function loadNext() {
        if (idx >= toLoad.length) {
            // Restore addEventListener setelah semua script selesai
            setTimeout(function () {
                document.addEventListener = _origDocAEL;
                if (callback) callback();
            }, 50);
            return;
        }
        var src = toLoad[idx++];
        var isVendor = _iclabsIsVendorSrc(src);
        var s = document.createElement('script');
        s.src = src;
        // Script halaman sendiri langsung dicatat (perlu di-reload tiap navigasi
        // seperti biasa). Vendor (CDN) baru dicatat "sudah dimuat" di dalam
        // onload - kalau gagal (mis. CDN down), TIDAK dicatat, supaya navigasi
        // berikutnya mencoba fetch ulang alih-alih permanen dianggap sukses.
        if (!isVendor) { _iclabsActivePageScripts.add(src); }
        s.onload  = function () { if (isVendor) { _iclabsLoadedVendorScripts.add(src); } loadNext(); };
        s.onerror = loadNext; // lanjut meski script gagal
        document.body.appendChild(s);
    }
    loadNext();
}

function _iclabsLoadPageStyles(newDoc) {
    // [BARU - AKAR MASALAH "CSS sering error setelah dipisah ke file eksternal"]
    // CSS spesifik-halaman ($css / $page_css di header.php) HANYA dirender
    // sebagai <link> di <head>. Navigasi AJAX di file ini cuma mengganti isi
    // #mainContent - <head> TIDAK PERNAH disentuh/diperbarui. Akibatnya: kalau
    // pengguna mendarat di halaman A (CSS A termuat di <head> saat itu), lalu
    // ber-AJAX-navigasi ke halaman B TANPA PERNAH full-reload di halaman B,
    // CSS B TIDAK PERNAH dimuat sama sekali - tampilan B jadi acak-acakan/
    // terpotong/tidak konsisten, tergantung urutan kunjungan halaman.
    // Ini persis kenapa style "sering error" sejak dipisah ke file eksternal:
    // selama masih inline di dalam #mainContent, ia ikut ter-swap otomatis;
    // setelah dipisah jadi <link> di <head>, tidak ada mekanisme yang menyalin
    // <link> baru tsb - beda dari <script> yang sudah punya mekanismenya sendiri
    // (_iclabsLoadPageScripts). <link> CSS aman ditambah terus (tidak seperti
    // <script> yang bisa re-declare error) - jadi tidak perlu dihapus lagi.
    var existingHrefs = Array.from(document.head.querySelectorAll('link[rel="stylesheet"]')).map(function (l) { return l.href; });
    newDoc.querySelectorAll('head link[rel="stylesheet"]').forEach(function (link) {
        if (link.href && existingHrefs.indexOf(link.href) === -1) {
            var newLink = document.createElement('link');
            newLink.rel = 'stylesheet';
            newLink.href = link.href;
            document.head.appendChild(newLink);
            existingHrefs.push(link.href);
        }
    });
}

function _iclabsNavigateTo(href, pushState) {
    _iclabsNavBarShow();

    fetch(href, {
        // Tidak menggunakan X-Requested-With agar controller yang mendeteksi
        // header ini (mis. izin, logbook AJAX filter) tetap mengembalikan
        // halaman penuh (dengan #mainContent), bukan partial HTML.
        credentials: 'same-origin'
    })
    .then(function (res) {
        if (!res.ok) throw new Error('HTTP ' + res.status);
        _iclabsNavBarProgress(70);
        return res.text();
    })
    .then(function (html) {
        var parser = new DOMParser();
        var newDoc = parser.parseFromString(html, 'text/html');

        // Muat <link rel="stylesheet"> spesifik-halaman baru SEDINI MUNGKIN
        // (sebelum konten di-swap) agar sempat mulai di-fetch browser dan
        // meminimalkan flash tampilan tanpa style.
        _iclabsLoadPageStyles(newDoc);

        // Konten utama: coba ambil #mainContent (ganti seluruh area konten)
        var newMain = newDoc.getElementById('mainContent');
        var curMain = document.getElementById('mainContent');

        if (!newMain || !curMain) {
            // Fallback ke navigasi penuh jika struktur tidak cocok
            window.location.href = href;
            return;
        }

        // Animasi keluar konten lama
        curMain.style.opacity = '0';
        curMain.style.transition = 'opacity .1s ease';

        setTimeout(function () {
            curMain.innerHTML = newMain.innerHTML;
            curMain.className  = newMain.className;

            // Animasi masuk konten baru
            curMain.style.opacity = '0';
            void curMain.offsetHeight; // force reflow
            curMain.style.transition = 'opacity .18s ease';
            curMain.style.opacity = '1';

            // Pasang toggle password & filter digit telepon pada elemen
            // baru yang baru saja di-swap (mis. Edit Profil dicapai lewat
            // navigasi AJAX) - inisialisasi awal di DOMContentLoaded cuma
            // sekali jalan untuk konten HALAMAN PERTAMA saja.
            initPasswordToggles(curMain);
            initPhoneDigitFilters(curMain);

            // [PENTING] Muat script halaman baru HANYA SETELAH konten baru
            // benar-benar terpasang di DOM. Sebelumnya dipanggil di luar
            // setTimeout ini (langsung setelah fetch selesai), sehingga script
            // yang menyimpan referensi elemen di top-level (mis.
            // assistant_search_modal.js, qr_page.js) sempat berjalan SAAT
            // #mainContent masih berisi halaman LAMA — akibatnya elemen yang
            // diambil via getElementById adalah elemen halaman sebelumnya
            // (atau null), bukan elemen halaman baru. Efeknya: modal/QR
            // tampak "tidak berfungsi" setelah navigasi AJAX kedua & seterusnya.
            _iclabsLoadPageScripts(newDoc, function () {
                _iclabsNavBarDone();
            });
        }, 100);

        // Update judul halaman
        var newTitle = newDoc.querySelector('title');
        if (newTitle) document.title = newTitle.textContent;

        // Update URL
        if (pushState !== false) {
            history.pushState({ iclabsNav: true, href: href }, document.title, href);
        }

        // Update item aktif di sidebar
        _iclabsUpdateSidebarActive(href);

        // Scroll ke atas
        window.scrollTo({ top: 0, behavior: 'instant' });
    })
    .catch(function () {
        // Fallback ke navigasi penuh jika AJAX gagal
        window.location.href = href;
    });
}

function _iclabsUpdateSidebarActive(href) {
    // Re-set kelas aktif pada item menu sidebar berdasarkan URL baru.
    // Catatan:
    //  - Link di #profileSection (Edit Profil) DIKECUALIKAN karena punya
    //    desain berbeda dari item menu nav biasa.
    //  - Icon <i> di dalam <a> juga diperbarui warnanya (text-white / text-gray-400)
    //    karena icon punya kelas warna sendiri yang override parent.
    try {
        var newPath = new URL(href, window.location.href).pathname;
        document.querySelectorAll('#sidebar nav a[href]').forEach(function (a) {
            // Hanya proses item menu nav (bukan tombol di profileSection / footer)
            if (!a.closest('nav')) return;

            var aPath = '';
            try { aPath = new URL(a.href, window.location.href).pathname; } catch (e) {}
            var keyword = a.getAttribute('data-keyword') || aPath.split('/').pop() || '';
            var isActive = keyword ? newPath.indexOf(keyword) !== -1 : (aPath && newPath === aPath);

            if (isActive) {
                a.classList.add('bg-blue-600', 'text-white', 'shadow-lg',
                                'shadow-blue-500/30', 'border-transparent');
                a.classList.remove('text-gray-600', 'hover:bg-gray-50',
                                   'hover:text-blue-600', 'hover:shadow-sm');
            } else {
                a.classList.remove('bg-blue-600', 'text-white', 'shadow-lg',
                                   'shadow-blue-500/30', 'border-transparent');
                a.classList.add('text-gray-600', 'hover:bg-gray-50',
                                'hover:text-blue-600', 'hover:shadow-sm');
            }

            // Perbarui warna icon di dalam item menu
            var icon = a.querySelector('i.fas, i.far, i.fab, i.fa');
            if (icon) {
                if (isActive) {
                    icon.classList.remove('text-gray-400', 'group-hover:text-blue-600');
                    icon.classList.add('text-white');
                } else {
                    icon.classList.remove('text-white');
                    icon.classList.add('text-gray-400', 'group-hover:text-blue-600');
                }
            }
        });
    } catch (e) {}
}

function initAjaxNav() {
    // Catat script halaman yang sudah ada saat pertama load
    document.querySelectorAll('body script[src]').forEach(function (s) {
        if (s.src) _iclabsActivePageScripts.add(s.src);
    });

    // Intersep klik pada link
    document.addEventListener('click', function (e) {
        // Cari elemen <a> terdekat yang diklik
        var link = e.target;
        while (link && link.tagName !== 'A') link = link.parentElement;
        if (!link || link.tagName !== 'A') return;

        var href = link.getAttribute('href');
        if (!href) return;

        // Konversi ke URL absolut
        try {
            href = new URL(href, window.location.href).href;
        } catch (err) { return; }

        // Lewati navigasi AJAX jika perlu navigasi penuh
        if (_iclabsSkipAjax(href)) return;
        if (link.hasAttribute('target') || link.hasAttribute('download')) return;

        e.preventDefault();
        _iclabsNavigateTo(href, true);
    }, true); // useCapture=true agar lebih awal dari event listener lain

    // Tangani tombol Back/Forward browser
    window.addEventListener('popstate', function (e) {
        var href = (e.state && e.state.href) ? e.state.href : window.location.href;
        _iclabsNavigateTo(href, false);
    });

    // Simpan state awal
    history.replaceState({ iclabsNav: true, href: window.location.href }, document.title, window.location.href);
}

document.addEventListener('DOMContentLoaded', () => {
  initSidebarController();
  initAjaxNav();
  initPasswordToggles();
  initPhoneDigitFilters();
});

// [BARU] Toggle lihat/sembunyikan password - berlaku OTOMATIS untuk SEMUA
// <input type="password"> di situs ini (login, edit profil, tambah/edit
// user, dst) tanpa perlu markup/JS tambahan per halaman. Default selalu
// tersembunyi (type="password"); ikon mata membalik ke type="text"
// sementara agar pengguna bisa memeriksa ejaan sebelum submit.
function initPasswordToggles(root) {
  var scope = root || document;
  var inputs = scope.querySelectorAll('input[type="password"]:not([data-pw-toggle-init])');

  inputs.forEach(function (input) {
    input.setAttribute('data-pw-toggle-init', '1');

    var wrapper = document.createElement('div');
    wrapper.className = 'relative';
    input.parentNode.insertBefore(wrapper, input);
    wrapper.appendChild(input);

    if (!/\bpr-10\b/.test(input.className)) {
      input.className = (input.className + ' pr-10').trim();
    }

    var btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-blue-600 transition';
    btn.tabIndex = -1;
    btn.setAttribute('aria-label', 'Tampilkan/sembunyikan password');
    btn.innerHTML = '<i class="fas fa-eye text-xs"></i>';

    btn.addEventListener('click', function () {
      var showing = input.type === 'text';
      input.type = showing ? 'password' : 'text';
      btn.innerHTML = showing
        ? '<i class="fas fa-eye text-xs"></i>'
        : '<i class="fas fa-eye-slash text-xs"></i>';
    });

    wrapper.appendChild(btn);
  });
}

// [BARU] Nomor telepon HANYA menerima digit - dipasang otomatis pada semua
// input bertanda class "js-phone-digits" (dipakai bersama pilihan kode
// negara, lihat admin/users.php & common/edit_profile.php).
function initPhoneDigitFilters(root) {
  var scope = root || document;
  var inputs = scope.querySelectorAll('.js-phone-digits:not([data-phone-filter-init])');

  inputs.forEach(function (input) {
    input.setAttribute('data-phone-filter-init', '1');
    input.addEventListener('input', function () {
      var digitsOnly = input.value.replace(/\D/g, '');
      if (digitsOnly !== input.value) input.value = digitsOnly;
    });
    input.addEventListener('paste', function (e) {
      e.preventDefault();
      var text = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
      var start = input.selectionStart, end = input.selectionEnd;
      input.value = input.value.slice(0, start) + text + input.value.slice(end);
      input.dispatchEvent(new Event('input'));
    });
  });
}

function initSidebarController() {
  const sidebar = document.getElementById('sidebar');
  const mainContent = document.getElementById('mainContent');
  const toggleBtn = document.getElementById('toggleSidebar');
  const toggleIcon = document.getElementById('toggleIcon');
  const logoHeader = document.getElementById('logoHeader');

  if (!sidebar || !mainContent) {
    return;
  }

  const textElements = document.querySelectorAll('.sidebar-text');
  const headers = document.querySelectorAll('.sidebar-header');
  const profileContainer = document.getElementById('profileContainer');
  const logoContainer = document.getElementById('logoContainer');

  // [BARU] Auto-minimize di layar sempit. Di bawah AUTO_MINIMIZE_MAX_WIDTH
  // (tablet/laptop kecil - masih >=768 jadi bukan mode overlay mobile),
  // sidebar otomatis diminimalkan supaya konten dapat ruang lebih, terlepas
  // dari preferensi tersimpan pengguna (yang hanya berlaku di layar lebar).
  // Tombol toggle tetap berfungsi sebagai override sementara (narrowOverride)
  // selama lebar layar masih di rentang sempit - direset begitu lebar layar
  // berpindah rentang (sempit <-> lebar <-> mobile).
  var AUTO_MINIMIZE_MAX_WIDTH = 1024;
  var narrowOverride = null;
  var lastWidthBucket = getWidthBucket();

  function getWidthBucket() {
    var w = window.innerWidth;
    if (w < 768) return 'mobile';
    if (w < AUTO_MINIMIZE_MAX_WIDTH) return 'narrow';
    return 'wide';
  }

  function computeIsMinimized() {
    var w = window.innerWidth;
    if (w < 768) return false; // mode overlay mobile, tidak relevan
    if (w < AUTO_MINIMIZE_MAX_WIDTH) {
      return narrowOverride !== null ? narrowOverride : true;
    }
    return localStorage.getItem('sidebarState') === 'minimized';
  }

  function updateSidebarState() {
    var bucket = getWidthBucket();
    if (bucket !== lastWidthBucket) {
      narrowOverride = null;
      lastWidthBucket = bucket;
    }
    var isMinimized = computeIsMinimized();
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
  initSidebarTooltips(sidebar);

  setTimeout(() => {
    document.documentElement.classList.remove('preload-minimized');
  }, 100);

  if (toggleBtn) {
    toggleBtn.addEventListener('click', e => {
      e.stopPropagation();

      var next = !computeIsMinimized();
      if (getWidthBucket() === 'narrow') {
        // Layar sempit: override sementara, tidak menimpa preferensi
        // tersimpan untuk layar lebar.
        narrowOverride = next;
      } else {
        localStorage.setItem('sidebarState', next ? 'minimized' : 'expanded');
      }
      updateSidebarState();
    });
  }

  if (logoHeader) {
    logoHeader.addEventListener('click', () => {
      if (computeIsMinimized() && window.innerWidth >= 768) {
        if (getWidthBucket() === 'narrow') {
          narrowOverride = false;
        } else {
          localStorage.setItem('sidebarState', 'expanded');
        }
        updateSidebarState();
      }
    });
  }

  // [PERBAIKAN] #mobileMenuBtn ada di dalam #mainContent, yang innerHTML-nya
  // DIGANTI TOTAL pada setiap navigasi AJAX (lihat _iclabsNavigateTo di atas)
  // - tombolnya sendiri ikut hancur & dibuat ulang tanpa listener setiap kali
  // pindah halaman, karena initSidebarController() ini cuma dipanggil SEKALI
  // saat DOMContentLoaded. Akibatnya hamburger berhenti berfungsi setelah
  // navigasi AJAX pertama - paling terasa saat menu diakses lewat tombol di
  // luar sidebar (mis. QR Presensi dari dashboard) karena sidebar dalam
  // keadaan tertutup saat itu, sehingga hamburger tampak mati total.
  // Solusi: pasang listener delegasi di document (elemen ini permanen, tidak
  // pernah diganti) yang mencari #mobileMenuBtn lewat e.target.closest() saat
  // diklik - otomatis mengikuti tombol versi manapun yang sedang ada di DOM.
  document.addEventListener('click', e => {
    var btn = e.target.closest && e.target.closest('#mobileMenuBtn');
    if (!btn) return;
    e.stopPropagation();
    sidebar.classList.toggle('-translate-x-full');
  });

  window.addEventListener('resize', updateSidebarState);

  document.addEventListener('click', e => {
    if (window.innerWidth < 768) {
      // [PERBAIKAN] Query ulang #mobileMenuBtn di sini alih-alih memakai
      // variabel yang ditangkap sekali di awal - node lama sudah lepas dari
      // DOM setelah navigasi AJAX, jadi .contains() terhadapnya akan selalu
      // false walau klik sebenarnya tepat di tombol yang baru, membuat
      // sidebar keliru dianggap "diklik di luar" lalu tertutup paksa.
      const liveMobileBtn = document.getElementById('mobileMenuBtn');
      const isClickInside =
        sidebar.contains(e.target) ||
        (liveMobileBtn && liveMobileBtn.contains(e.target));

      if (!isClickInside && !sidebar.classList.contains('-translate-x-full')) {
        sidebar.classList.add('-translate-x-full');
      }
    }
  });
}

// [BARU] Tooltip nama menu sidebar saat mode minimize - posisi dihitung &
// diterapkan lewat JS sebagai position:fixed (lihat .sidebar-tooltip di
// global.css) supaya lolos dari overflow-y-auto milik <nav> sidebar, yang
// sebelumnya diam-diam memotong tooltip absolute-positioned (baik versi
// lama di samping kanan, maupun versi atas) sehingga hint menu tidak
// pernah benar-benar terlihat.
function initSidebarTooltips(sidebar) {
  var tooltips = sidebar.querySelectorAll('.sidebar-tooltip');

  tooltips.forEach(function (tip) {
    var anchor = tip.closest('.group');
    if (!anchor) return;

    anchor.addEventListener('mouseenter', function () {
      if (!sidebar.classList.contains('w-20')) return; // hanya saat minimize
      var rect = anchor.getBoundingClientRect();
      tip.style.left = (rect.left + rect.width / 2) + 'px';
      tip.style.top = rect.top + 'px';
      tip.classList.add('tooltip-show');
    });

    anchor.addEventListener('mouseleave', function () {
      tip.classList.remove('tooltip-show');
    });
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
