(() => {
  "use strict";

  document.addEventListener("DOMContentLoaded", () => {
    initClock();
  });

  function initClock() {
    const liveDate = document.getElementById("liveDate");
    const liveTime = document.getElementById("liveTime");

    if (!liveDate || !liveTime) return;

    function updateClock() {
      const now = new Date();

      liveDate.innerText = now.toLocaleDateString("id-ID", {
        day: "2-digit",
        month: "long",
        year: "numeric"
      });

      liveTime.innerText = now
        .toLocaleTimeString("id-ID", {
          hour: "2-digit",
          minute: "2-digit",
          second: "2-digit",
          hour12: false
        })
        .replace(/\./g, ":");
    }

    updateClock();
    setInterval(updateClock, 1000);
  }
})();
