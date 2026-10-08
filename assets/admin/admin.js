/* ==========================================================================
   Arslanlı · Yönetim paneli davranışları
   ========================================================================== */
(function () {
  'use strict';
  var d = document;
  var $ = function (s, r) { return (r || d).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || d).querySelectorAll(s)); };
  var isMac = /Mac|iPhone|iPad/.test(navigator.platform);

  /* ---------- Kenar menü (mobil) ---------- */
  var app = $('.app');
  // Paneli kullanan tarayıcının kendi ziyaretleri sitedeki ziyaretçi sayacına yazılmaz (bkz. assets/js/app.js)
  if (app) { try { localStorage.setItem('arsl-sayma', '1'); } catch (e) {} }
  // Odak tuzağı: Tab, açık pencerenin içinde döner (komut paleti, mobil menü)
  var focusables = function (root) { return $$('a[href], button:not([disabled]), input:not([disabled]):not([type="hidden"]), select, textarea, [tabindex]:not([tabindex="-1"])', root).filter(function (e) { return e.offsetParent !== null; }); };
  var trap = function (root, e) {
    if (e.key !== 'Tab') return;
    var f = focusables(root); if (!f.length) return;
    var first = f[0], last = f[f.length - 1];
    if (e.shiftKey && (d.activeElement === first || !root.contains(d.activeElement))) { e.preventDefault(); last.focus(); }
    else if (!e.shiftKey && (d.activeElement === last || !root.contains(d.activeElement))) { e.preventDefault(); first.focus(); }
  };
  var side = $('#side'), sideOpener = null;
  var sideOpen = function (b) { sideOpener = b; app.classList.add('is-nav'); $$('[data-side-open]').forEach(function (x) { x.setAttribute('aria-expanded', 'true'); }); setTimeout(function () { var cur = $('.side__link.is-on', side) || $('.side__link', side); if (cur) cur.focus(); }, 30); };
  var sideClose = function (back) { if (!app.classList.contains('is-nav')) return; app.classList.remove('is-nav'); $$('[data-side-open]').forEach(function (x) { x.setAttribute('aria-expanded', 'false'); }); if (back && sideOpener) sideOpener.focus(); };
  $$('[data-side-open]').forEach(function (b) { b.setAttribute('aria-expanded', 'false'); b.addEventListener('click', function () { sideOpen(b); }); });
  $$('[data-side-close]').forEach(function (b) { b.addEventListener('click', function () { sideClose(true); }); });
  if (side) {
    var cur0 = $('.side__link.is-on', side); if (cur0 && window.innerWidth > 960 && cur0.offsetTop + cur0.offsetHeight > side.clientHeight - 110) side.scrollTop = cur0.offsetTop - 140;   // seçili bölüm altta kalıyorsa menü kendiliğinden kayar (scrollIntoView tuş odağı başlangıcını kaydırır, kullanılmaz)
    side.addEventListener('keydown', function (e) { if (e.key === 'Escape') sideClose(true); else if (app.classList.contains('is-nav')) trap(side, e); });
    window.addEventListener('resize', function () { if (window.innerWidth > 960) sideClose(false); });
  }

  /* ---------- Kaydırılabilir bölgeler klavyeyle de kaydırılabilsin ---------- */
  var scrollers = function () { $$('.tbl-wrap, .sx-pre, .vz-wrap').forEach(function (el) { if (el.scrollWidth > el.clientWidth + 1 || el.scrollHeight > el.clientHeight + 1 || el.classList.contains('sx-pre')) el.setAttribute('tabindex', '0'); else el.removeAttribute('tabindex'); }); };
  scrollers(); window.addEventListener('resize', scrollers); window.addEventListener('load', scrollers);

  /* ---------- Üst çubuk gölgesi ---------- */
  var top = $('.top');
  if (top) {
    var onScroll = function () { top.classList.toggle('is-stuck', window.scrollY > 4); };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }

  /* ---------- Bildirim ---------- */
  var toast = $('[data-toast]');
  if (toast) {
    // Uzun iletiler (ör. geri yükleme sonucu) okunabilecek kadar kalır; üzerine gelinince ya da odaklanınca bekler, düğmeyle kapatılır
    var tmr = null, hold = false;
    var bye = function () { toast.classList.add('is-out'); setTimeout(function () { toast.remove(); }, 400); };
    var arm = function () { clearTimeout(tmr); tmr = setTimeout(function () { if (hold) arm(); else bye(); }, Math.min(60000, 4200 + 70 * (toast.textContent || '').length)); };
    toast.addEventListener('mouseenter', function () { hold = true; });
    toast.addEventListener('mouseleave', function () { hold = false; });
    toast.addEventListener('focusin', function () { hold = true; });
    var x = $('[data-toast-x]', toast);
    if (x) x.addEventListener('click', function () { clearTimeout(tmr); bye(); });
    arm();
  }

  /* ---------- Komut paleti (⌘K) ---------- */
  var pal = $('[data-palette]');
  if (pal) {
    var data = [];
    try { data = JSON.parse($('[data-palette-data]').textContent); } catch (e) {}
    var input = $('[data-palette-input]', pal);
    var list = $('[data-palette-list]', pal);
    var sel = 0, shown = [];
    var norm = function (s) {
      return String(s).toLocaleLowerCase('tr').replace(/[çğıöşü]/g, function (c) { return { 'ç': 'c', 'ğ': 'g', 'ı': 'i', 'ö': 'o', 'ş': 's', 'ü': 'u' }[c]; });
    };
    var render = function () {
      var q = norm(input.value.trim());
      shown = data.filter(function (it) { return !q || norm(it.t + ' ' + it.k).indexOf(q) > -1; }).slice(0, 40);
      sel = Math.min(sel, Math.max(0, shown.length - 1));
      // Sonuçlar DOM düğümleri olarak kurulur (başlıklar yönetilen içeriktir: hizmet / yazı / duyuru adı); HTML metni birleştirilmez
      list.textContent = '';
      if (!shown.length) {
        var none = d.createElement('li'); none.className = 'pal__none'; none.setAttribute('role', 'status'); none.textContent = 'Sonuç bulunamadı'; list.appendChild(none);
        input.removeAttribute('aria-activedescendant');
        return;
      }
      shown.forEach(function (it, i) {
        var li = d.createElement('li'), a = d.createElement('a'), t = d.createElement('span'), k = d.createElement('small');
        li.className = 'pal__item' + (i === sel ? ' is-on' : ''); li.setAttribute('role', 'presentation'); a.setAttribute('role', 'option'); a.id = 'pal-o' + i; a.setAttribute('aria-selected', i === sel ? 'true' : 'false');
        a.setAttribute('href', it.u); t.textContent = it.t; k.textContent = it.k;
        a.appendChild(t); a.appendChild(k); li.appendChild(a); list.appendChild(li);
      });
      input.setAttribute('aria-activedescendant', 'pal-o' + sel);
    };
    var palFrom = null;
    var open = function () { palFrom = d.activeElement; pal.hidden = false; input.value = ''; sel = 0; render(); setTimeout(function () { input.focus(); }, 10); };
    var close = function () { pal.hidden = true; if (palFrom && palFrom.focus) palFrom.focus(); };
    input.setAttribute('role', 'combobox'); input.setAttribute('aria-expanded', 'true'); input.setAttribute('aria-controls', 'pal-list'); input.setAttribute('aria-autocomplete', 'list'); list.id = 'pal-list';
    $$('[data-palette-open]').forEach(function (b) { b.addEventListener('click', open); });
    pal.addEventListener('click', function (e) { if (e.target === pal) close(); });
    input.addEventListener('input', function () { sel = 0; render(); });
    input.addEventListener('keydown', function (e) {
      if (e.key === 'ArrowDown') { e.preventDefault(); sel = Math.min(sel + 1, shown.length - 1); render(); }
      if (e.key === 'ArrowUp') { e.preventDefault(); sel = Math.max(sel - 1, 0); render(); }
      if (e.key === 'Enter' && shown[sel]) { e.preventDefault(); location.href = shown[sel].u; }
      if (e.key === 'Escape') close();
      // Tab odağı paletin dışına taşımaz: sonuçlar arasında gezdirir
      if (e.key === 'Tab') { e.preventDefault(); sel = Math.max(0, Math.min(shown.length - 1, sel + (e.shiftKey ? -1 : 1))); render(); }
    });
    d.addEventListener('keydown', function (e) {
      if ((isMac ? e.metaKey : e.ctrlKey) && e.key.toLowerCase() === 'k') { e.preventDefault(); pal.hidden ? open() : close(); }
      if (e.key === 'Escape' && !pal.hidden) close();
    });
  }

  /* ---------- Kaydedilmemiş değişiklikler ---------- */
  var bar = $('[data-savebar]');
  if (bar) {
    var form = d.getElementById(bar.getAttribute('data-form'));
    var dirty = false;
    var mark = function () { if (!dirty) { dirty = true; bar.hidden = false; } };
    if (form) {
      form.addEventListener('input', mark);
      form.addEventListener('change', mark);
      form.addEventListener('rp:change', mark);
      form.addEventListener('submit', function () { dirty = false; window.onbeforeunload = null; });
      d.addEventListener('keydown', function (e) {
        if ((isMac ? e.metaKey : e.ctrlKey) && e.key.toLowerCase() === 's') {
          e.preventDefault();
          if (form.requestSubmit) form.requestSubmit(); else form.submit();
        }
      });
    }
    $('[data-savebar-reset]', bar).addEventListener('click', function () { dirty = false; location.reload(); });
    window.addEventListener('beforeunload', function (e) { if (dirty) { e.preventDefault(); e.returnValue = ''; } });
  }

  /* ---------- Onay penceresi ----------
     data-confirm (form) ve data-confirm-btn / button[data-confirm] (düğme) olan işlemler biçimli bir <dialog> ile sorulur.
     <dialog> yoksa ya da pencere kurulurken hata olursa yerel window.confirm() sorar; koruma hiçbir durumda kalkmaz. */
  var dlg = null;
  var build = function () {
    var el = function (tag, cls, txt) { var n = d.createElement(tag); if (cls) n.className = cls; if (txt) n.textContent = txt; return n; };
    var dg = el('dialog', 'dlg');
    dg.setAttribute('aria-labelledby', 'dlg-t'); dg.setAttribute('aria-describedby', 'dlg-d');
    var box = el('div', 'dlg__box');
    var bar = el('p', 'dlg__bar'); var stamp = el('span', 'dlg__stamp'); bar.appendChild(stamp);
    var t = el('h2', 'dlg__t'); t.id = 'dlg-t';
    var p = el('p', 'dlg__d'); p.id = 'dlg-d';
    var act = el('div', 'dlg__act');
    var no = el('button', 'btn btn--soft', 'Vazgeç'); no.type = 'button';
    var yes = el('button', 'btn', 'Onayla'); yes.type = 'button';
    act.appendChild(no); act.appendChild(yes);
    [bar, t, p, act].forEach(function (n) { box.appendChild(n); });
    dg.appendChild(box); d.body.appendChild(dg);
    return { dg: dg, stamp: stamp, t: t, p: p, no: no, yes: yes };
  };
  // Mesajı soru (başlık) ve ayrıntıya ayırır: "Bu kayıt silinsin mi? Geri alınamaz." → soru + ayrıntı
  var split = function (msg) {
    var parts = String(msg).replace(/\s+$/, '').match(/[^.?!\n]+[.?!]*/g) || [msg];
    parts = parts.map(function (s) { return s.trim(); }).filter(Boolean);
    var qi = -1; parts.forEach(function (s, i) { if (qi < 0 && /\?$/.test(s)) qi = i; });
    if (qi < 0) return { q: '', det: parts.join(' ') };
    var det = parts.filter(function (s, i) { return i !== qi; }).join(' ');
    return { q: parts[qi], det: det };
  };
  var DANGER = /silin|silinsin|kalıcı|geri alınamaz|kaldırıl|üzerine yaz/i;
  var ask = function (msg, o) {
    o = o || {};
    return new Promise(function (resolve) {
      // Yerel onay da bir sonraki turda sorulur: gönderim olayı sürerken aynı form yeniden gönderilemez (HTML kuralı)
      var native = function () { setTimeout(function () { resolve(window.confirm(msg)); }, 0); };
      try {
        if (typeof HTMLDialogElement === 'undefined') return native();
        if (!dlg) dlg = build();
        if (!dlg.dg.showModal) return native();
        var sp = o.title ? { q: o.title, det: o.detail || '' } : split(msg);
        if (o.more) sp.det = (sp.det ? sp.det + ' ' : '') + o.more;   // data-confirm-detail: işlemin neyi etkilediği
        var danger = o.danger !== undefined ? o.danger : DANGER.test(msg);
        var opener = o.opener || d.activeElement;
        dlg.dg.className = 'dlg' + (danger ? ' dlg--danger' : '');
        dlg.stamp.textContent = danger ? 'Dikkat' : 'Onay';
        dlg.t.textContent = sp.q || 'Onaylıyor musunuz?';
        dlg.p.textContent = sp.det; dlg.p.hidden = !sp.det;
        dlg.yes.textContent = o.yes || 'Onayla';
        dlg.yes.className = 'btn' + (danger ? ' btn--destroy' : '');
        dlg.no.textContent = o.no || 'Vazgeç';
        var done = function (ok) {
          dlg.no.removeEventListener('click', onNo); dlg.yes.removeEventListener('click', onYes);
          dlg.dg.removeEventListener('cancel', onCancel); dlg.dg.removeEventListener('click', onBack);
          if (dlg.dg.open) dlg.dg.close();
          if (opener && opener.focus && d.contains(opener)) opener.focus();
          resolve(ok);
        };
        var onNo = function () { done(false); };
        var onYes = function () { done(true); };
        var onCancel = function (e) { e.preventDefault(); done(false); };   // Esc
        var onBack = function (e) { if (e.target === dlg.dg) done(false); };   // arka plana tıklama
        dlg.no.addEventListener('click', onNo); dlg.yes.addEventListener('click', onYes);
        dlg.dg.addEventListener('cancel', onCancel); dlg.dg.addEventListener('click', onBack);
        dlg.dg.showModal();
        dlg.no.focus();   // varsayılan odak Vazgeç'te
      } catch (err) { native(); }
    });
  };
  window.admAsk = ask;
  var label = function (b) { var t = b && (b.textContent || '').replace(/\s+/g, ' ').trim(); return t ? t : 'Onayla'; };
  d.addEventListener('submit', function (e) {
    var f = e.target;
    var msg = f.getAttribute && f.getAttribute('data-confirm');
    if (!msg || f.__confirmed) return;
    e.preventDefault();
    var sb = e.submitter || null;
    ask(msg, { opener: sb || d.activeElement, more: f.getAttribute('data-confirm-detail') || '', yes: label(sb), danger: (sb && sb.classList.contains('btn--danger')) || !!f.closest('.card--danger') || DANGER.test(msg) }).then(function (ok) {
      if (!ok) return;
      f.__confirmed = true;   // aynı gönderici ve aynı alanlarla yeniden gönderilir; bu kez onay sorulmaz
      try { if (f.requestSubmit) f.requestSubmit(sb || undefined); else f.submit(); } finally { f.__confirmed = false; }
    });
  });
  d.addEventListener('click', function (e) {
    var b = e.target.closest && e.target.closest('[data-confirm-btn], button[data-confirm]');
    if (!b || b.__confirmed) return;
    var msg = b.getAttribute('data-confirm-btn') || b.getAttribute('data-confirm');
    e.preventDefault();
    ask(msg, { opener: b, more: b.getAttribute('data-confirm-detail') || '', yes: label(b) }).then(function (ok) {
      if (!ok) return;
      b.__confirmed = true;
      try { b.click(); } finally { b.__confirmed = false; }
    });
  }, true);

  /* ---------- Karakter sayacı ---------- */
  $$('[data-counter]').forEach(function (inp) {
    var max = +inp.getAttribute('maxlength');
    var c = d.createElement('span');
    c.className = 'counter';
    inp.closest('.fld').insertBefore(c, inp.nextSibling);
    var upd = function () { var n = inp.value.length; c.textContent = n + ' / ' + max; c.classList.toggle('is-over', n > max * 0.95); };
    inp.addEventListener('input', upd);
    upd();
  });

  /* ---------- Otomatik adres (slug) ---------- */
  var slugify = function (s) {
    var m = { 'ç': 'c', 'ğ': 'g', 'ı': 'i', 'i̇': 'i', 'ö': 'o', 'ş': 's', 'ü': 'u' };
    return s.toLocaleLowerCase('tr').replace(/[çğıöşü]/g, function (c) { return m[c] || c; }).normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
  };
  $$('[data-slug-from]').forEach(function (slug) {
    var src = $(slug.getAttribute('data-slug-from'));
    if (!src) return;
    var touched = slug.value !== '';
    slug.addEventListener('input', function () { touched = slug.value !== ''; });
    src.addEventListener('input', function () { if (!touched) slug.value = slugify(src.value); });
  });

  /* ---------- Tekrarlanan gruplar ---------- */
  $$('[data-rp]').forEach(function (rp) {
    var name = rp.getAttribute('data-rp-name');
    var items = $('[data-rp-items]', rp);
    var tpl = $('[data-rp-tpl]', rp);
    var min = +(rp.getAttribute('data-rp-min') || 0);
    var esc = name.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    var re = new RegExp('^' + esc + '\\[(\\d+|__i__)\\]');
    var changed = function () { rp.dispatchEvent(new CustomEvent('rp:change', { bubbles: true })); };
    var renumber = function () {
      $$('[data-rp-item]', items).forEach(function (it, i) {
        $$('[name]', it).forEach(function (f) { f.name = f.name.replace(re, name + '[' + i + ']'); });
      });
    };
    rp.addEventListener('click', function (e) {
      var t = e.target.closest('button');
      if (!t || !rp.contains(t)) return;
      var item = t.closest('[data-rp-item]');
      if (t.hasAttribute('data-rp-add')) {
        var node = tpl.content.firstElementChild.cloneNode(true);
        node.classList.add('is-new');
        items.appendChild(node);
        renumber();
        var f = $('input, textarea, select', node);
        if (f) f.focus();
        changed();
      } else if (item && t.hasAttribute('data-rp-del')) {
        if ($$('[data-rp-item]', items).length <= min) { $$('input, textarea', item).forEach(function (f) { f.value = ''; }); }
        else item.remove();
        renumber(); changed();
      } else if (item && t.hasAttribute('data-rp-up') && item.previousElementSibling) {
        items.insertBefore(item, item.previousElementSibling); renumber(); changed(); t.focus();
      } else if (item && t.hasAttribute('data-rp-down') && item.nextElementSibling) {
        items.insertBefore(item.nextElementSibling, item); renumber(); changed(); t.focus();
      }
    });
    // Sürükle bırak: yalnızca tutamaçtan başlar
    var dragEl = null;
    rp.addEventListener('pointerdown', function (e) {
      var h = e.target.closest('[data-rp-handle]');
      if (h && rp.contains(h)) h.closest('[data-rp-item]').setAttribute('draggable', 'true');
    });
    items.addEventListener('dragstart', function (e) {
      dragEl = e.target.closest('[data-rp-item]');
      if (!dragEl) return;
      dragEl.classList.add('is-drag');
      e.dataTransfer.effectAllowed = 'move';
      try { e.dataTransfer.setData('text/plain', ''); } catch (er) {}
    });
    items.addEventListener('dragover', function (e) {
      if (!dragEl) return;
      e.preventDefault();
      var over = e.target.closest('[data-rp-item]');
      if (!over || over === dragEl) return;
      var r = over.getBoundingClientRect();
      items.insertBefore(dragEl, e.clientY < r.top + r.height / 2 ? over : over.nextSibling);
    });
    items.addEventListener('dragend', function () {
      if (!dragEl) return;
      dragEl.classList.remove('is-drag');
      dragEl.removeAttribute('draggable');
      dragEl = null;
      renumber(); changed();
    });
    renumber();
  });

  /* ---------- Görsel alanı ---------- */
  $$('[data-img]').forEach(function (box) {
    var input = $('.img__input', box);
    var prev = $('[data-img-preview]', box);
    var text = $('[data-img-text]', box);
    var rem = $('[data-img-remove]', box);
    var drop = $('.img__drop', box);
    input.addEventListener('change', function () {
      var f = input.files && input.files[0];
      if (!f) return;
      if (!/^image\/(jpeg|png|webp)$/.test(f.type)) { alert('Yalnızca JPG, PNG ya da WebP görsel seçebilirsiniz.'); input.value = ''; return; }
      prev.innerHTML = '<img alt="" src="' + URL.createObjectURL(f) + '">';
      box.classList.add('has-img');
      rem.value = '0';
      text.textContent = f.name + ' (' + Math.max(1, Math.round(f.size / 1024)) + ' KB) kaydedince yüklenecek';
    });
    ['dragenter', 'dragover'].forEach(function (t) { drop.addEventListener(t, function () { drop.classList.add('is-over'); }); });
    ['dragleave', 'drop'].forEach(function (t) { drop.addEventListener(t, function () { drop.classList.remove('is-over'); }); });
    var del = $('[data-img-del]', box);
    if (del) del.addEventListener('click', function () {
      rem.value = '1'; input.value = ''; prev.innerHTML = ''; box.classList.remove('has-img');
      text.textContent = 'Görsel kaldırılacak. Yeni görsel seçmek için tıklayın.';
      box.dispatchEvent(new Event('change', { bubbles: true }));
    });
  });

  /* ---------- Zengin metin editörü ---------- */
  $$('[data-rt-wrap]').forEach(function (wrap) {
    var area = $('[data-rt-area]', wrap);
    var out = $('[data-rt-out]', wrap);
    var sync = function () { out.value = area.innerHTML; };
    try { d.execCommand('defaultParagraphSeparator', false, 'p'); } catch (e) {}
    if (!area.innerHTML.trim()) area.innerHTML = '<p><br></p>';
    var exec = function (cmd, val) { area.focus(); d.execCommand(cmd, false, val); sync(); area.dispatchEvent(new Event('input', { bubbles: true })); };
    var linkBar = null;
    $$('[data-rt]', wrap).forEach(function (b) {
      b.addEventListener('mousedown', function (e) { e.preventDefault(); });
      b.addEventListener('click', function () {
        var c = b.getAttribute('data-rt');
        if (c === 'h2' || c === 'h3' || c === 'p') exec('formatBlock', '<' + c + '>');
        else if (c === 'quote') exec('formatBlock', '<blockquote>');
        else if (c === 'bold') exec('bold');
        else if (c === 'italic') exec('italic');
        else if (c === 'ul') exec('insertUnorderedList');
        else if (c === 'ol') exec('insertOrderedList');
        else if (c === 'clear') { exec('removeFormat'); exec('formatBlock', '<p>'); }
        else if (c === 'link') {
          var s = window.getSelection();
          if (!s || s.isCollapsed) { alert('Önce bağlantı vermek istediğiniz metni seçin.'); return; }
          var range = s.getRangeAt(0).cloneRange();
          if (linkBar) linkBar.remove();
          linkBar = d.createElement('div');
          linkBar.className = 'fld';
          linkBar.style.cssText = 'padding:8px;border-bottom:1px solid var(--line);background:#fff;display:flex;gap:8px';
          linkBar.innerHTML = '<input class="inp" type="url" placeholder="https://..." style="flex:1"><button type="button" class="btn btn--sm">Ekle</button><button type="button" class="btn btn--ghost btn--sm">Vazgeç</button>';
          wrap.insertBefore(linkBar, area);
          var li = $('input', linkBar);
          li.focus();
          var done = function (ok) {
            if (ok && /^(https?:\/\/|mailto:|tel:|\/)/i.test(li.value.trim())) {
              var sel = window.getSelection(); sel.removeAllRanges(); sel.addRange(range);
              exec('createLink', li.value.trim());
            }
            linkBar.remove(); linkBar = null;
          };
          $$('button', linkBar)[0].addEventListener('click', function () { done(true); });
          $$('button', linkBar)[1].addEventListener('click', function () { done(false); });
          li.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); done(true); } if (e.key === 'Escape') done(false); });
        }
      });
    });
    // Yapıştırılan metin düz metne çevrilir (Word biçimleri taşınmaz); boş satırlar paragraf olur
    area.addEventListener('paste', function (e) {
      e.preventDefault();
      var text = (e.clipboardData || window.clipboardData).getData('text/plain');
      var html = text.split(/\n{2,}/).map(function (p) { return '<p>' + p.replace(/[&<>]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;' }[c]; }).replace(/\n/g, '<br>') + '</p>'; }).join('');
      d.execCommand('insertHTML', false, html);
      sync();
    });
    area.addEventListener('input', sync);
    area.addEventListener('keydown', function (e) {
      if ((isMac ? e.metaKey : e.ctrlKey) && e.key.toLowerCase() === 'b') { e.preventDefault(); exec('bold'); }
      if ((isMac ? e.metaKey : e.ctrlKey) && e.key.toLowerCase() === 'i') { e.preventDefault(); exec('italic'); }
    });
    // Etkin biçimi araç çubuğunda göster
    d.addEventListener('selectionchange', function () {
      if (!area.contains(d.activeElement) && d.activeElement !== area) return;
      var block = (d.queryCommandValue('formatBlock') || '').toLowerCase();
      $$('[data-rt]', wrap).forEach(function (b) {
        var c = b.getAttribute('data-rt'), on = false;
        if (c === 'bold' || c === 'italic') on = d.queryCommandState(c);
        else if (c === 'ul') on = d.queryCommandState('insertUnorderedList');
        else if (c === 'ol') on = d.queryCommandState('insertOrderedList');
        else if (c === 'quote') on = block === 'blockquote';
        else if (c === 'h2' || c === 'h3') on = block === c;
        b.classList.toggle('is-on', on);
      });
    });
    var form = area.closest('form');
    if (form) form.addEventListener('submit', sync);
  });
})();
