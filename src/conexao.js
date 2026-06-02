function validarConfigConexao(cfg) {
  const erros = [];
  if (!cfg || typeof cfg !== 'object') return { valido: false, erros: ['Configuração inválida'] };
  if (!cfg.host || String(cfg.host).trim() === '') erros.push('Host é obrigatório');
  if (!cfg.user || String(cfg.user).trim() === '') erros.push('Usuário é obrigatório');
  if (!cfg.db || String(cfg.db).trim() === '') erros.push('Nome do banco é obrigatório');
  return { valido: erros.length === 0, erros };
}

function gerarStringConexao(cfg) {
  const ok = validarConfigConexao(cfg);
  if (!ok.valido) return null;
  return `mysql://${cfg.user}@${cfg.host}/${cfg.db}`;
}

module.exports = { validarConfigConexao, gerarStringConexao };
