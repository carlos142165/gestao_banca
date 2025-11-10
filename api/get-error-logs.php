<?php
header('Content-Type: text/plain; charset=utf-8');

// Caminho do arquivo de log do PHP
$logFile = 'C:\\xampp\\php\\logs\\php_error_log';

if (!file_exists($logFile)) {
    echo "❌ Arquivo de log não encontrado: {$logFile}\n";
    exit;
}

// Ler as últimas 100 linhas do arquivo
$lines = file($logFile, FILE_IGNORE_NEW_LINES);

if (!$lines) {
    echo "📭 Arquivo de log vazio\n";
    exit;
}

// Filtrar apenas linhas com "DEBUG" ou "OVER" ou "Validação"
$filtered = [];
foreach ($lines as $line) {
    if (preg_match('/DEBUG|OVER|Validação|FILTRO|TIME1|TIME2|Comparando/i', $line)) {
        $filtered[] = $line;
    }
}

// Mostrar as últimas 50 linhas filtradas
if (empty($filtered)) {
    echo "❌ Nenhum log de debug encontrado nos últimos registros\n";
    echo "📍 Procure por linhas com: DEBUG, OVER, Validação, FILTRO, TIME1, TIME2\n\n";
    echo "=== Últimas 20 linhas do arquivo de log (não filtradas) ===\n";
    echo implode("\n", array_slice($lines, -20));
} else {
    echo "✅ Últimas linhas de debug encontradas:\n";
    echo "=".str_repeat("=", 99)."\n";
    echo implode("\n", array_slice($filtered, -50));
}
?>
