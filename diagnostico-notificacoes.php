<?php
// Diagnóstico de Notificações - Verificar Status

header('Content-Type: application/json; charset=utf-8');

// Verificar arquivo de log
$logsDir = __DIR__ . '/logs';
$logFile = $logsDir . '/notif-' . date('Y-m-d') . '.log';

$diagnostico = [
    'timestamp' => date('Y-m-d H:i:s'),
    'timezone' => date_default_timezone_get(),
    'php_version' => phpversion(),
    'arquivo_log' => [
        'caminho' => $logFile,
        'existe' => file_exists($logFile),
        'tamanho' => file_exists($logFile) ? filesize($logFile) : 0,
        'readable' => file_exists($logFile) && is_readable($logFile),
        'writable' => is_writable($logsDir),
    ],
    'logs_dir' => [
        'existe' => is_dir($logsDir),
        'writable' => is_writable($logsDir),
        'permissions' => substr(sprintf('%o', fileperms($logsDir)), -4),
    ],
    'arquivos_necessarios' => [
        'registrar-log-notificacao.php' => file_exists(__DIR__ . '/registrar-log-notificacao.php'),
        'ver-logs-notificacoes.php' => file_exists(__DIR__ . '/ver-logs-notificacoes.php'),
        'js/notificacoes-sistema.js' => file_exists(__DIR__ . '/js/notificacoes-sistema.js'),
        'js/telegram-mensagens.js' => file_exists(__DIR__ . '/js/telegram-mensagens.js'),
    ],
];

// Se houver log, ler as últimas 10 entradas
if (file_exists($logFile)) {
    $linhas = file($logFile, FILE_IGNORE_NEW_LINES);
    $diagnostico['ultimas_linhas'] = array_slice(array_reverse($linhas), 0, 10);
    $diagnostico['total_linhas'] = count($linhas);
    
    // Contar por tipo
    $contadores = ['NOTIFICACAO' => 0, 'DETECTAR_TIPO' => 0, 'TESTE_MANUAL' => 0];
    foreach ($linhas as $linha) {
        $entrada = json_decode($linha, true);
        if ($entrada && isset($entrada['tipo'])) {
            if (isset($contadores[$entrada['tipo']])) {
                $contadores[$entrada['tipo']]++;
            }
        }
    }
    $diagnostico['contadores'] = $contadores;
}

echo json_encode($diagnostico, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
