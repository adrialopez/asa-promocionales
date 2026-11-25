/* ASA Core: atribución, consentimiento, lista de cotización, formularios y WhatsApp. */
(() => {
  const cfg = window.ASA || {};
  const DAYS90 = 90 * 864e5;
  const read = (s, k) => { try { return JSON.parse(s.getItem(k)); } catch { return null; } };
  const write = (s, k, v) => { try { s.setItem(k, JSON.stringify(v)); } catch { /* sin almacenamiento */ } };
  const dl = (event, data = {}) => (window.dataLayer = window.dataLayer || []).push({ event, ...data });

  /* ---------- Origen de la visita ---------- */
  const consent = () => read(localStorage, 'asa_consent');
  function currentTouch() {
    const q = new URLSearchParams(location.search);
    const t = {};
    ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'gclid', 'gbraid', 'wbraid', 'fbclid', 'msclkid']
      .forEach(k => { if (q.get(k)) t[k] = q.get(k).slice(0, 250); });
    try {
      const host = document.referrer && new URL(document.referrer).hostname;
      if (host && host !== location.hostname) t.ref = host;
    } catch { /* referencia no válida */ }
    return t;
  }
  function loadAttr() {
    let a = read(sessionStorage, 'asa_attr');
    if (consent() === true) {
      const saved = read(localStorage, 'asa_attr');
      if (saved && Date.now() - saved.at < DAYS90) a = saved;
    }
    return a || { first: null, last: null };
  }
  let attr = loadAttr();
  function saveAttr() {
    attr.at = attr.at || Date.now();
    write(sessionStorage, 'asa_attr', attr);
    if (consent() === true) write(localStorage, 'asa_attr', attr);
  }
  const touch = currentTouch();
  const external = Object.keys(touch).length > 0;
  const stamped = { ...touch, landing: location.pathname.slice(0, 250), ts: new Date().toISOString() };
  if (!attr.first) attr.first = stamped;
  if (external || !attr.last) attr.last = stamped;
  saveAttr();
  const attribution = () => JSON.stringify({ first: attr.first, last: attr.last, consent: consent() === true });

  if (consent() === null) {
    const b = document.createElement('div');
    b.className = 'consent';
    b.innerHTML = `<p>Podemos recordar durante 90 días cómo llegaste para saber qué canales nos funcionan. <a href="${cfg.privacy}">Aviso de privacidad</a></p>
      <div><button type="button" class="btn btn--sm" data-c="1">Aceptar</button><button type="button" class="btn btn--sm btn--outline" data-c="0">Rechazar</button></div>`;
    b.addEventListener('click', e => {
      const c = e.target.dataset.c;
      if (c === undefined) return;
      write(localStorage, 'asa_consent', c === '1');
      if (c === '1') saveAttr(); else localStorage.removeItem('asa_attr');
      b.remove();
    });
    document.body.append(b);
  }

  /* ---------- Lista de cotización ---------- */
  let list = read(localStorage, 'asa_list') || [];
  const saveList = () => { write(localStorage, 'asa_list', list); paintCount(); };
  const paintCount = () => document.querySelectorAll('[data-count]').forEach(n => { n.textContent = list.length; n.hidden = !list.length; });
  const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

  function toast(html) {
    document.querySelector('.toast')?.remove();
    const t = document.createElement('div');
    t.className = 'toast'; t.setAttribute('role', 'status'); t.innerHTML = html;
    document.body.append(t);
    setTimeout(() => t.remove(), 4500);
  }

  function renderList() {
    const box = document.getElementById('asa-list');
    if (!box) return;
    if (!list.length) {
      box.innerHTML = `<div class="empty"><p>Aún no agregas productos. También puedes describir lo que necesitas en el mensaje.</p><a class="btn btn--outline" href="${esc(box.dataset.catalog)}">Explorar el catálogo</a></div>`;
      return;
    }
    const techs = ['', 'Serigrafía', 'Bordado', 'Grabado láser', 'Sublimación', 'Tampografía'];
    box.innerHTML = list.map((i, n) => `<div class="line" data-n="${n}">
      <img src="${esc(i.img)}" alt="" width="72" height="72">
      <div><strong>${esc(i.name)}</strong>${i.color ? `<small>Color: ${esc(i.color)}</small>` : ''}
        <div class="line__f"><label>Cantidad<input type="number" min="1" data-f="qty" value="${esc(i.qty)}"></label>
        <label>Técnica<select data-f="tech">${techs.map(t => `<option value="${t}"${t === i.tech ? ' selected' : ''}>${t || 'Que me asesoren'}</option>`).join('')}</select></label></div></div>
      <button type="button" class="line__x" data-del aria-label="Quitar ${esc(i.name)}">&times;</button></div>`).join('');
  }

  document.addEventListener('input', e => {
    const line = e.target.closest('.line');
    if (line && e.target.dataset.f) { list[line.dataset.n][e.target.dataset.f] = e.target.value; saveList(); }
  });

  document.addEventListener('click', e => {
    const add = e.target.closest('[data-add]');
    if (add) {
      const scope = add.closest('[data-product]');
      const color = scope?.querySelector('[data-variant].is-on')?.dataset.label || '';
      const qty = Math.max(1, parseInt(scope?.querySelector('[data-qty]')?.value, 10) || 100);
      const id = Number(add.dataset.add);
      const found = list.find(i => i.id === id && i.color === color);
      if (found) found.qty = qty; else list.push({ id, name: add.dataset.name, img: add.dataset.img, qty, color, tech: '' });
      saveList();
      dl('add_to_quote', { product_id: id });
      toast(`<span><strong>${esc(add.dataset.name)}</strong> está en tu lista.</span><a href="${cfg.quote}">Ver lista</a>`);
      return;
    }
    const del = e.target.closest('[data-del]');
    if (del) { list.splice(del.closest('.line').dataset.n, 1); saveList(); renderList(); return; }

    const wa = e.target.closest('[data-wa]');
    if (wa) {
      e.preventDefault();
      const abc = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
      const folio = 'W-' + Array.from(crypto.getRandomValues(new Uint8Array(6)), v => abc[v % abc.length]).join('');
      const product = wa.dataset.product;
      const items = product ? [{ id: Number(wa.dataset.productId), qty: 1 }] : list;
      const text = `Hola ASA, ${product ? `me interesa cotizar: ${product}` : 'quiero cotizar artículos promocionales'}. Folio ${folio}`;
      const body = new FormData();
      body.set('folio', folio); body.set('attribution', attribution()); body.set('page', location.pathname);
      body.set('items', JSON.stringify(items));
      navigator.sendBeacon(cfg.rest + 'wa', body);
      dl('whatsapp_click', { folio });
      // Con el número de ejemplo se abre WhatsApp sin destinatario.
      const to = /^520+$/.test(cfg.whatsapp) ? '' : cfg.whatsapp;
      window.open(`https://wa.me/${to}?text=${encodeURIComponent(text)}`, '_blank', 'noopener');
    }
  });

  /* ---------- Formularios ---------- */
  document.querySelectorAll('.asa-form').forEach(form => {
    const started = Date.now();
    form.addEventListener('submit', async e => {
      e.preventDefault();
      const msg = form.querySelector('.form-msg');
      const btn = form.querySelector('[type=submit]');
      const type = form.dataset.type;
      const body = new FormData(form);
      body.set('type', type);
      body.set('elapsed', Math.round((Date.now() - started) / 1000));
      body.set('attribution', attribution());
      body.set('page', location.pathname);
      if (type === 'cotizacion') body.set('items', JSON.stringify(list));
      btn.disabled = true; msg.className = 'form-msg'; msg.textContent = 'Enviando…';
      try {
        const res = await fetch(cfg.rest + 'lead', { method: 'POST', body, credentials: 'omit' });
        const data = await res.json();
        if (!data.ok) throw new Error(data.message);
        form.reset();
        if (type === 'cotizacion') { list = []; saveList(); renderList(); }
        msg.className = 'form-msg ok';
        msg.textContent = `Recibimos tu solicitud. Tu folio es ${data.folio}.`;
        dl('generate_lead', { folio: data.folio, lead_type: type });
      } catch (err) {
        msg.className = 'form-msg error';
        msg.textContent = err.message && !(err instanceof TypeError) ? err.message : 'No pudimos enviar tu solicitud. Revisa tu conexión.';
      } finally { btn.disabled = false; }
    });
  });

  paintCount();
  renderList();
})();
