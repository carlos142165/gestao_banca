<?php
/**
 * 🔔 TESTE DO SISTEMA DE NOTIFICAÇÕES
 * 
 * Use este arquivo para testar:
 * 1. Permissões de notificações
 * 2. Som de alerta
 * 3. Redirecionamento após clique
 */
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔔 Teste de Notificações</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            max-width: 600px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header h1 {
            color: #667eea;
            font-size: 28px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .header p {
            color: #666;
            font-size: 14px;
        }

        .test-section {
            margin-bottom: 30px;
            padding: 20px;
            background: #f5f5f5;
            border-radius: 12px;
            border-left: 4px solid #667eea;
        }

        .test-section h3 {
            color: #333;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .status {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 15px;
            font-size: 13px;
            font-weight: 500;
        }

        .status.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .status.warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }

        .status.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .button-group {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        button {
            flex: 1;
            min-width: 140px;
            padding: 12px 16px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        button.primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        button.primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }

        button.secondary {
            background: #e9ecef;
            color: #333;
            border: 2px solid #667eea;
        }

        button.secondary:hover {
            background: #667eea;
            color: white;
        }

        button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .code-block {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 15px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            overflow-x: auto;
            margin-top: 10px;
            line-height: 1.5;
        }

        .code-block code {
            display: block;
            margin-bottom: 5px;
        }

        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
            font-size: 13px;
            color: #1565c0;
        }

        .icon {
            font-size: 20px;
        }

        @media (max-width: 600px) {
            .container {
                padding: 20px;
            }

            .header h1 {
                font-size: 22px;
            }

            button {
                min-width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-bell icon"></i> Teste de Notificações</h1>
            <p>Teste o sistema de notificações com som e redirecionamento</p>
        </div>

        <!-- Seção 1: Status das Permissões -->
        <div class="test-section">
            <h3><i class="fas fa-key icon"></i> 1. Permissões do Navegador</h3>
            <div id="permission-status" class="status"></div>
            <button class="primary" onclick="verificarPermissao()">
                <i class="fas fa-check-circle"></i> Verificar Permissão
            </button>
            <button class="secondary" onclick="solicitarPermissao()">
                <i class="fas fa-envelope-open"></i> Solicitar Permissão
            </button>
            <div class="info-box">
                ℹ️ Se vir "granted", notificações já estão habilitadas. Se vir "denied", limpe os dados do site e tente novamente.
            </div>
        </div>

        <!-- Seção 2: Teste de Som -->
        <div class="test-section">
            <h3><i class="fas fa-volume-up icon"></i> 2. Teste de Som</h3>
            <div id="som-status" class="status"></div>
            <button class="primary" onclick="testarSom()">
                <i class="fas fa-music"></i> Tocar Som de Alerta
            </button>
            <div class="info-box">
                ℹ️ Você deveria escutar um "bip" curto. Se não ouve, verifique o volume do navegador e do sistema.
            </div>
        </div>

        <!-- Seção 3: Teste de Notificação Visual -->
        <div class="test-section">
            <h3><i class="fas fa-bell icon"></i> 3. Notificação Visual</h3>
            <div id="notif-status" class="status"></div>
            <button class="primary" onclick="testarNotificacao()">
                <i class="fas fa-paper-plane"></i> Enviar Notificação de Teste
            </button>
            <button class="secondary" onclick="testarNotificacaoCompleta()">
                <i class="fas fa-star"></i> Notificação Completa
            </button>
            <div class="info-box">
                ℹ️ Aparecerá uma notificação no canto da tela. Clique nela para ir para bot_aovivo.php.
            </div>
        </div>

        <!-- Seção 3B: Teste de Notificações Melhoradas (Novo Visual) -->
        <div class="test-section">
            <h3><i class="fas fa-image icon"></i> 3B. Notificações Melhoradas (Novo Visual)</h3>
            <div id="notif-novo-status" class="status"></div>
            <button class="primary" onclick="testarNotificacaoCantos()" style="background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);">
                <i class="fas fa-flag"></i> Teste CANTOS (Laranja)
            </button>
            <button class="primary" onclick="testarNotificacaoGols()" style="background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); margin-top: 10px;">
                <i class="fas fa-futbol"></i> Teste GOLS (Azul)
            </button>
            <div class="info-box">
                ℹ️ <strong>Novo:</strong> Notificações agora mostram ícone específico (canto/gols), times destacados e tipo claro no título!
            </div>
        </div>

        <!-- Seção 4: Verificação do Sistema -->
        <div class="test-section">
            <h3><i class="fas fa-gear icon"></i> 4. Verificação do Sistema</h3>
            <div id="system-status" class="status"></div>
            <button class="primary" onclick="verificarSistema()">
                <i class="fas fa-stethoscope"></i> Verificar Sistema
            </button>
            <div class="code-block" id="debug-info" style="display: none;"></div>
        </div>

        <!-- Seção 5: Informações Técnicas -->
        <div class="test-section">
            <h3><i class="fas fa-info-circle icon"></i> 5. Informações Técnicas</h3>
            <div id="tech-info" class="status"></div>
        </div>
    </div>

    <!-- Carregar sistema de notificações -->
    <script src="js/notificacoes-sistema.js?v=<?php echo time(); ?>"></script>

    <script>
        // Função para verificar permissão
        function verificarPermissao() {
            const status = document.getElementById('permission-status');
            const perm = Notification.permission;

            let html = '';
            if (perm === 'granted') {
                html = '<span class="success">✅ Permissão CONCEDIDA</span><br>Notificações estão habilitadas.';
                status.className = 'status success';
            } else if (perm === 'denied') {
                html = '<span class="error">❌ Permissão NEGADA</span><br>Você recusou notificações. Limpe os dados do site.';
                status.className = 'status error';
            } else {
                html = '<span class="warning">⏳ Permissão NÃO SOLICITADA</span><br>Clique em "Solicitar Permissão".';
                status.className = 'status warning';
            }

            status.innerHTML = html;
        }

        // Função para solicitar permissão
        function solicitarPermissao() {
            Notification.requestPermission().then((permission) => {
                verificarPermissao();
            });
        }

        // Função para testar som
        function testarSom() {
            const status = document.getElementById('som-status');
            status.className = 'status warning';
            status.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Reproduzindo som...';

            NotificacoesSistema.reproduzirSom();
            setTimeout(() => {
                status.className = 'status success';
                status.innerHTML = '✅ Som reproduzido! Se não ouviu nada, verifique o volume.';
            }, 500);
        }

        // Função para testar notificação simples
        function testarNotificacao() {
            const status = document.getElementById('notif-status');

            if (Notification.permission !== 'granted') {
                status.className = 'status error';
                status.innerHTML = '❌ Permissão não concedida. Solicite primeiro.';
                return;
            }

            NotificacoesSistema.mostrarNotificacao('🧪 Notificação de Teste', {
                body: 'Este é um teste do sistema de notificações',
                tag: 'test-notification'
            });

            status.className = 'status success';
            status.innerHTML = '✅ Notificação enviada! Verifique o canto da sua tela.';
        }

        // Função para testar notificação completa
        function testarNotificacaoCompleta() {
            const status = document.getElementById('notif-status');

            if (Notification.permission !== 'granted') {
                status.className = 'status error';
                status.innerHTML = '❌ Permissão não concedida. Solicite primeiro.';
                return;
            }

            NotificacoesSistema.notificarNovaMensagem({
                id: Math.random(),
                titulo: 'Teste Completo - Flamengo vs Botafogo',
                text: 'Teste Completo +0.5 GOLS | Odds: 1.85',
            });

            status.className = 'status success';
            status.innerHTML = '✅ Notificação completa enviada!';
        }

        // Função para testar notificação de CANTOS
        function testarNotificacaoCantos() {
            const status = document.getElementById('notif-novo-status');

            if (Notification.permission !== 'granted') {
                status.className = 'status error';
                status.innerHTML = '❌ Permissão não concedida. Solicite primeiro.';
                return;
            }

            NotificacoesSistema.notificarNovaMensagem({
                id: Math.random(),
                time_1: "Flamengo",
                time_2: "Botafogo",
                titulo: "🚩 OPORTUNIDADE DE CANTOS!",
                text: "Flamengo vs Botafogo | +1.5 CANTOS | Odds: 1.85"
            });

            status.className = 'status success';
            status.innerHTML = '✅ Notificação de CANTOS enviada! (Ícone laranja com bandeira)';
        }

        // Função para testar notificação de GOLS
        function testarNotificacaoGols() {
            const status = document.getElementById('notif-novo-status');

            if (Notification.permission !== 'granted') {
                status.className = 'status error';
                status.innerHTML = '❌ Permissão não concedida. Solicite primeiro.';
                return;
            }

            NotificacoesSistema.notificarNovaMensagem({
                id: Math.random(),
                time_1: "São Paulo",
                time_2: "Santos",
                titulo: "⚽ OPORTUNIDADE DE GOLS!",
                text: "São Paulo vs Santos | +0.5 GOLS | Odds: 1.65"
            });

            status.className = 'status success';
            status.innerHTML = '✅ Notificação de GOLS enviada! (Ícone azul com bola)';
        }

        // Função para verificar sistema
        function verificarSistema() {
            const status = document.getElementById('system-status');
            const debugDiv = document.getElementById('debug-info');

            let info = {
                notificacoes_api: typeof Notification !== 'undefined' ? '✅ Disponível' : '❌ Indisponível',
                web_audio_api: typeof AudioContext !== 'undefined' || typeof webkitAudioContext !== 'undefined' ? '✅ Disponível' : '❌ Indisponível',
                notificacoes_sistema: typeof NotificacoesSistema !== 'undefined' ? '✅ Carregado' : '❌ Não carregado',
                permissao_status: Notification.permission,
                sistema_pronto: typeof NotificacoesSistema !== 'undefined' && NotificacoesSistema.permissaoNotificacao ? '✅ Pronto' : '⏳ Aguardando permissão'
            };

            let html = '<code>';
            for (let [chave, valor] of Object.entries(info)) {
                html += chave + ': ' + valor + '<br>';
            }
            html += '</code>';

            debugDiv.innerHTML = html;
            debugDiv.style.display = 'block';

            // Status visual
            const temErros = Object.values(info).some(v => v.includes('❌'));
            status.className = 'status ' + (temErros ? 'warning' : 'success');
            status.innerHTML = temErros ? '⚠️ Alguns recursos indisponíveis' : '✅ Sistema pronto!';
        }

        // Carregar informações técnicas ao abrir
        window.addEventListener('load', () => {
            verificarPermissao();

            const techInfo = document.getElementById('tech-info');
            const userAgent = navigator.userAgent;
            const isHttps = window.location.protocol === 'https:';

            techInfo.innerHTML = `
                <strong>Navegador:</strong> ${userAgent.substring(0, 80)}...<br>
                <strong>Protocolo:</strong> ${isHttps ? '✅ HTTPS' : '⚠️ HTTP (pode limitar notificações)'}<br>
                <strong>JavaScript:</strong> ✅ Habilitado<br>
                <strong>Page Visibility:</strong> ${document.hidden ? 'Oculta' : 'Visível'}
            `;
        });
    </script>
</body>
</html>
