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

        /* Status badge */
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

        #ws-dot {
            width: 10px; height: 10px;
            border-radius: 50%;
            background: #888;
            transition: background 0.3s;
        }

        #ws-dot.conectado  { background: #4caf50; box-shadow: 0 0 8px #4caf50; }
        #ws-dot.erro       { background: #f44336; }
        #ws-dot.conectando { background: #ff9800; animation: pulse 1s infinite; }

        @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:0.4} }

        /* Layout */
        .admin-body {
            display: grid;
            grid-template-columns: 1fr 340px;
            gap: 24px;
            padding: 28px 32px;
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Pedidos em tempo real */
        .painel-live h2, .historico h2 {
            font-size: 1rem;
            color: #d4a056;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 16px;
            padding-bottom: 8px;
            border-bottom: 1px solid #4a2c0a;
        }

        /* Cards de pedido novo */
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

        #sem-pedidos {
            text-align: center;
            color: #555;
            padding: 40px;
            font-size: 0.9rem;
        }

        /* Histórico */
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

        /* Contador */
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

        /* Som de notificação visual */
        .flash {
            animation: flash 0.5s;
        }
        @keyframes flash {
            0%,100% { background: linear-gradient(135deg, #2c1503, #3a1f08); }
            50%      { background: linear-gradient(135deg, #4a2c08, #6b3d10); }
        }

        /* Scrollbar */
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

    <!-- Coluna esquerda: eventos ao vivo -->
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

        <h2>🔔 Pedidos ao Vivo</h2>

        <div id="feed">
            <div id="sem-pedidos">Aguardando novos pedidos...</div>
        </div>
    </div>

    <!-- Coluna direita: histórico do banco -->
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
    // ── Estado ───────────────────────────────────────────────────────────
    let novosCount = 0;
    let ws = null;
    let reconnectTimer = null;

    const dot    = document.getElementById('ws-dot');
    const texto  = document.getElementById('ws-texto');
    const feed   = document.getElementById('feed');
    const cntNov = document.getElementById('cnt-novos');
    const semPed = document.getElementById('sem-pedidos');

    // Porta do WebSocket — mesma máquina, porta 8082
    const WS_URL = `ws://${location.hostname}:8082`;

    // ── Conecta WebSocket ─────────────────────────────────────────────────
    function conectar() {
        setStatus('conectando', 'Conectando...');

        ws = new WebSocket(WS_URL);

        ws.onopen = () => {
            setStatus('conectado', 'Conectado ✓');
            clearTimeout(reconnectTimer);
        };

        ws.onmessage = (evt) => {
            try {
                const data = JSON.parse(evt.data);
                if (data.tipo === 'novo_pedido') {
                    adicionarPedidoAoFeed(data);
                }
            } catch (e) {
                console.warn('Mensagem inválida:', evt.data);
            }
        };

        ws.onclose = () => {
            setStatus('erro', 'Desconectado — reconectando...');
            reconnectTimer = setTimeout(conectar, 3000);
        };

        ws.onerror = () => {
            setStatus('erro', 'Erro de conexão');
        };
    }

    // ── Adiciona card de pedido novo ──────────────────────────────────────
    function adicionarPedidoAoFeed(data) {
        // Remove placeholder
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

        // Insere no topo do feed
        feed.insertBefore(card, feed.firstChild);

        // Notificação no título da aba
        document.title = `(${novosCount}) ☕ Novo pedido!`;
        setTimeout(() => { document.title = '☕ Painel Admin — Cafeteria'; }, 3000);

        // Toca beep via AudioContext
        tocarBeep();
    }

    // ── Helpers ───────────────────────────────────────────────────────────
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
        } catch(e) { /* silencia se não suportado */ }
    }

    // ── Ping para manter conexão ──────────────────────────────────────────
    setInterval(() => {
        if (ws && ws.readyState === WebSocket.OPEN) {
            ws.send(JSON.stringify({ tipo: 'ping' }));
        }
    }, 30000);

    // ── Inicia ────────────────────────────────────────────────────────────
    conectar();
</script>

</body>
</html>
