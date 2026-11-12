const test = require('node:test');
const assert = require('node:assert/strict');

global.window = {};
require('../catalog-data.js');
const catalog = global.window.GPM_CATALOG;

const fixture = {
  ref: 'GPM-TST-001',
  name: 'Produto de teste',
  area: 'construction',
  summary: 'Registo técnico de teste',
  keywords: ['cimento'],
  specifications: [{ label: 'Material', value: 'Amostra técnica' }]
};

test('normaliza acentos e maiúsculas para pesquisa', () => {
  assert.equal(catalog.normalizeText('Alimentação Profissional'), 'alimentacao profissional');
});

test('publica apenas referências válidas e elimina referências duplicadas', () => {
  const products = catalog.getProducts([
    fixture,
    { ...fixture, ref: ' gpm-tst-001 ', name: 'Duplicado de teste' },
    { ref: 'GPM-TST-002', name: 'Sem área válida', area: 'unknown' },
    { ref: 'GPM-TST-003', name: '   ', area: 'offices' },
    null
  ]);
  assert.equal(products.length, 1);
  assert.equal(products[0].name, 'Produto de teste');
});

test('pesquisa ignora acentos e inclui a área e as especificações', () => {
  assert.equal(catalog.searchProducts([fixture], 'construcao').length, 1);
  assert.equal(catalog.searchProducts([fixture], 'amostra tecnica').length, 1);
  assert.equal(catalog.searchProducts([fixture], 'hotelaria').length, 0);
});

test('resolve referências sem depender de maiúsculas ou espaços exteriores', () => {
  assert.equal(catalog.findProduct(' gpm-tst-001 ', [fixture]), fixture);
  assert.equal(catalog.findProduct('GPM-NAO-EXISTE', [fixture]), null);
});
