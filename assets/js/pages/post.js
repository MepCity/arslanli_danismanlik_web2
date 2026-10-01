/**
 * Makale · okuma ilerlemesi
 * Kurşun kalem sol kenarda metinle birlikte aşağı iner ve arkasında bir çizgi bırakır.
 */

export default function init({ gsap, reduced }) {
  const pencil = document.querySelector('[data-pencil]');
  const text = document.querySelector('[data-text]');
  if (!pencil || !text) return;

  let target = 0;
  let shown = 0;
  let wob = 0;
  let dirty = true;

  function measure() {
    const r = text.getBoundingClientRect();
    const read = window.innerHeight * 0.55;          // okunan satır: ekranın biraz altı
    target = Math.min(1, Math.max(0, (read - r.top) / r.height));
    dirty = true;
  }
  window.addEventListener('scroll', measure, { passive: true });
  window.addEventListener('resize', measure);
  measure();

  const tick = (time) => {
    if (!dirty && Math.abs(target - shown) < 0.0005) return;
    const prev = shown;
    shown += (target - shown) * (reduced ? 1 : 0.14);
    if (Math.abs(target - shown) < 0.0005) { shown = target; dirty = false; }
    pencil.style.setProperty('--p', shown.toFixed(4));
    if (!reduced) {
      // yazarken kalem hafifçe sallanır
      const speed = Math.min(1, Math.abs(shown - prev) * 400);
      wob += ((Math.sin(time * 18) * 5 * speed) - wob) * 0.25;
      pencil.style.setProperty('--wob', wob.toFixed(2) + 'deg');
    }
  };

  if (gsap) gsap.ticker.add(tick);
  else {
    const loop = (t) => { tick(t / 1000); requestAnimationFrame(loop); };
    requestAnimationFrame(loop);
  }
}
