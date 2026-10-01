/**
 * Misyonumuz: defter masaya konur; maddelerin işaretleri görünür oldukça kalemle atılır (CSS, data-on).
 */

export default function init({ gsap, reduced }) {
  if (reduced || !gsap) return;
  const delay = window.__vt ? 0.45 : 0.05;
  gsap.from('.ms__head > *', { y: 30, opacity: 0, duration: 1, ease: 'expo.out', stagger: 0.07, delay });
  gsap.from('[data-defter]', { y: 110, rotation: -4, opacity: 0, duration: 1.4, ease: 'expo.out', delay: delay + 0.15, clearProps: 'transform' });
  gsap.from('.defter__spiral i', { scaleY: 0, transformOrigin: '50% 100%', duration: 0.5, ease: 'back.out(2)', stagger: 0.018, delay: delay + 0.7 });
}
