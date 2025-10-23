<?php
/**
 * PROCESSAR PAGAMENTO - SOLUÇÃO FINAL
 * ====================================
 * Redireciona para link de pagamento pré-configurado
 * Este é o método mais confiável para credenciais APP_USR
 */

error_reporting(0);
ob_start();
header('Content-Type: application/json; charset=utf-8');

require_once 'config.php';
require_once 'carregar_sessao.php';

ob_end_clean();

// Validar sessão
if (!isset($_SESSION['usuario_id']) || empty($_SESSION['usuario_id'])) {
    die(json_encode([
        'success' => false,
        'message' => 'Usuário não autenticado'
    ]));
}

$id_usuario = $_SESSION['usuario_id'];
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['id_plano']) || !isset($data['tipo_ciclo'])) {
    die(json_encode([
        'success' => false,
        'message' => 'Dados incompletos'
    ]));
}

$id_plano = intval($data['id_plano']);
$tipo_ciclo = $data['tipo_ciclo'];

// ✅ LINKS DE PAGAMENTO PRÉ-CONFIGURADOS
// Você deve criar esses links no painel do Mercado Pago
// e colocar os URLs aqui

$links_pagamento = [
    // Formato: "plano_id_mensal" => "URL do link de pagamento"
    "1_mensal" => "https://link.mercadopago.com.br/gratuito",  // Plano GRATUITO
    "1_anual" => "https://link.mercadopago.com.br/gratuito",   // Plano GRATUITO
    "2_mensal" => "https://link.mercadopago.com.br/prata",     // Plano PRATA
    "2_anual" => "https://link.mercadopago.com.br/prata-anual",
    "3_mensal" => "https://link.mercadopago.com.br/ouro",      // Plano OURO
    "3_anual" => "https://link.mercadopago.com.br/ouro-anual",
    "4_mensal" => "https://link.mercadopago.com.br/diamante",  // Plano DIAMANTE
    "4_anual" => "https://link.mercadopago.com.br/diamante-anual",
];

// Procurar o link
$key = "{$id_plano}_{$tipo_ciclo}";
$link_pagamento = $links_pagamento[$key] ?? null;

if (!$link_pagamento) {
    die(json_encode([
        'success' => false,
        'message' => 'Link de pagamento não configurado para este plano',
        'debug' => "Chave procurada: $key"
    ]));
}

// ✅ Armazenar informação de tentativa de pagamento
$stmt = $conexao->prepare("
    INSERT INTO transacoes_mercadopago 
    (id_usuario, id_plano, tipo_ciclo, status, data_criacao)
    VALUES (?, ?, ?, 'pendente', NOW())
");
$stmt->bind_param("iss", $id_usuario, $id_plano, $tipo_ciclo);
$stmt->execute();
$stmt->close();

error_log("✅ Redirecionando usuário $id_usuario para link de pagamento");

// ✅ REDIRECIONAR PARA LINK DE PAGAMENTO
die(json_encode([
    'success' => true,
    'preference_url' => $link_pagamento,
    'message' => 'Redirecionando para pagamento...',
    'metodo' => 'link_pagamento'
]));
?>
