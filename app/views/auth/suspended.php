<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akun Dinonaktifkan – ICLABS</title>
    <link rel="icon" type="image/png" href="<?= ASSET_URL ?>/img/Logo_ICLABS.png">
    <link rel="shortcut icon" type="image/png" href="<?= ASSET_URL ?>/img/Logo_ICLABS.png">
    <link rel="apple-touch-icon" href="<?= ASSET_URL ?>/img/Logo_ICLABS.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        html, body { background: #060b18; }
        body { overflow: hidden; }

        @keyframes pulse-slow { 0%,100%{opacity:.6} 50%{opacity:1} }
        .pulse-slow { animation: pulse-slow 3s ease-in-out infinite; }

        /* Dua canvas ditumpuk - crossfade antar animasi murni lewat transisi
           CSS opacity pada elemen <canvas>-nya sendiri (bukan alpha per-frame
           di dalam context 2D), supaya tiap scene tetap punya canvas &
           riwayat partikel sendiri yang independen saat berpindah. */
        .bg-scene {
            position: fixed;
            inset: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            opacity: 0;
            transition: opacity 1.6s ease;
            cursor: default;
        }
        .bg-scene.is-visible { opacity: 1; }

        .suspended-content {
            position: relative;
            z-index: 10;
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-6">

    <canvas id="bgSceneA" class="bg-scene is-visible"></canvas>
    <canvas id="bgSceneB" class="bg-scene"></canvas>

    <div class="max-w-md w-full suspended-content">
        <!-- Logo -->
        <div class="flex justify-center mb-8">
            <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-blue-600 to-cyan-500 flex items-center justify-center shadow-lg shadow-blue-500/30 p-3">
                <img src="<?= BASE_URL ?>/assets/img/Logo_ICLABS_White.webp" alt="ICLABS" class="w-full h-full object-contain pulse-slow">
            </div>
        </div>

        <!-- Card -->
        <div id="suspendedCard" class="bg-slate-800/95 backdrop-blur border border-slate-700 rounded-3xl overflow-hidden shadow-2xl">

            <!-- Header gradient -->
            <div class="bg-gradient-to-r from-slate-700 to-slate-600 px-8 py-6 text-center border-b border-slate-600">
                <div class="w-14 h-14 rounded-full bg-red-500/20 border-2 border-red-400/40 flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-user-slash text-red-400 text-xl"></i>
                </div>
                <h1 class="text-xl font-extrabold text-white">Akun Dinonaktifkan</h1>
                <p class="text-slate-400 text-sm mt-1">ICLABS – Lab Assistant System</p>
            </div>

            <!-- Body -->
            <div class="px-8 py-7 text-center">
                <p class="text-slate-300 text-sm leading-relaxed mb-2">
                    Halo, <span class="font-bold text-white"><?= htmlspecialchars($name) ?></span>.
                </p>
                <p class="text-slate-400 text-sm leading-relaxed mb-6">
                    Akun Anda saat ini <span class="text-red-400 font-semibold">dinonaktifkan</span> oleh Administrator.
                    Anda tidak dapat mengakses fitur apapun hingga akun diaktifkan kembali.
                </p>

                <div class="bg-slate-700/50 border border-slate-600 rounded-2xl p-4 mb-6 text-left space-y-2">
                    <div class="flex items-center gap-3 text-xs text-slate-300">
                        <i class="fas fa-info-circle text-blue-400 w-4 shrink-0"></i>
                        <span>Data kehadiran Anda sudah diarsipkan dengan aman.</span>
                    </div>
                    <div class="flex items-center gap-3 text-xs text-slate-300">
                        <i class="fas fa-calendar-check text-cyan-400 w-4 shrink-0"></i>
                        <span>Jadwal Anda tidak terpengaruh.</span>
                    </div>
                    <div class="flex items-center gap-3 text-xs text-slate-300">
                        <i class="fas fa-redo text-green-400 w-4 shrink-0"></i>
                        <span>Setelah diaktifkan kembali, semua fitur langsung tersedia.</span>
                    </div>
                </div>

                <p class="text-slate-500 text-xs mb-6">
                    Hubungi <span class="text-blue-400">Administrator Lab</span> jika ada pertanyaan
                    atau jika Anda merasa ini adalah kekeliruan.
                </p>

                <a href="<?= BASE_URL ?>/auth/logout"
                   class="flex items-center justify-center gap-2 w-full py-3 rounded-xl bg-gradient-to-r from-blue-600 to-cyan-500 text-white font-bold text-sm hover:opacity-90 transition shadow-lg shadow-blue-500/20">
                    <i class="fas fa-sign-out-alt"></i> Keluar dari Sistem
                </a>
            </div>
        </div>

        <p class="text-center text-slate-600 text-xs mt-6">
            &copy; <?= date('Y') ?> ICLABS &mdash; ICo Labs-UMI
        </p>
    </div>

    <script>
    (() => {
        "use strict";

        // ================================================================
        // Latar belakang tetap: pemandangan galaksi Milky Way (bintang +
        // nebula + pita galaksi) - SELALU tampil di kedua canvas, tidak
        // ikut fade/cycle. Di atasnya, 3 animasi foreground bergantian
        // dengan fade (bintang jatuh interaktif, halo gradasi berputar
        // mengikuti ukuran kartu, meteor warna mengikuti kursor) - lihat
        // galaxyBackground + scenes[] di bawah.
        // ================================================================

        const DPR = Math.min(window.devicePixelRatio || 1, 2);
        let W = 0, H = 0;

        function fitCanvas(canvas) {
            W = window.innerWidth;
            H = window.innerHeight;
            canvas.width = W * DPR;
            canvas.height = H * DPR;
            const ctx = canvas.getContext('2d');
            ctx.setTransform(DPR, 0, 0, DPR, 0, 0);
            return ctx;
        }

        const canvasA = document.getElementById('bgSceneA');
        const canvasB = document.getElementById('bgSceneB');
        let ctxA = fitCanvas(canvasA);
        let ctxB = fitCanvas(canvasB);

        window.addEventListener('resize', () => {
            ctxA = fitCanvas(canvasA);
            ctxB = fitCanvas(canvasB);
            galaxyBackground.onResize();
            scenes.forEach((s) => s.onResize && s.onResize());
        });

        // Posisi kursor global (dipakai oleh scene aurora & meteor).
        const pointer = { x: W / 2, y: H / 2, active: false, vx: 0, vy: 0 };
        window.addEventListener('mousemove', (e) => {
            pointer.vx = e.clientX - pointer.x;
            pointer.vy = e.clientY - pointer.y;
            pointer.x = e.clientX;
            pointer.y = e.clientY;
            pointer.active = true;
            const scene = scenes[currentIndex];
            if (scene.onMouseMove) scene.onMouseMove(pointer.x, pointer.y);
        });
        window.addEventListener('click', (e) => {
            const scene = scenes[currentIndex];
            if (scene.onClick) scene.onClick(e.clientX, e.clientY);
        });

        function rand(min, max) { return Math.random() * (max - min) + min; }

        // ── Latar galaksi Milky Way (permanen, dipakai di ketiga scene) ──
        const galaxyBackground = (() => {
            let stars = [];
            let nebulae = [];

            function seed() {
                stars = [];
                const count = Math.round((W * H) / 4800);
                for (let i = 0; i < count; i++) {
                    stars.push({
                        x: rand(0, W), y: rand(0, H),
                        r: rand(0.4, 1.7),
                        phase: rand(0, Math.PI * 2),
                        speed: rand(0.4, 1.6),
                        bright: Math.random() < 0.12,
                    });
                }
                nebulae = [
                    { x: W * 0.18, y: H * 0.28, r: Math.max(W, H) * 0.35, hue: '124,58,237' },
                    { x: W * 0.82, y: H * 0.68, r: Math.max(W, H) * 0.32, hue: '14,165,233' },
                    { x: W * 0.55, y: H * 0.18, r: Math.max(W, H) * 0.26, hue: '236,72,153' },
                ];
            }

            // Pita galaksi (Milky Way) diagonal - gradient lebar diputar
            // sedikit miring, ditumpangi bintang lebih rapat sepanjang
            // pitanya supaya terasa seperti tekstur galaksi sungguhan.
            function drawMilkyWay(ctx) {
                ctx.save();
                ctx.translate(W * 0.5, H * 0.5);
                ctx.rotate(-0.4);
                const len = Math.max(W, H) * 1.7;
                const grad = ctx.createLinearGradient(0, -170, 0, 170);
                grad.addColorStop(0, 'rgba(199,210,254,0)');
                grad.addColorStop(0.5, 'rgba(199,210,254,0.12)');
                grad.addColorStop(1, 'rgba(199,210,254,0)');
                ctx.fillStyle = grad;
                ctx.fillRect(-len / 2, -170, len, 340);
                ctx.restore();
            }

            return {
                init() { seed(); },
                onResize() { seed(); },
                draw(ctx) {
                    ctx.fillStyle = '#050714';
                    ctx.fillRect(0, 0, W, H);

                    for (const neb of nebulae) {
                        const grad = ctx.createRadialGradient(neb.x, neb.y, 0, neb.x, neb.y, neb.r);
                        grad.addColorStop(0, `rgba(${neb.hue}, 0.09)`);
                        grad.addColorStop(1, `rgba(${neb.hue}, 0)`);
                        ctx.fillStyle = grad;
                        ctx.beginPath();
                        ctx.arc(neb.x, neb.y, neb.r, 0, Math.PI * 2);
                        ctx.fill();
                    }

                    drawMilkyWay(ctx);

                    const t = performance.now() / 1000;
                    for (const st of stars) {
                        const o = (0.25 + 0.55 * Math.abs(Math.sin(t * st.speed + st.phase))) * (st.bright ? 1 : 0.6);
                        ctx.globalAlpha = o;
                        ctx.fillStyle = st.bright ? '#c7d2fe' : '#e2e8f0';
                        ctx.beginPath();
                        ctx.arc(st.x, st.y, st.r, 0, Math.PI * 2);
                        ctx.fill();
                    }
                    ctx.globalAlpha = 1;
                },
            };
        })();

        // ── Scene 1: Bintang Jatuh (diagonal kiri → kanan) ──────────────
        // Latar (bintang tetap + nebula + Milky Way) sudah digambar oleh
        // galaxyBackground di loop utama - scene ini HANYA menggambar
        // elemen foreground-nya sendiri (bintang jatuh + riak klik).
        function makeStarsScene() {
            let stars = [];
            let ripples = [];
            let spawnTimer = 0;

            // Bintang jatuh selalu bergerak diagonal kiri->kanan, turun
            // landai (bukan lurus ke bawah) - gaya "shooting star" klasik.
            function spawnStar(x, y) {
                const palette = ['#ffffff', '#bfe3ff', '#ffe9b3', '#e6d1ff'];
                stars.push({
                    x: x ?? rand(-120, W * 0.35),
                    y: y ?? rand(-60, H * 0.35),
                    vx: rand(260, 440),
                    vy: rand(90, 170),
                    len: rand(100, 180),
                    color: palette[Math.floor(rand(0, palette.length))],
                });
            }

            return {
                init() { stars = []; ripples = []; spawnTimer = 500; },
                onResize() {},
                // Klik = membuat SATU bintang mulai jatuh persis dari titik
                // itu (diagonal, gaya sama seperti yang otomatis) + riak
                // cincin lembut sebagai umpan balik - BUKAN ledakan partikel.
                onClick(x, y) {
                    spawnStar(x, y);
                    ripples.push({ x, y, age: 0, life: 0.55 });
                },
                onMouseMove() {},
                update(dt) {
                    spawnTimer -= dt * 1000;
                    if (spawnTimer <= 0) {
                        spawnStar();
                        spawnTimer = rand(900, 2200);
                    }
                    for (const s of stars) {
                        s.x += s.vx * dt;
                        s.y += s.vy * dt;
                    }
                    stars = stars.filter((s) => s.x < W + 160 && s.y < H + 160);

                    for (const r of ripples) r.age += dt;
                    ripples = ripples.filter((r) => r.age < r.life);
                },
                draw(ctx) {
                    for (const r of ripples) {
                        const p = r.age / r.life;
                        ctx.globalAlpha = (1 - p) * 0.55;
                        ctx.strokeStyle = '#fef9c3';
                        ctx.lineWidth = 1.5;
                        ctx.beginPath();
                        ctx.arc(r.x, r.y, 4 + p * 28, 0, Math.PI * 2);
                        ctx.stroke();
                    }
                    ctx.globalAlpha = 1;

                    for (const s of stars) {
                        const angle = Math.atan2(s.vy, s.vx);
                        const tailX = s.x - Math.cos(angle) * s.len;
                        const tailY = s.y - Math.sin(angle) * s.len;

                        const grad = ctx.createLinearGradient(s.x, s.y, tailX, tailY);
                        grad.addColorStop(0, s.color);
                        grad.addColorStop(0.4, 'rgba(255,255,255,0.35)');
                        grad.addColorStop(1, 'rgba(255,255,255,0)');
                        ctx.strokeStyle = grad;
                        ctx.lineWidth = 2.4;
                        ctx.lineCap = 'round';
                        ctx.beginPath();
                        ctx.moveTo(s.x, s.y);
                        ctx.lineTo(tailX, tailY);
                        ctx.stroke();

                        const glow = ctx.createRadialGradient(s.x, s.y, 0, s.x, s.y, 9);
                        glow.addColorStop(0, 'rgba(255,255,255,0.95)');
                        glow.addColorStop(1, 'rgba(255,255,255,0)');
                        ctx.fillStyle = glow;
                        ctx.beginPath();
                        ctx.arc(s.x, s.y, 9, 0, Math.PI * 2);
                        ctx.fill();

                        ctx.fillStyle = '#ffffff';
                        ctx.beginPath();
                        ctx.arc(s.x, s.y, 2, 0, Math.PI * 2);
                        ctx.fill();
                    }
                },
            };
        }

        // ── Scene 2: Halo gradasi berputar, membesar mengikuti kartu ────
        // Menggantikan aurora+gunung. Cincin rounded-rect berwarna-warni
        // (conic gradient, berputar) memancar dari bentuk & posisi kartu
        // modal (#suspendedCard) lalu membesar keluar secara berulang.
        function makeGradientHaloScene() {
            let card = { cx: 0, cy: 0, w: 200, h: 200, r: 24 };
            let elapsed = 0;
            const RING_PERIOD = 4.2;
            const RING_COUNT = 4;
            const GROW_SCALE = 3.2;

            function measureCard() {
                const el = document.getElementById('suspendedCard');
                if (!el) return;
                const rect = el.getBoundingClientRect();
                card = {
                    cx: rect.left + rect.width / 2,
                    cy: rect.top + rect.height / 2,
                    w: rect.width,
                    h: rect.height,
                    r: 28,
                };
            }

            function roundedRectPath(ctx, x, y, w, h, r) {
                const rr = Math.min(r, w / 2, h / 2);
                ctx.beginPath();
                ctx.moveTo(x + rr, y);
                ctx.lineTo(x + w - rr, y);
                ctx.arcTo(x + w, y, x + w, y + rr, rr);
                ctx.lineTo(x + w, y + h - rr);
                ctx.arcTo(x + w, y + h, x + w - rr, y + h, rr);
                ctx.lineTo(x + rr, y + h);
                ctx.arcTo(x, y + h, x, y + h - rr, rr);
                ctx.lineTo(x, y + rr);
                ctx.arcTo(x, y, x + rr, y, rr);
                ctx.closePath();
            }

            return {
                init() { elapsed = 0; measureCard(); },
                onResize() { measureCard(); },
                onClick() {},
                onMouseMove() {},
                update(dt) { elapsed += dt; },
                draw(ctx) {
                    const t = performance.now() / 1000;
                    const rotation = t * 0.7;

                    for (let i = 0; i < RING_COUNT; i++) {
                        const phase = (((elapsed / RING_PERIOD) + i / RING_COUNT) % 1 + 1) % 1;
                        const scale = 1 + phase * GROW_SCALE;
                        const w = card.w * scale;
                        const h = card.h * scale;
                        const x = card.cx - w / 2;
                        const y = card.cy - h / 2;
                        const fade = Math.pow(1 - phase, 1.4);

                        let strokeStyle;
                        if (ctx.createConicGradient) {
                            const grad = ctx.createConicGradient(rotation, card.cx, card.cy);
                            const stops = [
                                ['#f472b6', 0], ['#facc15', 1 / 6], ['#4ade80', 2 / 6],
                                ['#22d3ee', 3 / 6], ['#818cf8', 4 / 6], ['#f472b6', 5 / 6], ['#f472b6', 1],
                            ];
                            for (const [color, pos] of stops) grad.addColorStop(pos, color);
                            strokeStyle = grad;
                        } else {
                            strokeStyle = `hsl(${(rotation * 60) % 360}, 85%, 65%)`;
                        }

                        ctx.save();
                        ctx.globalAlpha = fade * 0.55;
                        ctx.strokeStyle = strokeStyle;
                        ctx.lineWidth = 2.5;
                        roundedRectPath(ctx, x, y, w, h, card.r * scale);
                        ctx.stroke();
                        ctx.restore();
                    }

                    // Cincin inti tipis menempel pas di tepi kartu supaya
                    // sumber animasinya terasa jelas berasal dari kartu.
                    ctx.save();
                    ctx.globalAlpha = 0.5;
                    if (ctx.createConicGradient) {
                        const coreGrad = ctx.createConicGradient(rotation * 1.4, card.cx, card.cy);
                        coreGrad.addColorStop(0, '#f472b6');
                        coreGrad.addColorStop(0.33, '#22d3ee');
                        coreGrad.addColorStop(0.66, '#facc15');
                        coreGrad.addColorStop(1, '#f472b6');
                        ctx.strokeStyle = coreGrad;
                    } else {
                        ctx.strokeStyle = '#22d3ee';
                    }
                    ctx.lineWidth = 3;
                    roundedRectPath(ctx, card.cx - card.w / 2 - 6, card.cy - card.h / 2 - 6, card.w + 12, card.h + 12, card.r + 6);
                    ctx.stroke();
                    ctx.restore();
                },
            };
        }

        // ── Scene 3: Meteor warna mengikuti kursor, latar Milky Way ─────
        function makeMeteorScene() {
            let sparks = [];
            let autoTimer = 0;

            function spawnSpark(x, y, hue, spread) {
                const a = rand(0, Math.PI * 2);
                const speed = rand(10, spread || 60);
                sparks.push({
                    x, y,
                    vx: Math.cos(a) * speed + pointer.vx * 0.15,
                    vy: Math.sin(a) * speed + pointer.vy * 0.15,
                    hue,
                    r: rand(1.5, 3.5),
                    life: rand(0.5, 0.9),
                    age: 0,
                });
            }

            function spawnAutoMeteor() {
                const startX = rand(0, W * 0.6);
                const startY = rand(-40, H * 0.3);
                const hue = Math.floor(rand(0, 360));
                for (let i = 0; i < 18; i++) {
                    sparks.push({
                        x: startX + i * 3,
                        y: startY + i * 3,
                        vx: rand(180, 260),
                        vy: rand(140, 200),
                        hue,
                        r: rand(1.5, 3),
                        life: 0.5,
                        age: i * 0.01,
                    });
                }
            }

            let hueCycle = 0;

            return {
                init() { sparks = []; autoTimer = rand(1500, 3000); },
                onResize() {},
                onClick(x, y) {
                    for (let i = 0; i < 26; i++) spawnSpark(x, y, Math.floor(rand(0, 360)), 220);
                },
                onMouseMove(x, y) {
                    hueCycle = (hueCycle + 6) % 360;
                    for (let i = 0; i < 2; i++) spawnSpark(x, y, hueCycle, 50);
                },
                update(dt) {
                    autoTimer -= dt * 1000;
                    if (autoTimer <= 0) {
                        spawnAutoMeteor();
                        autoTimer = rand(2200, 4500);
                    }
                    for (const s of sparks) {
                        s.age += dt;
                        s.x += s.vx * dt;
                        s.y += s.vy * dt;
                        s.vx *= 0.98;
                        s.vy *= 0.98;
                    }
                    sparks = sparks.filter((s) => s.age < s.life);
                },
                draw(ctx) {
                    for (const s of sparks) {
                        const p = 1 - s.age / s.life;
                        ctx.globalAlpha = Math.max(0, p);

                        const glow = ctx.createRadialGradient(s.x, s.y, 0, s.x, s.y, s.r * 3);
                        glow.addColorStop(0, `hsl(${s.hue}, 95%, 70%)`);
                        glow.addColorStop(1, `hsla(${s.hue}, 95%, 60%, 0)`);
                        ctx.fillStyle = glow;
                        ctx.beginPath();
                        ctx.arc(s.x, s.y, s.r * 3, 0, Math.PI * 2);
                        ctx.fill();

                        ctx.fillStyle = `hsl(${s.hue}, 90%, 75%)`;
                        ctx.beginPath();
                        ctx.arc(s.x, s.y, s.r * p + 0.4, 0, Math.PI * 2);
                        ctx.fill();
                    }
                    ctx.globalAlpha = 1;
                },
            };
        }

        const scenes = [makeStarsScene(), makeGradientHaloScene(), makeMeteorScene()];
        let currentIndex = 0;

        const slotA = { canvas: canvasA, ctx: ctxA, index: 0 };
        const slotB = { canvas: canvasB, ctx: ctxB, index: null };
        let activeSlot = slotA;
        let standbySlot = slotB;

        galaxyBackground.init();
        scenes[0].init();

        const SCENE_DURATION = 15000;
        const FADE_DURATION = 1600;
        let sceneTimer = SCENE_DURATION;
        let transitioning = false;

        function startTransition() {
            transitioning = true;
            const nextIndex = (currentIndex + 1) % scenes.length;
            standbySlot.index = nextIndex;
            standbySlot.ctx = fitCanvas(standbySlot.canvas);
            scenes[nextIndex].init();

            standbySlot.canvas.classList.add('is-visible');
            activeSlot.canvas.classList.remove('is-visible');

            setTimeout(() => {
                currentIndex = nextIndex;
                const finishedSlot = activeSlot;
                activeSlot = standbySlot;
                standbySlot = finishedSlot;
                standbySlot.index = null;
                transitioning = false;
            }, FADE_DURATION);
        }

        let lastTime = performance.now();
        function loop(now) {
            const dt = Math.min(0.05, (now - lastTime) / 1000);
            lastTime = now;

            sceneTimer -= (now - (loop.prevNow || now));
            loop.prevNow = now;
            if (!transitioning && sceneTimer <= 0) {
                sceneTimer = SCENE_DURATION;
                startTransition();
            }

            const aScene = scenes[slotA.index];
            if (aScene) {
                aScene.update(dt);
                galaxyBackground.draw(slotA.ctx);
                aScene.draw(slotA.ctx);
            }
            const bScene = slotB.index !== null ? scenes[slotB.index] : null;
            if (bScene) {
                bScene.update(dt);
                galaxyBackground.draw(slotB.ctx);
                bScene.draw(slotB.ctx);
            }

            requestAnimationFrame(loop);
        }
        requestAnimationFrame(loop);
    })();
    </script>

</body>
</html>
