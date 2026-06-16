
    // Cek status sidebar di localStorage segera saat loading
    if (localStorage.getItem('sidebarState') === 'minimized' && window.innerWidth >= 768) {
        document.documentElement.classList.add('preload-minimized');
    }
