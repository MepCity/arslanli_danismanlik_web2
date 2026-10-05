/**
 * Yasal metinler: madde listesi okunan maddeyi işaretler, "Tümünü sadeleştir"
 * bütün sade Türkçe notlarını birlikte açar.
 */

export default function init({ lenis, t }) {
  const links = [...document.querySelectorAll('[data-toc]')];
  const sections = [...document.querySelectorAll('[data-madde]')];

  // Okunan madde
  if (links.length && 'IntersectionObserver' in window) {
    const byId = new Map(links.map((a) => [a.dataset.toc, a]));
    const visible = new Set();
    const pick = () => {
      const first = sections.find((s) => visible.has(s.id));
      links.forEach((a) => {
        const on = first && a.dataset.toc === first.id;
        a.classList.toggle('is-cur', !!on);
        if (on) a.setAttribute('aria-current', 'true'); else a.removeAttribute('aria-current');
      });
    };
    const io = new IntersectionObserver((entries) => {
      entries.forEach((en) => (en.isIntersecting ? visible.add(en.target.id) : visible.delete(en.target.id)));
      pick();
    }, { rootMargin: '-30% 0px -55% 0px' });
    sections.forEach((s) => byId.has(s.id) && io.observe(s));
  }

  // Madde bağlantıları yumuşak kaydırmayla
  links.forEach((a) => a.addEventListener('click', (e) => {
    const target = document.getElementById(a.dataset.toc);
    if (!target || !lenis) return;
    e.preventDefault();
    const hdr = parseFloat(getComputedStyle(document.documentElement).getPropertyValue('--hdr-h')) || 76;
    lenis.scrollTo(target, { offset: -(hdr + 20), duration: 1.1 });
    history.replaceState(null, '', '#' + a.dataset.toc);
    target.querySelector('h2')?.setAttribute('tabindex', '-1');
    setTimeout(() => target.querySelector('h2')?.focus({ preventScroll: true }), 700);
  }));

  // Tümünü sadeleştir
  const all = document.querySelector('[data-sade-all]');
  const notes = [...document.querySelectorAll('details.sade')];
  if (all && notes.length) {
    const sync = () => {
      const open = notes.every((d) => d.open);
      all.setAttribute('aria-pressed', String(open));
      all.textContent = t(open ? 'yasal.liste.tumu_kapat' : 'yasal.liste.tumu');
    };
    all.addEventListener('click', () => {
      const open = !notes.every((d) => d.open);
      notes.forEach((d) => (d.open = open));
      sync();
    });
    notes.forEach((d) => d.addEventListener('toggle', sync));
  }
}
