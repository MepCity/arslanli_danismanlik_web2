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
  $$('[data-side-open]').forEach(function (b) { b.addEventListener('click', function () { app.classList.add('is-nav'); }); });
  $$('[data-side-close]').forEach(function (b) { b.addEventListener('click', function () { app.classList.remove('is-nav'); }); });

  /* ---------- Üst çubuk gölgesi ---------- */
  var top = $('.top');
  if (top) {
    var onScroll = function () { top.classList.toggle('is-stuck', window.scrollY > 4); };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }

  /* ---------- Bildirim ---------- */
  var toast = $('[data-toast]');
  if (toast) setTimeout(function () { toast.classList.add('is-out'); setTimeout(function () { toast.remove(); }, 400); }, 4200);

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
        var none = d.createElement('li'); none.className = 'pal__none'; none.textContent = 'Sonuç bulunamadı'; list.appendChild(none);
        return;
      }
      shown.forEach(function (it, i) {
        var li = d.createElement('li'), a = d.createElement('a'), t = d.createElement('span'), k = d.createElement('small');
        li.className = 'pal__item' + (i === sel ? ' is-on' : ''); li.setAttribute('role', 'option');
        a.setAttribute('href', it.u); t.textContent = it.t; k.textContent = it.k;
        a.appendChild(t); a.appendChild(k); li.appendChild(a); list.appendChild(li);
      });
    };
    var open = function () { pal.hidden = false; input.value = ''; sel = 0; render(); setTimeout(function () { input.focus(); }, 10); };
    var close = function () { pal.hidden = true; };
    $$('[data-palette-open]').forEach(function (b) { b.addEventListener('click', open); });
    pal.addEventListener('click', function (e) { if (e.target === pal) close(); });
    input.addEventListener('input', function () { sel = 0; render(); });
    input.addEventListener('keydown', function (e) {
      if (e.key === 'ArrowDown') { e.preventDefault(); sel = Math.min(sel + 1, shown.length - 1); render(); }
      if (e.key === 'ArrowUp') { e.preventDefault(); sel = Math.max(sel - 1, 0); render(); }
      if (e.key === 'Enter' && shown[sel]) { e.preventDefault(); location.href = shown[sel].u; }
      if (e.key === 'Escape') close();
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

  /* ---------- Silme onayı ---------- */
  d.addEventListener('submit', function (e) {
    var f = e.target;
    var msg = f.getAttribute && f.getAttribute('data-confirm');
    if (msg && !window.confirm(msg)) e.preventDefault();
  });

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
