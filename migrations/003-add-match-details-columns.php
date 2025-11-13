<?php
// ✅ MIGRATION: Adicionar colunas de detalhes da partida
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config.php';

$conexao = obterConexao();

$migrations = [
    // Tempo e odds iniciais
    "ALTER TABLE bote ADD COLUMN tempo_minuto INT DEFAULT NULL COMMENT 'Tempo atual da partida em minutos' AFTER escanteios_2",
    "ALTER TABLE bote ADD COLUMN odds_inicial_casa DECIMAL(5,2) DEFAULT NULL COMMENT 'Odds inicial - Casa' AFTER tempo_minuto",
    "ALTER TABLE bote ADD COLUMN odds_inicial_empate DECIMAL(5,2) DEFAULT NULL COMMENT 'Odds inicial - Empate' AFTER odds_inicial_casa",
    "ALTER TABLE bote ADD COLUMN odds_inicial_fora DECIMAL(5,2) DEFAULT NULL COMMENT 'Odds inicial - Fora' AFTER odds_inicial_empate",
    
    // Estádio/Competição
    "ALTER TABLE bote ADD COLUMN estadio VARCHAR(100) DEFAULT NULL COMMENT 'Estádio ou Competição' AFTER odds_inicial_fora",
    
    // Estatísticas de ataque
    "ALTER TABLE bote ADD COLUMN ataques_perigosos_1 INT DEFAULT NULL COMMENT 'Ataques perigosos - Time 1' AFTER estadio",
    "ALTER TABLE bote ADD COLUMN ataques_perigosos_2 INT DEFAULT NULL COMMENT 'Ataques perigosos - Time 2' AFTER ataques_perigosos_1",
    
    // Cartões
    "ALTER TABLE bote ADD COLUMN cartoes_amarelos_1 INT DEFAULT NULL COMMENT 'Cartões amarelos - Time 1' AFTER ataques_perigosos_2",
    "ALTER TABLE bote ADD COLUMN cartoes_amarelos_2 INT DEFAULT NULL COMMENT 'Cartões amarelos - Time 2' AFTER cartoes_amarelos_1",
    "ALTER TABLE bote ADD COLUMN cartoes_vermelhos_1 INT DEFAULT NULL COMMENT 'Cartões vermelhos - Time 1' AFTER cartoes_amarelos_2",
    "ALTER TABLE bote ADD COLUMN cartoes_vermelhos_2 INT DEFAULT NULL COMMENT 'Cartões vermelhos - Time 2' AFTER cartoes_vermelhos_1",
    
    // Chutes
    "ALTER TABLE bote ADD COLUMN chutes_lado_1 INT DEFAULT NULL COMMENT 'Chutes ao lado - Time 1' AFTER cartoes_vermelhos_2",
    "ALTER TABLE bote ADD COLUMN chutes_lado_2 INT DEFAULT NULL COMMENT 'Chutes ao lado - Time 2' AFTER chutes_lado_1",
    "ALTER TABLE bote ADD COLUMN chutes_alvo_1 INT DEFAULT NULL COMMENT 'Chutes no alvo - Time 1' AFTER chutes_lado_2",
    "ALTER TABLE bote ADD COLUMN chutes_alvo_2 INT DEFAULT NULL COMMENT 'Chutes no alvo - Time 2' AFTER chutes_alvo_1",
    
    // Posse de bola
    "ALTER TABLE bote ADD COLUMN posse_bola_1 INT DEFAULT NULL COMMENT 'Posse de bola - Time 1 (%)' AFTER chutes_alvo_2",
    "ALTER TABLE bote ADD COLUMN posse_bola_2 INT DEFAULT NULL COMMENT 'Posse de bola - Time 2 (%)' AFTER posse_bola_1"
];

$sucesso = true;
$messages = [];

foreach ($migrations as $sql) {
    // Verificar se coluna já existe (evitar erro "Duplicate column name")
    $columnName = preg_match('/ADD COLUMN (\w+)/', $sql, $m) ? $m[1] : 'unknown';
    
    if ($conexao->query($sql)) {
        $messages[] = "✅ Coluna '$columnName' adicionada com sucesso";
    } else {
        // Se erro for "Duplicate column", apenas avisa, não falha
        if (strpos($conexao->error, 'Duplicate column') !== false) {
            $messages[] = "⚠️ Coluna '$columnName' já existe";
        } else {
            $messages[] = "❌ Erro ao adicionar coluna '$columnName': " . $conexao->error;
            $sucesso = false;
        }
    }
}

echo json_encode([
    'sucesso' => $sucesso,
    'mensagem' => $sucesso ? '✅ Todas as colunas foram adicionadas!' : '⚠️ Houve erros na migração',
    'detalhes' => $messages
]);
?>
