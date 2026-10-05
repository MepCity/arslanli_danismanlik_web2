/* Açılışta öne çıkan duyuru: masaya gelen acele evrak.
 * Oturum başına bir kez, ziyaretçinin ilk hareketinde açılır (sayfa yüklenirken değil); kapatılınca sol altta çip kalır.
 * Duyurular ve Haberdar Ol sayfalarında kendiliğinden açılmaz. Ekranın ortasında açılan gerçek bir modal <dialog>. */
(function () {
  'use strict';
  var d = document, h = d.documentElement;
  var tpl = d.getElementById('spot-tpl');
  if (!tpl || !('content' in tpl)) return;

  var frag = tpl.content.cloneNode(true);
  var dlg = frag.querySelector('.spot');
  var chip = frag.querySelector('[data-spot-chip]');
  if (!dlg || !chip || typeof dlg.showModal !== 'function') return;
  d.body.appendChild(frag);

  var page = tpl.getAttribute('data-page');
  var storeKey = 'arsl-spot-' + tpl.getAttribute('data-key');
  var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var seen = false;
  var shown = false;   // pencere bu sayfada en az bir kez açıldı mı
  try { seen = sessionStorage.getItem(storeKey) === '1'; } catch (e) { /* depolama kapalı: her sayfada değil, yalnız bu sayfada bir kez */ }

  var menu = d.querySelector('[data-menu]');
  var nl = d.getElementById('bulten');
  function busy() { return (menu && menu.classList.contains('is-open')) || (nl && nl.open); }
  function lenis() { return window.__ctx && window.__ctx.lenis; }

  /* ---------- Geri sayım ---------- */
  var target = tpl.getAttribute('data-target') ? new Date(tpl.getAttribute('data-target')).getTime() : 0;
  var units = {};
  dlg.querySelectorAll('[data-u]').forEach(function (b) { units[b.getAttribute('data-u')] = b; });
  var lastday = dlg.querySelector('[data-spot-lastday]');
  var timer = null;
  function pad(n) { return (n < 10 ? '0' : '') + n; }
  function set(u, v) {
    var el = units[u];
    if (!el || el.textContent === v) return;
    el.textContent = v;
    if (!reduce) { el.classList.remove('is-tick'); void el.offsetWidth; el.classList.add('is-tick'); }
  }
  function tick() {
    if (!target) return;
    var left = Math.max(0, Math.floor((target - Date.now()) / 1000));
    var days = Math.floor(left / 86400);
    set('d', pad(days));
    set('h', pad(Math.floor(left % 86400 / 3600)));
    set('m', pad(Math.floor(left % 3600 / 60)));
    set('s', pad(left % 60));
    if (lastday) lastday.hidden = days > 0;
  }

  /* ---------- Cetvel: bayraklardan etiketlere iplik ---------- */
  // Bayraklar tarihe göre orantılı yerleşir; etiketler ise cetvelin altında eşit sütunlardadır, yani birbirine binemez.
  // Burada her bayraktan kendi etiketine düz bir çizgi çekilir; uç noktalar aynı sırada olduğu için çizgiler kesişmez.
  var time = dlg.querySelector('[data-spot-time]');
  var ties = dlg.querySelector('.spot__ties');
  var flags = [].slice.call(dlg.querySelectorAll('[data-flag]'));
  var evs = [].slice.call(dlg.querySelectorAll('[data-ev]'));
  var NS = 'http://www.w3.org/2000/svg';
  function drawTies() {
    if (!ties || !flags.length || !ties.getClientRects().length) return;
    var box = ties.getBoundingClientRect();
    ties.setAttribute('viewBox', '0 0 ' + box.width + ' ' + box.height);
    while (ties.firstChild) ties.removeChild(ties.firstChild);
    flags.forEach(function (f, i) {
      if (!evs[i]) return;
      var fr = f.getBoundingClientRect(), er = evs[i].getBoundingClientRect();
      var x1 = fr.left + fr.width / 2 - box.left, x2 = er.left + 0.75 - box.left;
      var ln = d.createElementNS(NS, 'line');
      ln.setAttribute('x1', x1); ln.setAttribute('y1', 0); ln.setAttribute('x2', x2); ln.setAttribute('y2', box.height);
      var dot = d.createElementNS(NS, 'circle');
      dot.setAttribute('cx', x2); dot.setAttribute('cy', box.height); dot.setAttribute('r', 2.5);
      ties.appendChild(ln); ties.appendChild(dot);
    });
  }
  if (time && 'ResizeObserver' in window) new ResizeObserver(drawTies).observe(time);
  // kâğıt düşerken (döner, kayar) ölçüm yanıltır: oturduktan sonra yeniden çizilir
  dlg.querySelector('.spot__sheet').addEventListener('animationend', drawTies);

  /* ---------- Aç / kapat ---------- */
  function showChip() { chip.classList.add('is-on'); }
  var prevOverflow = '';

  function open() {
    if (dlg.open || busy()) return;
    shown = true;
    dlg.classList.remove('is-closing');
    chip.classList.remove('is-on');
    prevOverflow = h.style.overflow;
    h.style.overflow = 'hidden';
    dlg.showModal();
    if (lenis()) lenis().stop();
    try { sessionStorage.setItem(storeKey, '1'); } catch (e) {}
    tick();
    drawTies();
    clearInterval(timer);
    timer = setInterval(tick, 1000);
    // odak kâğıdın kendisinde başlar; ilk Tab kapatma düğmesine gider
    var sheet = dlg.querySelector('.spot__sheet');
    sheet.setAttribute('tabindex', '-1');
    sheet.style.outline = 'none';
    sheet.focus({ preventScroll: true });
  }
  var closing = false;
  function close() {
    if (!dlg.open || closing) return;
    if (reduce) { dlg.close(); return; }
    closing = true;
    dlg.classList.add('is-closing');
    setTimeout(function () { dlg.close(); }, 280);
  }
  dlg.addEventListener('close', function () {
    closing = false;
    dlg.classList.remove('is-closing');
    clearInterval(timer);
    h.style.overflow = prevOverflow;
    if (lenis() && !busy()) lenis().start();
    showChip();
    // klavyeyle kapatan ziyaretçinin odağı çipe döner
    if (!dlg.dataset.go) chip.focus({ preventScroll: true });
    delete dlg.dataset.go;
  });
  dlg.addEventListener('cancel', function (e) { e.preventDefault(); close(); });   // Escape
  dlg.addEventListener('click', function (e) {
    if (e.target === dlg || e.target.closest('[data-spot-close]')) close();   // kararmış alana ya da kapat düğmesine
  });
  dlg.querySelectorAll('[data-spot-go]').forEach(function (a) { a.addEventListener('click', function () { dlg.dataset.go = '1'; }); });
  chip.addEventListener('click', open);

  /* ---------- Ne zaman açılır ---------- */
  // Duyurular sayfasında ve bülten sayfasında kendiliğinden açılmaz; çip görünür.
  var autoOpen = !seen && page !== 'announcements' && page !== 'signup';
  function schedule() {
    if (!autoOpen) { showChip(); return; }
    var wait = function () {
      if (shown || dlg.open) return;                               // ziyaretçi çipten kendisi açtıysa yeniden açılmaz
      if (busy()) { setTimeout(wait, 400); return; }               // menü ya da bülten penceresi açıkken bekle
      open();
    };
    // Ziyaretçinin ilk hareketiyle açılır (fare, kaydırma, dokunma, tuş): sayfanın kendi içeriği önce boyanır.
    // Hareket olmazsa 4 sn sonra yalnızca çip görünür.
    var events = ['mousemove', 'pointerdown', 'wheel', 'scroll', 'touchstart', 'keydown'];
    var armed = false;
    var arm = function () {
      if (armed) return;
      armed = true;
      events.forEach(function (ev) { window.removeEventListener(ev, arm, true); });
      setTimeout(wait, reduce ? 150 : 650);
    };
    events.forEach(function (ev) { window.addEventListener(ev, arm, { capture: true, passive: true }); });
    setTimeout(function () { if (!armed && !dlg.open) showChip(); }, 4000);
  }
  if (d.readyState === 'complete') schedule(); else window.addEventListener('load', schedule, { once: true });
})();
