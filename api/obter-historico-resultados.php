<?php

// ✅ NÃO USAR SESSION - API PÚBLICA
// session_start();

header('Content-Type: application/json; charset=utf-8');

// ✅ CONFIGURAR TIMEZONE
date_default_timezone_set('America/Sao_Paulo');

// ✅ INCLUIR CONFIG CENTRALIZADA
require_once '../config.php';

// Verificar se é POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método não permitido']);
    exit;
}

// Receber dados JSON
$input = json_decode(file_get_contents('php://input'), true);

$time1 = isset($input['time1']) ? trim($input['time1']) : '';
$time2 = isset($input['time2']) ? trim($input['time2']) : '';
$tipo = isset($input['tipo']) ? trim($input['tipo']) : 'gols';
$limite = isset($input['limite']) ? intval($input['limite']) : 10;

// 🔧 REMOVER EMOJIS DOS TIMES (alguns times têm ⚽️ no início)
$time1 = preg_replace('/[\p{Emoji_Presentation}]/u', '', $time1);
$time2 = preg_replace('/[\p{Emoji_Presentation}]/u', '', $time2);
$time1 = trim($time1);
$time2 = trim($time2);

// Validar limites
if ($limite < 1 || $limite > 50) $limite = 10;
if (empty($time1) || empty($time2)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Times inválidos']);
    exit;
}

// ✅ CONECTAR AO BANCO DE DADOS
// A conexão já vem de config.php ($conexao)

// Verificar conexão
if ($conexao->connect_error) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro de conexão com o banco de dados'
    ]);
    exit;
}

// ✅ FORÇAR UTF-8
$conexao->set_charset("utf8mb4");

try {
    // ✅ USAR A MESMA LÓGICA DE DETECÇÃO QUE obter-placar-dia.php
    // Esta função usa regex para detectar padrões de título como:
    // "+1⚽GOL", "+0.5⚽GOL", "+1⛳️CANTOS"
    
    $tipo_cantos = strtolower(trim($tipo)) === 'cantos';
    
    // ✅ CONSTRUIR FILTRO INTELIGENTE (regex + tipo_aposta)
    // Usa a mesma abordagem que extrairReferencia() de obter-placar-dia.php
    if ($tipo_cantos) {
        // Filtro para CANTOS: tipo_aposta + padrões LIKE (MySQL REGEXP tem problemas com emojis)
        $filtro_tipo = "AND (
            LOWER(tipo_aposta) LIKE '%CANTOS%'
            OR titulo LIKE '%CANTOS%' 
            OR titulo LIKE '%Cantos%'
            OR titulo LIKE '%cantos%' 
            OR titulo LIKE '%CANTO%'
            OR titulo LIKE '%Canto%'
            OR titulo LIKE '%canto%'
            OR titulo LIKE '%ESCANTEIOS%'
            OR titulo LIKE '%escanteios%'
            OR titulo LIKE '%ESCANTEI%'
            OR titulo LIKE '%escantei%'
            OR titulo LIKE '%⛳%'
        )";
    } else {
        // Filtro para GOLS: tipo_aposta + padrões LIKE
        $filtro_tipo = "AND (
            LOWER(tipo_aposta) LIKE '%GOL%'
            OR titulo LIKE '%GOL%' 
            OR titulo LIKE '%Gol%'
            OR titulo LIKE '%gol%'
            OR titulo LIKE '%GOLS%'
            OR titulo LIKE '%Gols%'
            OR titulo LIKE '%gols%'
            OR titulo LIKE '%⚽%'
        )";
    }

    // ✅ BUSCAR ÚLTIMOS JOGOS DO TIME 1 (filtrados por tipo de mensagem)
    $sql1 = "SELECT 
                resultado,
                data_criacao,
                time_1,
                time_2,
                placar_1,
                placar_2,
                titulo,
                tipo_aposta
            FROM bote 
            WHERE (
                (LOWER(time_1) LIKE CONCAT('%', LOWER(?), '%') OR LOWER(time_2) LIKE CONCAT('%', LOWER(?), '%'))
                $filtro_tipo
            )
            ORDER BY data_criacao DESC
            LIMIT ?";

    $stmt1 = $conexao->prepare($sql1);
    if ($stmt1 === false) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Erro na preparação da query (time1): ' . $conexao->error]);
        exit;
    }
    
    $stmt1->bind_param('ssi', $time1, $time1, $limite);
    
    if (!$stmt1->execute()) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Erro ao executar query (time1): ' . $stmt1->error]);
        exit;
    }
    
    $resultado1 = $stmt1->get_result();
    $historico_time1 = [];

    while ($row = $resultado1->fetch_assoc()) {
        // 🔧 Ignorar jogos sem resultado (ainda não finalizados)
        if ($row['resultado'] === null || $row['resultado'] === '') {
            continue;
        }
        
        $historico_time1[] = [
            'resultado' => $row['resultado'],
            'data_criacao' => $row['data_criacao'],
            'time_1' => $row['time_1'],
            'time_2' => $row['time_2'],
            'placar_1' => $row['placar_1'],
            'placar_2' => $row['placar_2'],
            'titulo' => $row['titulo'],
            'tipo_aposta' => $row['tipo_aposta']
        ];
    }
    $stmt1->close();

    // ✅ BUSCAR ÚLTIMOS JOGOS DO TIME 2 (filtrados por tipo de mensagem)
    $sql2 = "SELECT 
                resultado,
                data_criacao,
                time_1,
                time_2,
                placar_1,
                placar_2,
                titulo,
                tipo_aposta
            FROM bote
            WHERE (
                (LOWER(time_1) LIKE CONCAT('%', LOWER(?), '%') OR LOWER(time_2) LIKE CONCAT('%', LOWER(?), '%'))
                $filtro_tipo
            )
            ORDER BY data_criacao DESC
            LIMIT ?";

    $stmt2 = $conexao->prepare($sql2);
    if ($stmt2 === false) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Erro na preparação da query (time2): ' . $conexao->error]);
        exit;
    }
    
    $stmt2->bind_param('ssi', $time2, $time2, $limite);

    if (!$stmt2->execute()) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Erro ao executar query (time2): ' . $stmt2->error]);
        exit;
    }

    $resultado2 = $stmt2->get_result();
    $historico_time2 = [];

    while ($row = $resultado2->fetch_assoc()) {
        // 🔧 Ignorar jogos sem resultado (ainda não finalizados)
        if ($row['resultado'] === null || $row['resultado'] === '') {
            continue;
        }
        
        $historico_time2[] = [
            'resultado' => $row['resultado'],
            'data_criacao' => $row['data_criacao'],
            'time_1' => $row['time_1'],
            'time_2' => $row['time_2'],
            'placar_1' => $row['placar_1'],
            'placar_2' => $row['placar_2'],
            'titulo' => $row['titulo'],
            'tipo_aposta' => $row['tipo_aposta']
        ];
    }
    $stmt2->close();

    // ✅ SINCRONIZAR RESULTADOS - Se um jogo foi GREEN/RED/REEMBOLSO, ambos os times devem ver o mesmo resultado
    // Isso é importante porque quando Everton x Fulham termina GREEN, tanto Everton quanto Fulham devem mostrar GREEN
    sincronizarResultados($historico_time1, $historico_time2);

    // ✅ RETORNAR SUCESSO
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'time1_historico' => $historico_time1,
        'time2_historico' => $historico_time2,
        'total_time1' => count($historico_time1),
        'total_time2' => count($historico_time2),
        'tipo' => $tipo
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao consultar banco de dados: ' . $e->getMessage()
    ]);
}

/**
 * ================================================================
 * FUNÇÃO: Sincronizar Resultados entre Times
 * ================================================================
 * 
 * Quando um jogo termina com um resultado (GREEN/RED/REEMBOLSO),
 * ambos os times envolvidos devem mostrar o mesmo resultado.
 * 
 * Exemplo:
 * - Everton x Fulham termina GREEN
 * - Quando buscar histórico de Everton, deve mostrar GREEN
 * - Quando buscar histórico de Fulham, também deve mostrar GREEN
 * 
 * Esta função compara os jogos pela data e pelos times envolvidos
 * e sincroniza os resultados para garantir consistência.
 */
function sincronizarResultados(&$historico_time1, &$historico_time2) {
    // Para cada jogo do time1, procurar correspondente no time2
    foreach ($historico_time1 as $idx1 => $jogo1) {
        // Procurar jogo de mesma data E que envolva os mesmos times
        foreach ($historico_time2 as $idx2 => $jogo2) {
            $mesmaData = $jogo1['data_criacao'] === $jogo2['data_criacao'];
            
            // Verificar se envolvem os mesmos times (em qualquer ordem)
            $mesmosTeams = (
                (strtolower($jogo1['time_1']) === strtolower($jogo2['time_1']) && 
                 strtolower($jogo1['time_2']) === strtolower($jogo2['time_2'])) ||
                (strtolower($jogo1['time_1']) === strtolower($jogo2['time_2']) && 
                 strtolower($jogo1['time_2']) === strtolower($jogo2['time_1']))
            );
            
            // Se for o mesmo jogo (mesma data e mesmos times)
            if ($mesmaData && $mesmosTeams) {
                // Sincronizar os resultados - usar o primeiro encontrado como referência
                if (!empty($jogo1['resultado'])) {
                    $historico_time2[$idx2]['resultado'] = $jogo1['resultado'];
                } elseif (!empty($jogo2['resultado'])) {
                    $historico_time1[$idx1]['resultado'] = $jogo2['resultado'];
                }
            }
        }
    }
}

$conexao->close();

/**
 * ================================================================
 * FUNÇÃO: Extrair Referência do Título (MESMO PADRÃO DE obter-placar-dia.php)
 * ================================================================
 * 
 * Detecta o tipo de aposta analisando o título usando REGEX,
 * seguindo exatamente a mesma lógica estabelecida em obter-placar-dia.php
 * 
 * Retorna:
 * - '+1⚽GOL'
 * - '+0.5⚽GOL'
 * - '+1⛳️CANTOS'
 * - null (se não detectar)
 */
function extrairReferencia($titulo) {
    if (empty($titulo)) {
        return null;
    }
    
    $titulo_limpo = trim($titulo);
    
    // 📌 PADRÃO 1: +1 GOL (sem decimal) - com suporte a múltiplas variações de emoji
    // Suporta: ⚽, ⚽️ (com variante), 🎯, espaços variados
    if (preg_match('/\+1\s*[\p{Emoji_Presentation}\s]*(g|G)(o|O)(l|L|ls|LS)\s*(?:ASIA|FT|AS|AFL)?/u', $titulo_limpo) || 
        preg_match('/\+1\s*GOL\s*(?:ASIA|FT|AS|AFL)?/i', $titulo_limpo)) {
        return '+1⚽GOL';
    }
    
    // 📌 PADRÃO 2: +0.5 GOL (com decimal) - com suporte a múltiplas variações de emoji
    if (preg_match('/\+0\.?5\s*[\p{Emoji_Presentation}\s]*(g|G)(o|O)(l|L|ls|LS)\s*(?:ASIA|FT|AS|AFL)?/u', $titulo_limpo) ||
        preg_match('/\+0\.?5\s*GOL\s*(?:ASIA|FT|AS|AFL)?/i', $titulo_limpo)) {
        return '+0.5⚽GOL';
    }
    
    // 📌 PADRÃO 3: +1 CANTOS - com suporte a múltiplas variações de emoji
    if (preg_match('/\+1\s*[\p{Emoji_Presentation}\s]*(c|C)(a|A)(n|N)(t|T)(o|O)(s|S|)?\s*(?:ASIA|FT|AS|AFL)?/u', $titulo_limpo) ||
        preg_match('/\+1\s*CANTO?S?\s*(?:ASIA|FT|AS|AFL)?/i', $titulo_limpo)) {
        return '+1⛳️CANTOS';
    }
    
    // Fallback: tentar buscar por substrings exatas (case-insensitive)
    // Remove todos os emojis e espaços extras para comparação
    $titulo_sem_emojis = preg_replace('/[\p{Emoji_Presentation}]/u', '', $titulo_limpo);
    $titulo_sem_emojis = preg_replace('/\s+/', ' ', $titulo_sem_emojis); // Remove espaços múltiplos
    $titulo_sem_emojis = trim($titulo_sem_emojis);
    
    // Verificar padrões básicos
    if (stripos($titulo_sem_emojis, '+1') !== false && stripos($titulo_sem_emojis, 'GOL') !== false && 
        stripos($titulo_sem_emojis, '+0.5') === false) {
        return '+1⚽GOL';
    }
    
    if (stripos($titulo_sem_emojis, '+0.5') !== false && stripos($titulo_sem_emojis, 'GOL') !== false) {
        return '+0.5⚽GOL';
    }
    
    if (stripos($titulo_sem_emojis, '+1') !== false && stripos($titulo_sem_emojis, 'CANTO') !== false) {
        return '+1⛳️CANTOS';
    }
    
    $padroes_fallback = [
        '+1⚽GOL' => '+1⚽GOL',
        '+0.5⚽GOL' => '+0.5⚽GOL',
        '+1⛳️CANTOS' => '+1⛳️CANTOS',
        '+1 ⚽GOL' => '+1⚽GOL',
        '+0.5 ⚽GOL' => '+0.5⚽GOL',
        '+1 ⛳️CANTOS' => '+1⛳️CANTOS',
        '+1 GOLS' => '+1⚽GOL',
        '+0.5 GOLS' => '+0.5⚽GOL',
        '+1 CANTOS' => '+1⛳️CANTOS'
    ];
    
    foreach ($padroes_fallback as $buscar => $resultado) {
        if (stripos($titulo_limpo, $buscar) !== false) {
            return $resultado;
        }
    }
    
    return null;
}
?>
