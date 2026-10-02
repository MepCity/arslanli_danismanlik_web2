/**
 * Arslanlı v3 · ortak davranışlar
 * Yumuşak kaydırma, üst bilgi, fihrist, canlı logo dalgası, görünüm tetikleyicileri,
 * formlar, kopyalama. Sayfaya özel betik varsa en sonda yüklenir.
 */

const gsap = window.gsap;
const ScrollTrigger = window.ScrollTrigger;
const html = document.documentElement;
const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const fine = window.matchMedia('(hover: hover) and (pointer: fine)').matches;

clearTimeout(window.__revealFallback);
if (gsap && ScrollTrigger) gsap.registerPlugin(ScrollTrigger);

/* ---------- Yumuşak kaydırma ---------- */

let lenis = null;
if (!reduced && window.Lenis && gsap) {
  lenis = new window.Lenis({ duration: 1.1, smoothWheel: true, wheelMultiplier: 0.95 });
  lenis.on('scroll', ScrollTrigger.update);
  gsap.ticker.add((t) => lenis.raf(t * 1000));
  gsap.ticker.lagSmoothing(0);
}

/* ---------- Bildirim ---------- */

const toastEl = document.querySelector('[data-toast]');
let toastTimer;
export function toast(msg) {
  if (!toastEl) return;
  toastEl.textContent = msg;
  toastEl.classList.add('is-on');
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => toastEl.classList.remove('is-on'), 2600);
}

/* ---------- Üst bilgi ---------- */

const hdr = document.querySelector('[data-hdr]');
let menuOpen = false;
let lastY = window.scrollY;
let velocity = 0;
function onScroll() {
  const y = window.scrollY;
  const dy = y - lastY;
  velocity = velocity * 0.6 + dy * 0.4;
  if (hdr) {
    hdr.classList.toggle('is-solid', y > 30);
    if (!menuOpen) hdr.classList.toggle('is-hidden', dy > 4 && y > 240);
    if (dy < -4) hdr.classList.remove('is-hidden');
  }
  lastY = y;
}
window.addEventListener('scroll', onScroll, { passive: true });
onScroll();

/* ---------- Fihrist ---------- */

const menu = document.querySelector('[data-menu]');
const openBtn = document.querySelector('[data-menu-open]');
let lastFocus = null;

function setMenu(open) {
  if (!menu || open === menuOpen) return;
  menuOpen = open;
  menu.classList.toggle('is-open', open);
  openBtn?.setAttribute('aria-expanded', String(open));
  document.querySelectorAll('main, footer, .wa').forEach((el) => (el.inert = open));
  if (open) {
    lastFocus = document.activeElement;
    lenis?.stop();
    hdr?.classList.remove('is-hidden');
    setTimeout(() => menu.querySelector('[data-menu-close]')?.focus(), 60);
    if (gsap && !reduced) {
      const rows = menu.querySelectorAll('.idx li, .fih__h, .fih__foot');
      gsap.fromTo(rows, { y: 26, opacity: 0 }, { y: 0, opacity: 1, duration: 0.7, ease: 'expo.out', stagger: 0.025, delay: 0.12 });
      gsap.fromTo(menu.querySelectorAll('.idx .dots'), { scaleX: 0 }, { scaleX: 1, duration: 0.9, ease: 'expo.out', stagger: 0.025, delay: 0.25 });
    }
  } else {
    lenis?.start();
    lastFocus?.focus?.();
  }
}
openBtn?.addEventListener('click', () => setMenu(true));
menu?.querySelector('[data-menu-close]')?.addEventListener('click', () => setMenu(false));
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape' && menuOpen) setMenu(false);
  if (e.key === 'Tab' && menuOpen && menu) {
    const f = [...menu.querySelectorAll('a, button')].filter((el) => el.offsetParent !== null);
    if (!f.length) return;
    const first = f[0], last = f[f.length - 1];
    if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
    else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
  }
});

/* ---------- Bülten kaydı: kenardaki ayraç formu açar ---------- */

const nl = document.getElementById('bulten');
if (nl && typeof nl.showModal === 'function') {
  const body = nl.querySelector('[data-nl-body]');
  const done = nl.querySelector('[data-nl-done]');
  const form = nl.querySelector('[data-nl-form]');
  let opener = null;

  const openNl = (e) => {
    e?.preventDefault();
    if (nl.open) return;
    opener = document.activeElement;
    if (menuOpen) setMenu(false);
    nl.classList.remove('is-closing');
    nl.showModal();
    lenis?.stop();
    html.style.overflow = 'hidden';
    if (!done || done.hidden) setTimeout(() => nl.querySelector('input:not([type="hidden"]):not([tabindex="-1"])')?.focus({ preventScroll: true }), 80);
  };
  const closeNl = () => {
    if (!nl.open || nl.classList.contains('is-closing')) return;
    if (reduced) { nl.close(); return; }
    nl.classList.add('is-closing');
    setTimeout(() => { nl.close(); nl.classList.remove('is-closing'); }, 320);
  };

  document.addEventListener('click', (e) => {
    const trigger = e.target.closest('[data-nl-open]');
    if (trigger) openNl(e);
  });
  nl.addEventListener('click', (e) => {
    if (e.target.closest('[data-nl-close]') || e.target === nl) closeNl();
  });
  nl.addEventListener('cancel', (e) => { e.preventDefault(); closeNl(); });
  nl.addEventListener('close', () => {
    lenis?.start();
    html.style.overflow = '';
    opener?.focus?.({ preventScroll: true });
  });
  form?.addEventListener('form:ok', () => {
    nl.classList.add('is-done');
    setTimeout(() => {
      nl.classList.add('is-swapped');
      if (body) body.hidden = true;
      if (done) { done.hidden = false; done.focus(); }
      nl.scrollTo({ top: 0 });
    }, reduced ? 0 : 750);
  });
}

/* ---------- Canlı logo: arkadaki ses dalgası kaydırma hızına göre titrer ---------- */

if (gsap && !reduced) {
  const groups = [...document.querySelectorAll('.logo__wave')].map((g) => ({ g, bars: [...g.children] }));
  const seeds = groups[0]?.bars.map((_, i) => 0.6 + ((i * 73) % 17) / 17) ?? [];
  let energy = 0;
  gsap.ticker.add((time) => {
    const v = Math.min(Math.abs(lenis ? lenis.velocity : velocity), 60);
    energy += (v / 60 - energy) * 0.08;
    const amp = 0.05 + energy * 0.55;
    for (const { g, bars } of groups) {
      if (!g.isConnected || (g.closest('.fih') && !menuOpen)) continue;
      for (let i = 0; i < bars.length; i++) {
        const s = 1 + amp * Math.sin(time * (2.2 + seeds[i]) + i * 0.7) * seeds[i];
        bars[i].style.setProperty('--s', s.toFixed(3));
      }
    }
  });
}

/* ---------- Görünüm tetikleyicileri ---------- */

const watch = '[data-rise], [data-stamp], .annot, [data-hl], [data-on]';
if ('IntersectionObserver' in window && !reduced) {
  const io = new IntersectionObserver((entries) => {
    for (const en of entries) {
      if (!en.isIntersecting) continue;
      en.target.classList.add('is-on');
      en.target.dispatchEvent(new CustomEvent('on'));
      io.unobserve(en.target);
    }
  }, { rootMargin: '0px 0px -12% 0px', threshold: 0.01 });
  document.querySelectorAll(watch).forEach((el) => io.observe(el));
  window.__observe = (el) => io.observe(el);
} else {
  document.querySelectorAll(watch).forEach((el) => el.classList.add('is-on'));
  window.__observe = (el) => el.classList.add('is-on');
}

/* ---------- Formlar ---------- */

document.querySelectorAll('form[data-form]').forEach((form) => {
  const status = form.querySelector('.form-status');
  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (form.dataset.busy) return;
    form.querySelectorAll('.field').forEach((f) => f.classList.remove('has-error'));
    form.querySelectorAll('.field__err').forEach((p) => (p.textContent = ''));
    if (!form.checkValidity()) {
      const bad = [...form.elements].filter((el) => el.willValidate && !el.checkValidity());
      bad.forEach((el) => {
        const f = el.closest('.field');
        f?.classList.add('has-error');
        const err = f?.querySelector('.field__err');
        if (err) err.textContent = el.validationMessage;
      });
      bad[0]?.focus();
      if (status) { status.textContent = 'Lütfen işaretli alanları kontrol edin.'; status.className = 'form-status is-error'; }
      return;
    }
    form.dataset.busy = '1';
    form.classList.add('is-busy');
    if (status) { status.textContent = 'Gönderiliyor…'; status.className = 'form-status'; }
    try {
      const res = await fetch(form.action, { method: 'POST', body: new FormData(form), headers: { Accept: 'application/json' } });
      const data = await res.json().catch(() => ({ ok: false, message: 'Beklenmeyen bir yanıt alındı.' }));
      if (data.ok) {
        if (status) { status.textContent = data.message; status.className = 'form-status is-ok'; }
        form.dispatchEvent(new CustomEvent('form:ok', { detail: data }));
      } else {
        if (status) { status.textContent = data.message || 'Gönderilemedi.'; status.className = 'form-status is-error'; }
        Object.entries(data.errors || {}).forEach(([name, msg]) => {
          const el = form.elements[name] || form.elements[name + '[]'];
          const f = (el?.length && !el.tagName ? el[0] : el)?.closest('.field');
          f?.classList.add('has-error');
          const err = f?.querySelector('.field__err');
          if (err) err.textContent = msg;
        });
        form.dispatchEvent(new CustomEvent('form:error', { detail: data }));
      }
    } catch {
      if (status) { status.textContent = 'Bağlantı kurulamadı. Lütfen telefonla ulaşın.'; status.className = 'form-status is-error'; }
    } finally {
      delete form.dataset.busy;
      form.classList.remove('is-busy');
    }
  });
});

// JavaScript olmadan gönderilen formdan dönüş
const durum = new URLSearchParams(location.search).get('durum');
if (durum) toast(durum === 'tamam' ? 'Mesajınız bize ulaştı. Teşekkürler.' : 'Gönderilemedi. Lütfen telefonla ulaşın.');

/* ---------- Kopyala ---------- */

document.addEventListener('click', async (e) => {
  const btn = e.target.closest('[data-copy]');
  if (!btn) return;
  const text = btn.dataset.copy;
  try {
    await navigator.clipboard.writeText(text);
    toast(btn.dataset.copyMsg || 'Kopyalandı');
    btn.classList.add('is-copied');
    setTimeout(() => btn.classList.remove('is-copied'), 1800);
  } catch {
    toast(text);
  }
});

/* ---------- Sayfa betiği ---------- */

const ctx = { gsap, ScrollTrigger, lenis, reduced, fine, toast };
window.__ctx = ctx;

const pageScript = document.querySelector('script[data-page-script]')?.dataset.pageScript;
async function boot() {
  if (pageScript) {
    try {
      const mod = await import(pageScript);
      await mod.default?.(ctx);
    } catch (err) {
      console.error('[arslanli] sayfa betiği yüklenemedi', err);
      html.classList.add('reveal-fallback');
    }
  }
  document.fonts?.ready.then(() => ScrollTrigger?.refresh());
}
boot();
