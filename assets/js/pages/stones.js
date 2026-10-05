/**
 * Mihenk Taşlarımız
 * Taşın üzerinde sürüklenen imleç altın izi bırakır. Bir ilkenin alanı yeterince
 * sürüldüğünde ilke altın rengiyle ortaya çıkar. "Hepsini sür" kalanları kendisi sürer.
 * Dokunmatikte taş yalnızca yatay hareketi yakalar (touch-action: pan-y); dikey hareket sayfayı kaydırır.
 */

const CELL = 18;          // kaplama ızgarasının hücre boyu (px)
const THRESHOLD = 0.28;   // bir ilkenin ortaya çıkması için gereken kaplama oranı

export default function init({ reduced, t: metin }) {
  const stone = document.querySelector('[data-stone]');
  const cv = stone?.querySelector('[data-cv]');
  if (!stone || !cv) return;
  const ctx = cv.getContext('2d');
  const items = [...stone.querySelectorAll('[data-p]')];
  const countEl = document.querySelector('[data-count]');
  const allBtn = document.querySelector('[data-rub-all]');
  const total = items.length;

  let dpr = Math.min(window.devicePixelRatio || 1, 2);
  let W = 0, H = 0, cols = 0, rows = 0;
  let grid = new Uint8Array(0);
  let regions = [];
  let revealed = 0;
  let busy = false;
  const pattern = ctx.createPattern(goldTexture(), 'repeat');

  /* ---------- ölçüler ---------- */

  function layout() {
    const r = stone.getBoundingClientRect();
    const nw = Math.round(r.width), nh = Math.round(r.height);
    if (!nw || !nh) return;
    // mevcut izleri koru
    let keep = null;
    if (W && H) {
      keep = document.createElement('canvas');
      keep.width = cv.width; keep.height = cv.height;
      keep.getContext('2d').drawImage(cv, 0, 0);
    }
    dpr = Math.min(window.devicePixelRatio || 1, 2);
    W = nw; H = nh;
    cv.width = Math.round(W * dpr);
    cv.height = Math.round(H * dpr);
    if (keep) ctx.drawImage(keep, 0, 0, cv.width, cv.height);

    cols = Math.ceil(W / CELL); rows = Math.ceil(H / CELL);
    grid = new Uint8Array(cols * rows);
    regions = items.map((el, i) => {
      const b = el.getBoundingClientRect();
      const x0 = Math.max(0, Math.floor((b.left - r.left) / CELL));
      const y0 = Math.max(0, Math.floor((b.top - r.top) / CELL));
      const x1 = Math.min(cols - 1, Math.floor((b.right - r.left) / CELL));
      const y1 = Math.min(rows - 1, Math.floor((b.bottom - r.top) / CELL));
      const prev = regions[i];
      return {
        el, x0, y0, x1, y1,
        box: { x: b.left - r.left, y: b.top - r.top, w: b.width, h: b.height },
        total: (x1 - x0 + 1) * (y1 - y0 + 1),
        done: prev ? prev.done : el.classList.contains('is-gold'),
      };
    });
  }

  /* ---------- çizim ---------- */

  const rubWidth = () => (W < 600 ? 28 : 36);

  // Bir sürtme izi: yan yana, kenarlara doğru soluklaşan ince çizgiler
  function newLines() {
    const n = 14;
    const ls = Array.from({ length: n }, (_, k) => {
      const t = k / (n - 1) - 0.5;
      return {
        off: t + (Math.random() - 0.5) * 0.06,
        a: Math.max(0.04, 0.34 * (1 - Math.abs(t) * 1.6) * (0.4 + Math.random() * 0.6)),
        w: 0.8 + Math.random() * 2.2,
      };
    });
    // alttaki yumuşak sıvama: çizgilerin arası boş kalmasın
    ls.unshift({ off: 0, a: 0.11, w: rubWidth() * 0.78, base: true });
    return ls;
  }

  function segment(a, b, lines) {
    const dx = b.x - a.x, dy = b.y - a.y;
    const len = Math.hypot(dx, dy);
    if (len < 0.4) return;
    const nx = -dy / len, ny = dx / len;
    const w = rubWidth();
    ctx.save();
    ctx.lineCap = 'butt';
    ctx.strokeStyle = pattern;
    for (const l of lines) {
      const o = l.off * w;
      ctx.globalAlpha = l.a;
      ctx.lineWidth = l.w * dpr;
      ctx.beginPath();
      ctx.moveTo((a.x + nx * o) * dpr, (a.y + ny * o) * dpr);
      ctx.lineTo((b.x + nx * o) * dpr, (b.y + ny * o) * dpr);
      ctx.stroke();
    }
    ctx.restore();
    mark(a, b, len, w / 2);
  }

  /* ---------- kaplama takibi ---------- */

  function mark(a, b, len, rad) {
    const steps = Math.max(1, Math.ceil(len / 6));
    let minX = Infinity, minY = Infinity, maxX = -1, maxY = -1;
    for (let s = 0; s <= steps; s++) {
      const px = a.x + (b.x - a.x) * (s / steps);
      const py = a.y + (b.y - a.y) * (s / steps);
      const cx0 = Math.max(0, Math.floor((px - rad) / CELL)), cx1 = Math.min(cols - 1, Math.floor((px + rad) / CELL));
      const cy0 = Math.max(0, Math.floor((py - rad * 0.6) / CELL)), cy1 = Math.min(rows - 1, Math.floor((py + rad * 0.6) / CELL));
      for (let y = cy0; y <= cy1; y++) for (let x = cx0; x <= cx1; x++) grid[y * cols + x] = 1;
      minX = Math.min(minX, cx0); maxX = Math.max(maxX, cx1);
      minY = Math.min(minY, cy0); maxY = Math.max(maxY, cy1);
    }
    for (const r of regions) {
      if (r.done || r.x1 < minX || r.x0 > maxX || r.y1 < minY || r.y0 > maxY) continue;
      let hit = 0;
      for (let y = r.y0; y <= r.y1; y++) for (let x = r.x0; x <= r.x1; x++) hit += grid[y * cols + x];
      if (hit / r.total >= THRESHOLD) reveal(r);
    }
  }

  function reveal(r) {
    if (r.done) return;
    r.done = true;
    r.el.classList.add('is-gold');
    update();
  }

  function update() {
    revealed = regions.filter((r) => r.done).length;
    if (countEl) {
      if (revealed >= total) {
        countEl.textContent = metin('mihenk.cubuk.tamam');
      } else {
        // Sayı kalın yazılır: metindeki {n} yerine <b> konur (metin düz yazıdır, HTML içermez)
        const [once, sonra = ''] = metin('mihenk.cubuk.sayac', { n: '\u0000', toplam: total }).split('\u0000');
        const b = document.createElement('b');
        b.textContent = revealed;
        countEl.replaceChildren(once, b, sonra);
      }
    }
    if (allBtn) allBtn.disabled = revealed >= total;
  }

  /* ---------- işaretçi ---------- */

  let rubbing = false;
  let last = null;
  let lines = null;
  const local = (e) => {
    const r = stone.getBoundingClientRect();
    return { x: e.clientX - r.left, y: e.clientY - r.top };
  };
  const stop = () => {
    rubbing = false;
    last = null;
    stone.classList.remove('is-rubbing');
  };

  stone.addEventListener('pointerdown', (e) => {
    if (e.pointerType === 'mouse' && e.button !== 0) return;
    if (busy) return;
    rubbing = true;
    last = local(e);
    lines = newLines();
    stone.classList.add('is-rubbing');
    if (e.pointerType === 'mouse') stone.setPointerCapture?.(e.pointerId);
  });
  stone.addEventListener('pointermove', (e) => {
    if (!rubbing) return;
    const p = local(e);
    segment(last, p, lines);
    last = p;
  });
  ['pointerup', 'pointercancel', 'lostpointercapture'].forEach((t) => stone.addEventListener(t, stop));

  /* ---------- otomatik sürtme ---------- */

  // Bir ilkenin alanında ileri geri, uçlarda yavaşlayan (elle sürtme gibi) bir yol
  function zigzag(box) {
    const pts = [];
    const passes = 4;
    const tilt = (Math.random() - 0.5) * 0.12;
    const x0 = box.x - 12, x1 = box.x + box.w + 12;
    const ys = Array.from({ length: passes + 1 }, (_, i) => box.y + box.h * (0.08 + 0.84 * (i / passes)));
    const phase = Math.random() * 6;
    for (let i = 0; i < passes; i++) {
      for (let k = 0; k <= 24; k++) {
        const t = k / 24;
        const e = (1 - Math.cos(Math.PI * t)) / 2;
        const x = i % 2 ? x1 + (x0 - x1) * e : x0 + (x1 - x0) * e;
        const y = ys[i] + (ys[i + 1] - ys[i]) * t + Math.sin(t * 6 + phase + i) * 4 + tilt * (x - box.x);
        pts.push({ x, y });
      }
    }
    return pts;
  }

  function along(pts, t) {
    const segs = [];
    let L = 0;
    for (let i = 1; i < pts.length; i++) { const d = Math.hypot(pts[i].x - pts[i - 1].x, pts[i].y - pts[i - 1].y); segs.push(d); L += d; }
    let target = L * t;
    for (let i = 0; i < segs.length; i++) {
      if (target <= segs[i]) {
        const k = segs[i] ? target / segs[i] : 0;
        return { x: pts[i].x + (pts[i + 1].x - pts[i].x) * k, y: pts[i].y + (pts[i + 1].y - pts[i].y) * k };
      }
      target -= segs[i];
    }
    return pts[pts.length - 1];
  }

  function rubPath(pts, duration) {
    const ln = newLines();
    if (reduced || duration <= 0) {
      for (let i = 1; i < pts.length; i++) segment(pts[i - 1], pts[i], ln);
      return Promise.resolve();
    }
    return new Promise((resolve) => {
      const t0 = performance.now();
      let prev = pts[0];
      const tick = (now) => {
        const t = Math.min(1, (now - t0) / duration);
        const eased = 1 - Math.pow(1 - t, 1.6);
        const p = along(pts, eased);
        segment(prev, p, ln);
        prev = p;
        if (t < 1) requestAnimationFrame(tick); else resolve();
      };
      requestAnimationFrame(tick);
    });
  }

  allBtn?.addEventListener('click', async () => {
    if (busy) return;
    busy = true;
    allBtn.disabled = true;
    for (const r of regions) {
      if (r.done) continue;
      await rubPath(zigzag(r.box), 620);
      reveal(r);
    }
    busy = false;
    update();
  });

  /* ---------- başlangıç ---------- */

  layout();
  if (reduced) {
    // Hareket azaltıldıysa ilkeler baştan okunur
    regions.forEach((r) => { r.done = true; r.el.classList.add('is-gold'); });
  }
  update();

  if ('ResizeObserver' in window) {
    let raf = 0;
    new ResizeObserver(() => { cancelAnimationFrame(raf); raf = requestAnimationFrame(layout); }).observe(stone);
  }
  window.addEventListener('load', layout, { once: true });
  document.fonts?.ready.then(layout);

  // İpucu: taş ilk göründüğünde köşesinde kısa bir altın izi belirir
  if (!reduced && 'IntersectionObserver' in window) {
    const io = new IntersectionObserver(([en]) => {
      if (!en.isIntersecting) return;
      io.disconnect();
      setTimeout(() => {
        if (revealed || rubbing) return;
        const b = regions[0]?.box;
        if (!b) return;
        rubPath([{ x: b.x - 20, y: b.y + b.h + 30 }, { x: b.x + b.w * 0.45, y: b.y + b.h + 18 }], 700);
      }, 700);
    }, { threshold: 0.35 });
    io.observe(stone);
  }
}

/* Altın dokusu: yatay lifli, tanecikli, sıcak sarı */
function goldTexture() {
  const s = 256;
  const c = document.createElement('canvas');
  c.width = c.height = s;
  const g = c.getContext('2d');
  const img = g.createImageData(s, s);
  const rowShade = Array.from({ length: s }, (_, y) => Math.sin(y * 0.9) * 0.18 + Math.sin(y * 0.23 + 1.3) * 0.22);
  for (let y = 0; y < s; y++) {
    for (let x = 0; x < s; x++) {
      const n = (Math.random() - 0.5) * 0.55 + rowShade[y];
      const i = (y * s + x) * 4;
      img.data[i] = clamp(224 + n * 60);
      img.data[i + 1] = clamp(183 + n * 62);
      img.data[i + 2] = clamp(88 + n * 48);
      img.data[i + 3] = 255;
    }
  }
  g.putImageData(img, 0, 0);
  return c;
}
const clamp = (v) => Math.max(0, Math.min(255, Math.round(v)));
