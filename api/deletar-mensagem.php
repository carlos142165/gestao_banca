<?php
/**
 * API: deletar-mensagem.php
 * 
 * Deleta uma mensagem da tabela 'bote'
 * RESTRIÇÃO: Apenas usuário ID 23 pode deletar
 */

// ✅ INICIAR SESSÃO PARA VERIFICAR USUÁRIO
session_start();

// ✅ PERMITIR REQUISIÇÕES CORS E JSON
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // ✅ VERIFICAR MÉTODO
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        throw new Exception('Método não permitido');
    }

    // ✅ VERIFICAR SESSÃO E PERMISSÃO
    $usuarioId = isset($_SESSION['usuario_id']) ? intval($_SESSION['usuario_id']) : 0;
    
    error_log("🔐 Tentativa de delete - Usuário ID: $usuarioId");
    
    if ($usuarioId !== 23) {
        http_response_code(403);
        throw new Exception('Acesso negado. Apenas admin pode deletar mensagens.');
    }

    // ✅ INCLUIR CONFIG
    if (!file_exists('../config.php')) {
        throw new Exception('Arquivo config.php não encontrado');
    }
    require_once '../config.php';

    // ✅ VERIFICAR CONEXÃO COM BANCO
    if (!isset($conexao) || !$conexao) {
        throw new Exception('Conexão com banco de dados não estabelecida');
    }

    // ✅ LER DADOS JSON
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!$data || !isset($data['message_id'])) {
        http_response_code(400);
        throw new Exception('ID da mensagem não fornecido');
    }

    $messageId = intval($data['message_id']);

    // ✅ VALIDAÇÃO
    if ($messageId <= 0) {
        http_response_code(400);
        throw new Exception('ID da mensagem inválido');
    }

    // ✅ PREPARAR STATEMENT (SQL Injection Prevention)
    $query = "DELETE FROM bote WHERE id = ?";
    $stmt = $conexao->prepare($query);

    if (!$stmt) {
        throw new Exception('Erro ao preparar consulta: ' . $conexao->error);
    }

    // ✅ BIND PARAMETER
    $stmt->bind_param('i', $messageId);

    // ✅ EXECUTAR
    if (!$stmt->execute()) {
        throw new Exception('Erro ao executar consulta: ' . $stmt->error);
    }

    // ✅ VERIFICAR SE DELETOU ALGO
    if ($stmt->affected_rows > 0) {
        // ✅ LOG
        $logDir = '../logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $logFile = $logDir . '/deletar-mensagem.log';
        $logEntry = "[" . date('Y-m-d H:i:s') . "] Usuário $usuarioId deletou mensagem ID: $messageId\n";
        file_put_contents($logFile, $logEntry, FILE_APPEND);

        // ✅ RESPOSTA DE SUCESSO
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Mensagem deletada com sucesso',
            'message_id' => $messageId,
            'user_id' => $usuarioId
        ]);
        error_log("✅ Mensagem $messageId deletada pelo usuário $usuarioId");
    } else {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Mensagem não encontrada'
        ]);
    }

    $stmt->close();

} catch (Exception $e) {
    error_log("❌ Erro em deletar-mensagem.php: " . $e->getMessage());
    
    $statusCode = http_response_code();
    if ($statusCode === 200) {
        http_response_code(500);
    }
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

?>
