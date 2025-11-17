<?php
/**
 * API para registrar logs de notificações
 * POST /gestao_banca/registrar-notif.php
 */

header('Content-Type: application/json');

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
        return true;
    }
}

try {
    $json = file_get_contents('php://input');
    $dados = json_decode($json, true);
    
    if (!$dados || !isset($dados['evento'])) {
        throw new Exception('Dados inválidos');
    }
    
    $logger = new SimpleLogger();
    $logger->registrar($dados['evento'], $dados['dados'] ?? []);
    
    echo json_encode(['sucesso' => true]);
} catch (Exception $e) {
    echo json_encode(['sucesso' => false, 'erro' => $e->getMessage()]);
}
