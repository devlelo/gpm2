(() => {
  const catalog = window.GPM_CATALOG;
  const products = catalog ? catalog.getProducts(window.GPM_PRODUCTS) : [];
  const filters = [...document.querySelectorAll('[data-filter]')];
  const search = document.querySelector('#catalog-query');
  const list = document.querySelector('#catalog-results-list');
  const empty = document.querySelector('#catalog-page-empty');
  const status = document.querySelector('#catalog-page-status');
  const total = document.querySelector('#catalog-total');
  let activeFilter = 'all';
  const requestedArea = new URLSearchParams(window.location.search).get('area');
  if (requestedArea && filters.some(button => button.dataset.filter === requestedArea)) {
    activeFilter = requestedArea;
    filters.forEach(button => button.setAttribute('aria-pressed', String(button.dataset.filter === activeFilter)));
  }

  function makeProductRow(product, index) {
    const row = document.createElement('a');
    row.className = 'catalog-product-row';
    row.href = './produto.html?ref=' + encodeURIComponent(product.ref.trim());

    const code = document.createElement('span');
    code.className = 'catalog-product-code';
    code.textContent = String(index + 1).padStart(2, '0');

    const info = document.createElement('span');
    const name = document.createElement('span');
    name.className = 'catalog-product-name';
    name.textContent = product.name.trim();

    const meta = document.createElement('span');
    meta.className = 'catalog-product-meta';
    meta.textContent = [product.ref.trim(), catalog.getAreaLabel(product.area)].join(' / ');
    info.append(name, meta);

    const action = document.createElement('span');
    action.className = 'catalog-product-link';
    action.textContent = 'Ver ficha ↗';

    row.append(code, info, action);
    return row;
  }

  function render() {
    const query = (search?.value || '').trim();
    const visible = catalog.searchProducts(products, query, activeFilter);

    list.replaceChildren(...visible.map(makeProductRow));
    empty.hidden = visible.length > 0;
    total.textContent = visible.length + (visible.length === 1 ? ' REFERÊNCIA' : ' REFERÊNCIAS') +
      ' / ' + products.length + ' PUBLICADAS';

    const emptyTitle = empty.querySelector('h3');
    const emptyText = empty.querySelector('p');

    if (products.length === 0) {
      emptyTitle.textContent = 'Catálogo em preparação';
      emptyText.textContent = 'A GPM ainda não publicou referências neste catálogo. Estamos a preparar a informação de cada produto para evitar apresentar especificações, preços ou disponibilidade sem validação.';
      status.textContent = 'Ainda não existem produtos publicados. A pesquisa e os filtros estão prontos para o catálogo validado.';
      return;
    }

    if (visible.length === 0) {
      if (query) {
        const scope = activeFilter === 'all' ? '' : ' na área ' + catalog.getAreaLabel(activeFilter);
        emptyTitle.textContent = 'Sem resultados para a pesquisa';
        emptyText.textContent = 'Não encontrámos referências para "' + query + '"' + scope +
          '. Experimente outra palavra ou selecione todas as áreas.';
        status.textContent = 'Nenhuma referência corresponde à pesquisa e à área selecionada.';
      } else {
        const areaLabel = catalog.getAreaLabel(activeFilter);
        emptyTitle.textContent = 'Sem referências nesta área';
        emptyText.textContent = 'Ainda não existem referências publicadas para ' + areaLabel +
          '. Selecione outra área para consultar os produtos disponíveis.';
        status.textContent = 'Não existem referências publicadas na área ' + areaLabel + '.';
      }
      return;
    }

    status.textContent = visible.length + ' referência(s) encontrada(s). Consulte cada ficha para conhecer a informação validada.';
  }

  filters.forEach(button => button.addEventListener('click', () => {
    activeFilter = button.dataset.filter || 'all';
    filters.forEach(item => item.setAttribute('aria-pressed', String(item === button)));
    render();
  }));
  search?.addEventListener('input', render);

  const toggle = document.querySelector('.menu-toggle');
  const nav = document.querySelector('.main-nav');
  toggle?.addEventListener('click', () => {
    const open = nav.classList.toggle('open');
    toggle.setAttribute('aria-expanded', String(open));
    toggle.setAttribute('aria-label', open ? 'Fechar menu' : 'Abrir menu');
    toggle.textContent = open ? '×' : '≡';
  });
  nav?.querySelectorAll('a').forEach(link => link.addEventListener('click', () => {
    nav.classList.remove('open');
    toggle?.setAttribute('aria-expanded', 'false');
    if (toggle) {
      toggle.setAttribute('aria-label', 'Abrir menu');
      toggle.textContent = '≡';
    }
  }));

  render();
})();
