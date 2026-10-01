/**
 * Ana sayfa
 * 1) Mercek: büyüteç kanun metninin üzerinde gezer, altında sade Türkçesi görünür.
 * 2) Takvim: kaydırdıkça yapraklar koparılır.
 * 3) Kaşeler: logolar sırayla basılır.
 */

export default function init({ gsap, ScrollTrigger, reduced, fine }) {
  mercek(gsap, reduced, fine);
  takvim(gsap, ScrollTrigger, reduced);
  kaseler(gsap, ScrollTrigger, reduced);
  dosyalar(gsap, ScrollTrigger, reduced);
}

/* ---------------------------------------------------------------- Mercek */

function mercek(gsap, reduced, fine) {
  const root = document.querySelector('[data-mercek]');
  const stage = root?.querySelector('[data-stage]');
  const lens = root?.querySelector('[data-lens]');
  if (!root || !stage || !lens) return;

  const sheet = root.querySelector('[data-ustyazi]');
  const plainBtn = root.querySelector('[data-plain-all]');
  const blocks = [...root.querySelectorAll('.wall--plain .mb')];

  const pos = { x: 0, y: 0 };
  const target = { x: 0, y: 0 };
  let mode = 'wander';      // wander | follow | hold
  let lastInput = 0;
  let stopIndex = -1;
  let stopUntil = 0;
  let visible = true;
  let plain = false;

  const set = () => {
    root.style.setProperty('--x', pos.x.toFixed(1) + 'px');
    root.style.setProperty('--y', pos.y.toFixed(1) + 'px');
  };

  // Görünür (üst yazının altında kalmayan) paragrafların sade metin noktaları
  function stops() {
    const s = stage.getBoundingClientRect();
    const cover = sheet?.getBoundingClientRect();
    const r = lens.offsetWidth / 2;
    return blocks
      .filter((b) => b.offsetParent !== null)
      .map((b) => {
        const p = b.querySelector('.mb__plain').getBoundingClientRect();
        const x = p.left - s.left + p.width / 2;
        const y = p.top - s.top + p.height / 2;
        return { x, y, abs: { x: x + s.left, y: y + s.top } };
      })
      .filter((pt) => {
        if (!cover || getComputedStyle(sheet.parentElement).position !== 'absolute') return true;
        return !(pt.abs.x > cover.left - 40 && pt.abs.x < cover.right + 40 && pt.abs.y > cover.top - 40 && pt.abs.y < cover.bottom + 40);
      });
  }

  function nextStop(now) {
    const list = stops();
    if (!list.length) return;
    stopIndex = (stopIndex + 1) % list.length;
    target.x = list[stopIndex].x;
    target.y = list[stopIndex].y;
    stopUntil = now + (reduced ? 1e9 : 2600);
  }

  // Başlangıç: büyüteç sağ alttan girer
  const sr = stage.getBoundingClientRect();
  pos.x = sr.width + 220;
  pos.y = sr.height * 0.8;
  set();
  nextStop(performance.now());
  if (reduced) { pos.x = target.x; pos.y = target.y; set(); }

  if (fine) {
    root.addEventListener('pointermove', (e) => {
      if (e.pointerType !== 'mouse' || plain) return;
      const s = stage.getBoundingClientRect();
      target.x = e.clientX - s.left;
      target.y = e.clientY - s.top;
      mode = 'follow';
      lastInput = performance.now();
    });
  }
  // Dokunmatik ve tıklama: paragrafa dokununca mercek oraya gider
  stage.addEventListener('pointerdown', (e) => {
    if (e.pointerType === 'mouse' || plain) return;
    const s = stage.getBoundingClientRect();
    target.x = e.clientX - s.left;
    target.y = e.clientY - s.top;
    mode = 'hold';
    lastInput = performance.now();
  });

  new IntersectionObserver(([en]) => (visible = en.isIntersecting)).observe(root);

  gsap.ticker.add(() => {
    if (!visible || plain) return;
    const now = performance.now();
    if (mode !== 'wander' && now - lastInput > (mode === 'hold' ? 6000 : 3200)) {
      mode = 'wander';
      stopUntil = 0;
    }
    if (mode === 'wander' && now > stopUntil) nextStop(now);
    const k = mode === 'follow' ? 0.16 : 0.055;
    const dx = (target.x - pos.x) * k;
    const dy = (target.y - pos.y) * k;
    if (Math.abs(dx) < 0.05 && Math.abs(dy) < 0.05) return; // yerinde: boyama yapma
    pos.x += dx;
    pos.y += dy;
    set();
  });

  window.addEventListener('resize', () => { stopUntil = 0; });

  // Tümünü sadeleştir: mercek bütün sayfayı kaplayana kadar büyür
  const rState = { r: 0 };
  plainBtn?.addEventListener('click', () => {
    plain = !plain;
    plainBtn.setAttribute('aria-pressed', String(plain));
    plainBtn.textContent = plain ? 'Kanun metnine dön' : 'Tümünü sadeleştir';
    const s = stage.getBoundingClientRect();
    const r0 = lens.offsetWidth / 2;
    const far = Math.hypot(Math.max(pos.x, s.width - pos.x), Math.max(pos.y, s.height - pos.y)) + 20;
    root.classList.toggle('is-plain', plain);
    if (reduced) {
      if (plain) root.style.setProperty('--r', far + 'px'); else root.style.removeProperty('--r');
      return;
    }
    gsap.killTweensOf(rState);
    rState.r = plain ? r0 : far;
    gsap.to(rState, {
      r: plain ? far : r0,
      duration: plain ? 1.1 : 0.8,
      ease: plain ? 'expo.inOut' : 'expo.out',
      onUpdate: () => root.style.setProperty('--r', rState.r + 'px'),
      onComplete: () => { if (!plain) root.style.removeProperty('--r'); },
    });
  });

  // Üst yazı masaya konur
  if (!reduced && sheet) {
    const delay = window.__vt ? 0.5 : 0.1;
    gsap.from(sheet, { y: 90, rotation: -4.5, opacity: 0, duration: 1.3, ease: 'expo.out', delay });
    gsap.from(sheet.querySelectorAll('.ustyazi__h .ln'), { yPercent: 60, opacity: 0, duration: 1.1, ease: 'expo.out', stagger: 0.09, delay: delay + 0.25 });
    gsap.from(root.querySelectorAll('.wall--law .mb'), { opacity: 0, duration: 1.4, ease: 'power2.out', stagger: 0.07, delay });
  }
}

/* ---------------------------------------------------------------- Takvim */

function takvim(gsap, ScrollTrigger, reduced) {
  const root = document.querySelector('[data-takvim]');
  if (!root) return;
  const leaves = [...root.querySelectorAll('[data-leaf]')];
  const toc = [...root.querySelectorAll('[data-toc]')];
  const n = leaves.length;

  const mark = (idx) => toc.forEach((li, i) => {
    li.classList.toggle('is-cur', i === idx);
    li.classList.toggle('is-done', i < idx);
  });
  mark(0);

  if (reduced || !ScrollTrigger) { root.classList.add('is-static'); return; }

  const tl = gsap.timeline({ defaults: { ease: 'none' } });
  leaves.slice(0, n - 1).forEach((leaf, i) => {
    const dir = i % 2 ? -1 : 1;
    tl.addLabel('y' + i);
    tl.to(leaf, { rotation: dir * 3.5, y: 10, duration: 0.28, ease: 'power1.out' })
      .to(leaf, {
        rotation: dir * (22 + (i % 3) * 7),
        rotationX: 38,
        x: dir * 90,
        y: '128%',
        opacity: 0,
        duration: 0.72,
        ease: 'power2.in',
      });
  });
  tl.addLabel('y' + (n - 1));

  ScrollTrigger.create({
    trigger: root,
    pin: root.querySelector('[data-takvim-pin]'),
    start: 'top top',
    end: () => '+=' + (n - 1) * window.innerHeight * 0.7,
    scrub: 0.6,
    animation: tl,
    snap: { snapTo: 'labelsDirectional', duration: { min: 0.2, max: 0.6 }, delay: 0.08, ease: 'power1.inOut' },
    onUpdate: (self) => mark(Math.min(n - 1, Math.round(self.progress * (n - 1)))),
    invalidateOnRefresh: true,
  });
}

/* ---------------------------------------------------------------- Kaşeler */

function kaseler(gsap, ScrollTrigger, reduced) {
  const logos = gsap.utils.shuffle([...document.querySelectorAll('.kaseler__grid .inklogo')]);
  if (!logos.length || reduced || !ScrollTrigger) return;
  gsap.set(logos, { opacity: 0, scale: 1.5, rotation: () => gsap.utils.random(-14, 14) });
  ScrollTrigger.create({
    trigger: '.kaseler__grid',
    start: 'top 78%',
    once: true,
    onEnter: () => gsap.to(logos, { opacity: 1, scale: 1, rotation: () => gsap.utils.random(-3, 3), duration: 0.42, ease: 'back.out(2.2)', stagger: 0.07 }),
  });
}

/* ---------------------------------------------------------------- Dosyalar */

function dosyalar(gsap, ScrollTrigger, reduced) {
  const tabs = document.querySelectorAll('.drow__tab');
  if (!tabs.length || reduced || !ScrollTrigger) return;
  gsap.set(tabs, { y: 26 });
  ScrollTrigger.create({
    trigger: '.dlist',
    start: 'top 80%',
    once: true,
    onEnter: () => gsap.to(tabs, { y: 0, duration: 0.8, ease: 'expo.out', stagger: 0.05 }),
  });
}
