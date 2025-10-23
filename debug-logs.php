<?php
/**
 * VISUALIZADOR DE LOGS - DEBUG MERCADO PAGO
 * ==========================================
 * Mostra os logs de error.log do PHP para diagnosticar problemas
 */

header('Content-Type: text/html; charset=utf-8');

// Encontrar arquivo de logs
$php_ini = php_ini_loaded_file();
$config_dir = dirname($php_ini);
$error_log_file = ini_get('error_log');

// Se não estiver definido, tentar padrão
if (!$error_log_file || $error_log_file === 'syslog') {
    $error_log_file = $config_dir . '/error.log';
    if (!file_exists($error_log_file)) {
        $error_log_file = getenv('XAMPP_ROOT') . '/apache/logs/error.log';
    }
    if (!file_exists($error_log_file)) {
        $error_log_file = 'C:/xampp/apache/logs/error.log';
    }
}

echo "<!DOCTYPE html>
<html lang=\"pt-BR\">
<head>
    <meta charset=\"UTF-8\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
    <title>🔍 Logs de Debug - Mercado Pago</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 20px;
            margin: 0;
        }
        h1 {
            color: #4ec9b0;
            border-bottom: 2px solid #4ec9b0;
            padding-bottom: 10px;
        }
        .info {
            background: #252526;
            border-left: 4px solid #007acc;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }
        .success { color: #6a9955; }
        .error { color: #f48771; }
        .warning { color: #dcdcaa; }
        .info-text { color: #9cdcfe; }
        pre {
            background: #1e1e1e;
            border: 1px solid #3e3e42;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
            margin: 15px 0;
        }
        .button {
            background: #007acc;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-family: 'Courier New', monospace;
            margin: 10px 5px 10px 0;
        }
        .button:hover {
            background: #005a9e;
        }
    </style>
</head>
<body>
    <h1>🔍 Visualizador de Logs - Debug Mercado Pago</h1>
    
    <div class=\"info\">
        <p><span class=\"info-text\">📂 Arquivo de Log:</span> " . $error_log_file . "</p>
        <p><span class=\"info-text\">📝 Tamanho:</span> " . (file_exists($error_log_file) ? number_format(filesize($error_log_file)) . " bytes" : "❌ NÃO ENCONTRADO") . "</p>
    </div>";

if (!file_exists($error_log_file)) {
    echo "<div class=\"info\" style=\"border-left-color: #f48771;\">
        <p class=\"error\">❌ Arquivo de log não encontrado!</p>
        <p>Procurado em:</p>
        <ul>
            <li>" . ini_get('error_log') . "</li>
            <li>$config_dir/error.log</li>
            <li>C:/xampp/apache/logs/error.log</li>
        </ul>
        <p>Tente criar um teste primeiro:</p>
        <a href=\"javascript:void(0)\" onclick=\"window.location.href='teste-json.php'; return false;\" class=\"button\">
            ▶ Executar teste-json.php
        </a>
    </div>";
} else {
    $lines = file($error_log_file);
    $total_lines = count($lines);
    
    // Mostrar últimas 100 linhas
    $show_lines = min(100, $total_lines);
    $start = $total_lines - $show_lines;
    
    echo "<div class=\"info\">
        <p><span class=\"success\">✅ Mostrando últimas $show_lines de $total_lines linhas</span></p>
    </div>";
    
    echo "<button class=\"button\" onclick=\"window.location.reload();\">🔄 Recarregar</button>";
    echo "<button class=\"button\" onclick=\"document.querySelector('pre').textContent = ''; alert('Logs foram limpos na próxima requisição.'); fetch('teste-json.php');\">🗑️ Limpar e Testar</button>";
    
    echo "<pre>";
    for ($i = $start; $i < $total_lines; $i++) {
        $line = htmlspecialchars($lines[$i]);
        
        // Colorizar linhas
        if (strpos($line, '❌') !== false || strpos($line, 'error') !== false) {
            echo "<span class=\"error\">$line</span>";
        } elseif (strpos($line, '✅') !== false || strpos($line, 'success') !== false) {
            echo "<span class=\"success\">$line</span>";
        } elseif (strpos($line, '⚠️') !== false || strpos($line, 'warning') !== false) {
            echo "<span class=\"warning\">$line</span>";
        } elseif (strpos($line, '🔍') !== false || strpos($line, '📤') !== false || strpos($line, '📥') !== false) {
            echo "<span class=\"info-text\">$line</span>";
        } else {
            echo $line;
        }
    }
    echo "</pre>";
}

echo "
</body>
</html>";
?>
