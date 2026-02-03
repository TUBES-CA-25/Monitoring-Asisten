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

      if (!isMinimized && window.innerWidth >= 768) {
        isMinimized = true;
        localStorage.setItem('sidebarState', 'minimized');
        updateSidebarState();
      }
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
