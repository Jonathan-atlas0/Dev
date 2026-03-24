/**
 * Testes Unitários - Módulo Carrinho
 * Arquivo: tests/unit/carrinho.test.js
 */

const {
  validarProduto,
  formatarValor,
  calcularTotal,
  buscarProduto,
  listarProdutos,
} = require('../../src/carrinho');

// ─── validarProduto ────────────────────────────────────────────────────────────
describe('Carrinho - validarProduto()', () => {
  test('deve retornar true para produto e valor válidos', () => {
    expect(validarProduto('Capuccino', 15.99)).toBe(true);
  });

  test('deve retornar true com valor em formato de string com vírgula', () => {
    expect(validarProduto('Mocha', '12,59')).toBe(true);
  });

  test('deve retornar false para produto vazio', () => {
    expect(validarProduto('', 15.99)).toBe(false);
  });

  test('deve retornar false para produto apenas com espaços', () => {
    expect(validarProduto('   ', 10.00)).toBe(false);
  });

  test('deve retornar false para valor zero', () => {
    expect(validarProduto('Expresso', 0)).toBe(false);
  });

  test('deve retornar false para valor negativo', () => {
    expect(validarProduto('Expresso', -5)).toBe(false);
  });

  test('deve retornar false para valor NaN', () => {
    expect(validarProduto('Expresso', 'abc')).toBe(false);
  });

  test('deve retornar false para produto null', () => {
    expect(validarProduto(null, 15.99)).toBe(false);
  });
});

// ─── formatarValor ─────────────────────────────────────────────────────────────
describe('Carrinho - formatarValor()', () => {
  test('deve formatar número inteiro com duas casas decimais', () => {
    expect(formatarValor(10)).toBe('10.00');
  });

  test('deve formatar string com vírgula corretamente', () => {
    expect(formatarValor('12,59')).toBe('12.59');
  });

  test('deve formatar string com ponto corretamente', () => {
    expect(formatarValor('15.99')).toBe('15.99');
  });

  test('deve retornar null para valor inválido', () => {
    expect(formatarValor('abc')).toBeNull();
  });

  test('deve formatar zero como "0.00"', () => {
    expect(formatarValor(0)).toBe('0.00');
  });
});

// ─── calcularTotal ─────────────────────────────────────────────────────────────
describe('Carrinho - calcularTotal()', () => {
  test('deve somar corretamente os valores dos itens', () => {
    const itens = [
      { produto: 'Capuccino', valor: '15,99' },
      { produto: 'Mocha',     valor: '12,59' },
    ];
    expect(calcularTotal(itens)).toBeCloseTo(28.58, 2);
  });

  test('deve retornar 0 para carrinho vazio', () => {
    expect(calcularTotal([])).toBe(0);
  });

  test('deve ignorar itens com valor inválido', () => {
    const itens = [
      { produto: 'Expresso', valor: 10.00 },
      { produto: 'Inválido', valor: 'abc' },
    ];
    expect(calcularTotal(itens)).toBeCloseTo(10.00, 2);
  });

  test('deve calcular total com apenas um item', () => {
    expect(calcularTotal([{ produto: 'Café preto', valor: 18.99 }])).toBeCloseTo(18.99, 2);
  });
});

// ─── buscarProduto ─────────────────────────────────────────────────────────────
describe('Carrinho - buscarProduto()', () => {
  test('deve encontrar produto pelo nome exato', () => {
    const p = buscarProduto('Capuccino');
    expect(p).not.toBeNull();
    expect(p.nome).toBe('Capuccino');
    expect(p.valor).toBe(15.99);
  });

  test('deve encontrar produto ignorando maiúsculas/minúsculas', () => {
    const p = buscarProduto('MOCHA');
    expect(p).not.toBeNull();
    expect(p.nome).toBe('Mocha');
  });

  test('deve retornar null para produto inexistente', () => {
    expect(buscarProduto('Suco de laranja')).toBeNull();
  });

  test('deve retornar produto com imagem correta', () => {
    const p = buscarProduto('Expresso');
    expect(p.imagem).toBe('./img/menu-4.png');
  });
});

// ─── listarProdutos ────────────────────────────────────────────────────────────
describe('Carrinho - listarProdutos()', () => {
  test('deve retornar todos os 6 produtos do cardápio', () => {
    expect(listarProdutos()).toHaveLength(6);
  });

  test('deve retornar uma cópia (não referência direta)', () => {
    const lista = listarProdutos();
    lista.push({ nome: 'Teste', valor: 99 });
    expect(listarProdutos()).toHaveLength(6);
  });

  test('todos os produtos devem ter nome, valor e imagem', () => {
    listarProdutos().forEach(p => {
      expect(p).toHaveProperty('nome');
      expect(p).toHaveProperty('valor');
      expect(p).toHaveProperty('imagem');
    });
  });

  test('todos os valores devem ser positivos', () => {
    listarProdutos().forEach(p => {
      expect(p.valor).toBeGreaterThan(0);
    });
  });
});
