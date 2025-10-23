<?php
/**
 * PROCESSAR PAGAMENTO - V5 FINAL
 * ================================
 * Tenta 3 métodos diferentes até conseguir redirecionar
 */

error_reporting(0);
ob_start();
header('Content-Type: application/json; charset=utf-8');

require_once 'config.php';
require_once 'carregar_sessao.php';
require_once 'config_mercadopago.php';

ob_end_clean();

error_log("=== PROCESSAMENTO DE PAGAMENTO V5 ===");

// Validar sessão
if (!isset($_SESSION['usuario_id']) || empty($_SESSION['usuario_id'])) {
    die(json_encode([
        'success' => false,
        'message' => 'Usuário não autenticado'
    ]));
}

$id_usuario = $_SESSION['usuario_id'];

// Receber dados
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['id_plano']) || !isset($data['tipo_ciclo'])) {
    die(json_encode([
        'success' => false,
        'message' => 'Dados incompletos: ' . json_encode($data)
    ]));
}

$id_plano = intval($data['id_plano']);
$tipo_ciclo = $data['tipo_ciclo'];

error_log("👤 Usuário: $id_usuario, Plano: $id_plano, Ciclo: $tipo_ciclo");

// Buscar dados
$stmt = $conexao->prepare("SELECT p.*, u.nome, u.email FROM planos p, usuarios u WHERE u.id = ? AND p.id = ?");
$stmt->bind_param("ii", $id_usuario, $id_plano);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die(json_encode(['success' => false, 'message' => 'Plano não encontrado']));
}

$plano = $result->fetch_assoc();
$stmt->close();

$preco = ($tipo_ciclo === 'anual') ? $plano['preco_ano'] : $plano['preco_mes'];
$descricao = "Plano {$plano['nome']} - " . ($tipo_ciclo === 'anual' ? '12 meses' : '1 mês');
$external_reference = "user_{$id_usuario}_plan_{$id_plano}_{$tipo_ciclo}";

error_log("💰 Preço: R$ $preco");
error_log("📝 Descrição: $descricao");

// ============================================
// MÉTODO 1: Tentar API de Preferences
// ============================================
error_log("🔄 Tentando MÉTODO 1: API de Preferences...");

$preference = [
    "items" => [[
        "title" => $descricao,
        "description" => "Acesso ao plano",
        "quantity" => 1,
        "unit_price" => floatval($preco),
        "currency_id" => "BRL"
    ]],
    "payer" => [
        "name" => $plano['nome'],
        "email" => $plano['email']
    ],
    "back_urls" => [
        "success" => "http://localhost/gestao/gestao_banca/webhook.php?status=success&external_reference={$external_reference}",
        "failure" => "http://localhost/gestao/gestao_banca/webhook.php?status=failure&external_reference={$external_reference}",
        "pending" => "http://localhost/gestao/gestao_banca/webhook.php?status=pending&external_reference={$external_reference}"
    ],
    "notification_url" => "http://localhost/gestao/gestao_banca/webhook.php",
    "external_reference" => $external_reference,
    "expires" => true,
    "expiration_date_to" => date('Y-m-d\TH:i:s\Z', strtotime('+24 hours')),
    "payment_methods" => ["installments" => 1]
];

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
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($preference));

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

error_log("   HTTP: $http_code");

$response_data = json_decode($response, true);

if ($http_code == 201 && !empty($response_data['init_point'])) {
    error_log("✅ MÉTODO 1 FUNCIONOU!");
    error_log("   init_point: " . $response_data['init_point']);
    
    die(json_encode([
        'success' => true,
        'preference_url' => $response_data['init_point'],
        'metodo' => 'api_preferences'
    ]));
}

error_log("   ❌ Falhou: " . ($response_data['message'] ?? 'HTTP ' . $http_code));

// ============================================
// MÉTODO 2: Usar Checkout Redirect Direto
// ============================================
error_log("🔄 Tentando MÉTODO 2: Checkout Redirect...");

// Armazenar dados na sessão
$_SESSION['checkout_data'] = [
    'title' => $descricao,
    'unit_price' => floatval($preco),
    'quantity' => 1,
    'currency_id' => 'BRL',
    'payer_name' => $plano['nome'],
    'payer_email' => $plano['email'],
    'external_reference' => $external_reference,
    'back_url' => "http://localhost/gestao/gestao_banca/webhook.php?status=success&external_reference={$external_reference}"
];

$checkout_url = 'checkout-redirect.php?session_id=' . session_id();

error_log("✅ MÉTODO 2: Redirecionando para checkout-redirect.php");

die(json_encode([
    'success' => true,
    'redirect_to' => $checkout_url,
    'metodo' => 'checkout_redirect'
]));
?>
