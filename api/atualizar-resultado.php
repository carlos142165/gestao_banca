<?php
/**
 * ================================================================
 * API: Atualizar Resultado da Aposta
 * ================================================================
 * 
 * Este arquivo recebe mensagens de resultado do Telegram
 * e atualiza a aposta correspondente no banco de dados.
 * 
 * FLUXO:
 * 1. Webhook recebe mensagem de resultado: "Resultado disponível!"
 * 2. Extrai o nome do jogo e tipo de aposta (ex: Nantes x Metz, Gols over +0.5)
 * 3. Chama esta API com os dados
 * 4. API busca a aposta correspondente na tabela "bote"
 * 5. Atualiza o campo "resultado" com GREEN/RED/REEMBOLSO
 * 
 * EXEMPLO DE RESULTADO RECEBIDO:
 * 
 * Resultado disponível!
 * ⚽️ Nantes (H) x Metz (A) (ao vivo)
 * Gols over +0.5 - ODD: 2.005 - GREEN✅
 * 
 * ================================================================
 */

header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 0);

// ✅ CONFIGURAR FUSO HORÁRIO
date_default_timezone_set('America/Sao_Paulo');

// ✅ INCLUIR CONFIGURAÇÕES
require_once '../config.php';

// ✅ LOG
$logFile = __DIR__ . '/../logs/atualizar-resultado.log';
if (!is_dir(dirname($logFile))) {
    mkdir(dirname($logFile), 0755, true);
}

try {
    
    // ✅ RECEBER DADOS JSON
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Log
    file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Requisição recebida\n", FILE_APPEND);
    file_put_contents($logFile, "Dados: " . json_encode($input) . "\n", FILE_APPEND);
    
    // ✅ VALIDAR DADOS
    if (!$input) {
        throw new Exception("Nenhum dado recebido");
    }
    
    if (!isset($input['time_1']) || !isset($input['time_2']) || !isset($input['tipo_aposta'])) {
        throw new Exception("Dados incompletos. Requeridos: time_1, time_2, tipo_aposta, resultado");
    }
    
    if (!isset($input['resultado']) || !in_array($input['resultado'], ['GREEN', 'RED', 'REEMBOLSO'])) {
        throw new Exception("Resultado inválido. Aceitos: GREEN, RED, REEMBOLSO");
    }
    
    // ✅ EXTRAIR DADOS
    $time_1 = trim($input['time_1']);
    $time_2 = trim($input['time_2']);
    $tipo_aposta = trim($input['tipo_aposta']);
    $resultado = trim($input['resultado']);
    $tipo_odds = isset($input['tipo_odds']) ? trim($input['tipo_odds']) : '';
    
    file_put_contents($logFile, "Times: $time_1 vs $time_2\n", FILE_APPEND);
    file_put_contents($logFile, "Tipo: $tipo_aposta\n", FILE_APPEND);
    file_put_contents($logFile, "Resultado: $resultado\n", FILE_APPEND);
    
    // ✅ BUSCAR APOSTA NÃO RESOLVIDA COM ESTES TIMES
    // Procurar por apostas que:
    // 1. Tenham os nomes dos times (com tolerância para variações)
    // 2. Tenham tipo de aposta similar
    // 3. Ainda não tenham resultado (resultado IS NULL)
    // 4. Sejam recentes (últimas 24 horas)
    
    $query = "
        SELECT id, titulo, tipo_odds, resultado
        FROM bote
        WHERE 
            status_aposta = 'ATIVA'
            AND resultado IS NULL
            AND data_criacao >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            AND (
                (time_1 LIKE ? AND time_2 LIKE ?)
                OR (time_1 LIKE ? AND time_2 LIKE ?)
            )
            AND tipo_aposta LIKE ?
        ORDER BY data_criacao DESC
        LIMIT 1
    ";
    
    $stmt = $conexao->prepare($query);
    
    if (!$stmt) {
        throw new Exception("Erro ao preparar query: " . $conexao->error);
    }
    
    // ✅ CRIAR PADRÕES DE BUSCA (case-insensitive, parcial)
    $time1_search = '%' . $time_1 . '%';
    $time2_search = '%' . $time_2 . '%';
    $tipo_search = '%' . str_replace(' over', '', $tipo_aposta) . '%';
    
    // Bind dos parâmetros (permite ambas as ordens dos times)
    $stmt->bind_param('sssss', $time1_search, $time2_search, $time2_search, $time1_search, $tipo_search);
    
    if (!$stmt->execute()) {
        throw new Exception("Erro ao buscar aposta: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $aposta = $result->fetch_assoc();
    $stmt->close();
    
    if (!$aposta) {
        file_put_contents($logFile, "⚠️ Nenhuma aposta encontrada para $time_1 x $time_2 com tipo $tipo_aposta\n", FILE_APPEND);
        
        http_response_code(404);
        echo json_encode([
            'sucesso' => false,
            'mensagem' => 'Aposta não encontrada',
            'debug' => [
                'time_1' => $time_1,
                'time_2' => $time_2,
                'tipo_aposta' => $tipo_aposta
            ]
        ]);
        exit;
    }
    
    file_put_contents($logFile, "✅ Aposta encontrada: ID " . $aposta['id'] . "\n", FILE_APPEND);
    
    // ✅ ATUALIZAR RESULTADO - PARA AMBOS OS TIMES
    // Se o jogo foi GREEN, AMBOS os times veem como GREEN
    // Atualizar TODAS as apostas que envolvem estes dois times com este tipo
    $updateQuery = "
        UPDATE bote
        SET 
            resultado = ?,
            status_aposta = CASE 
                WHEN ? = 'GREEN' THEN 'GANHA'
                WHEN ? = 'RED' THEN 'PERDIDA'
                WHEN ? = 'REEMBOLSO' THEN 'CANCELADA'
                ELSE 'ATIVA'
            END
        WHERE 
            resultado IS NULL
            AND status_aposta = 'ATIVA'
            AND (
                (time_1 LIKE ? AND time_2 LIKE ?)
                OR (time_1 LIKE ? AND time_2 LIKE ?)
            )
            AND tipo_aposta LIKE ?
            AND data_criacao >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ";
    
    $updateStmt = $conexao->prepare($updateQuery);
    
    if (!$updateStmt) {
        throw new Exception("Erro ao preparar update: " . $conexao->error);
    }
    
    $updateStmt->bind_param('sssssssss', $resultado, $resultado, $resultado, $resultado, $time1_search, $time2_search, $time2_search, $time1_search, $tipo_search);
    
    if (!$updateStmt->execute()) {
        throw new Exception("Erro ao atualizar: " . $updateStmt->error);
    }
    
    $rowsAffected = $updateStmt->affected_rows;
    $updateStmt->close();
    
    file_put_contents($logFile, "💾 Apostas atualizadas com resultado: $resultado (Total: $rowsAffected)\n", FILE_APPEND);
    file_put_contents($logFile, "✅ Sucesso\n\n", FILE_APPEND);
    
    // ✅ RESPONDER COM SUCESSO
    http_response_code(200);
    echo json_encode([
        'sucesso' => true,
        'mensagem' => 'Resultado atualizado com sucesso',
        'aposta_id' => $aposta['id'],
        'resultado' => $resultado,
        'titulo' => $aposta['titulo'],
        'apostas_atualizadas' => $rowsAffected
    ]);
    
} catch (Exception $e) {
    file_put_contents($logFile, "❌ ERRO: " . $e->getMessage() . "\n\n", FILE_APPEND);
    
    http_response_code(400);
    echo json_encode([
        'sucesso' => false,
        'mensagem' => $e->getMessage()
    ]);
}

?>
