/**
 * İletişim · Dilekçe
 * - Yazılan ad imza satırına, seçilen konu "Konu:" satırına yansır.
 * - Gönderilince "ALINDI" kaşesi basılır; dilekçe üçe katlanır, zarfa girer ve yola çıkar.
 * - Kartvizit tıklayınca (ya da klavye odağı arka yüze geçince) döner.
 */

export default function init({ gsap, lenis, reduced }) {
  const form = document.querySelector('[data-letter]');
  if (!form) return;

  mirrors(form);
  card();

  form.addEventListener('form:error', () => {
    if (reduced || !gsap) return;
    gsap.fromTo(form, { x: -7 }, { x: 0, duration: 0.7, ease: 'elastic.out(1, 0.28)', clearProps: 'x' });
  });

  form.addEventListener('form:ok', (e) => {
    const message = e.detail?.message || 'Dilekçeniz bize ulaştı.';
    form.inert = true;
    form.classList.add('is-received', 'is-stamped');
    const post = document.querySelector('[data-post]');
    const done = document.querySelector('[data-done]');
    const msg = document.querySelector('[data-done-msg]');
    if (msg) msg.textContent = message;

    if (reduced || !gsap) {
      if (post) post.hidden = false;
      done?.focus({ preventScroll: true });
      return;
    }
    setTimeout(() => foldAndSend(gsap, lenis, form, post, done), 950);
  });
}

/* ---------- Ad ve konu yansımaları ---------- */

function mirrors(form) {
  const nameIn = form.querySelector('[data-mirror-src="name"]');
  const nameOut = form.querySelector('[data-mirror="name"]');
  const topicIn = form.querySelector('[data-mirror-src="konu"]');
  const topicOut = form.querySelector('[data-mirror="konu"]');

  const syncName = () => {
    if (!nameIn || !nameOut) return;
    const v = nameIn.value.trim();
    nameOut.textContent = v || nameOut.dataset.empty || '';
    nameOut.classList.toggle('is-empty', !v);
  };
  const syncTopic = () => {
    if (topicIn && topicOut) topicOut.textContent = topicIn.value;
  };
  nameIn?.addEventListener('input', syncName);
  topicIn?.addEventListener('change', syncTopic);
  syncName();
  syncTopic();
}

/* ---------- Kartvizit ---------- */

function card() {
  const kv = document.querySelector('[data-kv]');
  if (!kv) return;
  const btn = kv.querySelector('[data-kv-flip]');
  const back = kv.querySelector('.kv__back');
  const set = (on) => {
    kv.classList.toggle('is-flipped', on);
    btn?.setAttribute('aria-pressed', String(on));
  };
  btn?.addEventListener('click', () => set(!kv.classList.contains('is-flipped')));
  kv.addEventListener('focusin', (e) => {
    if (e.target === btn) return;
    set(back.contains(e.target));
  });
}

/* ---------- Katla, zarfla, gönder ---------- */

function foldAndSend(gsap, lenis, form, post, done) {
  const desk = form.parentElement;
  const W = form.offsetWidth;
  const H = form.offsetHeight;
  const third = H / 3;

  // Dilekçenin üç dilimi: aynı kâğıdın kopyaları, her biri kendi üçte birini gösterir
  const fold = document.createElement('div');
  fold.className = 'fold';
  fold.setAttribute('aria-hidden', 'true');
  Object.assign(fold.style, { inset: 'auto', left: form.offsetLeft + 'px', top: form.offsetTop + 'px', width: W + 'px', height: H + 'px' });

  const makeClone = (offset) => {
    const c = form.cloneNode(true);
    // seçili konu ve yazılan değerler kopyaya da geçsin
    const src = form.querySelectorAll('input, select, textarea');
    c.querySelectorAll('input, select, textarea').forEach((el, i) => { if (src[i]) el.value = src[i].value; });
    c.classList.remove('is-stamped');
    c.classList.add('fold__clone', 'is-received');
    c.removeAttribute('data-form');
    c.removeAttribute('data-letter');
    c.querySelectorAll('[id]').forEach((el) => el.removeAttribute('id'));
    c.querySelectorAll('[name]').forEach((el) => el.removeAttribute('name'));
    c.inert = true;
    Object.assign(c.style, { top: -offset + 'px', width: W + 'px', height: H + 'px' });
    return c;
  };

  const panel = (name, top, withBack) => {
    const p = document.createElement('div');
    p.className = 'fold__panel fold__panel--' + name;
    Object.assign(p.style, { top: top + 'px', height: third + 'px' });
    const face = document.createElement('div');
    face.className = 'fold__face';
    face.appendChild(makeClone(top));
    p.appendChild(face);
    if (withBack) {
      const b = document.createElement('div');
      b.className = 'fold__back';
      p.appendChild(b);
    }
    return p;
  };

  // Katlanma görünür olsun diye dilekçenin ortası ekranın ortasına gelir
  const centerY = desk.getBoundingClientRect().top + window.scrollY + form.offsetTop + H / 2 - window.innerHeight / 2;
  if (lenis) lenis.scrollTo(centerY, { duration: 0.9 }); else window.scrollTo({ top: centerY, behavior: 'smooth' });

  const mid = panel('mid', third, false);
  const bot = panel('bot', third * 2, true);
  const top = panel('top', 0, true);
  fold.append(mid, bot, top);
  desk.appendChild(fold);
  form.style.visibility = 'hidden';

  // Zarf: arka katman (gövde + açık kapak) ve ön katman (cep + kapanan kapak)
  const ZW = Math.min(W * 0.92, 560);
  const ZH = ZW * 0.56;
  const zLeft = form.offsetLeft + (W - ZW) / 2;
  const zTop = form.offsetTop + H / 2 - ZH * 0.15;
  const mkZarf = (cls, inner) => {
    const z = document.createElement('div');
    z.className = 'zarf ' + cls;
    z.setAttribute('aria-hidden', 'true');
    Object.assign(z.style, { left: zLeft + 'px', top: zTop + 'px', width: ZW + 'px', height: ZH + 'px' });
    z.innerHTML = inner;
    desk.appendChild(z);
    return z;
  };
  const zBack = mkZarf('zarf--back', '<div class="zarf__back"></div><div class="zarf__flap" data-flap-open></div>');
  const zFront = mkZarf('zarf--front', '<div class="zarf__pocket"></div><div class="zarf__flap" data-flap-close></div>');
  zBack.style.zIndex = 3;
  fold.style.zIndex = 4;
  zFront.style.zIndex = 5;
  const flapOpen = zBack.querySelector('[data-flap-open]');
  const flapClose = zFront.querySelector('[data-flap-close]');
  gsap.set(flapOpen, { rotationX: 180, transformPerspective: 900, transformOrigin: '50% 0' });
  gsap.set(flapClose, { rotationX: 90, transformPerspective: 900, transformOrigin: '50% 0', autoAlpha: 0 });
  gsap.set([zBack, zFront], { autoAlpha: 0, y: 40 });

  const s = Math.min((ZW * 0.9) / W, (ZH * 0.86) / third, 1);
  const dy = ZH * 0.35;

  const tl = gsap.timeline({
    onComplete: () => {
      fold.remove(); zBack.remove(); zFront.remove();
      form.style.display = 'none';
      if (post) post.hidden = false;
      gsap.from(done, { y: 24, autoAlpha: 0, duration: 0.8, ease: 'expo.out' });
      done?.focus({ preventScroll: true });
      const y = desk.getBoundingClientRect().top + window.scrollY - 120;
      if (lenis) lenis.scrollTo(y, { duration: 1 }); else window.scrollTo({ top: y, behavior: 'smooth' });
    },
  });

  tl.to(bot, { rotationX: 180, transformPerspective: 1800, duration: 0.8, ease: 'power2.inOut', delay: 0.5 })
    .to(top, { rotationX: -180, transformPerspective: 1800, duration: 0.75, ease: 'power2.inOut' }, '+=0.05')
    .to(fold, { scale: s, duration: 0.7, ease: 'power3.inOut', transformOrigin: '50% 50%' }, '+=0.1')
    .to([zBack, zFront], { autoAlpha: 1, y: 0, duration: 0.6, ease: 'expo.out' }, '<0.25')
    .to(fold, { y: dy, duration: 0.65, ease: 'power2.inOut' }, '+=0.05')
    .to(flapOpen, { rotationX: 90, duration: 0.22, ease: 'power1.in' })
    .set(flapOpen, { autoAlpha: 0 })
    .set(flapClose, { autoAlpha: 1 })
    .to(flapClose, { rotationX: 0, duration: 0.32, ease: 'power2.out' })
    .to([zBack, zFront, fold], { x: () => W * 0.7, y: (i) => (i === 2 ? dy : 0) - 60, autoAlpha: 0, duration: 0.85, ease: 'power2.in' }, '+=0.35');
}
