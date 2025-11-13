<?php
// ✅ INSERIR MENSAGEM DE TESTE COM DADOS COMPLETOS

header('Content-Type: application/json; charset=utf-8');
require_once 'config.php';

$mensagem = "Oportunidade! 🚨

📊 🚨 OVER ( +0.5 ⚽️GOL  ) FT


⚽️ Bologna (H) x Le Havre (A) (ao vivo)

⏰ Tempo: 82'
Odds iniciais: Casa: 1.9 - Emp. 3.4 - Fora: 4.1
🏟 Japan J-League

🥅 Placar: 0 - 0  
Gols over +0.5: 1.5
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

// Extrair dados (simulando webhook)
$titulo = "+0.5 ⚽️ GOL";
$tipo_aposta = "GOL";
$time_1 = "Bologna";
$time_2 = "Le Havre";
$placar_1 = 0;
$placar_2 = 0;
$escanteios_1 = 10;
$escanteios_2 = 2;
$valor_over = 0.5;
$odds = 1.5;
$tipo_odds = "Gols Odds";

// Novos dados
$tempo_minuto = 82;
$odds_inicial_casa = 1.9;
$odds_inicial_empate = 3.4;
$odds_inicial_fora = 4.1;
$estadio = "Japan J-League";
$ataques_perigosos_1 = 57;
$ataques_perigosos_2 = 25;
$cartoes_amarelos_1 = 1;
$cartoes_amarelos_2 = 1;
$cartoes_vermelhos_1 = 0;
$cartoes_vermelhos_2 = 0;
$chutes_lado_1 = 12;
$chutes_lado_2 = 4;
$chutes_alvo_1 = 3;
$chutes_alvo_2 = 1;
$posse_bola_1 = 55;
$posse_bola_2 = 45;

$hora_mensagem = date('H:i:s');
$status_aposta = "ATIVA";
$resultado = null;
$telegram_message_id = time();

$query = "INSERT INTO bote (
    telegram_message_id, mensagem_completa, titulo, tipo_aposta, 
    time_1, time_2, placar_1, placar_2, escanteios_1, escanteios_2, 
    valor_over, odds, tipo_odds, hora_mensagem, status_aposta, resultado,
    tempo_minuto, odds_inicial_casa, odds_inicial_empate, odds_inicial_fora,
    estadio, ataques_perigosos_1, ataques_perigosos_2, 
    cartoes_amarelos_1, cartoes_amarelos_2, 
    cartoes_vermelhos_1, cartoes_vermelhos_2,
    chutes_lado_1, chutes_lado_2, chutes_alvo_1, chutes_alvo_2,
    posse_bola_1, posse_bola_2
) 
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conexao->prepare($query);

if (!$stmt) {
    echo json_encode([
        'sucesso' => false,
        'erro' => 'Erro ao preparar statement: ' . $conexao->error
    ]);
    exit;
}

// Bind params com 33 parâmetros
$stmt->bind_param(
    "isssssiiiddsssssidddsiiiiiiiiiiii",
    $telegram_message_id, $mensagem, $titulo, $tipo_aposta, 
    $time_1, $time_2, $placar_1, $placar_2, $escanteios_1, $escanteios_2,
    $valor_over, $odds, $tipo_odds, $hora_mensagem, $status_aposta, $resultado,
    $tempo_minuto, $odds_inicial_casa, $odds_inicial_empate, $odds_inicial_fora,
    $estadio, $ataques_perigosos_1, $ataques_perigosos_2,
    $cartoes_amarelos_1, $cartoes_amarelos_2,
    $cartoes_vermelhos_1, $cartoes_vermelhos_2,
    $chutes_lado_1, $chutes_lado_2, $chutes_alvo_1, $chutes_alvo_2,
    $posse_bola_1, $posse_bola_2
);

if ($stmt->execute()) {
    $novo_id = $conexao->insert_id;
    echo json_encode([
        'sucesso' => true,
        'mensagem' => '✅ Mensagem de teste com TODOS os dados inserida com sucesso!',
        'id_inserido' => $novo_id,
        'dados_inseridos' => [
            'titulo' => $titulo,
            'tipo_aposta' => $tipo_aposta,
            'time_1' => $time_1,
            'time_2' => $time_2,
            'odds' => $odds,
            'tempo_minuto' => $tempo_minuto,
            'odds_inicial_casa' => $odds_inicial_casa,
            'odds_inicial_empate' => $odds_inicial_empate,
            'odds_inicial_fora' => $odds_inicial_fora,
            'estadio' => $estadio,
            'ataques_perigosos' => "$ataques_perigosos_1 - $ataques_perigosos_2",
            'cartoes_amarelos' => "$cartoes_amarelos_1 - $cartoes_amarelos_2",
            'cartoes_vermelhos' => "$cartoes_vermelhos_1 - $cartoes_vermelhos_2",
            'chutes_lado' => "$chutes_lado_1 - $chutes_lado_2",
            'chutes_alvo' => "$chutes_alvo_1 - $chutes_alvo_2",
            'posse_bola' => "$posse_bola_1% - $posse_bola_2%"
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
