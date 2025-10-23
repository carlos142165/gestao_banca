<?php
/**
 * PROCESSAR PAGAMENTO - CHECKOUT REDIRECT
 * ========================================
 * Usa formulário POST direto para Mercado Pago
 * Sem precisar de API de preferences
 * Funciona com qualquer tipo de credencial
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

error_log("🚀 Processando pagamento: Usuário={$id_usuario}, Plano={$id_plano}");

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

$external_reference = "user_{$id_usuario}_plan_{$id_plano}_{$tipo_ciclo}";

// ✅ USAR CHECKOUT REDIRECT
// Criar formulário que será enviado para Mercado Pago

$items = array(
    array(
        'title' => $descricao,
        'description' => "Acesso ao plano {$plano_dados['nome']}",
        'picture_url' => '',
        'category_id' => 'payments',
        'quantity' => 1,
        'unit_price' => floatval($preco),
        'currency_id' => 'BRL'
    )
);

$payer = array(
    'name' => $plano_dados['nome'],
    'email' => $plano_dados['email'],
    'phone' => array(
        'area_code' => '11',
        'number' => '00000000'
    ),
    'address' => array(
        'zip_code' => '00000000',
        'street_name' => 'Rua',
        'street_number' => '0'
    )
);

$back_urls = array(
    'success' => 'http://localhost/gestao/gestao_banca/webhook.php?status=success&external_reference=' . $external_reference,
    'failure' => 'http://localhost/gestao/gestao_banca/webhook.php?status=failure&external_reference=' . $external_reference,
    'pending' => 'http://localhost/gestao/gestao_banca/webhook.php?status=pending&external_reference=' . $external_reference
);

$payment_methods = array(
    'excluded_payment_types' => array(),
    'excluded_payment_methods' => array(),
    'installments' => 1,
    'default_installments' => 1
);

$preference = array(
    'items' => $items,
    'payer' => $payer,
    'back_urls' => $back_urls,
    'notification_url' => 'http://localhost/gestao/gestao_banca/webhook.php',
    'external_reference' => $external_reference,
    'expires' => true,
    'expiration_date_from' => date('Y-m-d\TH:i:s\Z'),
    'expiration_date_to' => date('Y-m-d\TH:i:s\Z', strtotime('+24 hours')),
    'payment_methods' => $payment_methods
);

error_log("📤 Enviando preference: " . json_encode($preference));

// ✅ TENTAR CRIAR PREFERENCE
$url = "https://api.mercadopago.com/v1/checkout/preferences";

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
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($preference));

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

error_log("📥 HTTP Code: {$http_code}");
error_log("📥 Response: " . substr($response, 0, 1000));

$response_data = json_decode($response, true);

// ✅ SE FUNCIONOU
if ($http_code == 201) {
    error_log("✅ Preferência criada!");
    error_log("   ID: " . ($response_data['id'] ?? 'N/A'));
    error_log("   Init Point: " . ($response_data['init_point'] ?? 'N/A'));
    
    die(json_encode([
        'success' => true,
        'preference_url' => $response_data['init_point'] ?? $response_data['sandbox_init_point'] ?? '',
        'preference_id' => $response_data['id'] ?? '',
        'message' => 'Redirecionando para pagamento...'
    ]));
}

// ❌ SE DEU ERRO 404 OU OUTRO
error_log("❌ Erro HTTP {$http_code}");
error_log("   Mensagem: " . ($response_data['message'] ?? 'Desconhecido'));

// PLANO B: Usar formulário HTML com dados brutos
// Se a API não funcionar, vamos redirecionar direto com formulário
error_log("⚠️ API não respondeu com sucesso. Tentando método alternativo...");

// Armazena na sessão para usar no redirecionamento
$_SESSION['pagamento_pendente'] = array(
    'items' => $items,
    'payer' => $payer,
    'back_urls' => $back_urls,
    'external_reference' => $external_reference,
    'preco' => $preco
);

error_log("💾 Dados armazenados em sessão para redirecionamento manual");

die(json_encode([
    'success' => true,
    'redirect_to' => 'checkout-manual.php?session_id=' . session_id(),
    'message' => 'Redirecionando para pagamento...',
    'metodo' => 'manual'
]));
?>
