(() => {
  const burger = document.querySelector('.burger');
  const nav = document.getElementById('nav');
  burger?.addEventListener('click', () => {
    const open = nav.classList.toggle('is-open');
    burger.setAttribute('aria-expanded', open);
  });
  const photo = document.getElementById('photo');
  document.addEventListener('click', e => {
    const b = e.target.closest('[data-photo]');
    if (!b || !photo) return;
    photo.src = b.dataset.photo;
    if (b.hasAttribute('data-variant')) {
      document.querySelectorAll('[data-variant]').forEach(v => { v.classList.toggle('is-on', v === b); v.setAttribute('aria-pressed', v === b); });
    }
  });
})();
