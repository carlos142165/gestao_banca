<?php
/**
 * 📋 PASSO A PASSO - Verificar e Testar Webhook
 * Acesse: http://localhost/gestao/gestao_banca/instrucoes.php
 */

date_default_timezone_set('America/Sao_Paulo');
header('Content-Type: text/html; charset=utf-8');

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instruções - Teste do Webhook</title>
    <style>
        * { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { margin: 0; padding: 20px; background: #f5f7fa; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
        
        h1 { color: #2c3e50; border-bottom: 4px solid #3498db; padding-bottom: 15px; }
        h2 { color: #34495e; margin-top: 30px; }
        
        .step { background: #ecf0f1; border-left: 5px solid #3498db; padding: 20px; margin: 20px 0; border-radius: 5px; }
        .step.completed { border-left-color: #27ae60; background: #d5f4e6; }
        .step.failed { border-left-color: #e74c3c; background: #fadbd8; }
        
        .step-number { 
            display: inline-block; 
            width: 35px; 
            height: 35px; 
            background: #3498db; 
            color: white; 
            border-radius: 50%; 
            text-align: center; 
            line-height: 35px; 
            font-weight: bold; 
            margin-right: 10px;
        }
        .step.completed .step-number { background: #27ae60; }
        .step.failed .step-number { background: #e74c3c; }
        
        .code-block { 
            background: #2c3e50; 
            color: #ecf0f1; 
            padding: 15px; 
            border-radius: 5px; 
            overflow-x: auto; 
            font-family: 'Courier New', monospace;
            margin: 15px 0;
        }
        
        button { 
            background: #3498db; 
            color: white; 
            padding: 12px 20px; 
            border: none; 
            border-radius: 5px; 
            cursor: pointer; 
            font-size: 14px; 
            margin: 10px 5px 10px 0;
        }
        button:hover { background: #2980b9; }
        button.success { background: #27ae60; }
        button.danger { background: #e74c3c; }
        
        .alert { padding: 15px; margin: 15px 0; border-radius: 5px; }
        .alert.info { background: #d1ecf1; border-left: 4px solid #17a2b8; color: #0c5460; }
        .alert.success { background: #d4edda; border-left: 4px solid #28a745; color: #155724; }
        .alert.warning { background: #fff3cd; border-left: 4px solid #ffc107; color: #856404; }
        .alert.danger { background: #f8d7da; border-left: 4px solid #dc3545; color: #721c24; }
        
        .check { color: #27ae60; font-weight: bold; }
        .cross { color: #e74c3c; font-weight: bold; }
        
        .link-btn { 
            background: #16a085; 
            color: white; 
            padding: 15px 20px; 
            border-radius: 5px; 
            display: inline-block; 
            text-decoration: none; 
            margin: 10px 5px 10px 0;
        }
        .link-btn:hover { background: #138d75; }
        
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 20px 0; }
        @media (max-width: 768px) { .grid { grid-template-columns: 1fr; } }
        
        .card { background: #f8f9fa; padding: 20px; border-radius: 5px; border: 1px solid #ddd; }
    </style>
</head>
<body>

<div class="container">
    <h1>📋 Instruções: Teste Completo do Webhook Telegram</h1>
    
    <div class="alert alert-info">
        <strong>ℹ️ Objetivo:</strong> Verificar se as mensagens do Telegram estão chegando corretamente no banco de dados local
    </div>

    <?php
    require_once 'config.php';
    
    $env = defined('ENVIRONMENT') ? ENVIRONMENT : 'desconhecido';
    $isLocal = $env === 'local';
    $colorClass = $isLocal ? 'success' : 'warning';
    $icon = $isLocal ? '✅' : '⚠️';
    
    echo '<div class="alert alert-' . $colorClass . '">';
    echo '<strong>' . $icon . ' Ambiente Detectado:</strong> ';
    echo '<code>' . strtoupper($env) . '</code> ';
    echo '(Banco: <code>' . DB_NAME . '</code>)';
    echo '</div>';
    ?>

    <h2>🚀 PASSO A PASSO</h2>

    <!-- PASSO 1 -->
    <div class="step">
        <span class="step-number">1</span>
        <strong>Verifique o Ambiente</strong>
        
        <p>Primeiro, confirme que você está em <strong>LOCAL</strong>:</p>
        <div class="code-block">
Ambiente: <?php echo strtoupper($env); ?>
Host: <?php echo DB_HOST; ?>
Banco: <?php echo DB_NAME; ?>
        </div>
        
        <p><strong>Esperado:</strong></p>
        <ul>
            <li>✅ Ambiente: <code>LOCAL</code></li>
            <li>✅ Host: <code>localhost</code></li>
            <li>✅ Banco: <code>formulario-carlos</code></li>
        </ul>
        
        <p><strong>Se não estiver assim:</strong></p>
        <ul>
            <li>❌ Acesse: <code>http://localhost/gestao/gestao_banca/instrucoes.php</code> (não por IP)</li>
            <li>❌ Se mesmo assim não funcionar, abra <a href="diagnostico-ambiente.php" class="link-btn">Diagnóstico Completo</a></li>
        </ul>
    </div>

    <!-- PASSO 2 -->
    <div class="step">
        <span class="step-number">2</span>
        <strong>Envie uma Mensagem no Telegram</strong>
        
        <p><strong>Abra o canal:</strong> <code>Bateubet_VIP</code></p>
        
        <p><strong>Copie e cole uma destas mensagens:</strong></p>
        
        <div class="code-block">
Oportunidade! 🚨

📊 OVER ( +0.5 ⚽GOL FT )

⚽ Flamengo (H) x Botafogo (A) (ao vivo)
Placar: 1 - 0
Gols over +0.5: 2.04
        </div>
        
        <p><strong>Ou copie este formato mais simples:</strong></p>
        
        <div class="code-block">
Oportunidade! 🚨
📊 OVER ( +2.5 ⚽GOLS )
Time A (H) x Time B (A)
Placar: 0 - 0
        </div>
        
        <p><strong>⏱️ Aguarde 2-3 segundos</strong> para a mensagem ser processada pelo webhook</p>
    </div>

    <!-- PASSO 3 -->
    <div class="step">
        <span class="step-number">3</span>
        <strong>Verifique se a Mensagem Foi Salva no Banco</strong>
        
        <p>Acesse este link para ver todas as mensagens salvas:</p>
        
        <a href="teste-webhook.php" class="link-btn">📊 Ver Mensagens no Banco</a>
        
        <p><strong>O que esperar:</strong></p>
        <ul>
            <li>✅ Tabela "Últimas 10 Mensagens"</li>
            <li>✅ Sua mensagem deve estar na lista (ID mais alto)</li>
            <li>✅ Título: deve conter "OVER" e o tipo de aposta</li>
        </ul>
        
        <p><strong>Se não aparecer:</strong></p>
        <ul>
            <li>❌ Volte ao passo 1 - verifique o ambiente</li>
            <li>❌ Verifique o Log do Webhook em <a href="teste-webhook.php">teste-webhook.php</a></li>
            <li>❌ Se houver erro, releia a mensagem do erro</li>
        </ul>
    </div>

    <!-- PASSO 4 -->
    <div class="step">
        <span class="step-number">4</span>
        <strong>Verifique se Aparece no Bot ao Vivo</strong>
        
        <p>Agora verifique se a mensagem aparece na interface:</p>
        
        <a href="bot_aovivo.php" class="link-btn">🤖 Abrir Bot ao Vivo</a>
        
        <p><strong>O que esperar:</strong></p>
        <ul>
            <li>✅ Página carrega normalmente</li>
            <li>✅ Bloco 1 (à esquerda) mostra as mensagens</li>
            <li>✅ Sua mensagem aparece com data/hora</li>
            <li>✅ Card com tipo de aposta (ex: "+0.5 GOL FT")</li>
        </ul>
        
        <p><strong>Se não aparecer:</strong></p>
        <ul>
            <li>❌ Recarregue a página (F5)</li>
            <li>❌ Abra o Console (F12) e procure por erros</li>
            <li>❌ Verifique se a mensagem existe em <a href="teste-webhook.php">teste-webhook.php</a></li>
        </ul>
    </div>

    <!-- PASSO 5 -->
    <div class="step">
        <span class="step-number">5</span>
        <strong>Teste o Filtro (Opcional)</strong>
        
        <p>Se tudo funcionou, teste o filtro de reembolso:</p>
        
        <ul>
            <li>Envie mensagem com <code>+0.5 ⚽GOL FT</code></li>
            <li>Abra o Bot ao Vivo</li>
            <li>Clique no card de "+0.5 ⚽GOL FT"</li>
            <li>Verifique se apenas resultados GREEN e RED aparecem (sem REEMBOLSO)</li>
        </ul>
    </div>

    <h2>🔧 Se Algo Não Funcionar</h2>

    <div class="grid">
        <div class="card">
            <h3>❌ Mensagem não aparece em "teste-webhook.php"</h3>
            <p><strong>Possíveis causas:</strong></p>
            <ol>
                <li>Webhook não configurado no Telegram</li>
                <li>Banco local não está funcionando</li>
                <li>Tabela "bote" não existe</li>
                <li>Erro de permissão ou credenciais</li>
            </ol>
            <p><strong>Solução:</strong></p>
            <a href="diagnostico-ambiente.php" class="link-btn">🔍 Abrir Diagnóstico</a>
            <a href="setup.php" class="link-btn">🔧 Configurar Webhook</a>
        </div>

        <div class="card">
            <h3>❌ Mensagem aparece em "teste-webhook.php" mas não no Bot</h3>
            <p><strong>Possíveis causas:</strong></p>
            <ol>
                <li>Erro no JavaScript de carregamento</li>
                <li>Cache do navegador</li>
                <li>Erro em "carregar-mensagens-banco.php"</li>
                <li>Problema com parsing de dados</li>
            </ol>
            <p><strong>Solução:</strong></p>
            <button onclick="window.location.reload()">🔄 Recarregar (F5)</button>
            <button onclick="document.location.href='bot_aovivo.php?nocache=' + Date.now()">🔄 Forçar Recarga</button>
            <br><small>Abra F12 e procure por erros no console</small>
        </div>

        <div class="card">
            <h3>❌ Erro "Conexão recusada"</h3>
            <p><strong>Possíveis causas:</strong></p>
            <ol>
                <li>XAMPP não está rodando</li>
                <li>MySQL não está ativo</li>
                <li>Banco local não existe</li>
                <li>Credenciais incorretas</li>
            </ol>
            <p><strong>Solução:</strong></p>
            <ol>
                <li>Abra XAMPP Control Panel</li>
                <li>Clique em "Start" para Apache</li>
                <li>Clique em "Start" para MySQL</li>
                <li>Recarregue a página</li>
            </ol>
        </div>

        <div class="card">
            <h3>❌ Erro "Tabela 'bote' não existe"</h3>
            <p><strong>Solução:</strong></p>
            <ol>
                <li>Abra phpMyAdmin</li>
                <li>Selecione banco: <code>formulario-carlos</code></li>
                <li>Procure pela tabela <code>bote</code></li>
                <li>Se não existir, execute SQL de criação</li>
            </ol>
            <p><small>Peça o SQL para criar a tabela</small></p>
        </div>
    </div>

    <h2>📞 Checklist Antes de Pedir Ajuda</h2>

    <div class="alert alert-warning">
        <p><strong>Verifique todos estes itens:</strong></p>
        <ul>
            <li>☐ XAMPP está rodando (Apache + MySQL)</li>
            <li>☐ Acesso via http://localhost (não por IP)</li>
            <li>☐ Ambiente mostra: LOCAL</li>
            <li>☐ Banco mostra: formulario-carlos</li>
            <li>☐ Mensagem foi enviada no Telegram</li>
            <li>☐ Aguardou 2-3 segundos</li>
            <li>☐ Verifique em teste-webhook.php</li>
            <li>☐ Se aparecer lá, verifique bot_aovivo.php</li>
            <li>☐ Abra F12 e procure por erros no console</li>
        </ul>
    </div>

    <h2>🎯 Resumo do Fluxo</h2>

    <div class="code-block">
TELEGRAM
   ↓ (mensagem)
WEBHOOK (telegram-webhook.php)
   ↓ (config.php detecta: LOCAL)
BANCO LOCAL (formulario-carlos)
   ↓ (salva mensagem)
Frontend (carregar-mensagens-banco.php)
   ↓ (config.php detecta: LOCAL)
BOT AO VIVO (bot_aovivo.php)
   ↓ (exibe no BLOCO 1)
USUÁRIO VÊ A MENSAGEM ✅
    </div>

    <div style="text-align: center; margin-top: 40px;">
        <a href="diagnostico-ambiente.php" class="link-btn">🔍 Diagnóstico Completo</a>
        <a href="teste-webhook.php" class="link-btn">📊 Ver Banco</a>
        <a href="bot_aovivo.php" class="link-btn">🤖 Bot ao Vivo</a>
        <a href="setup.php" class="link-btn">🔧 Setup Webhook</a>
    </div>

    <hr style="margin-top: 40px; border: none; border-top: 2px solid #ddd;">
    <p style="text-align: center; color: #7f8c8d; font-size: 12px;">
        Última atualização: <?php echo date('d/m/Y H:i:s'); ?>
    </p>
</div>

</body>
</html>
