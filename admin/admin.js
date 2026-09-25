(function () {
  var form = document.getElementById('editor');
  if (!form) return;

  var dirty = false;
  var hint = form.querySelector('.dirty-hint');
  function markDirty() { dirty = true; if (hint) hint.hidden = false; }
  form.addEventListener('input', markDirty);
  form.addEventListener('change', markDirty);
  form.addEventListener('submit', function () { dirty = false; });
  window.addEventListener('beforeunload', function (e) { if (dirty) { e.preventDefault(); e.returnValue = ''; } });

  // Titel in der zugeklappten Zeile mitschreiben
  form.addEventListener('input', function (e) {
    var item = e.target.closest('.item');
    if (!item) return;
    var t = item.querySelector('[data-title-from]');
    var key = t && t.getAttribute('data-title-from');
    if (key && e.target.name && e.target.name.slice(-(key.length + 2)) === '[' + key + ']') {
      var v = e.target.value.replace(/\s+/g, ' ').trim();
      t.textContent = v ? (v.length > 70 ? v.slice(0, 69) + '…' : v) : 'Neuer Eintrag';
    }
  });

  // Einträge
  var counter = Date.now();
  form.addEventListener('click', function (e) {
    var btn = e.target.closest('button');
    if (!btn) return;

    if (btn.hasAttribute('data-add')) {
      var list = btn.closest('.list');
      var tpl = list.querySelector('template');
      var html = tpl.innerHTML.replace(/__IDX__/g, 'n' + (counter++));
      var wrap = document.createElement('div');
      wrap.innerHTML = html.trim();
      var node = wrap.firstElementChild;
      var items = list.querySelector('.items');
      if (list.dataset.prepend === '1') items.prepend(node); else items.append(node);
      var empty = list.querySelector('.empty');
      if (empty) empty.remove();
      node.classList.add('flash-new');
      node.scrollIntoView({ behavior: 'smooth', block: 'center' });
      var first = node.querySelector('input[type=text], textarea');
      if (first) setTimeout(function () { first.focus({ preventScroll: true }); }, 300);
      markDirty();
    }

    if (btn.hasAttribute('data-move')) {
      var it = btn.closest('.item');
      var dir = btn.getAttribute('data-move');
      if (dir === '-1' && it.previousElementSibling) it.parentNode.insertBefore(it, it.previousElementSibling);
      if (dir === '1' && it.nextElementSibling) it.parentNode.insertBefore(it.nextElementSibling, it);
      btn.focus();
      markDirty();
    }

    if (btn.hasAttribute('data-delete')) {
      var del = btn.closest('.item');
      var name = del.querySelector('.item-title').textContent;
      if (confirm('„' + name + '“ wirklich löschen?\n\nGelöscht ist es erst, wenn Sie danach auf „Änderungen speichern“ klicken.')) {
        del.remove();
        markDirty();
      }
    }

    if (btn.hasAttribute('data-remove-img')) {
      var f = btn.closest('.img-field');
      f.querySelector('input[type=hidden]').value = '';
      f.querySelector('.img-preview').innerHTML = '<span>Kein Bild</span>';
      f.classList.remove('has-img');
      markDirty();
    }
  });

  // Fotos hochladen
  form.addEventListener('change', function (e) {
    var input = e.target;
    if (!input.hasAttribute('data-upload') || !input.files.length) return;
    var field = input.closest('.img-field');
    var status = field.querySelector('.img-status');
    var save = form.querySelector('.savebar .btn');
    status.className = 'img-status';
    status.textContent = 'Foto wird hochgeladen …';
    save.disabled = true;

    var data = new FormData();
    data.append('bild', input.files[0]);
    data.append('csrf', window.CSRF);

    fetch('upload.php', { method: 'POST', body: data, credentials: 'same-origin' })
      .then(function (r) { return r.json().catch(function () { return { fehler: 'Das Foto ist zu groß oder der Server hat nicht geantwortet.' }; }); })
      .then(function (res) {
        if (res.pfad) {
          field.querySelector('input[type=hidden]').value = res.pfad;
          field.querySelector('.img-preview').innerHTML = '<img src="../' + res.pfad + '" alt="">';
          field.classList.add('has-img');
          status.textContent = 'Fertig. Zum Übernehmen unten speichern.';
          markDirty();
        } else {
          status.className = 'img-status err';
          status.textContent = res.fehler || 'Das hat nicht geklappt.';
        }
      })
      .catch(function () {
        status.className = 'img-status err';
        status.textContent = 'Keine Verbindung. Bitte noch einmal versuchen.';
      })
      .finally(function () { save.disabled = false; input.value = ''; });
  });
})();
