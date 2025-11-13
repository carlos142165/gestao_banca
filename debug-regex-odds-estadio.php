<?php
// ✅ DEBUG: Testar regex para capturar Odds iniciais e Estádio

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

$lines = array_map('trim', explode("\n", $mensagem));
$lines = array_filter($lines);

echo "=== TESTANDO REGEX ===\n\n";

$odds_inicial_casa = null;
$odds_inicial_empate = null;
$odds_inicial_fora = null;
$estadio = null;

foreach ($lines as $line) {
    // Teste 1: Odds iniciais
    if (preg_match('/Odds iniciais:\s*Casa:\s*([\d\.]+)\s*-\s*Emp[p\.]?\s*:\s*([\d\.]+)\s*-\s*Fora:\s*([\d\.]+)/i', $line, $m)) {
        echo "✅ ENCONTROU ODDS INICIAIS!\n";
        echo "   Linha: $line\n";
        echo "   Casa: " . $m[1] . "\n";
        echo "   Empate: " . $m[2] . "\n";
        echo "   Fora: " . $m[3] . "\n\n";
        $odds_inicial_casa = floatval($m[1]);
        $odds_inicial_empate = floatval($m[2]);
        $odds_inicial_fora = floatval($m[3]);
    }
    
    // Teste 2: Estádio
    if (preg_match('/🏟\s*(.+)/u', $line, $m)) {
        echo "✅ ENCONTROU ESTÁDIO!\n";
        echo "   Linha: $line\n";
        echo "   Estádio: " . trim($m[1]) . "\n\n";
        $estadio = trim($m[1]);
    }
}

echo "=== RESULTADOS FINAIS ===\n";
echo "Casa: " . ($odds_inicial_casa ?? "❌ NÃO ENCONTRADO") . "\n";
echo "Empate: " . ($odds_inicial_empate ?? "❌ NÃO ENCONTRADO") . "\n";
echo "Fora: " . ($odds_inicial_fora ?? "❌ NÃO ENCONTRADO") . "\n";
echo "Estádio: " . ($estadio ?? "❌ NÃO ENCONTRADO") . "\n";
?>
