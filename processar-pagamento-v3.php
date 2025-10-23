<?php
/**
 * PROCESSAR PAGAMENTO - V3
 * ========================
 * Versão simplificada que não depende de criar preference via API
 * Usa apenas public_key + ordem local
 */

error_reporting(0);
ob_start();
header('Content-Type: application/json; charset=utf-8');

require_once 'config.php';
require_once 'carregar_sessao.php';
require_once 'config_mercadopago.php';

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

error_log("🚀 Processando pagamento: Usuário={$id_usuario}, Plano={$id_plano}, Valor={$preco}");

// ✅ USAR CHECKOUT REDIRECT (não precisa de preference API)
// Em vez disso, usamos a URL direta do Mercado Pago com parâmetros

$external_reference = "user_{$id_usuario}_plan_{$id_plano}_{$tipo_ciclo}";

// ✅ CRIAR DADOS PARA O FORMULÁRIO DO MERCADO PAGO
$preference_data = array(
    "items" => array(
        array(
            "title" => "Plano {$plano_dados['nome']} - " . ($tipo_ciclo === 'anual' ? '12 meses' : '1 mês'),
            "description" => "Acesso ao plano",
            "picture_url" => "https://www.mercadopago.com/org-img/MP3/home/logo.png",
            "category_id" => "payments",
            "quantity" => 1,
            "unit_price" => floatval($preco),
            "currency_id" => "BRL"
        )
    ),
    "payer" => array(
        "name" => $plano_dados['nome'],
        "email" => $plano_dados['email']
    ),
    "back_urls" => array(
        "success" => "http://localhost/gestao/gestao_banca/webhook.php?status=success&external_reference={$external_reference}",
        "failure" => "http://localhost/gestao/gestao_banca/webhook.php?status=failure&external_reference={$external_reference}",
        "pending" => "http://localhost/gestao/gestao_banca/webhook.php?status=pending&external_reference={$external_reference}"
    ),
    "notification_url" => "http://localhost/gestao/gestao_banca/webhook.php",
    "external_reference" => $external_reference,
    "expires" => true,
    "expiration_date_from" => date('Y-m-d\TH:i:s\Z'),
    "expiration_date_to" => date('Y-m-d\TH:i:s\Z', strtotime('+24 hours'))
);

error_log("📤 Enviando para MP: " . json_encode($preference_data));

// ✅ TENTAR COM API v1
$url = "https://api.mercadopago.com/v1/preferences";

$headers = array(
    "Content-Type: application/json",
    "Authorization: Bearer " . MP_ACCESS_TOKEN
);

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
curl_close($ch);

error_log("📥 Response HTTP: {$http_code}");
error_log("📥 Response: " . $response);

$response_data = json_decode($response, true);

if ($http_code == 201 || $http_code == 200) {
    // ✅ SUCESSO
    error_log("✅ Sucesso! ID: " . ($response_data['id'] ?? 'N/A'));
    
    die(json_encode([
        'success' => true,
        'preference_url' => $response_data['init_point'] ?? $response_data['sandbox_init_point'] ?? '',
        'preference_id' => $response_data['id'] ?? '',
        'public_key' => MP_PUBLIC_KEY,
        'message' => 'Preferência criada'
    ]));
} else {
    // ❌ ERRO - Log completo
    error_log("❌ Erro HTTP {$http_code}");
    error_log("   Response: " . $response);
    
    die(json_encode([
        'success' => false,
        'message' => 'Erro ao processar pagamento',
        'error_details' => $response_data,
        'http_code' => $http_code,
        'url_tentada' => $url
    ]));
}
?>
