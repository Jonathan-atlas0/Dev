<?php
if (!isset($pedidos)) {
    if (session_status() === PHP_SESSION_NONE) { session_start(); }
    require_once __DIR__ . '/Conexao.php';
    require_once __DIR__ . '/bootstrap.php';
    try {
        $pedidos = $queryBus->dispatch(new \Cafeteria\CQRS\Queries\BuscarPedidosFinalizadosQuery());
    } catch (\Throwable $e) {
        $pedidos = [];
    }
}

// Carrega estoque
try {
    $stmtEstoque = $conn->query("SELECT produto, quantidade FROM Estoque ORDER BY produto");
    $estoque = $stmtEstoque->fetchAll(PDO::FETCH_ASSOC);
} catch (\Throwable $e) {
    $estoque = [];
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>☕ Painel Admin — Cafeteria</title>
    <link rel="stylesheet" href="/styles.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Montserrat', sans-serif;
            background: #1a0a00;
            color: #f5e6d3;
            min-height: 100vh;
        }

        .admin-header {
            background: linear-gradient(135deg, #2c1503, #4a2c0a);
            padding: 20px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 2px solid #8B4513;
            box-shadow: 0 2px 20px rgba(0,0,0,0.5);
        }

        .admin-header h1 { font-size: 1.6rem; color: #d4a056; }
        .admin-header .sub { font-size: 0.85rem; color: #a07040; margin-top: 4px; }

        #ws-status {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.85rem;
            padding: 6px 14px;
            border-radius: 20px;
            background: rgba(0,0,0,0.3);
            border: 1px solid #444;
        }

        #ws-dot { width: 10px; height: 10px; border-radius: 50%; background: #888; transition: background 0.3s; }
        #ws-dot.conectado  { background: #4caf50; box-shadow: 0 0 8px #4caf50; }
        #ws-dot.erro       { background: #f44336; }
        #ws-dot.conectando { background: #ff9800; animation: pulse 1s infinite; }

        @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:0.4} }

        /* Tabs */
        .tabs {
            display: flex;
            gap: 4px;
            margin-bottom: 20px;
            border-bottom: 2px solid #3a1f08;
        }

        .tab-btn {
            padding: 10px 20px;
            background: none;
            border: none;
            color: #a07040;
            font-family: 'Montserrat', sans-serif;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            margin-bottom: -2px;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.2s;
        }

        .tab-btn:hover { color: #d4a056; }
        .tab-btn.ativo { color: #d4a056; border-bottom-color: #d4a056; }

        .tab-content { display: none; }
        .tab-content.ativo { display: block; }

        .admin-body {
            display: grid;
            grid-template-columns: 1fr 340px;
            gap: 24px;
            padding: 28px 32px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .painel-live h2, .historico h2 {
            font-size: 1rem;
            color: #d4a056;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 16px;
            padding-bottom: 8px;
            border-bottom: 1px solid #4a2c0a;
        }

        .pedido-card {
            background: linear-gradient(135deg, #2c1503, #3a1f08);
            border: 1px solid #5a3010;
            border-left: 4px solid #d4a056;
            border-radius: 10px;
            padding: 16px 20px;
            margin-bottom: 12px;
            animation: slideIn 0.4s ease;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-10px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .pedido-card .usuario { font-weight: 700; color: #d4a056; font-size: 1rem; }
        .pedido-card .detalhes { color: #a07040; font-size: 0.85rem; margin-top: 4px; }
        .pedido-card .total { font-size: 1.1rem; color: #4caf50; font-weight: 700; margin-top: 8px; }
        .pedido-card .horario { font-size: 0.75rem; color: #666; margin-top: 4px; }

        #sem-pedidos { text-align: center; color: #555; padding: 40px; font-size: 0.9rem; }

        .historico {
            background: #200e00;
            border-radius: 12px;
            padding: 20px;
            border: 1px solid #3a1f08;
            max-height: 80vh;
            overflow-y: auto;
        }

        .historico-item {
            padding: 10px 0;
            border-bottom: 1px solid #2c1503;
            font-size: 0.85rem;
        }

        .historico-item:last-child { border-bottom: none; }
        .historico-item .h-user { color: #d4a056; font-weight: 600; }
        .historico-item .h-produto { color: #c8a070; }
        .historico-item .h-valor { color: #4caf50; float: right; font-weight: 700; }

        .stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 24px;
        }

        .stat-box {
            background: #200e00;
            border: 1px solid #3a1f08;
            border-radius: 10px;
            padding: 16px;
            text-align: center;
        }

        .stat-box .num { font-size: 2rem; font-weight: 700; color: #d4a056; }
        .stat-box .label { font-size: 0.75rem; color: #666; margin-top: 4px; text-transform: uppercase; }

        /* Estoque */
        .estoque-tabela {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }

        .estoque-tabela th {
            text-align: left;
            padding: 10px 14px;
            color: #d4a056;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 1px;
            border-bottom: 2px solid #3a1f08;
        }

        .estoque-tabela td {
            padding: 10px 14px;
            border-bottom: 1px solid #2c1503;
            color: #f5e6d3;
        }

        .estoque-tabela tr:hover td { background: #200e00; }

        .qtd-badge {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 0.85rem;
        }

        .qtd-badge.ok     { background: #1a3a1a; color: #4caf50; }
        .qtd-badge.baixo  { background: #3a2a00; color: #ff9800; }
        .qtd-badge.zero   { background: #3a1010; color: #f44336; }

        .btn-estoque {
            padding: 4px 10px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.8rem;
            font-weight: 600;
            margin-left: 4px;
        }

        .btn-mais  { background: #1a3a1a; color: #4caf50; }
        .btn-menos { background: #3a1010; color: #f44336; }
        .btn-mais:hover  { background: #4caf50; color: #fff; }
        .btn-menos:hover { background: #f44336; color: #fff; }

        .flash { animation: flash 0.5s; }
        @keyframes flash {
            0%,100% { background: linear-gradient(135deg, #2c1503, #3a1f08); }
            50%      { background: linear-gradient(135deg, #4a2c08, #6b3d10); }
        }

        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #1a0a00; }
        ::-webkit-scrollbar-thumb { background: #4a2c0a; border-radius: 3px; }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body>

<div class="admin-header">
    <div>
        <h1>☕ Painel Admin — Cafeteria</h1>
        <div class="sub">Pedidos em tempo real via WebSocket + Redis</div>
    </div>
    <div id="ws-status">
        <div id="ws-dot" class="conectando"></div>
        <span id="ws-texto">Conectando...</span>
    </div>
</div>

<div class="admin-body">

    <div class="painel-live">

        <div class="stats">
            <div class="stat-box">
                <div class="num" id="cnt-novos">0</div>
                <div class="label">Novos esta sessão</div>
            </div>
            <div class="stat-box">
                <div class="num" id="cnt-historico"><?= count($pedidos) ?></div>
                <div class="label">Total finalizado</div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="tabs">
            <button class="tab-btn ativo" onclick="trocarTab('pedidos', this)">🔔 Pedidos ao Vivo</button>
            <button class="tab-btn" onclick="trocarTab('estoque', this)">📦 Estoque</button>
        </div>

        <!-- Tab: Pedidos -->
        <div id="tab-pedidos" class="tab-content ativo">
            <div id="feed">
                <div id="sem-pedidos">Aguardando novos pedidos...</div>
            </div>
        </div>

        <!-- Tab: Estoque -->
        <div id="tab-estoque" class="tab-content">
            <table class="estoque-tabela" id="tabela-estoque">
                <thead>
                    <tr>
                        <th>Produto</th>
                        <th>Quantidade</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($estoque)): ?>
                    <tr><td colspan="3" style="color:#555;text-align:center;padding:30px">Nenhum produto cadastrado no estoque.</td></tr>
                <?php else: ?>
                    <?php foreach ($estoque as $item): ?>
                        <?php
                            $qtd = (int)$item['quantidade'];
                            $badge = $qtd === 0 ? 'zero' : ($qtd <= 5 ? 'baixo' : 'ok');
                        ?>
                        <tr id="estoque-<?= htmlspecialchars($item['produto']) ?>">
                            <td><?= htmlspecialchars($item['produto']) ?></td>
                            <td><span class="qtd-badge <?= $badge ?>" id="qtd-<?= htmlspecialchars($item['produto']) ?>"><?= $qtd ?></span></td>
                            <td>
                                <button class="btn-estoque btn-mais"  onclick="ajustarEstoque('<?= htmlspecialchars($item['produto']) ?>', 1)">+1</button>
                                <button class="btn-estoque btn-menos" onclick="ajustarEstoque('<?= htmlspecialchars($item['produto']) ?>', -1)">-1</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>

    <div class="historico">
        <h2>📋 Histórico (Banco)</h2>
        <?php if (empty($pedidos)): ?>
            <p style="color:#555;font-size:.85rem">Nenhum pedido finalizado ainda.</p>
        <?php else: ?>
            <?php foreach ($pedidos as $p): ?>
                <div class="historico-item">
                    <span class="h-valor">R$ <?= number_format($p['valor'], 2, ',', '.') ?></span>
                    <div class="h-user"><?= htmlspecialchars($p['nome']) ?></div>
                    <div class="h-produto"><?= htmlspecialchars($p['produto']) ?></div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>

<script>
    let novosCount = 0;
    let ws = null;
    let reconnectTimer = null;

    const dot    = document.getElementById('ws-dot');
    const texto  = document.getElementById('ws-texto');
    const feed   = document.getElementById('feed');
    const cntNov = document.getElementById('cnt-novos');
    const semPed = document.getElementById('sem-pedidos');

    const WS_SCHEME = (location.protocol === 'https:') ? 'wss' : 'ws';
    const WS_HOSTS_TO_TRY = [location.hostname, '127.0.0.1', 'localhost', 'host.docker.internal'];

    // ── Tabs ──────────────────────────────────────────────────────────────
    function trocarTab(nome, btn) {
        document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('ativo'));
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('ativo'));
        document.getElementById('tab-' + nome).classList.add('ativo');
        btn.classList.add('ativo');
    }

    // ── Estoque ───────────────────────────────────────────────────────────
    function ajustarEstoque(produto, delta) {
        fetch(`/api/estoque.php?produto=${encodeURIComponent(produto)}`, {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ delta })
        })
        .then(r => r.json())
        .then(data => {
            if (data.quantidade !== undefined) {
                const span = document.getElementById('qtd-' + produto);
                if (span) {
                    const qtd = data.quantidade;
                    span.textContent = qtd;
                    span.className = 'qtd-badge ' + (qtd === 0 ? 'zero' : qtd <= 5 ? 'baixo' : 'ok');
                }
            } else {
                alert(data.erro || 'Erro ao ajustar estoque');
            }
        })
        .catch(() => alert('Erro de conexão com a API de estoque'));
    }

    // ── WebSocket ─────────────────────────────────────────────────────────
    function conectar() {
        setStatus('conectando', 'Conectando...');

        const urls = WS_HOSTS_TO_TRY.map(h => {
            const host = h.includes(':') ? `[${h}]` : h;
            return `${WS_SCHEME}://${host}:8082`;
        });

        let attempt = 0;

        function tryNext() {
            if (attempt >= urls.length) {
                setStatus('erro', 'Não foi possível conectar — tentando novamente...');
                reconnectTimer = setTimeout(conectar, 3000);
                return;
            }

            const url = urls[attempt++];
            setStatus('conectando', `Tentando ${url}`);
            const s = new WebSocket(url);
            let opened = false;

            s.onopen = () => {
                opened = true;
                ws = s;
                setStatus('conectado', 'Conectado ✓');
                clearTimeout(reconnectTimer);

                ws.onmessage = (evt) => {
                    try {
                        const data = JSON.parse(evt.data);
                        if (data.tipo === 'novo_pedido') {
                            adicionarPedidoAoFeed(data);
                            atualizarEstoqueAposVenda(data);
                        }
                    } catch (e) { console.warn('Mensagem inválida:', evt.data); }
                };

                ws.onclose = () => {
                    setStatus('erro', 'Desconectado — reconectando...');
                    reconnectTimer = setTimeout(conectar, 3000);
                };
            };

            s.onerror = () => {
                try { s.close(); } catch(_) {}
                if (!opened) setTimeout(tryNext, 200);
            };
        }

        tryNext();
    }

    // Atualiza os badges de estoque em tempo real quando chega evento WS
    function atualizarEstoqueAposVenda(data) {
        // Recarrega a aba de estoque via fetch para refletir as baixas
        fetch('/api/estoque.php')
            .then(r => r.json())
            .then(itens => {
                itens.forEach(item => {
                    const span = document.getElementById('qtd-' + item.produto);
                    if (span) {
                        const qtd = parseInt(item.quantidade);
                        span.textContent = qtd;
                        span.className = 'qtd-badge ' + (qtd === 0 ? 'zero' : qtd <= 5 ? 'baixo' : 'ok');
                    }
                });
            })
            .catch(() => {});
    }

    function adicionarPedidoAoFeed(data) {
        if (semPed) semPed.remove();

        novosCount++;
        cntNov.textContent = novosCount;

        const card = document.createElement('div');
        card.className = 'pedido-card flash';
        card.innerHTML = `
            <div class="usuario">👤 ${escapeHtml(data.usuario)}</div>
            <div class="detalhes">${data.itens} item(s)</div>
            <div class="total">R$ ${parseFloat(data.total).toFixed(2).replace('.', ',')}</div>
            <div class="horario">🕐 ${data.horario}</div>
        `;

        feed.insertBefore(card, feed.firstChild);
        document.title = `(${novosCount}) ☕ Novo pedido!`;
        setTimeout(() => { document.title = '☕ Painel Admin — Cafeteria'; }, 3000);
        tocarBeep();
    }

    function setStatus(classe, msg) {
        dot.className = classe;
        texto.textContent = msg;
    }

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g,'&amp;').replace(/</g,'&lt;')
            .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function tocarBeep() {
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.frequency.value = 880;
            osc.type = 'sine';
            gain.gain.setValueAtTime(0.3, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.4);
            osc.start(ctx.currentTime);
            osc.stop(ctx.currentTime + 0.4);
        } catch(e) {}
    }

    setInterval(() => {
        if (ws && ws.readyState === WebSocket.OPEN) ws.send(JSON.stringify({ tipo: 'ping' }));
    }, 30000);

    conectar();
</script>

</body>
</html>