<?php

session_start();

header('Content-Type: application/json; charset=utf-8');

// ✅ CONFIGURAR TIMEZONE
date_default_timezone_set('America/Sao_Paulo');

// ✅ INCLUIR CONFIG CENTRALIZADA
require_once '../config.php';

// Verificar se é POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método não permitido']);
    exit;
}

// Receber dados JSON
$input = json_decode(file_get_contents('php://input'), true);

$time1 = isset($input['time1']) ? trim($input['time1']) : '';
$time2 = isset($input['time2']) ? trim($input['time2']) : '';
$tipo = isset($input['tipo']) ? trim($input['tipo']) : 'gols';
$limite = isset($input['limite']) ? intval($input['limite']) : 10;

// Log de debug
error_log("📊 Requisição de histórico: time1='$time1', time2='$time2', tipo='$tipo', limite=$limite");

// Validar limites
if ($limite < 1 || $limite > 50) $limite = 10;
if (empty($time1) || empty($time2)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Times inválidos: time1=' . $time1 . ', time2=' . $time2]);
    exit;
}

// ✅ CONECTAR AO BANCO DE DADOS
// A conexão já vem de config.php ($conexao)

// Verificar conexão
if ($conexao->connect_error) {
    http_response_code(500);
    error_log("❌ Erro de conexão: " . $conexao->connect_error);
    echo json_encode([
        'success' => false,
        'error' => 'Erro de conexão: ' . $conexao->connect_error
    ]);
    exit;
}

// ✅ FORÇAR UTF-8
$conexao->set_charset("utf8mb4");

try {
    // Consultar histórico do TIME 1 na tabela 'bote'
    $sql1 = "SELECT 
                resultado,
                data_criacao,
                time_1,
                time_2,
                placar_1,
                placar_2,
                tipo_aposta
            FROM bote 
            WHERE (
                (LOWER(time_1) = LOWER(?) AND resultado IN ('GREEN', 'RED', 'REEMBOLSO'))
                OR (LOWER(time_2) = LOWER(?) AND resultado IN ('GREEN', 'RED', 'REEMBOLSO'))
            )
            AND LOWER(tipo_aposta) LIKE LOWER(CONCAT('%', ?, '%'))
            ORDER BY data_criacao DESC
            LIMIT ?";

    error_log("🔍 Executando query para $time1 com tipo=" . $tipo);

    $stmt1 = $conexao->prepare($sql1);
    if ($stmt1 === false) {
        error_log("❌ Erro na preparação SQL1: " . $conexao->error);
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Erro na preparação da query (time1): ' . $conexao->error]);
        exit;
    }
    
    $stmt1->bind_param('sssi', $time1, $time1, $tipo, $limite);
    
    if (!$stmt1->execute()) {
        error_log("❌ Erro ao executar SQL1: " . $stmt1->error);
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Erro ao executar query (time1): ' . $stmt1->error]);
        exit;
    }
    
    $resultado1 = $stmt1->get_result();
    $historico_time1 = [];

    while ($row = $resultado1->fetch_assoc()) {
        $historico_time1[] = [
            'resultado' => $row['resultado'],
            'data_criacao' => $row['data_criacao'],
            'time_1' => $row['time_1'],
            'time_2' => $row['time_2'],
            'placar_1' => $row['placar_1'],
            'placar_2' => $row['placar_2'],
            'tipo_aposta' => $row['tipo_aposta']
        ];
    }
    $stmt1->close();

    error_log("✅ Time 1 ($time1): " . count($historico_time1) . " resultados encontrados");

    // Consultar histórico do TIME 2 na tabela 'bote'
    $sql2 = "SELECT 
                resultado,
                data_criacao,
                time_1,
                time_2,
                placar_1,
                placar_2,
                tipo_aposta
            FROM bote
            WHERE (
                (LOWER(time_1) = LOWER(?) AND resultado IN ('GREEN', 'RED', 'REEMBOLSO'))
                OR (LOWER(time_2) = LOWER(?) AND resultado IN ('GREEN', 'RED', 'REEMBOLSO'))
            )
            AND LOWER(tipo_aposta) LIKE LOWER(CONCAT('%', ?, '%'))
            ORDER BY data_criacao DESC
            LIMIT ?";

    $stmt2 = $conexao->prepare($sql2);
    if ($stmt2 === false) {
        error_log("❌ Erro na preparação SQL2: " . $conexao->error);
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Erro na preparação da query (time2): ' . $conexao->error]);
        exit;
    }
    
    $stmt2->bind_param('sssi', $time2, $time2, $tipo, $limite);

    if (!$stmt2->execute()) {
        error_log("❌ Erro ao executar SQL2: " . $stmt2->error);
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Erro ao executar query (time2): ' . $stmt2->error]);
        exit;
    }

    $resultado2 = $stmt2->get_result();
    $historico_time2 = [];

    while ($row = $resultado2->fetch_assoc()) {
        $historico_time2[] = [
            'resultado' => $row['resultado'],
            'data_criacao' => $row['data_criacao'],
            'time_1' => $row['time_1'],
            'time_2' => $row['time_2'],
            'placar_1' => $row['placar_1'],
            'placar_2' => $row['placar_2'],
            'tipo_aposta' => $row['tipo_aposta']
        ];
    }
    $stmt2->close();

    error_log("✅ Time 2 ($time2): " . count($historico_time2) . " resultados encontrados");

    // ✅ RETORNAR SUCESSO
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'time1_historico' => $historico_time1,
        'time2_historico' => $historico_time2,
        'total_time1' => count($historico_time1),
        'total_time2' => count($historico_time2),
        'tipo' => $tipo
    ]);

} catch (Exception $e) {
    http_response_code(500);
    error_log("❌ Exceção: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao consultar banco de dados: ' . $e->getMessage()
    ]);
}

$conexao->close();
?>
