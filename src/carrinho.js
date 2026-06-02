const PRODUTOS = [
  { nome: 'Capuccino', valor: 15.99, imagem: './img/menu-1.png' },
  { nome: 'Mocha', valor: 12.59, imagem: './img/menu-2.png' },
  { nome: 'Latte', valor: 10.5, imagem: './img/menu-3.png' },
  { nome: 'Expresso', valor: 8.99, imagem: './img/menu-4.png' },
  { nome: 'Café com leite', valor: 9.99, imagem: './img/menu-5.png' },
  { nome: 'Chocolate quente', valor: 11.0, imagem: './img/menu-6.png' },
];

function formatarValor(valor) {
  if (typeof valor === 'number' && Number.isFinite(valor)) return valor.toFixed(2);
  if (typeof valor !== 'string') return null;
  const normalized = valor.replace(',', '.').trim();
  const num = parseFloat(normalized);
  if (!Number.isFinite(num)) return null;
  return num.toFixed(2);
}

function validarProduto(nome, valor) {
  if (typeof nome !== 'string' || nome === null) return false;
  if (nome.trim() === '') return false;
  const f = formatarValor(valor);
  if (f === null) return false;
  const num = parseFloat(f);
  if (!(num > 0)) return false;
  return true;
}

function calcularTotal(itens) {
  if (!Array.isArray(itens) || itens.length === 0) return 0;
  let total = 0;
  itens.forEach(i => {
    const f = formatarValor(i.valor);
    if (f === null) return;
    total += parseFloat(f);
  });
  return total;
}

function listarProdutos() {
  return PRODUTOS.map(p => ({ nome: p.nome, valor: p.valor, imagem: p.imagem }));
}

function buscarProduto(nome) {
  if (!nome) return null;
  const lower = String(nome).toLowerCase();
  const p = PRODUTOS.find(x => x.nome.toLowerCase() === lower);
  if (!p) return null;
  return { nome: p.nome, valor: p.valor, imagem: p.imagem };
}

module.exports = {
  validarProduto,
  formatarValor,
  calcularTotal,
  buscarProduto,
  listarProdutos,
};
