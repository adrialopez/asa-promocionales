(function () {
  'use strict';
  var C = window.ASA || {};
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };
  var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* ---------- Google Ads (solo si hay ID real) ---------- */
  var gadsOn = C.gadsId && !/X{4,}/.test(C.gadsId);
  window.dataLayer = window.dataLayer || [];
  window.gtag = function () { window.dataLayer.push(arguments); };
  if (gadsOn) {
    var s = document.createElement('script');
    s.async = true; s.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(C.gadsId);
    document.head.appendChild(s);
    gtag('js', new Date()); gtag('config', C.gadsId);
  }
  function track(evt, params) {
    gtag('event', evt, params || {});
    var label = C.gadsConversions && C.gadsConversions[evt];
    if (gadsOn && label && !/X{4,}/.test(label)) gtag('event', 'conversion', { send_to: label });
    if (!gadsOn && window.console) console.info('[maqueta] evento', evt, params || {});
  }

  /* ---------- WhatsApp ---------- */
  $$('[data-wa]').forEach(function (a) {
    a.href = 'https://wa.me/' + C.whatsapp + '?text=' + encodeURIComponent(a.getAttribute('data-wa'));
    a.target = '_blank'; a.rel = 'noopener';
    a.addEventListener('click', function () { track('whatsapp_click', { origen: a.getAttribute('data-track') }); });
  });

  /* ---------- Menú móvil ---------- */
  var burger = $('#burger'), nav = $('#nav');
  burger.addEventListener('click', function () {
    var open = nav.classList.toggle('is-open');
    burger.setAttribute('aria-expanded', open);
    burger.querySelector('use').setAttribute('href', open ? '#i-close' : '#i-menu');
  });
  $$('#nav a').forEach(function (a) { a.addEventListener('click', function () {
    nav.classList.remove('is-open'); burger.setAttribute('aria-expanded', 'false');
    burger.querySelector('use').setAttribute('href', '#i-menu');
  }); });
  window.addEventListener('scroll', function () {
    $('#header').classList.toggle('is-stuck', window.scrollY > 10);
  }, { passive: true });

  /* ---------- Banner de promociones ---------- */
  var slides = $$('.slide'), dotsBox = $('#heroDots'), cur = 0, timer = null, hero = $('.hero');
  slides.forEach(function (sl, i) {
    var d = document.createElement('button');
    d.setAttribute('role', 'tab'); d.setAttribute('aria-label', 'Promoción ' + (i + 1));
    d.addEventListener('click', function () { go(i, true); });
    dotsBox.appendChild(d);
  });
  var dots = $$('button', dotsBox);
  function go(i, user) {
    cur = (i + slides.length) % slides.length;
    slides.forEach(function (s, k) {
      s.classList.toggle('is-active', k === cur);
      s.setAttribute('aria-hidden', k === cur ? 'false' : 'true');
    });
    dots.forEach(function (d, k) { d.setAttribute('aria-selected', k === cur); d.classList.toggle('on', k === cur); });
    if (user) restart();
  }
  function restart() {
    clearInterval(timer);
    if (!reduce && C.heroAutoplayMs) timer = setInterval(function () { go(cur + 1); }, C.heroAutoplayMs);
  }
  $('#heroPrev').addEventListener('click', function () { go(cur - 1, true); });
  $('#heroNext').addEventListener('click', function () { go(cur + 1, true); });
  hero.addEventListener('mouseenter', function () { clearInterval(timer); });
  hero.addEventListener('mouseleave', restart);
  hero.addEventListener('focusin', function () { clearInterval(timer); });
  var x0 = null;
  hero.addEventListener('touchstart', function (e) { x0 = e.touches[0].clientX; }, { passive: true });
  hero.addEventListener('touchend', function (e) {
    if (x0 === null) return;
    var dx = e.changedTouches[0].clientX - x0;
    if (Math.abs(dx) > 40) go(cur + (dx < 0 ? 1 : -1), true);
    x0 = null;
  });
  $$('[data-promo]').forEach(function (a) {
    a.addEventListener('click', function () { track('select_promotion', { promotion_name: a.getAttribute('data-promo') }); });
  });
  go(0); restart();

  /* ---------- Productos destacados ---------- */
  var products = [
    { n: 'Audífonos inalámbricos', c: 'Tecnología', i: 'audifonos.jpg', ref: 'MOP020' },
    { n: 'Bolígrafo metálico', c: 'Escritura', i: 'pluma.jpg' },
    { n: 'Bolsa térmica', c: 'Lonchera y outdoor', i: 'termica.jpg' },
    { n: 'Mochila roll-top', c: 'Mochilas y bolsas', i: 'mochila.jpg' },
    { n: 'Libreta ejecutiva', c: 'Oficina', i: 'libreta.jpg' }
  ];
  $('#productGrid').innerHTML = products.map(function (p) {
    var name = p.n + (p.ref ? ' ' + p.ref : '');
    return '<article class="card"><div class="card__img"><img src="img/' + p.i + '" alt="' + p.n + '" loading="lazy"></div>' +
      '<div class="card__body"><span class="tag">' + p.c + '</span><h3>' + p.n + '</h3>' +
      '<a class="link" href="#contacto" data-product="' + name + '">Cotizar <svg class="ico"><use href="#i-arrow"/></svg></a></div></article>';
  }).join('');

  /* Cualquier enlace con data-product precarga el formulario */
  document.addEventListener('click', function (e) {
    var a = e.target.closest('[data-product]');
    if (!a) return;
    var p = a.getAttribute('data-product'), msg = $('#f-msg');
    if (msg && !msg.value) msg.value = 'Me interesa cotizar: ' + p + '. Cantidad aproximada: ';
    var sel = $('#f-interes'), opt = $$('option', sel).filter(function (o) { return o.textContent === p; })[0];
    if (opt) sel.value = opt.textContent;
  });

  /* ---------- Catálogo ---------- */
  $('#catalogPdf').addEventListener('click', function (e) {
    track('catalog_download');
    if (!C.catalogPdf) {
      e.preventDefault();
      alert('Maqueta: aquí se descargará el catálogo en PDF. Falta indicar la URL en js/config.js (catalogPdf).');
    }
  });
  if (C.catalogPdf) $('#catalogPdf').href = C.catalogPdf;

  /* ---------- Validación y envío ---------- */
  function setMsg(el, text, ok) { el.textContent = text; el.className = el.className.replace(/\s(ok|err)$/, '') + (ok ? ' ok' : ' err'); }
  var emailRx = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

  $('#newsForm').addEventListener('submit', function (e) {
    e.preventDefault();
    var em = $('#newsEmail').value.trim(), msg = $('#newsMsg');
    if (!emailRx.test(em)) { setMsg(msg, 'Escribe un correo válido.', false); return; }
    if (!C.mailchimpUrl) { setMsg(msg, 'Maqueta: suscripción simulada. Falta la URL de Mailchimp en js/config.js.', true); track('newsletter_signup'); return; }
    // Mailchimp admite POST sin CORS vía JSONP; aquí se envía en segundo plano con no-cors.
    var body = new URLSearchParams(new FormData(this));
    fetch(C.mailchimpUrl, { method: 'POST', mode: 'no-cors', body: body })
      .then(function () { setMsg(msg, 'Listo. Revisa tu correo para confirmar la suscripción.', true); track('newsletter_signup'); e.target.reset(); })
      .catch(function () { setMsg(msg, 'No se pudo suscribir. Inténtalo de nuevo.', false); });
  });

  $('#contactForm').addEventListener('submit', function (e) {
    e.preventDefault();
    var f = e.target, msg = $('#formMsg'), ok = true;
    ['nombre', 'email', 'mensaje'].forEach(function (n) {
      var el = f.elements[n], bad = !el.value.trim() || (n === 'email' && !emailRx.test(el.value));
      el.classList.toggle('invalid', bad); if (bad) ok = false;
    });
    if (!f.elements.privacidad.checked) ok = false;
    if (!ok) { setMsg(msg, 'Revisa los campos marcados y acepta el aviso de privacidad.', false); return; }
    var data = Object.fromEntries(new FormData(f).entries());
    var done = function () { setMsg(msg, 'Gracias. Recibimos tu solicitud y te responderemos pronto.', true); track('generate_lead', { interes: data.interes || '' }); f.reset(); };
    if (!C.contactEndpoint) { console.info('[maqueta] datos del formulario', data); done(); return; }
    fetch(C.contactEndpoint, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) })
      .then(function (r) { if (!r.ok) throw 0; done(); })
      .catch(function () { setMsg(msg, 'No se pudo enviar. Inténtalo de nuevo o escríbenos por WhatsApp.', false); });
  });
})();
