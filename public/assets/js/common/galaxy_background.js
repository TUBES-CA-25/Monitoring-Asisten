/**
 * galaxy_background.js — Latar animasi galaksi Milky Way (bintang + nebula
 * + pita galaksi) yang bisa dipakai ulang di halaman manapun.
 *
 * Cari elemen <canvas id="galaxyBgCanvas"> di halaman - kalau tidak ada,
 * script tidak melakukan apa-apa (aman disertakan di halaman manapun).
 * Canvas mengikuti ukuran boundingClientRect-nya SENDIRI (bukan selalu
 * window penuh) supaya bisa dipakai baik untuk latar satu halaman penuh
 * (mis. scan.php) maupun untuk panel/section tertentu saja (mis. qr_page.php).
 */
(() => {
  "use strict";

  const canvas = document.getElementById("galaxyBgCanvas");
  if (!canvas) return;

  const ctx = canvas.getContext("2d");
  const DPR = Math.min(window.devicePixelRatio || 1, 2);
  let W = 0, H = 0;
  let stars = [];
  let nebulae = [];

  function rand(min, max) { return Math.random() * (max - min) + min; }

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
      { x: W * 0.18, y: H * 0.28, r: Math.max(W, H) * 0.35, hue: "124,58,237" },
      { x: W * 0.82, y: H * 0.68, r: Math.max(W, H) * 0.32, hue: "14,165,233" },
      { x: W * 0.55, y: H * 0.18, r: Math.max(W, H) * 0.26, hue: "236,72,153" },
    ];
  }

  function fit() {
    const rect = canvas.getBoundingClientRect();
    W = Math.max(1, Math.round(rect.width));
    H = Math.max(1, Math.round(rect.height));
    canvas.width = W * DPR;
    canvas.height = H * DPR;
    ctx.setTransform(DPR, 0, 0, DPR, 0, 0);
    seed();
  }

  // Pita galaksi (Milky Way) diagonal - gradient lebar diputar sedikit
  // miring, memberi kesan tekstur galaksi di balik bintang-bintang.
  function drawMilkyWay() {
    ctx.save();
    ctx.translate(W * 0.5, H * 0.5);
    ctx.rotate(-0.4);
    const len = Math.max(W, H) * 1.7;
    const grad = ctx.createLinearGradient(0, -170, 0, 170);
    grad.addColorStop(0, "rgba(199,210,254,0)");
    grad.addColorStop(0.5, "rgba(199,210,254,0.12)");
    grad.addColorStop(1, "rgba(199,210,254,0)");
    ctx.fillStyle = grad;
    ctx.fillRect(-len / 2, -170, len, 340);
    ctx.restore();
  }

  function draw() {
    ctx.fillStyle = "#050714";
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

    drawMilkyWay();

    const t = performance.now() / 1000;
    for (const st of stars) {
      const o = (0.25 + 0.55 * Math.abs(Math.sin(t * st.speed + st.phase))) * (st.bright ? 1 : 0.6);
      ctx.globalAlpha = o;
      ctx.fillStyle = st.bright ? "#c7d2fe" : "#e2e8f0";
      ctx.beginPath();
      ctx.arc(st.x, st.y, st.r, 0, Math.PI * 2);
      ctx.fill();
    }
    ctx.globalAlpha = 1;
  }

  function loop() {
    draw();
    requestAnimationFrame(loop);
  }

  let resizeTimer = null;
  window.addEventListener("resize", () => {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(fit, 150);
  });

  fit();
  requestAnimationFrame(loop);
})();
