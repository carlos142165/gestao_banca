<?php
/**
 * SISTEMA DE LOGGING SIMPLES
 */

class SimpleLogger {
    private $arquivo;
    
    public function __construct() {
        $pasta = __DIR__ . '/logs';
        if (!is_dir($pasta)) @mkdir($pasta, 0777, true);
        $this->arquivo = $pasta . '/notif-' . date('Y-m-d') . '.log';
    }
    
    public function registrar($evento, $dados) {
        $msg = "[" . date('Y-m-d H:i:s') . "] $evento\n";
        $msg .= json_encode($dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        $msg .= str_repeat("-", 80) . "\n\n";
        @file_put_contents($this->arquivo, $msg, FILE_APPEND);
    }
    
    public function obter() {
        return @file_get_contents($this->arquivo) ?: '';
    }
}

$logger = new SimpleLogger();

// Processar POST
if ($_POST['acao'] === 'limpar') {
    @unlink($logger->arquivo);
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

$logs = $logger->obter();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>📊 Logs</title>
    <style>
        * { margin: 0; padding: 0; }
        body { font-family: monospace; background: #1a1a1a; color: #ddd; padding: 20px; }
        .header { background: #2a2a2a; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        h1 { color: #4ec9b0; margin-bottom: 10px; }
        .controls { margin-top: 15px; }
        button { padding: 8px 16px; margin-right: 10px; background: #007acc; color: white; border: none; cursor: pointer; border-radius: 3px; }
        button:hover { background: #005a9e; }
        .logs { background: #252526; padding: 15px; border-radius: 5px; max-height: 600px; overflow-y: auto; white-space: pre-wrap; word-wrap: break-word; font-size: 12px; line-height: 1.6; }
        .empty { text-align: center; color: #666; padding: 40px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>📊 Logs de Notificações</h1>
        <div class="controls">
            <button onclick="location.reload()">🔄 Atualizar</button>
            <button onclick="setInterval(location.reload, 5000); this.textContent='⏱️ Auto ON';">⏱️ Auto (5s)</button>
            <form method="POST" style="display: inline;">
                <input type="hidden" name="acao" value="limpar">
                <button type="submit" style="background: #d13438;" onclick="return confirm('Limpar logs?')">🗑️ Limpar</button>
            </form>
        </div>
    </div>
    
    <div class="logs">
        <?php if (empty(trim($logs))): ?>
            <div class="empty">📭 Nenhum log registrado ainda</div>
        <?php else: ?>
            <?php echo htmlspecialchars($logs); ?>
        <?php endif; ?>
    </div>
</body>
</html>
