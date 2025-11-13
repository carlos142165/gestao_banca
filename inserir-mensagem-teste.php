<?php
// ✅ INSERIR MENSAGEM DE TESTE NO BANCO
header('Content-Type: application/json; charset=utf-8');

require_once 'config.php';

$mensagem = "Oportunidade! 🚨

📊 🚨 OVER ( +1⛳️ CANTOS ) AS..


⚽️ Bologna (H) x Le Havre (A) (ao vivo)

⏰ Tempo: 82'
Odds iniciais: Casa: 1.9 - Emp. 3.4 - Fora: 4.1
🏟 Japan J-League

🥅 Placar: 0 - 0  
Escanteios over +1.0: 1.52 
Stake: 1%
    
⛳️ Escanteios: 10 - 2  
↪️ Último escanteio: 81' - 59'
🔥 Ataques perigosos: 57 - 25
🔥 Ataques perigosos/min. (5min.): 1.2 - 0
🔥 Ataques perigosos/min. (Total): 0.69 - 0.3
🟨 Cartões amarelos: 1 - 1
↪️ Último cartão amarelo: 40' - 73'
🟥 Cartões vermelhos: 0 - 0
🎯 Chutes ao lado: 12 - 4
↪️ Último chute ao lado: 81' - 76'
🎯 Chutes no alvo: 3 - 1
↪️ Último chute no alvo: 16' - 24'
💯 Posse de bola: 55% - 45%
🧠 PI 1: 51 - 9
⚡️ PI 2: 12 - 0


Links da partida:

Bet365 (https://www.bet365.bet.br/#/AX/K%5EMachida%20Zelvia) | Betfair (https://betfair.bet.br/exchange/plus/en/football/japanese-j-league/fc-machida-v-fc-tokyo-betting-34912413)";

// Extrair dados da mensagem (simulando webhook)
$titulo = "+1⛳️ CANTOS";
$tipo_aposta = "CANTOS";
$time_1 = "Bologna";
$time_2 = "Le Havre";
$placar_1 = 0;
$placar_2 = 0;
$escanteios_1 = 10;
$escanteios_2 = 2;
$valor_over = 1.0;
$odds = 1.52; // 🔑 A ODDS CORRETA
$tipo_odds = "Cantos Odds";
$hora_mensagem = date('H:i:s');
$status_aposta = "ATIVA";
$resultado = ""; // Sem resultado ainda
$telegram_message_id = time(); // ID único baseado em timestamp

$query = "INSERT INTO bote (telegram_message_id, mensagem_completa, titulo, tipo_aposta, time_1, time_2, placar_1, placar_2, escanteios_1, escanteios_2, valor_over, odds, tipo_odds, hora_mensagem, status_aposta, resultado) 
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conexao->prepare($query);

if (!$stmt) {
    echo json_encode([
        'sucesso' => false,
        'erro' => 'Erro ao preparar statement: ' . $conexao->error
    ]);
    exit;
}

$stmt->bind_param(
    "isssssiiiddsssss",
    $telegram_message_id,
    $mensagem,
    $titulo,
    $tipo_aposta,
    $time_1,
    $time_2,
    $placar_1,
    $placar_2,
    $escanteios_1,
    $escanteios_2,
    $valor_over,
    $odds,
    $tipo_odds,
    $hora_mensagem,
    $status_aposta,
    $resultado
);

if ($stmt->execute()) {
    $novo_id = $conexao->insert_id;
    echo json_encode([
        'sucesso' => true,
        'mensagem' => '✅ Mensagem de CANTOS inserida com sucesso!',
        'id_inserido' => $novo_id,
        'dados_inseridos' => [
            'titulo' => $titulo,
            'tipo_aposta' => $tipo_aposta,
            'time_1' => $time_1,
            'time_2' => $time_2,
            'valor_over' => $valor_over,
            'odds' => $odds,
            'tipo_odds' => $tipo_odds,
            'escanteios_1' => $escanteios_1,
            'escanteios_2' => $escanteios_2
        ]
    ]);
} else {
    echo json_encode([
        'sucesso' => false,
        'erro' => 'Erro ao executar insert: ' . $stmt->error
    ]);
}

$stmt->close();
?>
