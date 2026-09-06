
    function updateClock() {
        const now = new Date();
        document.getElementById('liveDate').innerText = now.toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });
        document.getElementById('liveTime').innerText = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false }).replace(/\./g, ':');
    }
    setInterval(updateClock, 1000); updateClock();
