/**
 * 404: kaşe kâğıda vurduğu an yazı hafifçe sarsılır.
 */

export default function init({ gsap, reduced }) {
  const stamp = document.querySelector('.nf__stamp');
  const letter = document.querySelector('[data-letter]');
  if (!stamp || !letter || !gsap || reduced) return;

  stamp.addEventListener('animationstart', () => {
    // stamp animasyonunun %55'inde (0.5 sn × 0.55) mürekkep kâğıda değer
    gsap.delayedCall(0.27, () => {
      gsap.fromTo(letter, { y: 4, scale: 0.996 }, { y: 0, scale: 1, duration: 0.5, ease: 'elastic.out(1.1, 0.4)', clearProps: 'transform' });
    });
  }, { once: true });
}
