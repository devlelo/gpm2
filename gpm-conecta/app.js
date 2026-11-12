(() => {
  const toggle = document.querySelector('.menu-toggle');
  const nav = document.querySelector('.main-nav');
  if (toggle && nav) {
    toggle.addEventListener('click', () => {
      const open = nav.classList.toggle('open');
      toggle.setAttribute('aria-expanded', String(open));
      toggle.setAttribute('aria-label', open ? 'Fechar menu' : 'Abrir menu');
      toggle.textContent = open ? '×' : '≡';
    });
    nav.querySelectorAll('a').forEach(link => link.addEventListener('click', () => {
      nav.classList.remove('open');
      toggle.setAttribute('aria-expanded', 'false');
      toggle.setAttribute('aria-label', 'Abrir menu');
      toggle.textContent = '≡';
    }));
  }

  const areaData = {
    construction: {
      title: 'Construção e materiais',
      description: 'Materiais, componentes e soluções para obras e projetos de construção. A disponibilidade e as especificações são confirmadas pela equipa GPM após receber o pedido.',
      code: 'AREA 01 / CONSTRUÇÃO'
    },
    hospitality: {
      title: 'Hotelaria e alojamento',
      description: 'Equipamentos, mobiliário, consumíveis e soluções para preparar e operar unidades de alojamento e espaços de hospitalidade.',
      code: 'AREA 02 / HOTELARIA'
    },
    worksites: {
      title: 'Estaleiros e operações',
      description: 'Artigos e equipamentos de apoio ao funcionamento de estaleiros, equipas no terreno e operações empresariais.',
      code: 'AREA 03 / ESTALEIROS'
    },
    offices: {
      title: 'Escritórios e empresas',
      description: 'Mobiliário, equipamento e materiais de apoio a espaços administrativos, escritórios e ambientes de trabalho.',
      code: 'AREA 04 / ESCRITÓRIOS'
    },
    foodservice: {
      title: 'Alimentação profissional',
      description: 'Produtos e soluções de abastecimento para cozinhas profissionais, cantinas e operações de alimentação coletiva.',
      code: 'AREA 05 / ALIMENTAÇÃO'
    }
  };

  const areaButtons = [...document.querySelectorAll('[data-area]')];
  const asideTitle = document.querySelector('#area-title');
  const asideDescription = document.querySelector('#area-description');
  const asideCode = document.querySelector('#area-code');
  function selectArea(key) {
    const data = areaData[key];
    if (!data) return;
    areaButtons.forEach(button => button.setAttribute('aria-pressed', String(button.dataset.area === key)));
    asideTitle.textContent = data.title;
    asideDescription.textContent = data.description;
    asideCode.textContent = data.code;
    const filter = document.querySelector('[data-filter="' + key + '"]');
    if (filter) setFilter(key);
  }
  areaButtons.forEach(button => button.addEventListener('click', () => selectArea(button.dataset.area)));

  const filters = [...document.querySelectorAll('[data-filter]')];
  const search = document.querySelector('#catalog-search');
  const empty = document.querySelector('#catalog-empty');
  const feedback = document.querySelector('#catalog-feedback');
  let activeFilter = 'all';
  function setFilter(value) {
    activeFilter = value;
    filters.forEach(button => button.setAttribute('aria-pressed', String(button.dataset.filter === value)));
    updateCatalogMessage();
  }
  function updateCatalogMessage() {
    const query = (search?.value || '').trim();
    const filterLabel = activeFilter === 'all' ? 'todas as áreas' : (areaData[activeFilter]?.title || 'área selecionada');
    if (empty) empty.hidden = false;
    if (feedback) feedback.textContent = query
      ? 'Pesquisa recebida: "' + query + '". O catálogo ainda não contém produtos publicados.'
      : 'Filtro: ' + filterLabel + '. O catálogo será atualizado quando os produtos e respetivas condições forem validados pela GPM.';
  }
  filters.forEach(button => button.addEventListener('click', () => setFilter(button.dataset.filter)));
  search?.addEventListener('input', updateCatalogMessage);
  updateCatalogMessage();
})();