/**
 * Haberdar Ol · Kupon
 * Kesik çizgi kuponun gerçek ölçüsüne göre çizilir; makas bu yolu izler.
 * Görününce makas üst kenardan biraz keser ve bekler. Gönderim başarılı olunca
 * kuponu baştan sona keser, kupon düşer, sayfadaki boşlukta alındı fişi kalır.
 */

export default function init({ gsap, lenis, reduced }) {
  const kupon = document.querySelector('[data-kupon]');
  const form = document.querySelector('[data-kp-form]');
  if (!kupon || !form) return;

  const hole = document.querySelector('[data-kp-hole]');
  const slip = document.querySelector('[data-kp-slip]');
  const msgEl = document.querySelector('[data-kp-msg]');
  const svg = kupon.querySelector('[data-kp-cut]');
  const dash = svg?.querySelector('.kupon__dash');
  const line = svg?.querySelector('.kupon__line');
  const scissors = kupon.querySelector('[data-kp-scissors]');

  const canCut = !reduced && gsap && svg && scissors
    && window.CSS?.supports?.('offset-path', "path('M 0 0 L 10 10')");

  const state = { p: 0 };
  let length = 0;

  function build() {
    const W = kupon.offsetWidth;
    const H = kupon.offsetHeight;
    const r = 14, i = 1;
    const d = `M ${r + i} ${i} H ${W - r - i} A ${r} ${r} 0 0 1 ${W - i} ${r + i} V ${H - r - i} A ${r} ${r} 0 0 1 ${W - r - i} ${H - i} H ${r + i} A ${r} ${r} 0 0 1 ${i} ${H - r - i} V ${r + i} A ${r} ${r} 0 0 1 ${r + i} ${i}`;
    svg.setAttribute('viewBox', `0 0 ${W} ${H}`);
    dash.setAttribute('d', d);
    line.setAttribute('d', d);
    scissors.style.offsetPath = `path('${d}')`;
    length = dash.getTotalLength();
    line.style.strokeDasharray = `${length} ${length}`;
    apply();
  }

  function apply() {
    scissors.style.offsetDistance = (state.p * 100).toFixed(3) + '%';
    line.style.strokeDashoffset = String(length * (1 - state.p));
  }

  if (canCut) {
    kupon.classList.add('has-cut');
    build();
    new ResizeObserver(() => build()).observe(kupon);

    // Görününce makas üst kenarda bir parça keser ve bekler
    const io = new IntersectionObserver(([en]) => {
      if (!en.isIntersecting) return;
      io.disconnect();
      scissors.classList.add('is-cutting');
      gsap.to(state, {
        p: 0.16,
        duration: 2.2,
        delay: 0.4,
        ease: 'power1.inOut',
        onUpdate: apply,
        onComplete: () => scissors.classList.remove('is-cutting'),
      });
    }, { rootMargin: '0px 0px -25% 0px' });
    io.observe(kupon);
  }

  form.addEventListener('form:ok', (e) => {
    if (msgEl && e.detail?.message) msgEl.textContent = e.detail.message;
    form.inert = true;
    if (hole) hole.hidden = false;

    if (!canCut) {
      kupon.hidden = true;
      slip?.focus({ preventScroll: true });
      return;
    }

    gsap.killTweensOf(state);
    gsap.set(slip, { autoAlpha: 0, y: 26 });
    scissors.classList.add('is-cutting');
    gsap.timeline()
      .to(state, { p: 1, duration: 2.4 * (1 - state.p) + 0.4, ease: 'power1.inOut', onUpdate: apply })
      .call(() => scissors.classList.remove('is-cutting'))
      .to(scissors, { autoAlpha: 0, duration: 0.3 })
      .to(kupon, {
        y: 180,
        x: 30,
        rotation: -7,
        autoAlpha: 0,
        duration: 0.95,
        ease: 'power2.in',
        onComplete: () => {
          kupon.hidden = true;
          slip?.focus({ preventScroll: true });
          const stage = document.querySelector('[data-kp-stage]');
          const y = stage.getBoundingClientRect().top + window.scrollY - 140;
          if (lenis) lenis.scrollTo(y, { duration: 0.9 }); else window.scrollTo({ top: y, behavior: 'smooth' });
        },
      }, '-=0.1')
      .to(slip, { y: 0, autoAlpha: 1, duration: 0.8, ease: 'expo.out' }, '-=0.35');
  });
}
