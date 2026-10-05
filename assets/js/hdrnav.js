/* Üst bilgideki sayfa dizini: üzerine gelinen (ya da klavyeyle odaklanılan) sayfanın etiketi koşu başlığında görünür,
   ayrılınca bulunulan sayfanın etiketi geri gelir. Numaralar-yalnız görünümde adın okunduğu yer burasıdır.
   JavaScript yoksa dizin yine tıklanır; yalnızca bu önizleme olmaz. */
(() => {
  const nav = document.querySelector('[data-hnav]');
  const label = document.querySelector('[data-folio]');
  if (!nav || !label) return;

  const home = label.innerHTML;

  // Üst bilgi aşağı kaydırınca gizlenir; klavyeyle içine odaklanan ziyaretçi için geri gelir
  const hdr = document.querySelector('[data-hdr]');
  hdr?.addEventListener('focusin', () => hdr.classList.remove('is-hidden'));

  const build = (no, name) => {
    const ev = document.createElement('span');
    ev.className = 'hdr__ev';
    const w = document.createElement('span');
    w.className = 'hdr__w';
    w.textContent = (label.dataset.word || '') + ' ';   // "Evrak" sözcüğü sunucudan gelir (data-word; kayıt defteri genel.folio.word)
    ev.append(w, no);
    const nm = document.createElement('span');
    nm.className = 'hdr__nm';
    const b = document.createElement('b');
    b.textContent = name;
    nm.append(' · ', b);
    return [ev, nm];
  };

  const show = (a) => {
    const name = a.querySelector('.hnav__t')?.textContent || '';
    label.replaceChildren(...build(a.dataset.no || '', name));
  };
  const reset = () => { label.innerHTML = home; };

  nav.querySelectorAll('a').forEach((a) => {
    a.addEventListener('pointerenter', () => show(a));
    a.addEventListener('focus', () => show(a));
  });
  nav.addEventListener('pointerleave', reset);
  nav.addEventListener('focusout', (e) => { if (!nav.contains(e.relatedTarget)) reset(); });
})();
