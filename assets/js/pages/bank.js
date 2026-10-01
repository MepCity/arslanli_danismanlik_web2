/**
 * Hesap Numaralarımız · Dekont
 * Fişler yazıcının ağzından satır satır çıkar; yazıcı ışığı basarken yanıp söner.
 * Hareket azaltıldıysa fişler zaten basılmış durur (CSS).
 */

export default function init({ gsap, reduced }) {
  const units = [...document.querySelectorAll('[data-pos]')];
  if (!units.length || reduced || !gsap) return;

  let queue = Promise.resolve();
  const delay = window.__vt ? 0.6 : 0.25;

  const print = (unit) => new Promise((resolve) => {
    const fis = unit.querySelector('[data-fis]');
    if (!fis) return resolve();
    const lines = Math.max(8, Math.round(fis.offsetHeight / 23));
    const dur = lines * 0.085;
    unit.classList.add('is-printing');
    gsap.timeline({
      delay,
      onComplete: () => {
        unit.classList.remove('is-printing');
        unit.classList.add('is-done');
        resolve();
      },
    })
      .to(fis, { y: 0, duration: dur, ease: `steps(${lines})` })
      .to(fis, { x: 1.1, duration: 0.045, repeat: Math.round(dur / 0.045), yoyo: true, ease: 'none' }, 0)
      .set(fis, { x: 0 })
      .to(fis, { y: 7, duration: 0.12, ease: 'power2.out' })
      .to(fis, { y: 0, duration: 0.5, ease: 'back.out(3)' });
    // ikinci fiş birinci yarıladığında başlasın
    setTimeout(resolve, (delay + dur * 0.45) * 1000);
  });

  const io = new IntersectionObserver((entries) => {
    entries.forEach((en) => {
      if (!en.isIntersecting) return;
      io.unobserve(en.target);
      queue = queue.then(() => print(en.target));
    });
  }, { rootMargin: '0px 0px -10% 0px' });
  units.forEach((u) => io.observe(u));
}
