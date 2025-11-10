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
$valorOver = isset($input['valorOver']) ? trim($input['valorOver']) : null; // ✅ NOVO: filtro de over
$filtrarSemReembolso = isset($input['filtrarSemReembolso']) && $input['filtrarSemReembolso'] === true; // ✅ MODIFICADO: Verificar se é true (não usar cast)

// 🔍 DEBUG: Verificar o que recebemos
error_log("=== DEBUG OVER ===");
error_log("💬 INPUT RECEBIDO: " . json_encode($input));
error_log("📌 time1='{$time1}' | time2='{$time2}' | tipo='{$tipo}' | valorOver='" . ($valorOver ?? 'NULL') . "' | filtrarSemReembolso=" . ($filtrarSemReembolso ? 'true' : 'false')); // ✅ NOVO
error_log("🔬 valorOver isset? " . var_export(isset($input['valorOver']), true));
error_log("🔬 valorOver is_null? " . var_export($valorOver === null, true));
error_log("🔬 valorOver empty? " . var_export(empty($valorOver), true));
error_log("🔬 valorOver length? " . strlen($valorOver ?? ''));
error_log("🔬 filtrarSemReembolso isset? " . var_export(isset($input['filtrarSemReembolso']), true)); // ✅ NOVO
error_log("🔬 filtrarSemReembolso value? " . var_export($input['filtrarSemReembolso'] ?? 'NOT SET', true)); // ✅ NOVO
error_log("🔬 filtrarSemReembolso after check? " . var_export($filtrarSemReembolso, true)); // ✅ NOVO
error_log("🔬 filtrarSemReembolso is true? " . var_export($filtrarSemReembolso === true, true)); // ✅ NOVO

// 🔧 REMOVER EMOJIS DOS TIMES (alguns times têm ⚽️ no início)
$time1 = preg_replace('/[\p{Emoji_Presentation}]/u', '', $time1);
$time2 = preg_replace('/[\p{Emoji_Presentation}]/u', '', $time2);
$time1 = trim($time1);
$time2 = trim($time2);

// ✅ FUNÇÃO: Extrair nome do time sem sigla inicial
// Exemplo: "EC Santos" -> "Santos", "Everton" -> "Everton"
function extrairNomeTime($timeCompleto) {
    // Se tem espaço, pega a parte depois da sigla (geralmente a sigla é a primeira parte)
    // Exemplo: "EC Santos" -> "Santos"
    $partes = explode(' ', trim($timeCompleto), 2);
    if (count($partes) > 1) {
        // Verificar se a primeira parte é uma sigla (até 3 caracteres, sem números)
        if (strlen($partes[0]) <= 3 && !preg_match('/\d/', $partes[0])) {
            return trim($partes[1]);
        }
    }
    return trim($timeCompleto);
}

$time1_nome = extrairNomeTime($time1);
$time2_nome = extrairNomeTime($time2);

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
    
    // ✅ NOVO: Se valorOver foi fornecido, criar filtro para buscar apenas esse valor
    // IMPORTANTE: Deve ser EXATO - não pegar +0.5 quando quer +1, ou +1.5 quando quer +1
    $filtro_valor_over = "";
    $filtro_valor_over_strict = false; // Flag para fazer validação extra em PHP
    
    error_log("🔍 DEBUG INICIAL: valorOver recebido = '" . ($valorOver ?? 'NULL') . "'");
    error_log("🔍 DEBUG: !empty(valorOver) = " . var_export(!empty($valorOver), true));
    
    if (!empty($valorOver)) {
        // ⚠️ NÃO usar LIKE no SQL! É muito permissivo
        // Exemplo: LIKE '%+0.5%' pega tanto "+0.5" quanto "+10.5", "+1.5", "+2.5"!
        // Vamos fazer a validação APENAS em PHP
        $filtro_valor_over_strict = true; // Flag para fazer validação OBRIGATÓRIA em PHP
        error_log("📊✅ FILTRO ATIVADO: Será feita validação em PHP para valorOver='{$valorOver}'");
    } else {
        error_log("📊❌ FILTRO NÃO ATIVADO: valorOver está vazio/null. Será retornado TODOS os resultados");
    }
    
    // ✅ CONSTRUIR FILTRO INTELIGENTE (regex + tipo_aposta)
    // Usa a mesma abordagem que extrairReferencia() de obter-placar-dia.php
    if ($tipo_cantos) {
        // Filtro para CANTOS: tipo_aposta + padrões LIKE (MySQL REGEXP tem problemas com emojis)
        // ✅ IMPORTANTE: Comparar em minúsculas - usar LOWER() em AMBOS os lados
        // ✅ Adicionado: búsca por emoji 🚩 que também representa cantos
        $filtro_tipo = "AND (
            LOWER(tipo_aposta) LIKE LOWER('%cantos%')
            OR LOWER(tipo_aposta) LIKE LOWER('%canto%')
            OR LOWER(titulo) LIKE LOWER('%cantos%') 
            OR LOWER(titulo) LIKE LOWER('%canto%')
            OR LOWER(titulo) LIKE LOWER('%escanteios%')
            OR LOWER(titulo) LIKE LOWER('%escantei%')
            OR titulo LIKE '%⛳%'
            OR titulo LIKE '%🚩%'
        )";
    } else {
        // Filtro para GOLS: tipo_aposta + padrões LIKE
        // ✅ IMPORTANTE: Comparar em minúsculas - usar LOWER() em AMBOS os lados
        $filtro_tipo = "AND (
            LOWER(tipo_aposta) LIKE LOWER('%gol%')
            OR LOWER(titulo) LIKE LOWER('%gol%') 
            OR LOWER(titulo) LIKE LOWER('%gols%')
            OR titulo LIKE '%⚽%'
        )";
    }

    // ✅ BUSCAR ÚLTIMOS JOGOS DO TIME 1 (filtrados por tipo de mensagem)
    // Melhorado: Busca por ambos time_1 e time_2, considerando nome completo E nome sem sigla
    $sql1 = "SELECT 
                resultado,
                data_criacao,
                time_1,
                time_2,
                placar_1,
                placar_2,
                titulo,
                tipo_aposta,
                valor_over
            FROM bote 
            WHERE (
                (LOWER(time_1) LIKE CONCAT('%', LOWER(?), '%') OR LOWER(time_1) LIKE CONCAT('%', LOWER(?), '%')
                 OR LOWER(time_2) LIKE CONCAT('%', LOWER(?), '%') OR LOWER(time_2) LIKE CONCAT('%', LOWER(?), '%'))
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
    
    $stmt1->bind_param('ssssi', $time1, $time1_nome, $time1, $time1_nome, $limite);
    
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
        
        // ✅ NOVO: Se filtrarSemReembolso está ativo, ignorar REEMBOLSO
        if ($filtrarSemReembolso && strtoupper($row['resultado']) === 'REEMBOLSO') {
            error_log("🚫 [TIME1] IGNORANDO REEMBOLSO (filtro ativo): titulo='{$row['titulo']}'");
            continue;
        }
        
        // ✅ SE FILTRO STRICT DE OVER, VALIDAR AQUI EM PHP (mais preciso que SQL LIKE)
        if ($filtro_valor_over_strict && !empty($valorOver)) {
            $titulo_check = strtolower($row['titulo'] ?? '');
            $valor_over_check = $row['valor_over'] ?? '';
            
            // DEBUG DETALHADO
            error_log("🔍 [TIME1] Processando: titulo='{$row['titulo']}' | valor_over_db='{$valor_over_check}'");
            error_log("🔍 [TIME1] Esperado: valorOver='{$valorOver}'");
            
            // ✅ CORREÇÃO: Se valor_over_check é "0.00" ou vazio, SEMPRE extrair do título
            if (empty($valor_over_check) || $valor_over_check === "0.00" || $valor_over_check === "0") {
                error_log("🔍 [TIME1] ⚠️ valor_over vazio/zero no banco, extraindo do título...");
                // Tentar extrair do título
                if (preg_match('/\+(\d+\.?\d*)\s*(?:⚽|⛳|gol|canto|gols|cantos)/i', $titulo_check, $match)) {
                    $valor_over_check = $match[1];
                    error_log("🔍 [TIME1] ✅ Extraído do título: '{$valor_over_check}'");
                } else {
                    error_log("🔍 [TIME1] ❌ REJEITANDO: Não conseguiu extrair OVER do título");
                    continue;
                }
            }
            
            // ✅ NORMALIZAR AMBOS os valores para comparação (float -> string)
            $valor_normalizado_db = (string)floatval($valor_over_check);
            $valor_normalizado_request = (string)floatval($valorOver);
            
            error_log("🔍 [TIME1] Comparando (normalizado): '{$valor_normalizado_db}' vs '{$valor_normalizado_request}'");
            if ($valor_normalizado_db !== $valor_normalizado_request) {
                error_log("🔍 [TIME1] ❌ REJEITANDO: '{$valor_normalizado_db}' ≠ '{$valor_normalizado_request}'");
                continue;
            }
            error_log("🔍 [TIME1] ✅ ACEITANDO: Valores correspondem!");
        }
        
        $historico_time1[] = [
            'resultado' => $row['resultado'],
            'data_criacao' => $row['data_criacao'],
            'time_1' => $row['time_1'],
            'time_2' => $row['time_2'],
            'placar_1' => $row['placar_1'],
            'placar_2' => $row['placar_2'],
            'titulo' => $row['titulo'],
            'tipo_aposta' => $row['tipo_aposta'],
            'valor_over' => $row['valor_over'] ?? null
        ];
    }
    $stmt1->close();

    // ✅ BUSCAR ÚLTIMOS JOGOS DO TIME 2 (filtrados por tipo de mensagem)
    // Melhorado: Busca por ambos time_1 e time_2, considerando nome completo E nome sem sigla
    $sql2 = "SELECT 
                resultado,
                data_criacao,
                time_1,
                time_2,
                placar_1,
                placar_2,
                titulo,
                tipo_aposta,
                valor_over
            FROM bote
            WHERE (
                (LOWER(time_1) LIKE CONCAT('%', LOWER(?), '%') OR LOWER(time_1) LIKE CONCAT('%', LOWER(?), '%')
                 OR LOWER(time_2) LIKE CONCAT('%', LOWER(?), '%') OR LOWER(time_2) LIKE CONCAT('%', LOWER(?), '%'))
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
    
    $stmt2->bind_param('ssssi', $time2, $time2_nome, $time2, $time2_nome, $limite);

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
        
        // ✅ NOVO: Se filtrarSemReembolso está ativo, ignorar REEMBOLSO
        if ($filtrarSemReembolso && strtoupper($row['resultado']) === 'REEMBOLSO') {
            error_log("🚫 [TIME2] IGNORANDO REEMBOLSO (filtro ativo): titulo='{$row['titulo']}'");
            continue;
        }
        
        // ✅ SE FILTRO STRICT DE OVER, VALIDAR AQUI EM PHP (mais preciso que SQL LIKE)
        if ($filtro_valor_over_strict && !empty($valorOver)) {
            $titulo_check = strtolower($row['titulo'] ?? '');
            $valor_over_check = $row['valor_over'] ?? '';
            
            // DEBUG DETALHADO
            error_log("🔍 [TIME2] Processando: titulo='{$row['titulo']}' | valor_over_db='{$valor_over_check}'");
            error_log("🔍 [TIME2] Esperado: valorOver='{$valorOver}'");
            
            // ✅ CORREÇÃO: Se valor_over_check é "0.00" ou vazio, SEMPRE extrair do título
            if (empty($valor_over_check) || $valor_over_check === "0.00" || $valor_over_check === "0") {
                error_log("🔍 [TIME2] ⚠️ valor_over vazio/zero no banco, extraindo do título...");
                // Tentar extrair do título
                if (preg_match('/\+(\d+\.?\d*)\s*(?:⚽|⛳|gol|canto|gols|cantos)/i', $titulo_check, $match)) {
                    $valor_over_check = $match[1];
                    error_log("🔍 [TIME2] ✅ Extraído do título: '{$valor_over_check}'");
                } else {
                    error_log("🔍 [TIME2] ❌ REJEITANDO: Não conseguiu extrair OVER do título");
                    continue;
                }
            }
            
            // ✅ NORMALIZAR AMBOS os valores para comparação (float -> string)
            $valor_normalizado_db = (string)floatval($valor_over_check);
            $valor_normalizado_request = (string)floatval($valorOver);
            
            error_log("🔍 [TIME2] Comparando (normalizado): '{$valor_normalizado_db}' vs '{$valor_normalizado_request}'");
            if ($valor_normalizado_db !== $valor_normalizado_request) {
                error_log("🔍 [TIME2] ❌ REJEITANDO: '{$valor_normalizado_db}' ≠ '{$valor_normalizado_request}'");
                continue;
            }
            error_log("🔍 [TIME2] ✅ ACEITANDO: Valores correspondem!");
        }
        
        $historico_time2[] = [
            'resultado' => $row['resultado'],
            'data_criacao' => $row['data_criacao'],
            'time_1' => $row['time_1'],
            'time_2' => $row['time_2'],
            'placar_1' => $row['placar_1'],
            'placar_2' => $row['placar_2'],
            'titulo' => $row['titulo'],
            'tipo_aposta' => $row['tipo_aposta'],
            'valor_over' => $row['valor_over'] ?? null
        ];
    }
    $stmt2->close();

    // ✅ SINCRONIZAR RESULTADOS - APENAS se NÃO houver filtro de OVER
    // Quando há filtro de OVER, queremos resultados DIFERENTES porque são apostas diferentes!
    // Exemplo: +0.5 GOL é uma aposta diferente de +1 GOL, mesmo que seja o mesmo jogo
    if (!$filtro_valor_over_strict) {
        sincronizarResultados($historico_time1, $historico_time2);
    }

    // 📊 LOG FINAL
    error_log("📊 RESULTADO FINAL: time1=" . count($historico_time1) . " jogos, time2=" . count($historico_time2) . " jogos");
    if ($filtro_valor_over_strict) {
        error_log("📊 Filtro OVER '{$valorOver}' ATIVADO - retornando APENAS resultados com este valor");
    } else {
        error_log("📊 Sem filtro OVER - retornando TODOS os resultados com sincronização");
    }
    
    // ✅ NOVO: Debug do filtro de reembolso
    if ($filtrarSemReembolso) {
        error_log("🚫 FILTRO DE REEMBOLSO ATIVADO - REEMBOLSO excluído dos resultados");
    } else {
        error_log("✅ FILTRO DE REEMBOLSO DESATIVADO - Todos os resultados (GREEN, RED, REEMBOLSO)");
    }
    
    error_log("═".str_repeat("═", 99));

    // ✅ RETORNAR SUCESSO COM DEBUG INFO
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'time1_historico' => $historico_time1,
        'time2_historico' => $historico_time2,
        'total_time1' => count($historico_time1),
        'total_time2' => count($historico_time2),
        'tipo' => $tipo,
        'filtro_valor_over_solicitado' => $valorOver ?? null, // ✅ NOVO: mostrar qual OVER foi solicitado
        'filtro_ativado' => $filtro_valor_over_strict, // ✅ NOVO: mostrar se filtro está ativo
        'filtro_reembolso_ativado' => $filtrarSemReembolso, // ✅ NOVO: mostrar se filtro de reembolso está ativo
        'filtro_debug' => $filtro_tipo // ✅ DEBUG: mostrar qual filtro foi usado
    ], JSON_UNESCAPED_UNICODE);

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
