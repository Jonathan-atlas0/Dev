/**
 * Testes Unitários - Módulo Modal
 * Arquivo: tests/unit/modal.test.js
 */

const { mostrarModal, esconderModal } = require('../../src/modal');

describe('Modal de Endereço - mostrarModal()', () => {
  let modal, mascara;

  beforeEach(() => {
    modal   = { style: { left: '-30%' } };
    mascara = { style: { visibility: 'hidden' } };
  });

  test('deve posicionar o modal no centro da tela', () => {
    mostrarModal(modal, mascara);
    expect(modal.style.left).toBe('50%');
  });

  test('deve tornar a máscara visível ao abrir o modal', () => {
    mostrarModal(modal, mascara);
    expect(mascara.style.visibility).toBe('visible');
  });

  test('deve alterar somente as propriedades necessárias', () => {
    mostrarModal(modal, mascara);
    expect(modal.style.left).toBe('50%');
    expect(mascara.style.visibility).toBe('visible');
  });
});

describe('Modal de Endereço - esconderModal()', () => {
  let modal, mascara;

  beforeEach(() => {
    modal   = { style: { left: '50%' } };
    mascara = { style: { visibility: 'visible' } };
  });

  test('deve mover o modal para fora da tela', () => {
    esconderModal(modal, mascara);
    expect(modal.style.left).toBe('-30%');
  });

  test('deve ocultar a máscara ao fechar o modal', () => {
    esconderModal(modal, mascara);
    expect(mascara.style.visibility).toBe('hidden');
  });

  test('deve restaurar o estado inicial do modal', () => {
    mostrarModal(modal, mascara);
    esconderModal(modal, mascara);
    expect(modal.style.left).toBe('-30%');
    expect(mascara.style.visibility).toBe('hidden');
  });
});
