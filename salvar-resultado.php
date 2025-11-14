<?php
/**
 * ✅ BACKEND: Salvar Resultado da Aposta
 * 
 * Recebe:
 *   - aposta_id: ID da aposta (da coluna `id` da tabela `bote`)
 *   - resultado: GREEN, RED ou REEMBOLSO
 * 
 * Salva na coluna `resultado` da tabela `bote`
 */

session_start();
header('Content-Type: application/json');

// ✅ VERIFICAR AUTENTICAÇÃO
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit();
}

// ✅ VERIFICAR AUTORIZAÇÃO (apenas usuário ID 23 pode salvar resultados)
$usuario_id = intval($_SESSION['usuario_id']);
if ($usuario_id !== 23) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Você não tem permissão para salvar resultados']);
    exit();
}

// ✅ INCLUIR CONFIGURAÇÃO DO BANCO
require_once __DIR__ . '/config.php';

// ✅ OBTER CONEXÃO
$conexao = obterConexao();
if (!$conexao) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao conectar com o banco de dados']);
    exit();
}

// ✅ COLETAR DADOS
$aposta_id = isset($_POST['aposta_id']) ? intval($_POST['aposta_id']) : 0;
$resultado = isset($_POST['resultado']) ? $_POST['resultado'] : '';

// ✅ VALIDAR DADOS
if (!$aposta_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID da aposta é obrigatório']);
    exit();
}

if (!in_array($resultado, ['GREEN', 'RED', 'REEMBOLSO'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Resultado inválido. Use: GREEN, RED ou REEMBOLSO']);
    exit();
}

// ✅ ATUALIZAR RESULTADO NO BANCO
$sql = "UPDATE bote SET resultado = ? WHERE id = ?";
$stmt = $conexao->prepare($sql);

if (!$stmt) {
    http_response_code(500);
    error_log("❌ Erro prepare: " . $conexao->error);
    echo json_encode(['success' => false, 'message' => 'Erro ao preparar query: ' . $conexao->error]);
    exit();
}

// Bind parameters (string resultado, int id)
if (!$stmt->bind_param("si", $resultado, $aposta_id)) {
    http_response_code(500);
    error_log("❌ Erro bind: " . $stmt->error);
    echo json_encode(['success' => false, 'message' => 'Erro ao processar dados: ' . $stmt->error]);
    exit();
}

// Executar
if ($stmt->execute()) {
    $affected_rows = $stmt->affected_rows;
    error_log("✅ Resultado atualizado! Linhas afetadas: " . $affected_rows);
    echo json_encode([
        'success' => true, 
        'message' => '✅ Resultado salvo com sucesso!',
        'aposta_id' => $aposta_id,
        'resultado' => $resultado,
        'affected_rows' => $affected_rows
    ]);
} else {
    http_response_code(500);
    error_log("❌ Erro execute: " . $stmt->error);
    echo json_encode(['success' => false, 'message' => 'Erro ao salvar resultado: ' . $stmt->error]);
}

$stmt->close();
$conexao->close();
?>
