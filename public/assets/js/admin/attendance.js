document.addEventListener('DOMContentLoaded', () => {
  initLiveClock();
});

function initLiveClock() {
  const dateEl = document.getElementById('liveDate');
  const timeEl = document.getElementById('liveTime');

  if (!dateEl || !timeEl) return;

  function updateClock() {
    const now = new Date();

    dateEl.innerText = now.toLocaleDateString('id-ID', {
      day: '2-digit',
      month: 'long',
      year: 'numeric'
    });

    timeEl.innerText = now
      .toLocaleTimeString('id-ID', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false
      })
      .replace(/\./g, ':');
  }

  updateClock();
  setInterval(updateClock, 1000);
}
