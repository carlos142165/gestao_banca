<?php
/**
 * Arquivo de teste para o sistema global de celebração de plano
 * 
 * Use este arquivo para testar se a celebração funciona corretamente
 * em múltiplas páginas
 */

session_start();

// Se não está logado, finge um login de teste
if (!isset($_SESSION['usuario_id'])) {
    $_SESSION['usuario_id'] = 1;
    $_SESSION['usuario_nome'] = 'Usuário Teste';
}

// Simular mudança de plano (para teste)
if (isset($_GET['simular_plano'])) {
    $plano = $_GET['simular_plano'];
    if (in_array($plano, ['Gratuito', 'Prata', 'Ouro', 'Diamante'])) {
        $_SESSION['plano_teste'] = $plano;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste - Celebração Global</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/celebracao-plano.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
            color: #333;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        }
        
        h1 {
            color: #667eea;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .description {
            color: #666;
            margin-bottom: 30px;
            padding: 15px;
            background: #f5f5f5;
            border-left: 4px solid #667eea;
            border-radius: 4px;
        }
        
        .test-section {
            margin-bottom: 30px;
        }
        
        .test-section h2 {
            font-size: 18px;
            color: #333;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-group {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
        }
        
        .btn {
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-prata {
            background: #c0392b;
            color: white;
        }
        
        .btn-prata:hover {
            background: #a93226;
            transform: translateY(-2px);
        }
        
        .btn-ouro {
            background: #f39c12;
            color: white;
        }
        
        .btn-ouro:hover {
            background: #d68910;
            transform: translateY(-2px);
        }
        
        .btn-diamante {
            background: #2980b9;
            color: white;
        }
        
        .btn-diamante:hover {
            background: #1f618d;
            transform: translateY(-2px);
        }
        
        .info-box {
            background: #e3f2fd;
            border: 2px solid #2196F3;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            color: #1565c0;
        }
        
        .console {
            background: #1e1e1e;
            color: #00ff00;
            padding: 15px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            max-height: 200px;
            overflow-y: auto;
            margin-top: 15px;
        }
        
        .console-line {
            margin: 5px 0;
        }
        
        .console-success {
            color: #00ff00;
        }
        
        .console-error {
            color: #ff6b6b;
        }
        
        .console-info {
            color: #87ceeb;
        }
        
        .status {
            display: inline-block;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 10px;
        }
        
        .status-ok {
            background: #4caf50;
            color: white;
        }
        
        .status-warning {
            background: #ff9800;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>
            <i class="fas fa-fireworks"></i>
            Teste - Sistema Global de Celebração
        </h1>
        
        <div class="description">
            <i class="fas fa-info-circle"></i>
            Este arquivo testa o sistema de celebração de plano que funciona globalmente
            em qualquer página do site. Quando um plano muda, o modal deve aparecer automaticamente.
        </div>
        
        <!-- Seção 1: Status do Sistema -->
        <div class="test-section">
            <h2><i class="fas fa-heartbeat"></i> Status do Sistema</h2>
            <div class="info-box">
                <strong>Session ID do Usuário:</strong> <?php echo $_SESSION['usuario_id']; ?><br>
                <strong>Nome:</strong> <?php echo $_SESSION['usuario_nome']; ?><br>
                <strong>localStorage:</strong> Será preenchido automaticamente <span class="status status-warning">Aguardando</span><br>
                <strong>sessionStorage:</strong> Será preenchido automaticamente <span class="status status-warning">Aguardando</span>
            </div>
        </div>
        
        <!-- Seção 2: Simular Mudança de Plano -->
        <div class="test-section">
            <h2><i class="fas fa-gift"></i> Simular Mudança de Plano</h2>
            <p style="color: #666; margin-bottom: 15px;">Clique em um dos botões para simular uma mudança de plano. Recarregue a página ou clique em outro botão para ver a celebração.</p>
            <div class="btn-group">
                <a href="?simular_plano=Gratuito" class="btn btn-primary">
                    <i class="fas fa-gift"></i> Gratuito
                </a>
                <a href="?simular_plano=Prata" class="btn btn-prata">
                    <i class="fas fa-coins"></i> Prata
                </a>
                <a href="?simular_plano=Ouro" class="btn btn-ouro">
                    <i class="fas fa-star"></i> Ouro
                </a>
                <a href="?simular_plano=Diamante" class="btn btn-diamante">
                    <i class="fas fa-gem"></i> Diamante
                </a>
            </div>
        </div>
        
        <!-- Seção 3: Instruções de Teste -->
        <div class="test-section">
            <h2><i class="fas fa-list-check"></i> Como Testar</h2>
            <div style="background: #f9f9f9; padding: 20px; border-radius: 8px; border-left: 4px solid #667eea;">
                <ol style="margin-left: 20px; color: #555;">
                    <li><strong>Abra o DevTools (F12)</strong> e vá até a aba "Console"</li>
                    <li><strong>Clique em um plano acima</strong> para simular uma mudança</li>
                    <li><strong>Verifique o localStorage</strong> clicando em um plano diferente</li>
                    <li><strong>A celebração deve aparecer</strong> quando detectar a mudança</li>
                    <li><strong>Monitore os logs</strong> no console para ver o funcionamento</li>
                </ol>
                
                <div style="margin-top: 20px; padding: 15px; background: #fff3cd; border-radius: 4px; border-left: 4px solid #ffc107;">
                    <strong>💡 Dica:</strong> Abra duas abas desta página em paralelo. Mude de plano em uma aba e veja se a outra aba também mostra a celebração (via localStorage event).
                </div>
            </div>
        </div>
        
        <!-- Seção 4: Monitoramento do Console -->
        <div class="test-section">
            <h2><i class="fas fa-terminal"></i> Console (DevTools)</h2>
            <div class="info-box">
                Abra o DevTools (F12) e vá até a aba "Console" para ver os logs em tempo real
            </div>
            <div id="console" class="console">
                <div class="console-line console-info">🔍 Aguardando inicialização...</div>
            </div>
        </div>
        
        <!-- Seção 5: Informações Técnicas -->
        <div class="test-section">
            <h2><i class="fas fa-code"></i> Informações Técnicas</h2>
            <div style="background: #f5f5f5; padding: 15px; border-radius: 8px; font-family: monospace; font-size: 12px;">
                <p><strong>Arquivo de script:</strong> js/celebracao-plano.js</p>
                <p><strong>Arquivo de CSS:</strong> css/celebracao-plano.css</p>
                <p><strong>Classe principal:</strong> CelebracaoPlanoGlobal</p>
                <p><strong>API de dados:</strong> minha-conta.php?acao=obter_dados</p>
                <p><strong>Interval de verificação:</strong> 3 segundos</p>
                <p><strong>Storage usado:</strong> localStorage (persistente) + sessionStorage (sessão)</p>
            </div>
        </div>
        
        <div style="margin-top: 40px; padding-top: 20px; border-top: 2px solid #eee; color: #999; font-size: 12px;">
            <p>Este é um arquivo de teste. Delete-o do servidor de produção.</p>
        </div>
    </div>
    
    <!-- Sistema Global de Celebração -->
    <script src="js/celebracao-plano.js" defer></script>
    
    <!-- Interceptar logs para mostrar no console local -->
    <script>
        const originalLog = console.log;
        const originalError = console.error;
        const consoleDiv = document.getElementById('console');
        
        console.log = function(...args) {
            originalLog.apply(console, args);
            
            const message = args.join(' ');
            const line = document.createElement('div');
            line.className = 'console-line console-success';
            line.textContent = message;
            consoleDiv.appendChild(line);
            consoleDiv.scrollTop = consoleDiv.scrollHeight;
        };
        
        console.error = function(...args) {
            originalError.apply(console, args);
            
            const message = args.join(' ');
            const line = document.createElement('div');
            line.className = 'console-line console-error';
            line.textContent = message;
            consoleDiv.appendChild(line);
            consoleDiv.scrollTop = consoleDiv.scrollHeight;
        };
        
        // Limpar console inicial
        consoleDiv.innerHTML = '';
        
        // Log de inicialização
        console.log('📄 Página de teste carregada');
        console.log('🎉 Sistema de celebração foi inicializado');
    </script>
</body>
</html>
