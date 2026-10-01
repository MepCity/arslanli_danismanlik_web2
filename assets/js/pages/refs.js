/**
 * Referanslar · Kaşe masası
 * Masaya tıklanan yere sıradaki kurumun kaşesi basılır. İnce imleçlerde ahşap kaşe aleti imleci izler.
 */

const INKS = ['blue', 'red', 'violet', 'navy'];
const INK_VARS = { blue: 'var(--pen)', red: 'var(--red)', violet: '#5b44b0', navy: 'var(--navy)' };

export default function init({ gsap, reduced, fine }) {
  const desk = document.querySelector('[data-desk]');
  const layer = desk?.querySelector('[data-layer]');
  const dataEl = document.querySelector('[data-refs]');
  if (!desk || !layer || !dataEl) return;

  const refs = JSON.parse(dataEl.textContent);
  const n = refs.length;
  const tool = desk.querySelector('[data-tool]');
  const toolLogo = desk.querySelector('[data-tool-logo]');
  const nextName = document.querySelector('[data-next-name]');
  const btnAll = document.querySelector('[data-stamp-all]');
  const btnOne = document.querySelector('[data-stamp-one]');
  const btnClear = document.querySelector('[data-clear]');
  const useTool = fine && !reduced && !!tool;

  const onDesk = new Map();          // slug -> adet
  let placed = [];                   // {x, y} yüzde
  let cursor = 0;                    // sıradaki kurum
  let count = 0;                     // basılan kaşe sayısı (mürekkep sırası)
  let busy = false;

  const rand = (a, b) => a + Math.random() * (b - a);
  const clamp = (v, a, b) => Math.min(b, Math.max(a, v));

  /* ---------- Hazır kaşeler ---------- */

  // Izgara: 'Hepsini bas' kaşeleri bu hücrelere dizer; hazır kaşeler de hücrelere oturur
  const grid = () => {
    const cols = desk.clientWidth > 700 ? 5 : 3;
    const rows = Math.ceil(n / cols);
    const cells = [];
    for (let r = 0; r < rows; r++) {
      for (let c = 0; c < cols; c++) cells.push({ x: ((c + 0.5) / cols) * 100, y: 9 + ((r + 0.5) / rows) * 82 });
    }
    return { cols, rows, cells };
  };

  const presets = [...layer.querySelectorAll('[data-preset]')];
  const g0 = grid();
  const presetCells = g0.cols === 5 ? [1, 3, 7, 13] : [0, 2, 7, 12];
  presets.forEach((el, i) => {
    const c = g0.cells[presetCells[i] ?? i];
    if (c) { el.style.left = (c.x + rand(-2, 2)).toFixed(2) + '%'; el.style.top = (c.y + rand(-2, 2)).toFixed(2) + '%'; }
    const ref = refs[i];
    onDesk.set(ref.slug, 1);
    placed.push({ x: parseFloat(el.style.left), y: parseFloat(el.style.top) });
    el.style.setProperty('--o', rand(0.82, 0.94).toFixed(2));
    if (reduced) el.classList.add('is-on');
    else setTimeout(() => el.classList.add('is-new'), (window.__vt ? 700 : 300) + i * 260);
  });
  cursor = presets.length % n;
  count = presets.length;

  /* ---------- Sıradaki kurum ---------- */

  function nextRef() {
    for (let k = 0; k < n; k++) {
      const ref = refs[(cursor + k) % n];
      if (!onDesk.has(ref.slug)) return { ref, idx: (cursor + k) % n };
    }
    return { ref: refs[cursor % n], idx: cursor % n };
  }

  function updateNext() {
    const { ref } = nextRef();
    if (nextName) nextName.textContent = ref.name;
    if (toolLogo) {
      toolLogo.style.setProperty('--src', `url("${ref.src}")`);
      toolLogo.style.setProperty('--ink', INK_VARS[INKS[count % INKS.length]]);
    }
    if (btnAll) btnAll.disabled = refs.every((r) => onDesk.has(r.slug));
  }
  updateNext();

  /* ---------- Kaşe bas ---------- */

  function stamp(xPct, yPct, ref) {
    if (!ref) {
      const nx = nextRef();
      ref = nx.ref;
      cursor = (nx.idx + 1) % n;
    }
    const ink = INKS[count % INKS.length];
    const el = document.createElement('span');
    el.className = `imp imp--${ink}`;
    el.style.left = clamp(xPct, 7, 93).toFixed(2) + '%';
    el.style.top = clamp(yPct, 9, 91).toFixed(2) + '%';
    el.style.setProperty('--rot', rand(-8, 8).toFixed(1) + 'deg');
    el.style.setProperty('--o', rand(0.78, 0.95).toFixed(2));
    const logo = document.createElement('span');
    logo.className = 'imp__logo';
    logo.style.setProperty('--src', `url("${ref.src}")`);
    el.appendChild(logo);
    layer.appendChild(el);
    if (reduced) el.classList.add('is-on');
    else requestAnimationFrame(() => el.classList.add('is-new'));

    onDesk.set(ref.slug, (onDesk.get(ref.slug) || 0) + 1);
    placed.push({ x: xPct, y: yPct });
    count++;
    desk.classList.add('is-used');
    updateNext();
    return el;
  }

  function pct(e) {
    const r = desk.getBoundingClientRect();
    return { x: ((e.clientX - r.left) / r.width) * 100, y: ((e.clientY - r.top) / r.height) * 100 };
  }

  // Boş bir yer bul: mevcut kaşelere en uzak aday nokta
  function freeSpot() {
    let best = null, bestD = -1;
    for (let k = 0; k < 40; k++) {
      const c = { x: rand(12, 88), y: rand(14, 84) };
      const d = placed.length ? Math.min(...placed.map((p) => Math.hypot(p.x - c.x, (p.y - c.y) * 0.7))) : 100;
      if (d > bestD) { bestD = d; best = c; }
    }
    return best;
  }

  /* ---------- Kaşe aleti ---------- */

  const t = { x: -300, y: -300, tx: -300, ty: -300, tilt: 0, active: false, auto: false };
  let toolH = 150;

  function press() {
    if (!tool) return;
    tool.classList.add('is-down');
    setTimeout(() => tool.classList.remove('is-down'), 150);
  }

  if (useTool) {
    toolH = tool.offsetHeight || 150;
    desk.addEventListener('pointermove', (e) => {
      if (e.pointerType !== 'mouse' || t.auto) return;
      const r = desk.getBoundingClientRect();
      t.tx = e.clientX - r.left;
      t.ty = e.clientY - r.top;
      if (!t.active) { t.x = t.tx; t.y = t.ty; }
      t.active = true;
      desk.classList.add('has-tool');
    });
    desk.addEventListener('pointerleave', () => {
      if (t.auto) return;
      t.active = false;
      desk.classList.remove('has-tool');
    });
    const inner = tool.querySelector('.tool__in');
    gsap.ticker.add(() => {
      if (!t.active && !t.auto) return;
      const px = t.x;
      t.x += (t.tx - t.x) * (t.auto ? 1 : 0.32);
      t.y += (t.ty - t.y) * (t.auto ? 1 : 0.32);
      const vx = t.x - px;
      t.tilt += (clamp(vx * 0.9, -14, 14) - t.tilt) * 0.18;
      tool.style.setProperty('--tx', (t.x - 66).toFixed(1) + 'px');
      tool.style.setProperty('--ty', (t.y - toolH + 4).toFixed(1) + 'px');
      inner.style.setProperty('--tilt', t.tilt.toFixed(2) + 'deg');
    });
  }

  /* ---------- Tıklama ---------- */

  let lastType = 'mouse';
  desk.addEventListener('pointerdown', (e) => {
    lastType = e.pointerType;
    if (busy || e.pointerType !== 'mouse' || e.button !== 0) return;
    const p = pct(e);
    if (useTool) { press(); setTimeout(() => stamp(p.x, p.y), 70); }
    else stamp(p.x, p.y);
  });
  desk.addEventListener('click', (e) => {
    if (busy || lastType === 'mouse') return;
    const p = pct(e);
    stamp(p.x, p.y);
  });

  btnOne?.addEventListener('click', () => {
    if (busy) return;
    const s = freeSpot();
    stamp(s.x, s.y);
  });

  /* ---------- Hepsini bas ---------- */

  btnAll?.addEventListener('click', async () => {
    if (busy) return;
    const rest = refs.filter((r) => !onDesk.has(r.slug));
    if (!rest.length) return;
    busy = true;
    setButtons(true);

    // Düzenli ama elle basılmış gibi bir ızgara
    const cells = grid().cells.map((c) => ({ x: c.x + rand(-2.5, 2.5), y: c.y + rand(-2, 2) }));
    const targets = [];
    for (const ref of rest) {
      if (!cells.length) { targets.push({ ...freeSpot(), ref }); continue; }
      const taken = placed.concat(targets);
      let best = cells[0], bestD = -1, bestI = 0;
      cells.forEach((c, i) => {
        const d = taken.length ? Math.min(...taken.map((p) => Math.hypot(p.x - c.x, (p.y - c.y) * 0.8))) : 100;
        if (d > bestD) { bestD = d; best = c; bestI = i; }
      });
      cells.splice(bestI, 1);
      targets.push({ ...best, ref });
    }

    const r = desk.getBoundingClientRect();
    if (useTool) { t.auto = true; desk.classList.add('has-tool'); if (!t.active) { t.x = t.tx = r.width / 2; t.y = t.ty = r.height + 40; } }
    for (const tg of targets) {
      if (useTool) {
        await new Promise((res) => gsap.to(t, { tx: (tg.x / 100) * r.width, ty: (tg.y / 100) * r.height, duration: 0.3, ease: 'power2.inOut', onComplete: res }));
        press();
        await wait(70);
      }
      stamp(tg.x, tg.y, tg.ref);
      await wait(reduced ? 0 : useTool ? 120 : 150);
    }
    if (useTool) { t.auto = false; if (!t.active) desk.classList.remove('has-tool'); }
    busy = false;
    setButtons(false);
    updateNext();
  });

  /* ---------- Masayı temizle ---------- */

  btnClear?.addEventListener('click', () => {
    if (busy) return;
    const imps = [...layer.querySelectorAll('.imp')];
    imps.forEach((el, i) => {
      if (reduced) { el.remove(); return; }
      setTimeout(() => el.classList.add('is-gone'), i * 30);
      setTimeout(() => el.remove(), 520 + i * 30);
    });
    onDesk.clear();
    placed = [];
    cursor = 0;
    desk.classList.remove('is-used');
    updateNext();
  });

  function setButtons(off) {
    [btnAll, btnOne, btnClear].forEach((b) => { if (b) b.disabled = off; });
  }
}

const wait = (ms) => new Promise((r) => setTimeout(r, ms));
