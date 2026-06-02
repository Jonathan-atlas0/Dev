/**
 * Testes Unitários - Módulo Conexão
 * Arquivo: tests/unit/conexao.test.js
 */

const { validarConfigConexao, gerarStringConexao } = require('../../src/conexao');

// ─── validarConfigConexao ──────────────────────────────────────────────────────
describe('Conexão - validarConfigConexao()', () => {
  const configValida = { host: 'localhost', user: 'root', password: '', db: 'meudb' };

  test('deve validar configuração completa como válida', () => {
    const result = validarConfigConexao(configValida);
    expect(result.valido).toBe(true);
    expect(result.erros).toHaveLength(0);
  });

  test('deve rejeitar configuração sem host', () => {
    const { host, ...semHost } = configValida;
    const result = validarConfigConexao(semHost);
    expect(result.valido).toBe(false);
    expect(result.erros).toContain('Host é obrigatório');
  });

  test('deve rejeitar configuração sem usuário', () => {
    const result = validarConfigConexao({ ...configValida, user: '' });
    expect(result.valido).toBe(false);
    expect(result.erros).toContain('Usuário é obrigatório');
  });

  test('deve rejeitar configuração sem banco de dados', () => {
    const result = validarConfigConexao({ ...configValida, db: '' });
    expect(result.valido).toBe(false);
    expect(result.erros).toContain('Nome do banco é obrigatório');
  });

  test('deve aceitar senha vazia (senha em branco é válida)', () => {
    const result = validarConfigConexao({ ...configValida, password: '' });
    expect(result.valido).toBe(true);
  });

  test('deve rejeitar configuração null', () => {
    const result = validarConfigConexao(null);
    expect(result.valido).toBe(false);
  });

  test('deve retornar múltiplos erros quando vários campos estão faltando', () => {
    const result = validarConfigConexao({ host: '', user: '', password: '', db: '' });
    expect(result.erros.length).toBeGreaterThan(1);
  });
});

// ─── gerarStringConexao ────────────────────────────────────────────────────────
describe('Conexão - gerarStringConexao()', () => {
  const config = { host: 'localhost', user: 'root', password: '', db: 'meudb' };

  test('deve gerar string de conexão no formato correto', () => {
    const conn = gerarStringConexao(config);
    expect(conn).toBe('mysql://root@localhost/meudb');
  });

  test('deve retornar null para configuração inválida', () => {
    expect(gerarStringConexao({ host: '', user: '', password: null, db: '' })).toBeNull();
  });

  test('deve conter o nome do banco na string de conexão', () => {
    const conn = gerarStringConexao(config);
    expect(conn).toContain('meudb');
  });

  test('deve conter o usuário na string de conexão', () => {
    const conn = gerarStringConexao(config);
    expect(conn).toContain('root');
  });
});
