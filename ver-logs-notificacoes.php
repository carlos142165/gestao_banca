<?php
// Backend para carregar/limpar logs - DEVE VIR ANTES DO HTML

$acao = $_GET['acao'] ?? $_POST['acao'] ?? '';
$logsDir = __DIR__ . '/logs';

// Criar pasta se não existir
if (!is_dir($logsDir)) {
    mkdir($logsDir, 0777, true);
}

if ($acao === 'carregar') {
    header('Content-Type: application/json');
    // Carregar logs do dia
    $arquivo = $logsDir . '/notif-' . date('Y-m-d') . '.log';
    
    if (!file_exists($arquivo)) {
        echo json_encode(['logs' => []]);
        exit;
    }
    
    $linhas = file($arquivo, FILE_IGNORE_NEW_LINES);
    $logs = [];
    
    foreach ($linhas as $linha) {
        if (empty($linha)) continue;
        $entrada = json_decode($linha, true);
        if ($entrada) {
            $logs[] = $entrada;
        }
    }
    
    echo json_encode(['logs' => array_reverse($logs)]);
    exit;
    
} elseif ($acao === 'limpar') {
    header('Content-Type: application/json');
    // Limpar logs do dia
    $arquivo = $logsDir . '/notif-' . date('Y-m-d') . '.log';
    
    if (file_exists($arquivo)) {
        unlink($arquivo);
    }
    
    echo json_encode(['sucesso' => true]);
    exit;
}

// Se não foi chamada uma ação, mostra o HTML
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📋 Logs de Notificações</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Monaco', 'Menlo', 'Consolas', monospace;
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 20px;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: #252526;
            border: 1px solid #3e3e42;
            border-radius: 4px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.5);
        }
        
        .header {
            background: #007acc;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #1e1e1e;
        }
        
        .header h1 {
            font-size: 18px;
            font-weight: 600;
            margin: 0;
        }
        
        .controls {
            display: flex;
            gap: 10px;
        }
        
        button {
            background: #0e639c;
            color: #fff;
            border: 1px solid #007acc;
            padding: 6px 12px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 12px;
            font-family: inherit;
            transition: all 0.2s;
        }
        
        button:hover {
            background: #1177bb;
            border-color: #3399ff;
        }
        
        button.active {
            background: #cc8800;
            border-color: #ffaa00;
        }
        
        .logs-container {
            height: 600px;
            overflow-y: auto;
            padding: 15px;
            background: #1e1e1e;
        }
        
        .log-entry {
            margin-bottom: 12px;
            padding: 10px;
            background: #252526;
            border-left: 3px solid #007acc;
            border-radius: 2px;
            font-size: 12px;
            word-wrap: break-word;
        }
        
        .log-entry.detectar { border-left-color: #4ec9b0; }
        .log-entry.notificacao { border-left-color: #ce9178; }
        .log-entry.erro { border-left-color: #f48771; }
        .log-entry.aviso { border-left-color: #dcdcaa; }
        .log-entry.info { border-left-color: #569cd6; }
        
        .timestamp {
            color: #858585;
            font-size: 11px;
            margin-right: 8px;
        }
        
        .tipo {
            color: #4ec9b0;
            font-weight: bold;
            margin-right: 8px;
        }
        
        .tipo.DETECTAR_TIPO { color: #4ec9b0; }
        .tipo.NOTIFICACAO { color: #ce9178; }
        .tipo.info { color: #569cd6; }
        
        .dados {
            color: #d4d4d4;
            margin-top: 5px;
            padding-left: 10px;
            border-left: 2px solid #3e3e42;
            font-size: 11px;
            max-height: 200px;
            overflow-y: auto;
        }
        
        .empty {
            text-align: center;
            color: #858585;
            padding: 40px;
            font-size: 14px;
        }
        
        .scrollbar-hint {
            color: #858585;
            font-size: 10px;
            text-align: right;
            padding: 10px 15px;
            border-top: 1px solid #3e3e42;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📋 Logs de Notificações</h1>
            <div class="controls">
                <button id="btnRefresh">🔄 Recarregar</button>
                <button id="btnAutoRefresh">⏱️ Auto (5s)</button>
                <button id="btnTeste">🧪 Teste</button>
                <button id="btnTestNotif">🔔 Testar Notif</button>
                <button id="btnLimpar">🗑️ Limpar</button>
            </div>
        </div>
        
        <div class="logs-container" id="logsContainer">
            <div class="empty">📭 Carregando logs...</div>
        </div>
        
        <div class="scrollbar-hint">↓ Scroll para ver mais</div>
    </div>

    <script>
        let autoRefreshActive = false;
        let autoRefreshInterval = null;
        
        // Carregar logs ao iniciar
        carregarLogs();
        
        // Eventos dos botões
        document.getElementById('btnRefresh').addEventListener('click', carregarLogs);
        document.getElementById('btnAutoRefresh').addEventListener('click', toggleAutoRefresh);
        document.getElementById('btnTeste').addEventListener('click', enviarTeste);
        document.getElementById('btnTestNotif').addEventListener('click', testarNotificacao);
        document.getElementById('btnLimpar').addEventListener('click', limparLogs);
        
        function carregarLogs() {
            const container = document.getElementById('logsContainer');
            
            // Lê arquivo de log do dia atual
            fetch('?acao=carregar')
                .then(r => r.json())
                .then(data => {
                    if (!data.logs || data.logs.length === 0) {
                        container.innerHTML = '<div class="empty">📭 Nenhum log registrado ainda</div>';
                        return;
                    }
                    
                    let html = '';
                    data.logs.forEach(entrada => {
                        const tipo = entrada.tipo || 'unknown';
                        const timestamp = entrada.timestamp || '??:??:??';
                        const classes = `log-entry ${tipo.toLowerCase().replace('_tipo', '').toLowerCase()}`;
                        
                        html += `<div class="${classes}">
                            <span class="timestamp">[${timestamp}]</span>
                            <span class="tipo tipo-${tipo}">${tipo}</span>
                            ${entrada.detectado ? `<strong style="color: #4ec9b0;">${entrada.detectado}</strong>` : ''}
                            ${entrada.resultado ? `→ <strong style="color: #ce9178;">${entrada.resultado}</strong>` : ''}
                            <div class="dados">${JSON.stringify(entrada.dados || {}, null, 2)}</div>
                        </div>`;
                    });
                    
                    container.innerHTML = html;
                    container.scrollTop = container.scrollHeight;
                })
                .catch(err => {
                    container.innerHTML = `<div class="empty">❌ Erro ao carregar: ${err.message}</div>`;
                });
        }
        
        function toggleAutoRefresh() {
            autoRefreshActive = !autoRefreshActive;
            const btn = document.getElementById('btnAutoRefresh');
            
            if (autoRefreshActive) {
                btn.classList.add('active');
                btn.textContent = '⏱️ Auto ON';
                autoRefreshInterval = setInterval(carregarLogs, 5000);
            } else {
                btn.classList.remove('active');
                btn.textContent = '⏱️ Auto (5s)';
                clearInterval(autoRefreshInterval);
            }
        }
        
        function limparLogs() {
            if (!confirm('Tem certeza que deseja limpar todos os logs?')) return;
            
            fetch('?acao=limpar', { method: 'POST' })
                .then(r => r.json())
                .then(data => {
                    if (data.sucesso) {
                        carregarLogs();
                    }
                });
        }
        
        function enviarTeste() {
            console.log('🧪 Enviando log de teste...');
            
            fetch('/gestao/gestao_banca/registrar-log-notificacao.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    tipo: 'TESTE_MANUAL',
                    dados: {
                        titulo: '🧪 Teste Manual',
                        mensagem: 'Log enviado manualmente do visualizador',
                        timestamp: new Date().toLocaleTimeString('pt-BR')
                    }
                })
            })
            .then(r => r.json())
            .then(data => {
                console.log('✅ Teste enviado:', data);
                alert('✅ Log de teste enviado! Verifique abaixo.');
                setTimeout(carregarLogs, 500);
            })
            .catch(err => {
                console.error('❌ Erro ao enviar teste:', err);
                alert('❌ Erro ao enviar teste: ' + err.message);
            });
        }
        
        function testarNotificacao() {
            console.log('🔔 Testando notificação do navegador...');
            
            // Verificar suporte
            if (!("Notification" in window)) {
                alert('❌ Navegador não suporta notificações');
                return;
            }
            
            console.log('Permissão atual:', Notification.permission);
            alert('Permissão de notificação: ' + Notification.permission);
            
            // Se não tem permissão, pedir
            if (Notification.permission === 'default') {
                console.log('🔔 Pedindo permissão...');
                Notification.requestPermission().then(permission => {
                    alert('Resposta: ' + permission);
                    console.log('Resposta:', permission);
                    if (permission === 'granted') {
                        mostrarNotificacaoTeste();
                    }
                });
            } else if (Notification.permission === 'granted') {
                console.log('✅ Permissão já concedida, mostrando notificação...');
                mostrarNotificacaoTeste();
            } else {
                alert('❌ Permissão bloqueada. Desbloqueia nas configurações do navegador.');
            }
        }
        
        function mostrarNotificacaoTeste() {
            try {
                const n = new Notification('🧪 Teste de Notificação', {
                    body: 'Se você vê isso, as notificações funcionam!',
                    icon: 'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><text y="75" font-size="75">🔔</text></svg>',
                    badge: 'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><text y="75" font-size="75">🔔</text></svg>'
                });
                console.log('✅ Notificação criada');
                setTimeout(() => n.close(), 5000);
            } catch (err) {
                console.error('❌ Erro ao criar notificação:', err);
                alert('❌ Erro: ' + err.message);
            }
        }
    </script>
</body>
</html>
