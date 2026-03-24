/**
 * Testes de Integração - Fluxo Carrinho + Validação
 * Arquivo: tests/integration/fluxo-compra.test.js
 */

const { validarProduto, formatarValor, calcularTotal, buscarProduto } = require('../../src/carrinho');
const { validarConfigConexao } = require('../../src/conexao');
const { mostrarModal, esconderModal } = require('../../src/modal');

describe('Integração - Fluxo completo de adição ao carrinho', () => {
  test('deve validar, buscar e formatar produto em sequência', () => {
    const nomeProduto = 'Capuccino';
    const valorBruto  = '15,99';

    // 1. Validar entrada
    expect(validarProduto(nomeProduto, valorBruto)).toBe(true);

    // 2. Buscar produto no cardápio
    const produto = buscarProduto(nomeProduto);
    expect(produto).not.toBeNull();

    // 3. Formatar valor para exibição
    const valorFormatado = formatarValor(produto.valor);
    expect(valorFormatado).toBe('15.99');
  });

  test('deve calcular total de um pedido com múltiplos itens', () => {
    const pedido = ['Capuccino', 'Mocha', 'Expresso'].map(nome => buscarProduto(nome));
    pedido.forEach(p => expect(p).not.toBeNull());

    const total = calcularTotal(pedido);
    expect(total).toBeCloseTo(38.58, 2);
  });

  test('deve rejeitar produto inválido antes de adicionar ao carrinho', () => {
    const produtoFake = { nome: '', valor: -1 };
    expect(validarProduto(produtoFake.nome, produtoFake.valor)).toBe(false);
  });
});

describe('Integração - Sistema de autenticação com banco de dados', () => {
  test('deve validar configuração padrão do projeto (localhost/meudb)', () => {
    const config = { host: 'localhost', user: 'root', password: '', db: 'meudb' };
    const result = validarConfigConexao(config);
    expect(result.valido).toBe(true);
  });

  test('não deve conectar com configuração incompleta', () => {
    const config = { host: 'localhost', user: '', password: '', db: '' };
    expect(validarConfigConexao(config).valido).toBe(false);
  });
});

describe('Integração - Modal de endereço com interação do usuário', () => {
  test('deve abrir e fechar modal corretamente em sequência', () => {
    const modal   = { style: { left: '-30%' } };
    const mascara = { style: { visibility: 'hidden' } };

    mostrarModal(modal, mascara);
    expect(modal.style.left).toBe('50%');
    expect(mascara.style.visibility).toBe('visible');

    esconderModal(modal, mascara);
    expect(modal.style.left).toBe('-30%');
    expect(mascara.style.visibility).toBe('hidden');
  });

  test('deve suportar múltiplos ciclos de abertura/fechamento', () => {
    const modal   = { style: { left: '-30%' } };
    const mascara = { style: { visibility: 'hidden' } };

    for (let i = 0; i < 3; i++) {
      mostrarModal(modal, mascara);
      expect(modal.style.left).toBe('50%');
      esconderModal(modal, mascara);
      expect(modal.style.left).toBe('-30%');
    }
  });
});
