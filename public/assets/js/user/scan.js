let html5QrcodeScanner = null;
let selfieStream = null;
let config = {};

const videoEl = document.getElementById('selfie-video');
const canvasEl = document.getElementById('selfie-canvas');
const resultImg = document.getElementById('selfie-result');

document.addEventListener('DOMContentLoaded', () => {
    readConfig();
    initQRWidget();
    initGeolocation();
});

function readConfig() {
    const el = document.getElementById('scan-config');
    if (!el) return;
    try {
        config = JSON.parse(el.textContent);
    } catch (e) {
        console.error('Scan config invalid JSON', e);
    }
}

function initGeolocation() {
    const gpsText = document.getElementById('gps-text');
    const gpsDot = document.getElementById('gps-dot');

    if (!navigator.geolocation) {
        gpsText.innerText = "Browser Tidak Support GPS";
        return;
    }

    const options = {
        enableHighAccuracy: true,
        timeout: 20000,
        maximumAge: 0
    };

    navigator.geolocation.getCurrentPosition(
        async (pos) => {
            const { latitude: lat, longitude: lng } = pos.coords;

            document.getElementById('geo-lat').value = lat;
            document.getElementById('geo-lng').value = lng;

            gpsText.innerText = "Mendapatkan Alamat...";
            gpsDot.className = "w-2 h-2 rounded-full bg-yellow-400 animate-pulse";

            try {
                const url = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`;
                const res = await fetch(url, {
                    headers: { 'Accept-Language': 'id' }
                });
                const data = await res.json();

                if (data && data.address) {
                    const addr = data.address;
                    const parts = [];

                    if (addr.road) parts.push(addr.road);
                    if (addr.suburb) parts.push(addr.suburb);
                    if (addr.city) parts.push(addr.city);
                    if (addr.county) parts.push(addr.county);

                    const fullAddress = parts.join(', ');
                    const shortAddress = parts.slice(0, 2).join(', ');

                    document.getElementById('geo-address').value = fullAddress || "Lokasi Terdeteksi";
                    gpsText.innerText = shortAddress.substring(0, 25) + (shortAddress.length > 25 ? '...' : '');
                    gpsDot.className = "w-2 h-2 rounded-full bg-green-500 shadow-[0_0_10px_#22c55e]";
                } else {
                    throw new Error("Alamat tidak ditemukan");
                }
            } catch (e) {
                const fallback = `${lat.toFixed(5)}, ${lng.toFixed(5)}`;
                document.getElementById('geo-address').value = fallback;
                gpsText.innerText = "Lokasi: Koordinat";
                gpsDot.className = "w-2 h-2 rounded-full bg-green-500";
            }
        },
        (err) => {
            gpsText.innerText = "GPS Error";
            gpsDot.className = "w-2 h-2 rounded-full bg-red-500";
            if (err.code === 1) showModal('error', 'Izin Ditolak', 'Mohon izinkan akses lokasi browser.');
        },
        options
    );
}

function initQRWidget() {
    html5QrcodeScanner = new Html5QrcodeScanner(
        "qr-reader",
        {
            fps: 10,
            qrbox: { width: 250, height: 250 },
            aspectRatio: 1.0,
            showTorchButtonIfSupported: true
        },
        false
    );

    html5QrcodeScanner.render(onScanSuccess, () => {});
}

function onScanSuccess(decodedText) {
    html5QrcodeScanner.clear().then(() => {
        let cleanToken = decodedText;
        let type = 'check_in';

        try {
            const parsed = JSON.parse(decodedText);
            if (parsed.token) cleanToken = parsed.token;
            if (parsed.type === 'CHECK_OUT') type = 'check_out';
        } catch {}

        document.getElementById('scanned-token').value = cleanToken;
        document.getElementById('scanned-type').value = type;

        document.getElementById('step-scan').classList.add('hidden');
        document.getElementById('step-selfie').classList.remove('hidden');

        const controls = document.getElementById('controls-selfie');
        controls.classList.remove('hidden');
        controls.classList.add('flex');

        setTimeout(startSelfieCamera, 500);
    });
}

async function startSelfieCamera() {
    try {
        selfieStream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: 'user', width: { ideal: 1080 }, height: { ideal: 1080 } },
            audio: false
        });
        videoEl.srcObject = selfieStream;
    } catch {
        try {
            selfieStream = await navigator.mediaDevices.getUserMedia({ video: true });
            videoEl.srcObject = selfieStream;
        } catch (err) {
            showModal('error', 'Kamera Error', err.message);
        }
    }
}

function takeSnapshot() {
    if (!selfieStream) return;

    const size = 1000;
    const imgHeight = 800;
    canvasEl.width = size;
    canvasEl.height = size;

    const ctx = canvasEl.getContext('2d');
    ctx.fillStyle = "#ffffff";
    ctx.fillRect(0, 0, size, size);

    ctx.save();
    ctx.translate(size, 0);
    ctx.scale(-1, 1);

    const vW = videoEl.videoWidth;
    const vH = videoEl.videoHeight;
    const minDim = Math.min(vW, vH);

    ctx.drawImage(
        videoEl,
        (vW - minDim) / 2,
        (vH - minDim) / 2,
        minDim,
        minDim,
        0,
        0,
        size,
        imgHeight
    );
    ctx.restore();

    const now = new Date();
    const dateStr = now.toLocaleDateString('id-ID', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    });
    const timeStr = now.toLocaleTimeString('id-ID', {
        hour: '2-digit',
        minute: '2-digit'
    }).replace(/\./g, ':');

    const name = config.userName || 'User';
    const address = document.getElementById('geo-address').value || 'Lokasi Tidak Terdeteksi';

    ctx.textAlign = "center";
    ctx.fillStyle = "#1f2937";
    ctx.font = "bold 40px sans-serif";
    ctx.fillText(name, size / 2, imgHeight + 60);

    ctx.fillStyle = "#4b5563";
    ctx.font = "italic 24px sans-serif";
    const displayAddr = address.length > 50 ? address.substring(0, 50) + "..." : address;
    ctx.fillText("📍 " + displayAddr, size / 2, imgHeight + 100);

    ctx.fillStyle = "#2563eb";
    ctx.font = "bold 32px monospace";
    ctx.fillText(`${timeStr} WITA • ${dateStr}`, size / 2, imgHeight + 150);

    const dataUrl = canvasEl.toDataURL('image/jpeg', 0.9);
    resultImg.src = dataUrl;
    resultImg.classList.remove('hidden');

    document.getElementById('final-image-base64').value = dataUrl;

    videoEl.classList.add('hidden');
    document.getElementById('btn-take').classList.add('hidden');

    const actions = document.getElementById('action-group');
    actions.classList.remove('hidden');
    actions.classList.add('flex');
}

function resetCamera() {
    resultImg.classList.add('hidden');
    videoEl.classList.remove('hidden');
    document.getElementById('btn-take').classList.remove('hidden');

    const actions = document.getElementById('action-group');
    actions.classList.add('hidden');
    actions.classList.remove('flex');
}

function submitAttendance() {
    const token = document.getElementById('scanned-token').value;
    const type = document.getElementById('scanned-type').value;
    const img = document.getElementById('final-image-base64').value;
    const address = document.getElementById('geo-address').value;

    if (!img) {
        showModal('error', 'Foto Kosong', 'Silakan ambil foto bukti.');
        return;
    }

    showLoading(true);

    const fd = new FormData();
    fd.append('token', token);
    fd.append('type', type);
    fd.append('image', img);
    fd.append('address', address);

    fetch(config.submitUrl, { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            showLoading(false);
            if (data.status === 'success') {
                showModal('success', 'Berhasil', data.message, () => {
                    window.location.href = config.dashboardUrl;
                });
            } else {
                showModal('error', 'Gagal', data.message);
            }
        })
        .catch(() => {
            showLoading(false);
            showModal('error', 'Koneksi Error', 'Gagal menghubungi server.');
        });
}

function showLoading(show) {
    const el = document.getElementById('loading-overlay');
    if (show) {
        el.classList.remove('hidden');
        el.classList.add('flex');
    } else {
        el.classList.add('hidden');
        el.classList.remove('flex');
    }
}

function showModal(type, title, message, onOk = null) {
    const m = document.getElementById('customModal');
    const iconCont = document.getElementById('modalIconContainer');
    const icon = document.getElementById('modalIcon');
    const btn = document.getElementById('modalBtn');

    document.getElementById('modalTitle').innerText = title;
    document.getElementById('modalMessage').innerText = message;

    iconCont.className =
        "w-20 h-20 rounded-full mx-auto mb-4 flex items-center justify-center text-4xl shadow-lg text-white";

    if (type === 'success') {
        iconCont.classList.add('bg-green-500');
        icon.className = "fas fa-check";
        btn.className = "w-full py-3.5 rounded-xl bg-green-600 text-white font-bold hover:bg-green-700 transition";
        btn.innerText = "Selesai";
    } else {
        iconCont.classList.add('bg-red-500');
        icon.className = "fas fa-times";
        btn.className = "w-full py-3.5 rounded-xl bg-red-600 text-white font-bold hover:bg-red-700 transition";
        btn.innerText = "Tutup";
    }

    m.classList.remove('hidden');
    btn.onclick = () => {
        m.classList.add('hidden');
        if (onOk) onOk();
    };
}

window.takeSnapshot = takeSnapshot;
window.resetCamera = resetCamera;
window.submitAttendance = submitAttendance;
