document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', event => {
      if (!confirm(el.getAttribute('data-confirm') || 'Tens a certeza?')) event.preventDefault();
    });
  });
  document.querySelectorAll('[data-gallery-image]').forEach(button => {
    button.addEventListener('click', () => {
      const main = document.getElementById('imagem-principal');
      if (!main) return;
      main.src = button.dataset.galleryImage;
      document.querySelectorAll('[data-gallery-image]').forEach(b => b.classList.remove('active'));
      button.classList.add('active');
    });
  });
});
