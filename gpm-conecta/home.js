(() => {
  const header = document.querySelector('.site-header');
  const toggle = document.querySelector('.menu-toggle');
  const nav = document.querySelector('.nav');
  const updateHeader = () => header?.classList.toggle('scrolled', window.scrollY > 30);
  updateHeader();
  window.addEventListener('scroll', updateHeader, { passive: true });

  if (toggle && nav && header) {
    toggle.addEventListener('click', () => {
      const open = header.classList.toggle('menu-open');
      toggle.setAttribute('aria-expanded', String(open));
      toggle.setAttribute('aria-label', open ? 'Fechar menu' : 'Abrir menu');
    });
    nav.querySelectorAll('a').forEach(link => link.addEventListener('click', () => {
      header.classList.remove('menu-open');
      toggle.setAttribute('aria-expanded', 'false');
      toggle.setAttribute('aria-label', 'Abrir menu');
    }));
  }

  const revealItems = document.querySelectorAll('.reveal');
  if ('IntersectionObserver' in window && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    const observer = new IntersectionObserver(entries => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('visible');
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.12 });
    revealItems.forEach(item => observer.observe(item));
  } else {
    revealItems.forEach(item => item.classList.add('visible'));
  }

  const form = document.querySelector('#enquiry-form');
  const status = document.querySelector('#form-status');
  form?.addEventListener('submit', async event => {
    event.preventDefault();
    if (!form.reportValidity()) return;
    const data = new FormData(form);
    const lines = [
      'PEDIDO DE CONTACTO | GPM CONECTA',
      'Nome: ' + data.get('name'),
      'Empresa: ' + data.get('company'),
      'Email: ' + data.get('email'),
      'Telefone: ' + (data.get('phone') || 'Não indicado'),
      'Área: ' + data.get('area'),
      'Mensagem: ' + data.get('message')
    ];
    const summary = lines.join('\n');
    try {
      await navigator.clipboard.writeText(summary);
      status.textContent = 'Pedido preparado e copiado. Ainda não foi enviado à GPM: a submissão online será ativada quando o formulário estiver ligado ao backend.';
    } catch {
      const helper = document.createElement('textarea');
      helper.value = summary;
      helper.setAttribute('readonly', '');
      helper.style.position = 'absolute';
      helper.style.left = '-9999px';
      document.body.appendChild(helper);
      helper.select();
      const copied = document.execCommand('copy');
      helper.remove();
      status.textContent = copied
        ? 'Pedido preparado e copiado. Ainda não foi enviado à GPM; a submissão online será ativada após a integração.'
        : 'Não foi possível copiar automaticamente. Reveja os campos e tente novamente.';
    }
  });

  const year = document.querySelector('#year');
  if (year) year.textContent = String(new Date().getFullYear());
})();