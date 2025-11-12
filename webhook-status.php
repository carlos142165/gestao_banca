<?php
/**
 * DASHBOARD DE MONITORAMENTO DO WEBHOOK
 * Mostra status em tempo real
 */

require_once 'config.php';

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status do Webhook</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: #0f1419;
            color: #fff;
            padding: 20px;
        }
        .container { max-width: 900px; margin: 0 auto; }
        h1 { margin-bottom: 20px; color: #00d4ff; }
        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        .status-card {
            background: #1a1f26;
            border: 2px solid #333;
            border-radius: 8px;
            padding: 15px;
            transition: all 0.3s;
        }
        .status-card.ok { border-color: #00ff88; }
        .status-card.error { border-color: #ff3333; }
        .status-card.warning { border-color: #ffaa00; }
        .status-card h3 {
            font-size: 14px;
            color: #00d4ff;
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        .status-value {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .status-ok { color: #00ff88; }
        .status-error { color: #ff3333; }
        .status-warning { color: #ffaa00; }
        .status-info { color: #00d4ff; }
        .status-detail {
            font-size: 12px;
            color: #999;
            margin-top: 5px;
        }
        .log-section {
            background: #1a1f26;
            border: 1px solid #333;
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
        }
        .log-section h2 {
            color: #00d4ff;
            margin-bottom: 10px;
            font-size: 16px;
        }
        .log-lines {
            background: #0f1419;
            border: 1px solid #333;
            border-radius: 4px;
            padding: 10px;
            max-height: 400px;
            overflow-y: auto;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            line-height: 1.5;
        }
        .log-line {
            padding: 3px 0;
            color: #ccc;
        }
        .log-line.error { color: #ff3333; }
        .log-line.success { color: #00ff88; }
        .log-line.warning { color: #ffaa00; }
        .log-line.info { color: #00d4ff; }
        .refresh-btn {
            background: #00d4ff;
            color: #000;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            margin-top: 10px;
        }
        .refresh-btn:hover { background: #00ffff; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Status do Webhook do Telegram</h1>
        
        <div class="status-grid">
            <?php
            // 1. Status do Banco
            $dbStatus = 'ok';
            $dbMessage = 'Conectado';
            if (!$conexao || !$conexao->ping()) {
                $dbStatus = 'error';
                $dbMessage = 'Desconectado';
            }
            ?>
            <div class="status-card <?php echo $dbStatus; ?>">
                <h3>🗄️ Banco de Dados</h3>
                <div class="status-value">
                    <span class="status-<?php echo $dbStatus; ?>"><?php echo $dbMessage; ?></span>
                </div>
                <div class="status-detail">
                    <?php echo DB_HOST . ' / ' . DB_NAME; ?>
                </div>
            </div>
            
            <?php
            // 2. Registros Hoje
            $todayCount = 0;
            if ($conexao && $conexao->ping()) {
                $result = $conexao->query("SELECT COUNT(*) as cnt FROM bote WHERE DATE(created_at) = CURDATE()");
                if ($result) {
                    $row = $result->fetch_assoc();
                    $todayCount = $row['cnt'] ?? 0;
                }
            }
            ?>
            <div class="status-card ok">
                <h3>📊 Registros Hoje</h3>
                <div class="status-value">
                    <span class="status-info"><?php echo $todayCount; ?></span>
                </div>
                <div class="status-detail">
                    Mensagens processadas
                </div>
            </div>
            
            <?php
            // 3. Registros Última Hora
            $lastHourCount = 0;
            if ($conexao && $conexao->ping()) {
                $result = $conexao->query("SELECT COUNT(*) as cnt FROM bote WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
                if ($result) {
                    $row = $result->fetch_assoc();
                    $lastHourCount = $row['cnt'] ?? 0;
                }
            }
            $hourStatus = $lastHourCount > 0 ? 'ok' : 'warning';
            ?>
            <div class="status-card <?php echo $hourStatus; ?>">
                <h3>⏰ Última Hora</h3>
                <div class="status-value">
                    <span class="status-<?php echo $hourStatus; ?>"><?php echo $lastHourCount; ?></span>
                </div>
                <div class="status-detail">
                    Mensagens recebidas
                </div>
            </div>
            
            <?php
            // 4. Verificar Logs
            $logFile = __DIR__ . '/logs/telegram-webhook.log';
            $logSize = 0;
            $logExists = false;
            if (file_exists($logFile)) {
                $logExists = true;
                $logSize = filesize($logFile);
            }
            ?>
            <div class="status-card <?php echo $logExists ? 'ok' : 'warning'; ?>">
                <h3>📝 Log do Webhook</h3>
                <div class="status-value">
                    <span class="status-<?php echo $logExists ? 'ok' : 'warning'; ?>">
                        <?php echo $logExists ? number_format($logSize / 1024, 1) . ' KB' : 'Não existe'; ?>
                    </span>
                </div>
                <div class="status-detail">
                    logs/telegram-webhook.log
                </div>
            </div>
            
            <?php
            // 5. Health Check Log
            $healthLogFile = __DIR__ . '/logs/webhook-health.log';
            $healthLogExists = file_exists($healthLogFile);
            ?>
            <div class="status-card <?php echo $healthLogExists ? 'ok' : 'warning'; ?>">
                <h3>💚 Health Check</h3>
                <div class="status-value">
                    <span class="status-<?php echo $healthLogExists ? 'ok' : 'warning'; ?>">
                        <?php echo $healthLogExists ? 'Ativo' : 'Não configurado'; ?>
                    </span>
                </div>
                <div class="status-detail">
                    Cron job status
                </div>
            </div>
        </div>
        
        <?php
        // Seção de Logs
        if ($logExists) {
            ?>
            <div class="log-section">
                <h2>📋 Últimas 20 Linhas do Log</h2>
                <div class="log-lines">
                    <?php
                    $lines = file($logFile);
                    $lastLines = array_slice($lines, max(0, count($lines) - 20));
                    foreach ($lastLines as $line) {
                        $line = rtrim($line);
                        $class = 'log-line';
                        if (strpos($line, '❌') !== false) $class .= ' error';
                        elseif (strpos($line, '✅') !== false) $class .= ' success';
                        elseif (strpos($line, '⚠️') !== false) $class .= ' warning';
                        elseif (strpos($line, '[') !== false) $class .= ' info';
                        ?>
                        <div class="<?php echo $class; ?>"><?php echo htmlspecialchars($line); ?></div>
                        <?php
                    }
                    ?>
                </div>
            </div>
            <?php
        }
        ?>
        
        <?php
        // Seção de Health Check
        if ($healthLogExists) {
            ?>
            <div class="log-section">
                <h2>💚 Health Check Log</h2>
                <div class="log-lines">
                    <?php
                    $lines = file($healthLogFile);
                    $lastLines = array_slice($lines, max(0, count($lines) - 15));
                    foreach ($lastLines as $line) {
                        $line = rtrim($line);
                        $class = 'log-line';
                        if (strpos($line, '❌') !== false) $class .= ' error';
                        elseif (strpos($line, '✅') !== false) $class .= ' success';
                        elseif (strpos($line, '⚠️') !== false) $class .= ' warning';
                        ?>
                        <div class="<?php echo $class; ?>"><?php echo htmlspecialchars($line); ?></div>
                        <?php
                    }
                    ?>
                </div>
            </div>
            <?php
        }
        ?>
        
        <button class="refresh-btn" onclick="location.reload()">🔄 Atualizar</button>
    </div>
    
    <script>
        // Auto-refresh a cada 30 segundos
        setTimeout(() => location.reload(), 30000);
    </script>
</body>
</html>
