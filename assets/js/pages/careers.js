/**
 * Kariyer · Özlük dosyası
 * - Yazılan ad ve soyad dosyanın sekmesine yazılır.
 * - Alan hataları Türkçe ve kutunun içinde gösterilir (ortak gönderim kodu hata metnini alandan okur).
 * - Özgeçmiş alanı tıklayarak, klavyeyle ve sürükleyip bırakarak çalışır; tür ve boyut gönderilmeden denetlenir.
 * - Gönderilince form dosyanın içine kayar, kapak kapanır ve "DOSYAYA EKLENDİ" kaşesi basılır.
 */

const MAX = 5 * 1024 * 1024;
const TYPES = ['pdf', 'docx'];

export default function init({ gsap, lenis, reduced }) {
  const form = document.querySelector('[data-kd]');
  if (!form) return;

  // Tarayıcının kendi (dile göre değişen) kutucuğu yerine bizim mesajlarımız gösterilsin
  form.noValidate = true;

  who(form);
  messages(form);
  const cv = attach(form);

  form.addEventListener('form:error', (e) => {
    // Sunucu bir alan için hata döndürdüyse dosya seçimi geçersiz sayılmış olabilir
    if (e.detail?.errors?.cv) cv.reset();
    if (reduced || !gsap) return;
    gsap.fromTo(form, { x: -7 }, { x: 0, duration: 0.7, ease: 'elastic.out(1, 0.28)', clearProps: 'x' });
  });

  form.addEventListener('form:ok', (e) => sent({ gsap, lenis, reduced }, form, e.detail?.message));
}

/* ---------- Sekmedeki ad ---------- */

function who(form) {
  const out = form.querySelector('[data-who]');
  const ad = form.querySelector('[data-who-src="ad"]');
  const soyad = form.querySelector('[data-who-src="soyad"]');
  if (!out || !ad || !soyad) return;
  const sync = () => {
    const v = (ad.value + ' ' + soyad.value).trim().replace(/\s+/g, ' ');
    out.textContent = v || out.dataset.empty;
    out.classList.toggle('is-empty', !v);
  };
  ad.addEventListener('input', sync);
  soyad.addEventListener('input', sync);
  sync();
}

/* ---------- Türkçe doğrulama mesajları ---------- */

const MSG = {
  ad: { valueMissing: 'Adınızı yazın.' },
  soyad: { valueMissing: 'Soyadınızı yazın.' },
  email: { valueMissing: 'E-posta adresinizi yazın.', typeMismatch: 'Geçerli bir e-posta adresi yazın.' },
  telefon: { valueMissing: 'Telefon numaranızı yazın.', patternMismatch: 'Telefonu 0532 000 00 00 gibi yazın.' },
  sehir: { valueMissing: 'Şehrinizi seçin.' },
  linkedin: { typeMismatch: 'Adres https:// ile başlamalı.' },
  kvkk: { valueMissing: 'Devam etmek için Aydınlatma Metni onayı gerekli.' },
  cv: { valueMissing: 'Özgeçmişinizi ekleyin.' },
};

function messages(form) {
  form.querySelectorAll('input, select, textarea').forEach((el) => {
    const table = MSG[el.name];
    if (!table) return;
    el.addEventListener('invalid', () => {
      // Dosya türü ya da boyutu için kendi mesajımız varsa ona dokunma
      if (el.dataset.custom) return;
      el.setCustomValidity('');
      const v = el.validity;
      const key = Object.keys(table).find((k) => v[k]);
      if (key) el.setCustomValidity(table[key]);
    });
    const clear = () => {
      if (el.dataset.custom) return;
      el.setCustomValidity('');
      // düzeltilen alanın hata notu silinir
      const f = el.closest('.field');
      if (f && f.classList.contains('has-error')) {
        f.classList.remove('has-error');
        const err = f.querySelector('.field__err');
        if (err) err.textContent = '';
      }
    };
    el.addEventListener('input', clear);
    el.addEventListener('change', clear);
  });
}

/* ---------- Özgeçmiş ---------- */

function attach(form) {
  const root = form.querySelector('[data-attach]');
  const drop = root.querySelector('[data-drop]');
  const input = root.querySelector('.drop__input');
  const box = root.querySelector('[data-drop-file]');
  const nameEl = root.querySelector('[data-drop-name]');
  const sizeEl = root.querySelector('[data-drop-size]');
  const clear = root.querySelector('[data-drop-clear]');
  const err = root.querySelector('.field__err');

  const size = (b) => (b >= 1048576 ? (b / 1048576).toFixed(1).replace('.', ',') + ' MB' : Math.max(1, Math.round(b / 1024)) + ' KB');
  const showError = (msg) => { root.classList.add('has-error'); err.textContent = msg; };
  const clearError = () => { root.classList.remove('has-error'); err.textContent = ''; };

  function reset() {
    input.value = '';
    box.hidden = true;
    root.classList.remove('has-file');
    delete input.dataset.custom;
    input.setCustomValidity('');
  }

  function check() {
    clearError();
    const f = input.files && input.files[0];
    if (!f) { reset(); return; }
    const ext = (f.name.split('.').pop() || '').toLowerCase();
    if (!TYPES.includes(ext)) {
      reset();
      showError('Yalnızca PDF ya da DOCX dosyası yükleyebilirsiniz.');
      return;
    }
    if (f.size > MAX) {
      reset();
      showError('Dosya en fazla 5 MB olabilir. Seçtiğiniz dosya ' + size(f.size) + '.');
      return;
    }
    nameEl.textContent = f.name;
    sizeEl.textContent = size(f.size);
    box.hidden = false;
    root.classList.add('has-file');
    // Seçilen alan gizlendiği için odağı kaldır düğmesine taşı (klavye kullanıcısı yolunu kaybetmesin)
    if (document.activeElement === input) clear.focus();
  }

  input.addEventListener('change', check);

  // Sürükle bırak: tarayıcının kendi davranışı yerine dosyayı biz alıp denetleriz
  ['dragenter', 'dragover'].forEach((t) => drop.addEventListener(t, (e) => { e.preventDefault(); drop.classList.add('is-over'); }));
  ['dragleave', 'dragend'].forEach((t) => drop.addEventListener(t, () => drop.classList.remove('is-over')));
  drop.addEventListener('drop', (e) => {
    e.preventDefault();
    drop.classList.remove('is-over');
    const files = e.dataTransfer?.files;
    if (!files || !files.length) return;
    try {
      const dt = new DataTransfer();
      dt.items.add(files[0]);
      input.files = dt.files;
      check();
    } catch {
      showError('Dosya sürüklenemedi. Lütfen tıklayarak seçin.');
    }
  });

  clear.addEventListener('click', () => {
    reset();
    clearError();
    input.focus();
  });
  form.addEventListener('reset', () => setTimeout(() => { reset(); clearError(); }, 0));

  return { reset };
}

/* ---------- Gönderim sonrası ---------- */

function sent({ gsap, lenis, reduced }, form, message) {
  const post = document.querySelector('[data-post]');
  const done = document.querySelector('[data-done]');
  const body = form.querySelector('.kd__body');
  const sheet = form.querySelector('.kd__sheet');
  const name = ((form.elements.ad?.value || '') + ' ' + (form.elements.soyad?.value || '')).trim().replace(/\s+/g, ' ');
  const upper = name.toLocaleUpperCase('tr-TR');

  form.inert = true;
  if (message) post.querySelector('[data-done-msg]').textContent = message;
  post.querySelector('[data-done-who]').textContent = upper;
  const tabWho = post.querySelector('[data-post-who]');
  if (tabWho && name) tabWho.textContent = name;

  const show = () => {
    form.hidden = true;
    post.hidden = false;
    post.classList.add('is-in', 'is-stamping');
    done.focus({ preventScroll: true });
    const y = post.getBoundingClientRect().top + window.scrollY - 140;
    if (lenis) lenis.scrollTo(y, { duration: 0.9 }); else window.scrollTo({ top: y, behavior: reduced ? 'auto' : 'smooth' });
  };

  if (reduced || !gsap) { show(); return; }

  // Dosyanın ortası ekranın ortasına gelsin ki kapanma görünsün
  const r = body.getBoundingClientRect();
  const y = r.top + window.scrollY + Math.min(r.height / 2, 300) - window.innerHeight / 2;
  if (lenis) lenis.scrollTo(y, { duration: 0.8 }); else window.scrollTo({ top: y, behavior: 'smooth' });

  // Ön kapak: dosyanın alt kenarından yükselerek formu örter
  const front = document.createElement('div');
  front.className = 'kd__front';
  front.setAttribute('aria-hidden', 'true');
  body.appendChild(front);
  gsap.set(front, { clipPath: 'inset(100% 0 0 0)' });

  gsap.timeline({ onComplete: show })
    .to(sheet, { y: 70, scale: 0.97, transformOrigin: '50% 100%', duration: 0.7, ease: 'power2.in', delay: 0.45 })
    .to(front, { clipPath: 'inset(0% 0 0 0)', duration: 0.8, ease: 'power3.inOut' }, '-=0.4')
    .to({}, { duration: 0.35 });
}
