# Cafezin - Projeto de Testes

Projeto de testes automatizados do sistema **Cafezin** (Cafeteria Web), implementado com [Jest](https://jestjs.io/).

## 📁 Estrutura do Projeto

```
cafeteria_tests/
├── src/                          # Módulos JS extraídos do projeto PHP
│   ├── modal.js                  # Lógica do modal de endereço (scripts.js)
│   ├── carrinho.js               # Lógica do carrinho de compras (carrinho.php)
│   └── conexao.js                # Validação de configuração de BD (Conexao.php)
├── tests/
│   ├── unit/                     # Testes Unitários
│   │   ├── modal.test.js         # 6 testes - mostrarModal / esconderModal
│   │   ├── carrinho.test.js      # 18 testes - validação, formatação, busca
│   │   └── conexao.test.js       # 11 testes - configuração de conexão
│   ├── integration/              # Testes de Integração
│   │   └── fluxo-compra.test.js  # 7 testes - fluxo completo de compra
│   └── e2e/                      # Testes E2E (simulação de jornada)
│       └── jornada-usuario.test.js # 10 testes - jornada do usuário
├── package.json
└── README.md
```

## 🚀 Como Executar

```bash
# Instalar dependências
npm install

# Executar todos os testes
npm test

# Executar com relatório de cobertura
npm run test:coverage
```

## 🧪 Cobertura de Testes

| Módulo         | Tipo          | Testes |
|----------------|---------------|--------|
| `modal.js`     | Unitário      | 6      |
| `carrinho.js`  | Unitário      | 18     |
| `conexao.js`   | Unitário      | 11     |
| `fluxo-compra` | Integração    | 7      |
| `jornada`      | E2E           | 10     |
| **Total**      |               | **52** |

## 📋 Funcionalidades Testadas

- **Modal de Endereço**: abertura, fechamento, ciclos de uso
- **Validação de Produto**: campos obrigatórios, valores, tipos
- **Formatação de Valores**: conversão de vírgula/ponto, casas decimais
- **Cálculo de Total**: soma de itens, carrinho vazio, valores inválidos
- **Busca de Produto**: por nome, case-insensitive, produto inexistente
- **Configuração BD**: validação de campos obrigatórios, string de conexão
- **Fluxo de Compra**: integração entre validação, busca e cálculo
- **Jornada do Usuário**: simulação E2E completa do fluxo de compra
