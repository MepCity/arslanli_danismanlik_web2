/**
 * Hizmet detayı · Açık dosya
 * Ek sekmeleri, mevzuat kartı, evrak listesi (tarayıcıda saklanır), yazdırma, kapak açılışı.
 */

export default function init({ gsap, lenis, reduced, toast, t }) {
  ekler(lenis, reduced);
  mevzuat(t);
  evrak(toast, t);
  if (!reduced && gsap) kapak(gsap);
}

/* ---------- Ek sekmeleri ---------- */

function ekler(lenis, reduced) {
  const links = [...document.querySelectorAll('[data-ek-link]')];
  const sections = [...document.querySelectorAll('[data-ek]')];
  if (!links.length) return;

  const set = (id) => links.forEach((a) => {
    if (a.dataset.ekLink === id) a.setAttribute('aria-current', 'true');
    else a.removeAttribute('aria-current');
  });
  set(sections[0]?.id);

  if ('IntersectionObserver' in window) {
    const io = new IntersectionObserver((entries) => {
      entries.forEach((en) => { if (en.isIntersecting) set(en.target.id); });
    }, { rootMargin: '-35% 0px -60% 0px' });
    sections.forEach((s) => io.observe(s));
  }

  links.forEach((a) => a.addEventListener('click', (e) => {
    const target = document.getElementById(a.dataset.ekLink);
    if (!target) return;
    e.preventDefault();
    if (lenis) lenis.scrollTo(target, { offset: -90, duration: 1.1 });
    else target.scrollIntoView({ behavior: reduced ? 'auto' : 'smooth' });
    history.replaceState(null, '', '#' + target.id);
    set(target.id);
  }));
}

/* ---------- Mevzuat kartı ---------- */

function mevzuat(t) {
  const box = document.querySelector('[data-mevzuat]');
  const btn = box?.querySelector('[data-mevzuat-btn]');
  if (!box || !btn) return;
  const law = box.querySelector('.mevzuat__face--law');
  const plain = box.querySelector('.mevzuat__face--plain');
  const sync = (on) => {
    box.classList.toggle('is-plain', on);
    btn.setAttribute('aria-pressed', String(on));
    btn.textContent = on ? t('hizmet.mevzuat.dugme_kanun') : t('hizmet.mevzuat.dugme_sade');
    law.setAttribute('aria-hidden', String(on));
    plain.setAttribute('aria-hidden', String(!on));
  };
  sync(false);
  btn.addEventListener('click', () => sync(!box.classList.contains('is-plain')));
}

/* ---------- Evrak listesi ---------- */

function evrak(toast, t) {
  const box = document.querySelector('[data-evrak]');
  if (!box) return;
  const key = 'arslanli:evrak:' + box.dataset.key;
  const boxes = [...box.querySelectorAll('[data-doc]')];
  const countEl = box.querySelector('[data-evrak-count] span');

  const read = () => {
    try { return JSON.parse(localStorage.getItem(key) || '[]'); } catch { return []; }
  };
  const write = (arr) => {
    try { localStorage.setItem(key, JSON.stringify(arr)); } catch { /* gizli pencere: saklanmaz */ }
  };

  const saved = read();
  boxes.forEach((cb) => { cb.checked = saved.includes(+cb.dataset.doc); });

  let wasFull = boxes.length > 0 && boxes.every((cb) => cb.checked);
  const update = (animate) => {
    const on = boxes.filter((cb) => cb.checked).map((cb) => +cb.dataset.doc);
    if (countEl) countEl.textContent = on.length;
    const full = on.length === boxes.length && boxes.length > 0;
    if (full && (!wasFull || !animate)) {
      box.classList.remove('is-full');
      void box.offsetWidth; // damgayı yeniden bas
      box.classList.add('is-full');
      if (animate) toast?.(t('hizmet.evrak.tamam'));
    }
    if (!full) box.classList.remove('is-full');
    wasFull = full;
    return on;
  };
  update(false);

  boxes.forEach((cb) => cb.addEventListener('change', () => write(update(true))));

  box.querySelector('[data-evrak-reset]')?.addEventListener('click', () => {
    boxes.forEach((cb) => { cb.checked = false; });
    write(update(true));
  });

  box.querySelector('[data-evrak-print]')?.addEventListener('click', () => {
    const html = document.documentElement;
    html.classList.add('print-evrak');
    const done = () => { html.classList.remove('print-evrak'); window.removeEventListener('afterprint', done); };
    window.addEventListener('afterprint', done);
    window.print();
    setTimeout(done, 1500);
  });
}

/* ---------- Kapak açılışı ---------- */

function kapak(gsap) {
  const board = document.querySelector('[data-kapak]');
  if (!board) return;
  const delay = window.__vt ? 0.45 : 0.05;
  const tl = gsap.timeline({ delay, defaults: { ease: 'expo.out' } });
  tl.from(board, { y: 60, rotation: 0.8, opacity: 0, duration: 1.1 })
    .from(board.querySelector('.kapak__tab'), { y: 30, duration: 0.8 }, 0.25)
    .from(board.querySelector('.kapak__tel'), { scaleY: 0.4, opacity: 0, transformOrigin: '50% 50%', duration: 0.9 }, 0.3)
    .from(board.querySelector('[data-label]'), { scale: 1.12, rotation: -5, opacity: 0, duration: 0.55, ease: 'back.out(1.8)' }, 0.45)
    .from(board.querySelectorAll('.docmeta, .kapak__lead, .kapak__act'), { y: 18, opacity: 0, duration: 0.9, stagger: 0.08 }, 0.55);
}
