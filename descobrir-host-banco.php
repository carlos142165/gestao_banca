<?php
header('Content-Type: application/json; charset=utf-8');

// Credenciais que você está usando
$username = 'u857325944_formu';
$password = 'JkF4B7N1';
$database = 'u857325944_formu';

// Hosts para testar
$hosts_para_testar = [
    'localhost',
    '127.0.0.1',
    '192.168.1.1',
    $_SERVER['SERVER_ADDR'] ?? 'unknown',
    gethostname(),
    'mysql.hostinger.com.br',
    'db.hostinger.com.br',
];

$resultados = [];

foreach ($hosts_para_testar as $host) {
    if (empty($host) || $host === 'unknown') continue;
    
    $conexao = @new mysqli($host, $username, $password, $database);
    
    if ($conexao->connect_error) {
        $resultados[] = [
            'host' => $host,
            'conectado' => false,
            'erro' => $conexao->connect_error
        ];
    } else {
        $resultados[] = [
            'host' => $host,
            'conectado' => true,
            'info' => $conexao->server_info
        ];
        $conexao->close();
    }
}

echo json_encode([
    'success' => true,
    'server_info' => [
        'SERVER_ADDR' => $_SERVER['SERVER_ADDR'] ?? 'N/A',
        'SERVER_NAME' => $_SERVER['SERVER_NAME'] ?? 'N/A',
        'HOSTNAME' => gethostname(),
        'PHP_UNAME' => php_uname()
    ],
    'testes' => $resultados
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
