<?php
require_once "config.php";

echo "=== TESTE BIND_PARAM CORRIGIDO ===\n\n";

try {
    $resultado = NULL;
    $telegram_id = 9999;
    $valor_over = 0.5;
    $odds = 1.57;
    
    echo "Valores: telegram_id={$telegram_id}, valor_over={$valor_over}, odds={$odds}\n\n";
    
    $query = "INSERT INTO bote (telegram_message_id, mensagem_completa, titulo, tipo_aposta, time_1, time_2, placar_1, placar_2, escanteios_1, escanteios_2, valor_over, odds, tipo_odds, hora_mensagem, status_aposta, resultado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conexao->prepare($query);
    if (!$stmt) {
        die("PREPARE ERRO: " . $conexao->error);
    }
    echo "✅ Prepare OK\n";
    
    $msg = "Teste mensagem completa do webhook";
    $tit = "OVER (+0.5 ⚽️ GOL)";
    $tipo = "GOL";
    $t1 = "Time 1";
    $t2 = "Time 2";
    $placar_1 = 0;
    $placar_2 = 0;
    $escanteios_1 = 0;
    $escanteios_2 = 0;
    $tipo_odds = "Gols Odds";
    $hora = date("H:i:s");
    $status = "ATIVA";
    
    $bindStr = "isssssiiiddsssss";
    echo "Bind string: {$bindStr}\n";
    echo "String length: " . strlen($bindStr) . " caracteres\n";
    echo "Número de parâmetros: 16\n\n";
    
    if (!$stmt->bind_param($bindStr, $telegram_id, $msg, $tit, $tipo, $t1, $t2, $placar_1, $placar_2, $escanteios_1, $escanteios_2, $valor_over, $odds, $tipo_odds, $hora, $status, $resultado)) {
        die("❌ BIND ERRO: " . $stmt->error);
    }
    echo "✅ Bind OK\n";
    
    if (!$stmt->execute()) {
        die("❌ EXECUTE ERRO: " . $stmt->error);
    }
    
    $newId = $conexao->insert_id;
    echo "✅ Execute OK - Novo ID: {$newId}\n\n";
    
    $stmt->close();
    
    // Verificar
    $check = "SELECT id, titulo, valor_over, odds FROM bote WHERE id = ? LIMIT 1";
    $checkStmt = $conexao->prepare($check);
    $checkStmt->bind_param("i", $newId);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        echo "=== REGISTRO SALVO COM SUCESSO ===\n";
        echo "ID: " . $row["id"] . "\n";
        echo "Título: " . $row["titulo"] . "\n";
        echo "valor_over: " . $row["valor_over"] . " (tipo: " . gettype($row["valor_over"]) . ")\n";
        echo "odds: " . $row["odds"] . " (tipo: " . gettype($row["odds"]) . ")\n";
        
        if ($row["valor_over"] == 0.5) {
            echo "\n✅✅✅ TESTE PASSOU! valor_over está correto (0.5)! ✅✅✅\n";
        } else {
            echo "\n❌ ERRO: valor_over é " . $row["valor_over"] . " (esperado 0.5)\n";
        }
    } else {
        echo "❌ Registro não encontrado!\n";
    }
    
    $checkStmt->close();
    
} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage();
}
?>
