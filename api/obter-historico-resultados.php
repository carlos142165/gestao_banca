<?php<?php

session_start();session_start();



header('Content-Type: application/json; charset=utf-8');header('Content-Type: application/json; charset=utf-8');



// Verificar se é POST// Verificar se é POST

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    http_response_code(405);    http_response_code(405);

    echo json_encode(['success' => false, 'error' => 'Método não permitido']);    echo json_encode(['success' => false, 'error' => 'Método não permitido']);

    exit;    exit;

}}



// Receber dados JSON// Receber dados JSON

$input = json_decode(file_get_contents('php://input'), true);$input = json_decode(file_get_contents('php://input'), true);



$time1 = isset($input['time1']) ? trim($input['time1']) : '';$time1 = isset($input['time1']) ? trim($input['time1']) : '';

$time2 = isset($input['time2']) ? trim($input['time2']) : '';$time2 = isset($input['time2']) ? trim($input['time2']) : '';

$tipo = isset($input['tipo']) ? trim($input['tipo']) : 'gols';$tipo = isset($input['tipo']) ? trim($input['tipo']) : 'gols';

$limite = isset($input['limite']) ? intval($input['limite']) : 10;$limite = isset($input['limite']) ? intval($input['limite']) : 10;



// Log de debug// Log de debug

error_log("📊 Requisição de histórico: time1='$time1', time2='$time2', tipo='$tipo', limite=$limite");error_log("📊 Requisição de histórico: time1='$time1', time2='$time2', tipo='$tipo', limite=$limite");



// Validar limites// Validar limites

if ($limite < 1 || $limite > 50) $limite = 10;if ($limite < 1 || $limite > 50) $limite = 10;

if (empty($time1) || empty($time2)) {if (empty($time1) || empty($time2)) {

    http_response_code(400);    http_response_code(400);

    echo json_encode(['success' => false, 'error' => 'Times inválidos: time1=' . $time1 . ', time2=' . $time2]);    echo json_encode(['success' => false, 'error' => 'Times inválidos: time1=' . $time1 . ', time2=' . $time2]);

    exit;    exit;

}}



// ✅ CONECTAR AO BANCO DE DADOS DA HOSTINGER// ✅ CONECTAR AO BANCO DE DADOS DA HOSTINGER

$db_host = '127.0.0.1';$db_host = '127.0.0.1';

$db_username = 'u857325944_formu';$db_username = 'u857325944_formu';

$db_password = 'JkF4B7N1';$db_password = 'JkF4B7N1';

$db_name = 'u857325944_formu';$db_name = 'u857325944_formu';



$conexao = new mysqli($db_host, $db_username, $db_password, $db_name);$conexao = new mysqli($db_host, $db_username, $db_password, $db_name);



// Verificar conexão// Verificar conexão

if ($conexao->connect_error) {if ($conexao->connect_error) {

    http_response_code(500);    http_response_code(500);

    error_log("❌ Erro de conexão: " . $conexao->connect_error);    error_log("❌ Erro de conexão: " . $conexao->connect_error);

    echo json_encode([    echo json_encode([

        'success' => false,        'success' => false,

        'error' => 'Erro de conexão: ' . $conexao->connect_error        'error' => 'Erro de conexão: ' . $conexao->connect_error

    ]);    ]);

    exit;    exit;

}}



// ✅ FORÇAR UTF-8// ✅ FORÇAR UTF-8

$conexao->set_charset("utf8mb4");$conexao->set_charset("utf8mb4");



try {try {

    // Consultar histórico do TIME 1 na tabela 'bote'    // Consultar histórico do TIME 1 na tabela 'bote'

    $sql1 = "SELECT     $sql1 = "SELECT 

                resultado,                resultado,

                data_criacao,                data_criacao,

                time_1,                time_1,

                time_2,                time_2,

                placar_1,                placar_1,

                placar_2,                placar_2,

                tipo_aposta                tipo_aposta

            FROM bote             FROM bote 

            WHERE (    WHERE (

                (LOWER(time_1) = LOWER(?) AND resultado IN ('GREEN', 'RED', 'REEMBOLSO'))                (LOWER(time_1) = LOWER(?) AND resultado IN ('GREEN', 'RED', 'REEMBOLSO'))

                OR (LOWER(time_2) = LOWER(?) AND resultado IN ('GREEN', 'RED', 'REEMBOLSO'))                OR (LOWER(time_2) = LOWER(?) AND resultado IN ('GREEN', 'RED', 'REEMBOLSO'))

            )            )

            AND LOWER(tipo_aposta) LIKE LOWER(CONCAT('%', ?, '%'))            AND LOWER(tipo_aposta) LIKE LOWER(CONCAT('%', ?, '%'))

            ORDER BY data_criacao DESC            ORDER BY data_criacao DESC

            LIMIT ?";            LIMIT ?";



    error_log("🔍 Executando query para $time1 com tipo=" . $tipo);    error_log("🔍 Executando query para $time1 com tipo=" . $tipo);



    $stmt1 = $conexao->prepare($sql1);    $stmt1 = $conexao->prepare($sql1);

    if ($stmt1 === false) {    if ($stmt1 === false) {

        error_log("❌ Erro na preparação SQL1: " . $conexao->error);        error_log("❌ Erro na preparação SQL1: " . $conexao->error);

        http_response_code(500);        http_response_code(500);

        echo json_encode(['success' => false, 'error' => 'Erro na preparação da query (time1): ' . $conexao->error]);        echo json_encode(['success' => false, 'error' => 'Erro na preparação da query (time1): ' . $conexao->error]);

        exit;        exit;

    }    }

        

    $stmt1->bind_param('sssi', $time1, $time1, $tipo, $limite);    $stmt1->bind_param('sssi', $time1, $time1, $tipo, $limite);

        

    if (!$stmt1->execute()) {    if (!$stmt1->execute()) {

        error_log("❌ Erro ao executar SQL1: " . $stmt1->error);        error_log("❌ Erro ao executar SQL1: " . $stmt1->error);

        http_response_code(500);        http_response_code(500);

        echo json_encode(['success' => false, 'error' => 'Erro ao executar query (time1): ' . $stmt1->error]);        echo json_encode(['success' => false, 'error' => 'Erro ao executar query (time1): ' . $stmt1->error]);

        exit;        exit;

    }    }

        

    $resultado1 = $stmt1->get_result();    $resultado1 = $stmt1->get_result();

    $historico_time1 = [];    $historico_time1 = [];



    while ($row = $resultado1->fetch_assoc()) {    while ($row = $resultado1->fetch_assoc()) {

        $historico_time1[] = [        $historico_time1[] = [

            'resultado' => $row['resultado'],            'resultado' => $row['resultado'],

            'data_criacao' => $row['data_criacao'],            'data_criacao' => $row['data_criacao'],

            'time_1' => $row['time_1'],            'time1' => $row['time1'],

            'time_2' => $row['time_2'],            'time2' => $row['time2'],

            'placar_1' => $row['placar_1'],            'placar1' => $row['placar1'],

            'placar_2' => $row['placar_2'],            'placar2' => $row['placar2']

            'tipo_aposta' => $row['tipo_aposta']        ];

        ];    }

    }    $stmt1->close();

    $stmt1->close();    

        error_log("✅ Time 1 ($time1): " . count($historico_time1) . " resultados encontrados");

    error_log("✅ Time 1 ($time1): " . count($historico_time1) . " resultados encontrados");

    // Consultar histórico do TIME 2

    // Consultar histórico do TIME 2 na tabela 'bote'    $sql2 = "SELECT 

    $sql2 = "SELECT                 resultado,

                resultado,                data_criacao,

                data_criacao,                time1,

                time_1,                time2,

                time_2,                placar1,

                placar_1,                placar2

                placar_2,            FROM telegram_mensagens 

                tipo_aposta            WHERE (

            FROM bote                 (LOWER(time1) = LOWER(?) AND resultado IN ('GREEN', 'RED', 'REEMBOLSO'))

            WHERE (                OR (LOWER(time2) = LOWER(?) AND resultado IN ('GREEN', 'RED', 'REEMBOLSO'))

                (LOWER(time_1) = LOWER(?) AND resultado IN ('GREEN', 'RED', 'REEMBOLSO'))            )

                OR (LOWER(time_2) = LOWER(?) AND resultado IN ('GREEN', 'RED', 'REEMBOLSO'))            AND UPPER(tipo) = ?

            )            ORDER BY data_criacao DESC

            AND LOWER(tipo_aposta) LIKE LOWER(CONCAT('%', ?, '%'))            LIMIT ?";

            ORDER BY data_criacao DESC

            LIMIT ?";    $stmt2 = $conexao->prepare($sql2);

    if ($stmt2 === false) {

    $stmt2 = $conexao->prepare($sql2);        error_log("❌ Erro na preparação SQL2: " . $conexao->error);

    if ($stmt2 === false) {        http_response_code(500);

        error_log("❌ Erro na preparação SQL2: " . $conexao->error);        echo json_encode(['success' => false, 'error' => 'Erro na preparação da query (time2): ' . $conexao->error]);

        http_response_code(500);        exit;

        echo json_encode(['success' => false, 'error' => 'Erro na preparação da query (time2): ' . $conexao->error]);    }

        exit;    

    }    $stmt2->bind_param('sssi', $time2, $time2, $tipo_upper, $limite);

        

    $stmt2->bind_param('sssi', $time2, $time2, $tipo, $limite);    if (!$stmt2->execute()) {

            error_log("❌ Erro ao executar SQL2: " . $stmt2->error);

    if (!$stmt2->execute()) {        http_response_code(500);

        error_log("❌ Erro ao executar SQL2: " . $stmt2->error);        echo json_encode(['success' => false, 'error' => 'Erro ao executar query (time2): ' . $stmt2->error]);

        http_response_code(500);        exit;

        echo json_encode(['success' => false, 'error' => 'Erro ao executar query (time2): ' . $stmt2->error]);    }

        exit;    

    }    $resultado2 = $stmt2->get_result();

        $historico_time2 = [];

    $resultado2 = $stmt2->get_result();

    $historico_time2 = [];    while ($row = $resultado2->fetch_assoc()) {

        $historico_time2[] = [

    while ($row = $resultado2->fetch_assoc()) {            'resultado' => $row['resultado'],

        $historico_time2[] = [            'data_criacao' => $row['data_criacao'],

            'resultado' => $row['resultado'],            'time1' => $row['time1'],

            'data_criacao' => $row['data_criacao'],            'time2' => $row['time2'],

            'time_1' => $row['time_1'],            'placar1' => $row['placar1'],

            'time_2' => $row['time_2'],            'placar2' => $row['placar2']

            'placar_1' => $row['placar_1'],        ];

            'placar_2' => $row['placar_2'],    }

            'tipo_aposta' => $row['tipo_aposta']    $stmt2->close();

        ];    

    }    error_log("✅ Time 2 ($time2): " . count($historico_time2) . " resultados encontrados");

    $stmt2->close();

        // ✅ RETORNAR SUCESSO

    error_log("✅ Time 2 ($time2): " . count($historico_time2) . " resultados encontrados");    http_response_code(200);

    echo json_encode([

    // ✅ RETORNAR SUCESSO        'success' => true,

    http_response_code(200);        'time1_historico' => $historico_time1,

    echo json_encode([        'time2_historico' => $historico_time2,

        'success' => true,        'total_time1' => count($historico_time1),

        'time1_historico' => $historico_time1,        'total_time2' => count($historico_time2),

        'time2_historico' => $historico_time2,        'tipo' => $tipo

        'total_time1' => count($historico_time1),    ]);

        'total_time2' => count($historico_time2),

        'tipo' => $tipo} catch (Exception $e) {

    ]);    http_response_code(500);

    echo json_encode([

} catch (Exception $e) {        'success' => false,

    http_response_code(500);        'error' => 'Erro ao consultar banco de dados: ' . $e->getMessage()

    error_log("❌ Exceção: " . $e->getMessage());    ]);

    echo json_encode([}

        'success' => false,

        'error' => 'Erro ao consultar banco de dados: ' . $e->getMessage()$conexao->close();

    ]);?>

}

$conexao->close();
?>
