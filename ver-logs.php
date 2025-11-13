<?php
// ✅ Ver logs de PHP
header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>";
echo "<html><head><meta charset='UTF-8'><style>";
echo "body { font-family: monospace; background: #1e1e1e; color: #0f0; padding: 20px; }";
echo "pre { background: #000; padding: 15px; border: 1px solid #0f0; max-height: 600px; overflow-y: auto; }";
echo "h1 { color: #0f0; }";
echo "</style></head><body>";

echo "<h1>📋 PHP Error Log</h1>";

// Tenta encontrar o arquivo de log do PHP
$possible_logs = [
    '/var/log/php-fpm/www-error.log',
    '/var/log/php.log',
    './php_errors.log',
    ini_get('error_log'),
    getenv('DOCUMENT_ROOT') . '/../php_errors.log'
];

echo "<h2>Procurando em:</h2>";
echo "<ul>";

$found = false;

foreach ($possible_logs as $log_path) {
    if ($log_path && file_exists($log_path)) {
        $found = true;
        echo "<li><strong>✅ Encontrado:</strong> {$log_path}</li>";
        echo "<pre>";
        $content = file_get_contents($log_path);
        $lines = explode("\n", $content);
        $last_50 = array_slice($lines, -50);
        echo htmlspecialchars(implode("\n", $last_50));
        echo "</pre>";
    } else {
        echo "<li>❌ Não encontrado: {$log_path}</li>";
    }
}

if (!$found) {
    echo "<p style='color: #ff6b6b;'>Nenhum arquivo de log encontrado. O log pode estar desabilitado.</p>";
}

echo "</ul>";
echo "</body></html>";
?>
