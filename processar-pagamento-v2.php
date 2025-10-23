<?php
/**
 * PROCESSAR PAGAMENTO - V2
 * ========================
 * Usa Mercado Pago Checkout Pro (com PUBLIC_KEY no frontend)
 * O backend só valida e registra a transação após webhook
 */

// Suprimir erros
error_reporting(0);
ob_start();

// Set header FIRST
header('Content-Type: application/json; charset=utf-8');

// Agora carrega as dependências
require_once 'config.php';
require_once 'carregar_sessao.php';
require_once 'config_mercadopago.php';

// ✅ DEBUG
error_log("🚀 === PROCESSAR PAGAMENTO V2 ===");

// Limpar output antes de enviar JSON
ob_end_clean();

// Validar sessão
if (!isset($_SESSION['usuario_id']) || empty($_SESSION['usuario_id'])) {
    die(json_encode([
        'success' => false,
        'message' => 'Usuário não autenticado'
    ]));
}

$id_usuario = $_SESSION['usuario_id'];

// Receber dados do AJAX
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['id_plano']) || !isset($data['tipo_ciclo'])) {
    die(json_encode([
        'success' => false,
        'message' => 'Dados incompletos'
    ]));
}

$id_plano = intval($data['id_plano']);
$tipo_ciclo = $data['tipo_ciclo']; // 'mensal' ou 'anual'
$modo_pagamento = $data['modo_pagamento'] ?? null;

error_log("👤 ID Usuário: $id_usuario");
error_log("📦 ID Plano: $id_plano");
error_log("📅 Tipo Ciclo: $tipo_ciclo");

// Buscar dados do plano e usuário
$stmt = $conexao->prepare("
    SELECT p.*, u.nome, u.email 
    FROM planos p, usuarios u
    WHERE u.id = ? AND p.id = ?
");
$stmt->bind_param("ii", $id_usuario, $id_plano);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die(json_encode([
        'success' => false,
        'message' => 'Plano ou usuário não encontrado'
    ]));
}

$plano_dados = $result->fetch_assoc();
$stmt->close();

// Determinar valor baseado no ciclo
$preco = ($tipo_ciclo === 'anual') ? $plano_dados['preco_ano'] : $plano_dados['preco_mes'];
$descricao = "Plano {$plano_dados['nome']} - " . ($tipo_ciclo === 'anual' ? '12 meses' : '1 mês');

error_log("💰 Preço: R$ $preco");
error_log("📝 Descrição: $descricao");

// ✅ CRIAR DADOS PARA O MERCADO PAGO
// Vamos gerar um preference ID local e passar para o frontend

$preference_data = [
    "items" => [
        [
            "id" => "item-{$id_plano}",
            "title" => $descricao,
            "description" => "Acesso ao plano {$plano_dados['nome']}",
            "picture_url" => "",
            "category_id" => "payments",
            "quantity" => 1,
            "unit_price" => floatval($preco),
            "currency_id" => "BRL"
        ]
    ],
    "payer" => [
        "name" => $plano_dados['nome'],
        "email" => $plano_dados['email'],
        "phone" => [
            "area_code" => "11",
            "number" => "00000000"
        ],
        "address" => [
            "zip_code" => "00000000",
            "street_name" => "Rua",
            "street_number" => 0
        ]
    ],
    "back_urls" => [
        "success" => "http://localhost/gestao/gestao_banca/webhook.php?status=success&external_reference=user_{$id_usuario}_plan_{$id_plano}_{$tipo_ciclo}",
        "failure" => "http://localhost/gestao/gestao_banca/webhook.php?status=failure&external_reference=user_{$id_usuario}_plan_{$id_plano}_{$tipo_ciclo}",
        "pending" => "http://localhost/gestao/gestao_banca/webhook.php?status=pending&external_reference=user_{$id_usuario}_plan_{$id_plano}_{$tipo_ciclo}"
    ],
    "notification_url" => "http://localhost/gestao/gestao_banca/webhook.php",
    "external_reference" => "user_{$id_usuario}_plan_{$id_plano}_{$tipo_ciclo}",
    "expires" => true,
    "expiration_date_from" => date('Y-m-d\TH:i:s\Z'),
    "expiration_date_to" => date('Y-m-d\TH:i:s\Z', strtotime('+24 hours')),
    "payment_methods" => [
        "excluded_payment_types" => [],
        "installments" => 1,
        "default_installments" => 1
    ],
    "statement_descriptor" => "GESTAO BANCA",
    "metadata" => [
        "id_usuario" => $id_usuario,
        "id_plano" => $id_plano,
        "tipo_ciclo" => $tipo_ciclo
    ]
];

error_log("📤 Dados para Mercado Pago: " . json_encode($preference_data, JSON_UNESCAPED_SLASHES));

// ✅ ENVIAR PARA MERCADO PAGO
$url = "https://api.mercadopago.com/v1/checkout/preferences";

$headers = [
    "Content-Type: application/json",
    "Authorization: Bearer " . MP_ACCESS_TOKEN
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($preference_data));

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

error_log("📥 HTTP Code: $http_code");
error_log("📥 Response: " . substr($response, 0, 500));

if ($curl_error) {
    error_log("❌ CURL Error: $curl_error");
    die(json_encode([
        'success' => false,
        'message' => 'Erro ao conectar com Mercado Pago: ' . $curl_error,
        'http_code' => $http_code
    ]));
}

$response_data = json_decode($response, true);

if ($http_code >= 200 && $http_code < 300) {
    // ✅ SUCESSO
    error_log("✅ Preferência criada com sucesso!");
    error_log("   ID: " . ($response_data['id'] ?? 'N/A'));
    error_log("   Init Point: " . ($response_data['init_point'] ?? 'N/A'));
    
    die(json_encode([
        'success' => true,
        'preference_url' => $response_data['init_point'] ?? '',
        'preference_id' => $response_data['id'] ?? '',
        'public_key' => MP_PUBLIC_KEY,
        'message' => 'Preferência criada com sucesso'
    ]));
} else {
    // ❌ ERRO
    error_log("❌ Erro ao criar preferência!");
    error_log("   HTTP: $http_code");
    error_log("   Message: " . ($response_data['message'] ?? 'Desconhecido'));
    
    die(json_encode([
        'success' => false,
        'message' => $response_data['message'] ?? 'Erro ao criar preferência',
        'details' => $response_data,
        'http_code' => $http_code
    ]));
}
?>
