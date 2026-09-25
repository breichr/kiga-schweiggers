(function () {
  // Menü auf kleinen Bildschirmen
  var toggle = document.querySelector('.nav-toggle');
  var nav = document.getElementById('hauptnav');
  if (toggle && nav) {
    toggle.addEventListener('click', function () {
      var open = nav.classList.toggle('open');
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      toggle.textContent = open ? 'Schließen' : 'Menü';
    });
    nav.addEventListener('click', function (e) {
      if (e.target.tagName === 'A') {
        nav.classList.remove('open');
        toggle.setAttribute('aria-expanded', 'false');
        toggle.textContent = 'Menü';
      }
    });
  }

  // Bilder vergrößern
  var lb = document.querySelector('.lightbox');
  if (!lb) return;
  var lbImg = lb.querySelector('img');
  var lastFocus = null;
  function close() { lb.hidden = true; lbImg.src = ''; if (lastFocus) lastFocus.focus(); }
  document.querySelectorAll('img.zoomable').forEach(function (img) {
    img.tabIndex = 0;
    img.setAttribute('role', 'button');
    img.setAttribute('aria-label', (img.alt ? img.alt + ' – ' : '') + 'Bild vergrößern');
    function open() { lastFocus = img; lbImg.src = img.currentSrc || img.src; lbImg.alt = img.alt; lb.hidden = false; lb.querySelector('.lb-close').focus(); }
    img.addEventListener('click', open);
    img.addEventListener('keydown', function (e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); open(); } });
  });
  lb.addEventListener('click', function (e) { if (e.target !== lbImg) close(); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !lb.hidden) close(); });
})();
