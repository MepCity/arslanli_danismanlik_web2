/**
 * Hizmetler · Dosya dolabı
 * Masaüstünde askılı dosyalar çekmecede durur; kaydırdıkça öndeki dosya öne düşer, arkadaki görünür.
 * "Ne yapmak istiyorsunuz?" seçimi ilgili dosyaların sırt etiketlerini kaldırır, diğerlerini soldurur.
 */

const GAP_Z = 74;    // dosyalar arası derinlik (px)
const SPAN = 0.55;   // dosya başına kaydırma (ekran yüksekliği oranı)

export default function init({ gsap, ScrollTrigger, lenis, reduced }) {
  const root = document.querySelector('[data-dolap]');
  if (!root) return;
  const files = [...root.querySelectorAll('[data-file]')];
  const n = files.length;
  const count = root.querySelector('[data-count]');
  const out = root.querySelector('[data-eleme-out]');
  const opts = [...root.querySelectorAll('[data-goal]')];
  const defaultOut = out?.innerHTML ?? '';
  const titles = files.map((f) => f.querySelector('.file__t a')?.textContent.trim() ?? '');

  let st = null;
  let front = 0;

  /* ---------- 3B çekmece ---------- */

  function render(p) {
    for (let i = 0; i < n; i++) {
      const el = files[i];
      const d = i - p;
      let tf, op;
      if (d >= 0) {
        // arkadaki dosyalar: derinlikte sıralı, en arkadakiler silikleşir
        tf = `translate3d(0,0,${(-d * GAP_Z).toFixed(1)}px)`;
        op = d > 5 ? Math.max(0, 1 - (d - 5) / 1.5) : 1;
      } else {
        // öndeki dosya: alt kenarından menteşelenip öne düşer
        const t = Math.min(1, -d);
        tf = `translate3d(0,${(t * 40).toFixed(1)}px,${(t * 30).toFixed(1)}px) rotateX(${(-t * 84).toFixed(2)}deg)`;
        op = Math.max(0, 1 - t * 1.35);
      }
      el.style.transform = tf;
      el.style.opacity = op.toFixed(3);
      el.style.pointerEvents = op < 0.05 ? 'none' : '';
    }
    const idx = Math.max(0, Math.min(n - 1, Math.round(p)));
    if (idx !== front || !count?.dataset.init) {
      front = idx;
      if (count) { count.textContent = `${idx + 1} / ${n}`; count.dataset.init = '1'; }
      files.forEach((el, i) => {
        el.classList.toggle('is-front', i === idx);
        // yalnızca öndeki dosyanın "aç" düğmesi tıklanabilir görünür
        const open = el.querySelector('.file__open');
        if (open) open.style.visibility = i === idx ? '' : 'hidden';
      });
    }
  }

  function clearStyles() {
    files.forEach((el) => {
      el.style.transform = el.style.opacity = el.style.pointerEvents = '';
      const open = el.querySelector('.file__open');
      if (open) open.style.visibility = '';
    });
  }

  function goTo(i) {
    if (st) {
      const y = st.start + (st.end - st.start) * (i / (n - 1));
      if (lenis) lenis.scrollTo(y, { duration: 1.1 });
      else window.scrollTo({ top: y, behavior: reduced ? 'auto' : 'smooth' });
    } else {
      const el = files[i];
      if (lenis) lenis.scrollTo(el, { offset: -120 });
      else el.scrollIntoView({ behavior: reduced ? 'auto' : 'smooth', block: 'start' });
    }
  }

  const proxy = { p: 0 };
  const mm = gsap.matchMedia();
  mm.add('(min-width: 961px) and (min-height: 640px) and (prefers-reduced-motion: no-preference)', () => {
    root.classList.add('is-3d');
    proxy.p = 0;
    render(0);
    const tween = gsap.to(proxy, { p: n - 1, ease: 'none', onUpdate: () => render(proxy.p) });
    st = ScrollTrigger.create({
      trigger: root,
      pin: root.querySelector('[data-dolap-pin]'),
      start: 'top top',
      end: () => '+=' + Math.round((n - 1) * window.innerHeight * SPAN),
      scrub: 0.5,
      animation: tween,
      snap: { snapTo: 1 / (n - 1), duration: { min: 0.15, max: 0.5 }, delay: 0.06, ease: 'power1.inOut' },
      invalidateOnRefresh: true,
    });
    // dosyalar çekmeceye iner
    gsap.from(root.querySelector('[data-scene]'), {
      y: -70, opacity: 0, duration: 1.1, ease: 'expo.out',
      scrollTrigger: { trigger: root, start: 'top 75%', once: true },
    });
    return () => {
      st?.kill(true);
      st = null;
      tween.kill();
      root.classList.remove('is-3d');
      clearStyles();
    };
  });

  /* ---------- Sırt etiketleri ve odak ---------- */

  root.querySelectorAll('[data-tab]').forEach((tab) => {
    tab.addEventListener('click', (e) => {
      e.preventDefault();
      goTo(+tab.dataset.tab);
    });
  });
  files.forEach((el, i) => {
    el.addEventListener('focusin', () => { if (st && i !== front) goTo(i); });
  });

  /* ---------- Eleme ---------- */

  const join = (arr) => (arr.length < 2 ? arr.join('') : arr.slice(0, -1).join(', ') + ' ve ' + arr[arr.length - 1]);

  opts.forEach((btn) => {
    btn.addEventListener('click', () => {
      const on = btn.getAttribute('aria-pressed') !== 'true';
      opts.forEach((b) => b.setAttribute('aria-pressed', 'false'));
      if (!on) {
        files.forEach((f) => f.classList.remove('is-match', 'is-dim'));
        if (out) out.innerHTML = defaultOut;
        return;
      }
      btn.setAttribute('aria-pressed', 'true');
      const slugs = btn.dataset.slugs.split(',');
      const hits = [];
      files.forEach((f, i) => {
        const hit = slugs.includes(f.dataset.slug);
        f.classList.toggle('is-match', hit);
        f.classList.toggle('is-dim', !hit);
        if (hit) hits.push(i);
      });
      if (out) {
        const links = hits.map((i) => `<a href="#${files[i].id}" data-go="${i}">${titles[i]}</a>`);
        out.innerHTML = `${hits.length === 1 ? 'Bakmanız gereken dosya' : hits.length + ' dosya öne çıktı'}: ${join(links)}.`;
      }
      if (st && hits.length && !hits.includes(front)) goTo(hits[0]);
    });
  });

  out?.addEventListener('click', (e) => {
    const a = e.target.closest('[data-go]');
    if (!a) return;
    e.preventDefault();
    goTo(+a.dataset.go);
    // odak dosyanın başlığına geçsin
    setTimeout(() => files[+a.dataset.go].querySelector('.file__t a')?.focus({ preventScroll: true }), st ? 900 : 300);
  });
}
