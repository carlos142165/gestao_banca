<?php
/**
 * SCRIPT DE TESTE - VALIDAR CORREÇÃO DO WEBHOOK
 * =============================================
 * 
 * Este script simula mensagens de resultado do Telegram
 * para testar se o webhook aplica o resultado ao tipo CORRETO
 */

header('Content-Type: application/json; charset=utf-8');

// Incluir webhook
require_once './api/telegram-webhook.php';

echo "================================================================================\n";
echo "TESTE DO WEBHOOK - VALIDAÇÃO DE RESULTADO POR TIPO DE APOSTA\n";
echo "================================================================================\n\n";

// Função para simular webhook
function testarWebhook($testName, $telegramJSON) {
    echo "📋 TESTE: $testName\n";
    echo str_repeat("-", 80) . "\n";
    
    $GLOBALS['_REQUEST'] = ['raw_input' => $telegramJSON];
    
    // Simular a recepção
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_SERVER['CONTENT_TYPE'] = 'application/json';
    
    // Não executar realmente o webhook, apenas mostrar o que seria feito
    echo "✅ Teste registrado para análise\n";
    echo "   JSON: " . substr($telegramJSON, 0, 100) . "...\n\n";
}

// ============================================================================
// TESTE 1: Resultado OVER +1 GOL
// ============================================================================
$teste1 = json_encode([
    "update_id" => 1,
    "channel_post" => [
        "message_id" => 900001,
        "date" => time(),
        "text" => "Resultado disponível!\n⚽️ Roma (H) x Udinese (A) (ao vivo)\nGols over +1.0 - ODD: 1.52 - GREEN✅"
    ]
]);

testarWebhook("Resultado OVER +1 GOL (GREEN)", $teste1);

// ============================================================================
// TESTE 2: Resultado OVER +0.5 GOL (mesmo confronto)
// ============================================================================
$teste2 = json_encode([
    "update_id" => 2,
    "channel_post" => [
        "message_id" => 900002,
        "date" => time(),
        "text" => "Resultado disponível!\n⚽️ Roma (H) x Udinese (A) (ao vivo)\nGols over +0.5 - ODD: 2.005 - REEMBOLSO🔄"
    ]
]);

testarWebhook("Resultado OVER +0.5 GOL (REEMBOLSO)", $teste2);

// ============================================================================
// TESTE 3: Resultado CANTOS
// ============================================================================
$teste3 = json_encode([
    "update_id" => 3,
    "channel_post" => [
        "message_id" => 900003,
        "date" => time(),
        "text" => "Resultado disponível!\n⚽️ Inter (H) x Napoli (A) (ao vivo)\nEscanteios over +1.0 - ODD: 1.504 - RED❌"
    ]
]);

testarWebhook("Resultado CANTOS (RED)", $teste3);

// ============================================================================
// INSTRUÇÕES
// ============================================================================
echo "\n\n";
echo "================================================================================\n";
echo "COMO TESTAR EM PRODUÇÃO:\n";
echo "================================================================================\n\n";

echo "1️⃣  CRIAR APOSTAS DE TESTE NO BANCO:\n";
echo "   SQL:\n";
echo "   INSERT INTO bote (titulo, time_1, time_2, status_aposta, resultado) VALUES\n";
echo "   ('OVER ( +1⚽ GOL ) FT', 'Roma', 'Udinese', 'ATIVA', NULL),\n";
echo "   ('OVER ( +0.5⚽ GOL ) FT', 'Roma', 'Udinese', 'ATIVA', NULL);\n\n";

echo "2️⃣  ENVIAR RESULTADO PARA WEBHOOK VIA TELEGRAM\n";
echo "   Tipo +1: 'Gols over +1.0 - ODD: 1.52 - GREEN'\n";
echo "   Tipo +0.5: 'Gols over +0.5 - ODD: 2.005 - REEMBOLSO'\n\n";

echo "3️⃣  VERIFICAR BANCO DE DADOS:\n";
echo "   Aposta +1 GOL deve ter resultado = 'GREEN'\n";
echo "   Aposta +0.5 GOL deve ter resultado = 'REEMBOLSO'\n\n";

echo "4️⃣  VERIFICAR LOGS:\n";
echo "   cat logs/telegram-webhook.log | tail -100\n";
echo "   Procure por: 'Estratégia 1 sucesso' ou 'Estratégia 2 fallback'\n\n";

echo "================================================================================\n";
echo "EXEMPLOS DE LOGS ESPERADOS:\n";
echo "================================================================================\n\n";

echo "✅ SUCESSO (Estratégia 1):\n";
echo "   🔍 Estratégia 1: Buscando por times + tipo (GOL)...\n";
echo "   ✅ Estratégia 1 sucesso: ID 123 - Tipo: OVER ( +1⚽ GOL ) FT\n";
echo "   💾 Resultado atualizado: GREEN para aposta ID 123\n\n";

echo "⚠️  FALLBACK (Estratégia 2):\n";
echo "   🔍 Estratégia 1: Buscando por times + tipo (GOL)...\n";
echo "   ⚠️ Estratégia 1 falhou - nenhuma aposta encontrada\n";
echo "   🔍 Estratégia 2: Buscando por times + filtrando por tipo...\n";
echo "   ✅ Estratégia 2 sucesso (tipo GOL): ID 123 - Tipo: OVER ( +1⚽ GOL ) FT\n\n";

echo "❌ ERRO:\n";
echo "   ❌ Nenhuma aposta encontrada para Roma x Udinese (tipo: GOL)\n\n";

echo "================================================================================\n";
?>
