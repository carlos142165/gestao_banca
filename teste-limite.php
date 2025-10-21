<?php
/**
 * TESTE RÁPIDO - Verificar Limite
 * ================================
 */

require_once 'config.php';
require_once 'carregar_sessao.php';
require_once 'config_mercadopago.php';

if (!isset($_SESSION['usuario_id'])) {
    echo "❌ Usuário não logado!";
    exit;
}

$id_usuario = $_SESSION['usuario_id'];

echo "=== TESTE DE LIMITE ===\n\n";

// 1. Obter plano atual
echo "1️⃣ Obtendo plano do usuário...\n";
$plano = MercadoPagoManager::obterPlanoAtual($id_usuario);
if ($plano) {
    echo "✅ Plano: {$plano['nome']}\n";
    echo "   - Limite de mentores: {$plano['mentores_limite']}\n";
    echo "   - Limite de entradas: {$plano['entradas_diarias']}\n";
} else {
    echo "❌ Erro ao obter plano\n";
}

// 2. Verificar limite de mentores
echo "\n2️⃣ Verificando limite de mentores...\n";
$pode_mentor = MercadoPagoManager::verificarLimiteMentores($id_usuario);
echo ($pode_mentor ? "✅ Pode adicionar mentor" : "❌ Atingiu limite de mentores") . "\n";

// 3. Verificar limite de entradas
echo "\n3️⃣ Verificando limite de entradas...\n";
$pode_entrada = MercadoPagoManager::verificarLimiteEntradas($id_usuario);
echo ($pode_entrada ? "✅ Pode adicionar entrada" : "❌ Atingiu limite de entradas") . "\n";

// 4. Contar dados do usuário
echo "\n4️⃣ Dados do usuário:\n";

$query_mentores = "SELECT COUNT(*) as total FROM mentores WHERE id_usuario = $id_usuario";
$result_mentores = $conn->query($query_mentores);
$row_mentores = $result_mentores->fetch_assoc();
echo "   - Mentores cadastrados: {$row_mentores['total']}\n";

$query_entradas = "SELECT COUNT(*) as total FROM valor_mentores WHERE id_usuario = $id_usuario AND DATE(data_criacao) = CURDATE()";
$result_entradas = $conn->query($query_entradas);
$row_entradas = $result_entradas->fetch_assoc();
echo "   - Entradas de hoje: {$row_entradas['total']}\n";

echo "\n=== TESTE CONCLUÍDO ===\n";
?>
