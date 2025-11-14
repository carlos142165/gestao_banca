<?php
/**
 * API: deletar-mensagem.php (VERSÃO FINAL - CONSOLIDADA)
 * 
 * Deleta uma mensagem da tabela 'bote'
 * RESTRIÇÃO: Apenas usuário ID 23 pode deletar
 * 
 * FUNCIONALIDADE:
 * - Tenta deletar por ID primário PRIMEIRO
 * - Se falhar, tenta por telegram_message_id
 * - Log detalhado de cada tentativa
 * - Debug logs para troubleshooting
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

    // ✅ CREATE LOG DIR IF NOT EXISTS
    $logDir = '../logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    $logFile = $logDir . '/deletar-mensagem.log';

    // ✅ LOG DEBUG
    $debugLog = "[" . date('Y-m-d H:i:s') . "] DEBUG DELETE\n";
    $debugLog .= "  messageId: $messageId (tipo: " . gettype($messageId) . ")\n";
    $debugLog .= "  usuarioId: $usuarioId\n";
    $debugLog .= "  Input recebido: " . $input . "\n";
    file_put_contents($logFile, $debugLog, FILE_APPEND);

    // ✅ PASSO 1: TENTAR DELETAR POR ID PRIMÁRIO PRIMEIRO
    $query1 = "DELETE FROM bote WHERE id = ?";
    $stmt1 = $conexao->prepare($query1);

    if (!$stmt1) {
        throw new Exception('Erro ao preparar consulta 1: ' . $conexao->error);
    }

    $stmt1->bind_param('i', $messageId);

    if (!$stmt1->execute()) {
        throw new Exception('Erro ao executar consulta 1: ' . $stmt1->error);
    }

    $affectedRows = $stmt1->affected_rows;
    $stmt1->close();

    // ✅ PASSO 2: SE NÃO DELETOU, TENTAR POR telegram_message_id
    if ($affectedRows === 0) {
        file_put_contents($logFile, "  ⚠️ ID primário não encontrado, tentando telegram_message_id...\n", FILE_APPEND);
        
        $query2 = "DELETE FROM bote WHERE telegram_message_id = ?";
        $stmt2 = $conexao->prepare($query2);

        if (!$stmt2) {
            throw new Exception('Erro ao preparar consulta 2: ' . $conexao->error);
        }

        $stmt2->bind_param('i', $messageId);

        if (!$stmt2->execute()) {
            throw new Exception('Erro ao executar consulta 2: ' . $stmt2->error);
        }

        $affectedRows = $stmt2->affected_rows;
        $stmt2->close();
    } else {
        file_put_contents($logFile, "  ✅ Deletado por ID primário\n", FILE_APPEND);
    }

    // ✅ VERIFICAR SE DELETOU ALGO
    if ($affectedRows > 0) {
        // ✅ LOG DE SUCESSO
        file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] ✅ Usuário $usuarioId deletou mensagem ID: $messageId (affected_rows: $affectedRows)\n", FILE_APPEND);

        // ✅ RESPOSTA DE SUCESSO
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Mensagem deletada com sucesso',
            'message_id' => $messageId,
            'user_id' => $usuarioId
        ]);
    } else {
        // ✅ NÃO ENCONTROU
        file_put_contents($logFile, "  ❌ Mensagem ID=$messageId não encontrada em nenhuma coluna\n", FILE_APPEND);
        
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Mensagem não encontrada (ID: ' . $messageId . ')',
            'message_id' => $messageId
        ]);
    }

} catch (Exception $e) {
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
