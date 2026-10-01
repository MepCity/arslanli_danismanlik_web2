/**
 * Vizyonumuz: mühürlü zarf.
 * "Mektubu şimdi açın" → mühür çatlar, kapak açılır, üçe katlanmış mektup zarftan çıkar ve açılır.
 * Animasyon gerçek mektubun kopyalarıyla yapılır; sonunda gerçek (erişilebilir) mektup yerine geçer.
 */

export default function init({ gsap, ScrollTrigger, lenis, reduced }) {
  const root = document.querySelector('[data-vz]');
  if (!root) return;
  const stage = root.querySelector('[data-stage]');
  const env = root.querySelector('[data-env]');
  const flap = root.querySelector('[data-flap]');
  const seal = root.querySelector('[data-seal]');
  const btn = root.querySelector('[data-open]');
  const letter = root.querySelector('[data-letter]');
  if (!stage || !env || !flap || !seal || !btn || !letter) return;

  if (reduced || !gsap) {
    root.classList.add('is-open');
    btn.setAttribute('aria-expanded', 'true');
    return;
  }

  const delay = window.__vt ? 0.45 : 0.05;
  gsap.from('.vz__head > *', { y: 30, opacity: 0, duration: 1, ease: 'expo.out', stagger: 0.07, delay });
  gsap.from(env, { y: 90, rotation: -4, opacity: 0, duration: 1.3, ease: 'expo.out', delay: delay + 0.15 });
  gsap.from(btn, { y: 20, opacity: 0, duration: 0.9, ease: 'expo.out', delay: delay + 0.5 });

  let opened = false;
  btn.addEventListener('click', open);
  seal.addEventListener('click', open);

  function open() {
    if (opened) return;
    opened = true;
    root.classList.add('is-opening');
    btn.setAttribute('aria-expanded', 'true');

    const year = root.dataset.year || '';
    const W = letter.offsetWidth;
    const H = letter.offsetHeight;
    const third = H / 3;
    const envW = env.offsetWidth;
    const envH = env.offsetHeight;
    const s0 = Math.min(1, (envW * 0.9) / W);
    const cs = getComputedStyle(stage);
    const ls = getComputedStyle(letter);
    const stageMT = parseFloat(cs.marginTop) || 0;
    const letterMT = parseFloat(ls.marginTop) || 0;

    // Katlanmış kopya: alt, orta, üst sırasıyla (üst panel en üstte boyanır)
    const fold = document.createElement('div');
    fold.className = 'fold';
    fold.setAttribute('aria-hidden', 'true');
    fold.style.width = W + 'px';
    fold.style.height = H + 'px';
    fold.style.left = (envW - W) / 2 + 'px';
    const src = letter.querySelector('.letter__in');
    const make = (i) => {
      const p = document.createElement('div');
      p.className = 'fold__p';
      p.style.top = i * third + 'px';
      p.style.height = third + 'px';
      const front = document.createElement('div');
      front.className = 'fold__face';
      const c = src.cloneNode(true);
      c.style.top = -i * third + 'px';
      c.style.height = H + 'px';
      front.appendChild(c);
      const shade = document.createElement('i');
      shade.className = 'fold__shade';
      front.appendChild(shade);
      p.appendChild(front);
      if (i !== 1) {
        const back = document.createElement('div');
        back.className = 'fold__face fold__back';
        if (i === 0) back.innerHTML = '<em>' + year + '’de açılacak</em>';
        p.appendChild(back);
      }
      return { p, shade };
    };
    const bottom = make(2), middle = make(1), top = make(0);
    fold.append(bottom.p, middle.p, top.p);
    env.appendChild(fold);

    gsap.set(top.p, { rotationX: -180, transformOrigin: '50% 100%', transformPerspective: 1800 });
    gsap.set(bottom.p, { rotationX: -180, transformOrigin: '50% 0%', transformPerspective: 1800 });
    gsap.set([top.shade, bottom.shade], { opacity: 0.22 });

    // Katlı mektubun görünen (orta) bölümü zarfın içinde başlar
    const st = { y: envH * 0.1 - s0 * third, s: s0 };
    let inEnv = true;
    const apply = () => {
      gsap.set(fold, { y: st.y, scale: st.s });
      if (inEnv) {
        const limit = (envH * 0.985 - st.y) / st.s;
        const cut = Math.max(0, H - limit);
        fold.style.clipPath = 'inset(0 -40px ' + cut.toFixed(1) + 'px -40px)';
      }
    };
    apply();

    const envTop = env.offsetTop;
    const yFinal = -envTop + (letterMT - stageMT);
    const stageTarget = H + letterMT - stageMT;
    stage.style.height = stage.offsetHeight + 'px';

    const halves = seal.querySelectorAll('.seal__half');
    const parts = env.querySelectorAll('.env__back, .env__pocket, .env__to, .env__flap, .env__note');

    const tl = gsap.timeline({ onComplete: finish });
    tl.to(seal, { scale: 1.1, duration: 0.14, ease: 'power2.out' })
      .to(seal, { scale: 1, duration: 0.12, ease: 'power2.in' })
      .to(halves[0], { x: -30, y: 80, rotation: -30, opacity: 0, duration: 0.75, ease: 'power2.in' })
      .to(halves[1], { x: 34, y: 90, rotation: 26, opacity: 0, duration: 0.75, ease: 'power2.in' }, '<')
      .to(flap, {
        rotationX: 180,
        duration: 0.8,
        ease: 'power2.inOut',
        onUpdate() { if (this.progress() > 0.5) flap.style.zIndex = '1'; },
      }, '<0.12')
      .to(st, { y: st.y - envH * 0.42, duration: 0.9, ease: 'power3.inOut', onUpdate: apply }, '-=0.1')
      .to(parts, { y: 70, opacity: 0, duration: 0.6, ease: 'power2.in' }, '-=0.25')
      .to(btn, { opacity: 0, duration: 0.3 }, '<')
      .add(() => { inEnv = false; fold.style.clipPath = 'none'; })
      .to(st, { y: yFinal, s: 1, duration: 1, ease: 'expo.inOut', onUpdate: apply }, '<0.05')
      .to(stage, { height: stageTarget, duration: 1.5, ease: 'expo.inOut' }, '<')
      .to(top.p, { rotationX: 0, duration: 0.9, ease: 'power3.inOut' }, '-=0.35')
      .to(top.shade, { opacity: 0, duration: 0.9, ease: 'power2.out' }, '<')
      .to(bottom.p, { rotationX: 0, duration: 0.9, ease: 'power3.inOut' }, '-=0.45')
      .to(bottom.shade, { opacity: 0, duration: 0.9, ease: 'power2.out' }, '<');

    function finish() {
      stage.hidden = true;
      root.classList.remove('is-opening');
      root.classList.add('is-open');
      fold.remove();
      letter.focus({ preventScroll: true });
      ScrollTrigger?.refresh();
      lenis?.resize?.();
    }
  }
}
