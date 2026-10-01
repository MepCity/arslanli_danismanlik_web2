/**
 * Hakkımızda
 * 1) Arşiv rafı: klasör sırtları sekme gibi çalışır; seçilen klasör raftan çekilir, içeriği altta açılır.
 * 2) Sicil kaydı: form alanları daktiloyla doldurulur.
 */

export default function init({ gsap, ScrollTrigger, reduced, fine }) {
  heading(gsap, reduced);
  shelf(gsap, ScrollTrigger, reduced, fine);
  typed(gsap, ScrollTrigger, reduced);
}

function heading(gsap, reduced) {
  if (reduced || !gsap) return;
  const delay = window.__vt ? 0.45 : 0.05;
  gsap.from('.ab-head__h .ln', { yPercent: 45, opacity: 0, duration: 1.1, ease: 'expo.out', stagger: 0.08, delay });
}

/* ---------------------------------------------------------------- Raf */

function shelf(gsap, ScrollTrigger, reduced, fine) {
  const root = document.querySelector('[data-raf]');
  if (!root) return;
  const row = root.querySelector('[data-tabs]');
  const scroller = root.querySelector('[data-shelf-scroll]');
  const spines = [...root.querySelectorAll('[data-spine]')];
  const panels = [...root.querySelectorAll('[data-panel]')];
  const wrap = root.querySelector('[data-panels]');
  if (!spines.length || spines.length !== panels.length) return;

  // Sekme düzeni (JS yokken tüm klasör içerikleri liste olarak görünür)
  row.setAttribute('role', 'tablist');
  spines.forEach((s) => s.setAttribute('role', 'tab'));
  panels.forEach((p) => { p.setAttribute('role', 'tabpanel'); p.tabIndex = 0; });

  let current = Math.max(0, spines.findIndex((s) => s.classList.contains('is-out')));

  function place(panel, spine) {
    if (window.matchMedia('(max-width: 760px)').matches) { panel.style.removeProperty('--px'); return; }
    const w = wrap.getBoundingClientRect();
    const s = spine.getBoundingClientRect();
    const pw = panel.offsetWidth;
    const want = s.left + s.width / 2 - w.left - Math.min(90, pw * 0.2);
    const px = Math.max(0, Math.min(w.width - pw, want));
    panel.style.setProperty('--px', px.toFixed(0) + 'px');
  }

  function select(i, { focus = false, animate = true } = {}) {
    const prev = current;
    current = i;
    spines.forEach((s, k) => {
      const on = k === i;
      s.classList.toggle('is-out', on);
      s.setAttribute('aria-selected', String(on));
      s.tabIndex = on ? 0 : -1;
    });
    panels.forEach((p, k) => { p.hidden = k !== i; });
    const panel = panels[i];
    // önceki konumdan kayarak gelsin
    if (prev !== i && panels[prev]) panel.style.setProperty('--px', panels[prev].style.getPropertyValue('--px') || '0px');
    requestAnimationFrame(() => place(panel, spines[i]));
    if (focus) spines[i].focus({ preventScroll: true });
    // mobilde seçilen klasör rafın ortasına gelsin
    if (scroller && scroller.scrollWidth > scroller.clientWidth + 4) {
      const s = spines[i];
      const left = s.offsetLeft - (scroller.clientWidth - s.offsetWidth) / 2;
      scroller.scrollTo({ left, behavior: reduced ? 'auto' : 'smooth' });
    }
    if (animate && !reduced && gsap && prev !== i) {
      gsap.fromTo(panel.children, { y: 14, opacity: 0 }, { y: 0, opacity: 1, duration: 0.6, ease: 'expo.out', stagger: 0.045, overwrite: true });
    }
  }

  select(current, { animate: false });

  let hoverTimer;
  spines.forEach((s, i) => {
    s.addEventListener('click', () => select(i));
    if (fine) {
      s.addEventListener('pointerenter', (e) => {
        if (e.pointerType !== 'mouse') return;
        clearTimeout(hoverTimer);
        hoverTimer = setTimeout(() => select(i), 140);
      });
      s.addEventListener('pointerleave', () => clearTimeout(hoverTimer));
    }
    s.addEventListener('keydown', (e) => {
      let n = null;
      if (e.key === 'ArrowRight' || e.key === 'ArrowDown') n = (i + 1) % spines.length;
      if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') n = (i - 1 + spines.length) % spines.length;
      if (e.key === 'Home') n = 0;
      if (e.key === 'End') n = spines.length - 1;
      if (n === null) return;
      e.preventDefault();
      select(n, { focus: true });
    });
  });

  window.addEventListener('resize', () => place(panels[current], spines[current]));

  // Klasörler rafa tek tek yerleştirilir
  if (!reduced && gsap && ScrollTrigger) {
    gsap.set(spines, { y: -150, opacity: 0, rotation: () => gsap.utils.random(-7, 7) });
    ScrollTrigger.create({
      trigger: root.querySelector('[data-shelf]'),
      start: 'top 82%',
      once: true,
      onEnter: () => gsap.to(spines, {
        y: 0,
        opacity: 1,
        rotation: 0,
        duration: 0.75,
        ease: 'back.out(1.25)',
        stagger: 0.055,
        clearProps: 'transform,opacity',
      }),
    });
  }
}

/* ---------------------------------------------------------------- Daktilo */

function typed(gsap, ScrollTrigger, reduced) {
  const card = document.querySelector('[data-typed]');
  if (!card || reduced || !gsap || !ScrollTrigger) return;
  const chars = [];
  card.querySelectorAll('[data-type]').forEach((el) => {
    const text = el.textContent;
    el.textContent = '';
    text.split(/(\s+)/).forEach((part) => {
      if (!part) return;
      if (/^\s+$/.test(part)) { el.appendChild(document.createTextNode(part)); return; }
      const w = document.createElement('span');
      w.className = 'w';
      for (const c of part) {
        const s = document.createElement('span');
        s.className = 'ch';
        s.textContent = c;
        w.appendChild(s);
        chars.push(s);
      }
      el.appendChild(w);
    });
  });
  gsap.set(chars, { opacity: 0 });
  ScrollTrigger.create({
    trigger: card,
    start: 'top 75%',
    once: true,
    onEnter: () => gsap.to(chars, { opacity: 1, duration: 0.01, stagger: 0.011, ease: 'none' }),
  });
}
