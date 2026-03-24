/**
 * Testes E2E (Simulação) - Jornada do Usuário no Cafezin
 * Arquivo: tests/e2e/jornada-usuario.test.js
 *
 * Simula o fluxo completo do usuário: entrar no site,
 * visualizar o menu, adicionar item ao carrinho e ver o endereço.
 */

const { listarProdutos, buscarProduto, validarProduto, calcularTotal } = require('../../src/carrinho');
const { mostrarModal, esconderModal } = require('../../src/modal');

// Simula o estado da página
function criarEstadoPagina() {
  return {
    paginaAtual: 'index',
    carrinho: [],
    modal:   { style: { left: '-30%' } },
    mascara: { style: { visibility: 'hidden' } },
  };
}

describe('E2E - Jornada: Usuário visita o menu e adiciona item ao carrinho', () => {
  let estado;

  beforeEach(() => { estado = criarEstadoPagina(); });

  test('1. Usuário acessa o menu e vê todos os produtos disponíveis', () => {
    const produtos = listarProdutos();
    expect(produtos.length).toBe(6);
    const nomes = produtos.map(p => p.nome);
    expect(nomes).toContain('Capuccino');
    expect(nomes).toContain('Café preto');
  });

  test('2. Usuário seleciona Macchiato (R$ 16,99) e confirma produto válido', () => {
    const produto = buscarProduto('Macchiato');
    expect(produto).not.toBeNull();
    expect(validarProduto(produto.nome, produto.valor)).toBe(true);
  });

  test('3. Usuário adiciona item ao carrinho e total é calculado', () => {
    const produto = buscarProduto('Macchiato');
    estado.carrinho.push(produto);
    const total = calcularTotal(estado.carrinho);
    expect(total).toBeCloseTo(16.99, 2);
  });

  test('4. Usuário adiciona segundo item e total acumula corretamente', () => {
    estado.carrinho.push(buscarProduto('Macchiato'));
    estado.carrinho.push(buscarProduto('Mocha'));
    const total = calcularTotal(estado.carrinho);
    expect(total).toBeCloseTo(29.58, 2);
  });

  test('5. Usuário clica em "Endereço" e o modal é exibido', () => {
    mostrarModal(estado.modal, estado.mascara);
    expect(estado.modal.style.left).toBe('50%');
    expect(estado.mascara.style.visibility).toBe('visible');
  });

  test('6. Usuário fecha o modal clicando na máscara', () => {
    mostrarModal(estado.modal, estado.mascara);
    esconderModal(estado.modal, estado.mascara);
    expect(estado.modal.style.left).toBe('-30%');
    expect(estado.mascara.style.visibility).toBe('hidden');
  });
});

describe('E2E - Jornada: Usuário tenta adicionar produto inexistente', () => {
  test('produto inexistente não é encontrado no cardápio', () => {
    const produto = buscarProduto('Frappuccino');
    expect(produto).toBeNull();
  });

  test('validação impede adição de produto com nome vazio', () => {
    expect(validarProduto('', 10.00)).toBe(false);
  });
});

describe('E2E - Jornada: Pedido com todos os itens do cardápio', () => {
  test('deve calcular total geral de todos os 6 produtos', () => {
    const todosItens = listarProdutos();
    const totalEsperado = 15.99 + 12.59 + 16.99 + 10.00 + 14.99 + 18.99;
    const total = calcularTotal(todosItens);
    expect(total).toBeCloseTo(totalEsperado, 2);
  });
});
