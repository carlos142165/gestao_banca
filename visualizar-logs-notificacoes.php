<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📝 Visualizar Logs de Notificações</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .header h1 {
            color: #667eea;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .controles {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 15px;
        }

        .controles input {
            flex: 1;
            min-width: 200px;
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        .controles button {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s;
        }

        .btn-carregar {
            background: #667eea;
            color: white;
        }

        .btn-carregar:hover {
            background: #5568d3;
        }

        .btn-limpar {
            background: #ff6b6b;
            color: white;
        }

        .btn-limpar:hover {
            background: #ee5a52;
        }

        .stats {
            display: flex;
            gap: 15px;
            margin-top: 15px;
            flex-wrap: wrap;
        }

        .stat {
            background: #f5f5f5;
            padding: 10px 15px;
            border-radius: 5px;
            font-size: 14px;
        }

        .logs-container {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .log-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            gap: 15px;
            transition: background 0.2s;
        }

        .log-item:hover {
            background: #f9f9f9;
        }

        .log-item:last-child {
            border-bottom: none;
        }

        .log-tipo {
            min-width: 80px;
            padding: 5px 10px;
            border-radius: 5px;
            text-align: center;
            font-weight: bold;
            font-size: 12px;
        }

        .tipo-sucesso {
            background: #d4edda;
            color: #155724;
        }

        .tipo-info {
            background: #d1ecf1;
            color: #0c5460;
        }

        .tipo-aviso {
            background: #fff3cd;
            color: #856404;
        }

        .tipo-erro {
            background: #f8d7da;
            color: #721c24;
        }

        .log-conteudo {
            flex: 1;
        }

        .log-timestamp {
            font-size: 12px;
            color: #999;
            margin-bottom: 5px;
        }

        .log-mensagem {
            margin-bottom: 5px;
            color: #333;
        }

        .log-dados {
            font-size: 12px;
            background: #f5f5f5;
            padding: 8px;
            border-radius: 3px;
            overflow-x: auto;
            font-family: 'Courier New', monospace;
            color: #666;
        }

        .log-ip {
            font-size: 11px;
            color: #aaa;
            margin-top: 5px;
        }

        .loading {
            text-align: center;
            padding: 40px;
            color: #999;
        }

        .empty {
            text-align: center;
            padding: 40px;
            color: #999;
        }

        .filtro-badge {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 12px;
            margin-left: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>
                <i class="fas fa-file-alt"></i>
                Logs de Notificações
            </h1>
            
            <div class="controles">
                <input 
                    type="text" 
                    id="filtro" 
                    placeholder="🔍 Filtrar logs (tipo, mensagem, dados...)"
                >
                <button class="btn-carregar" onclick="carregarLogs()">
                    <i class="fas fa-sync"></i> Carregar
                </button>
                <button class="btn-limpar" onclick="limparLogs()">
                    <i class="fas fa-trash"></i> Limpar
                </button>
            </div>

            <div class="stats">
                <div class="stat">
                    <strong>📊 Total:</strong> <span id="total-logs">0</span>
                </div>
                <div class="stat">
                    <strong>✅ Sucesso:</strong> <span id="total-sucesso">0</span>
                </div>
                <div class="stat">
                    <strong>ℹ️ Info:</strong> <span id="total-info">0</span>
                </div>
                <div class="stat">
                    <strong>⚠️ Aviso:</strong> <span id="total-aviso">0</span>
                </div>
                <div class="stat">
                    <strong>❌ Erro:</strong> <span id="total-erro">0</span>
                </div>
            </div>
        </div>

        <div class="logs-container" id="logs-container">
            <div class="loading">
                <i class="fas fa-spinner fa-spin"></i> Carregando logs...
            </div>
        </div>
    </div>

    <script>
        function carregarLogs() {
            const filtro = document.getElementById('filtro').value;
            const container = document.getElementById('logs-container');
            
            container.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i> Carregando logs...</div>';
            
            const url = `logs/LogNotificacoes.php?acao=visualizar_logs${filtro ? '&filtro=' + encodeURIComponent(filtro) : ''}`;
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.logs.length === 0) {
                        container.innerHTML = '<div class="empty"><i class="fas fa-inbox"></i><br><br>Nenhum log encontrado</div>';
                        atualizarStats([], filtro);
                        return;
                    }
                    
                    let html = '';
                    data.logs.forEach(log => {
                        const tipoClasse = `tipo-${log.tipo}`;
                        const icone = {
                            'sucesso': '✅',
                            'info': 'ℹ️',
                            'aviso': '⚠️',
                            'erro': '❌'
                        }[log.tipo] || '•';
                        
                        html += `
                            <div class="log-item">
                                <div class="log-tipo ${tipoClasse}">
                                    ${icone} ${log.tipo.toUpperCase()}
                                </div>
                                <div class="log-conteudo">
                                    <div class="log-timestamp">
                                        ${log.timestamp} | ${log.ip}
                                    </div>
                                    <div class="log-mensagem">
                                        <strong>${log.mensagem}</strong>
                                    </div>
                                    ${log.dados && Object.keys(log.dados).length > 0 ? `
                                        <div class="log-dados">
                                            ${JSON.stringify(log.dados, null, 2)}
                                        </div>
                                    ` : ''}
                                    <div class="log-ip">
                                        ${log.url}
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    
                    container.innerHTML = html;
                    atualizarStats(data.logs, filtro);
                })
                .catch(error => {
                    console.error('Erro:', error);
                    container.innerHTML = `<div class="empty"><i class="fas fa-exclamation-triangle"></i><br><br>Erro ao carregar logs: ${error.message}</div>`;
                });
        }

        function limparLogs() {
            if (confirm('Tem certeza que deseja limpar todos os logs?')) {
                // Implementar limpeza via PHP se necessário
                alert('Função de limpeza não implementada ainda');
            }
        }

        function atualizarStats(logs, filtro) {
            const stats = {
                total: logs.length,
                sucesso: logs.filter(l => l.tipo === 'sucesso').length,
                info: logs.filter(l => l.tipo === 'info').length,
                aviso: logs.filter(l => l.tipo === 'aviso').length,
                erro: logs.filter(l => l.tipo === 'erro').length
            };

            document.getElementById('total-logs').textContent = stats.total;
            document.getElementById('total-sucesso').textContent = stats.sucesso;
            document.getElementById('total-info').textContent = stats.info;
            document.getElementById('total-aviso').textContent = stats.aviso;
            document.getElementById('total-erro').textContent = stats.erro;
        }

        // Carregar logs ao abrir a página
        document.addEventListener('DOMContentLoaded', carregarLogs);

        // Auto-recarregar a cada 5 segundos
        setInterval(carregarLogs, 5000);

        // Carregar ao pressionar Enter no filtro
        document.getElementById('filtro').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                carregarLogs();
            }
        });
    </script>
</body>
</html>
