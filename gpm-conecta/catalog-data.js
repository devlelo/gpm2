(() => {
  const areaNames = Object.freeze({
    construction: 'Construção e materiais',
    hospitality: 'Hotelaria e alojamento',
    worksites: 'Estaleiros e operações',
    offices: 'Escritórios e empresas',
    foodservice: 'Alimentação profissional'
  });

  function normalizeText(value) {
    return String(value ?? '')
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .toLocaleLowerCase('pt')
      .trim();
  }

  function normalizeReference(value) {
    return String(value ?? '').trim().toLocaleUpperCase('pt');
  }

  function isPublicProduct(product) {
    return Boolean(
      product &&
      typeof product === 'object' &&
      !Array.isArray(product) &&
      typeof product.ref === 'string' &&
      normalizeReference(product.ref) &&
      typeof product.name === 'string' &&
      product.name.trim() &&
      Object.prototype.hasOwnProperty.call(areaNames, product.area)
    );
  }

  function getProducts(source) {
    if (!Array.isArray(source)) return [];

    const seenReferences = new Set();
    return source.filter(product => {
      if (!isPublicProduct(product)) return false;
      const reference = normalizeReference(product.ref);
      if (seenReferences.has(reference)) return false;
      seenReferences.add(reference);
      return true;
    });
  }

  function getAreaLabel(area) {
    return areaNames[area] || 'Área GPM';
  }

  function findProduct(reference, source) {
    const target = normalizeReference(reference);
    if (!target) return null;
    return getProducts(source).find(product => normalizeReference(product.ref) === target) || null;
  }

  function searchProducts(source, query = '', area = 'all') {
    const normalizedQuery = normalizeText(query);
    return getProducts(source).filter(product => {
      if (area && area !== 'all' && product.area !== area) return false;
      if (!normalizedQuery) return true;

      const specifications = Array.isArray(product.specifications)
        ? product.specifications.flatMap(spec => spec && typeof spec === 'object' ? [spec.label, spec.value] : [])
        : [];
      const keywords = Array.isArray(product.keywords) ? product.keywords : [];
      const searchable = [
        product.name,
        product.ref,
        product.summary,
        product.description,
        getAreaLabel(product.area),
        ...keywords,
        ...specifications
      ].filter(value => typeof value === 'string').join(' ');

      return normalizeText(searchable).includes(normalizedQuery);
    });
  }

  window.GPM_CATALOG = Object.freeze({
    areaNames,
    normalizeText,
    normalizeReference,
    isPublicProduct,
    getProducts,
    getAreaLabel,
    findProduct,
    searchProducts
  });
})();
