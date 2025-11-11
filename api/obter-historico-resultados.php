<?php

// ✅ NÃO USAR SESSION - API PÚBLICA
// session_start();

header('Content-Type: application/json; charset=utf-8');

// ✅ CONFIGURAR TIMEZONE
date_default_timezone_set('America/Sao_Paulo');

// ✅ FUNÇÃO: Gerar logs em arquivo
$LOG_FILE = __DIR__ . '/../logs/obter-historico-resultados-' . date('Y-m-d') . '.log';
function escreverLog($mensagem) {
    global $LOG_FILE;
    $timestamp = date('H:i:s.u');
    $linha = "[$timestamp] $mensagem\n";
    file_put_contents($LOG_FILE, $linha, FILE_APPEND);
}

// ✅ LOGGING INICIAL
escreverLog("═════════════════════════════════════════════════════════════");
escreverLog("🔍 API CHAMADA - obter-historico-resultados.php");
escreverLog("🔍 METHOD: " . $_SERVER['REQUEST_METHOD']);
escreverLog("🔍 TEMPO: " . date('Y-m-d H:i:s'));

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

escreverLog("📩 DADOS RECEBIDOS:");
escreverLog("   time1: '$time1'");
escreverLog("   time2: '$time2'");
escreverLog("   tipo: '$tipo'");
escreverLog("   limite: $limite");

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

// ✅ DETECTAR TIPO ESPECÍFICO DA APOSTA A PARTIR DO PARÂMETRO "tipo"
// O tipo agora pode ser: +0.5GOL, +1GOL, +1CANTOS, +2.5GOL, +3.5GOL, etc
// Também suporta valores genéricos: gols, cantos
$tipo_normalizado = strtoupper(trim($tipo));
$is_cantos = false;
$filtro_tipo = "";

// 🔧 LOG DE DEBUG
error_log("🔍 API DEBUG - Tipo recebido: '$tipo' (normalizado: '$tipo_normalizado')");

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
    // ✅ NORMALIZAR OS TIMES PARA COMPARAÇÃO
    // Remove emojis e espaços extras
    $time1_normalizado = trim(strtolower(preg_replace('/\s+/', ' ', $time1)));
    $time2_normalizado = trim(strtolower(preg_replace('/\s+/', ' ', $time2)));
    
    // ✅ BUSCAR ÚLTIMOS JOGOS DO TIME 1 (sem filtro SQL - será feito em PHP)
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
                LOWER(time_1) LIKE CONCAT('%', LOWER(?), '%') OR LOWER(time_2) LIKE CONCAT('%', LOWER(?), '%')
            )
            AND (LOWER(tipo_aposta) LIKE '%GOL%' OR LOWER(tipo_aposta) LIKE '%CANTO%')
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
        
        // ✅ FILTRAR EM PHP USANDO extrairReferencia() - MESMO MÉTODO DO JavaScript
        $referenciaJogo = extrairReferencia($row['titulo']);
        escreverLog("🔍 TIME1 Título: '{$row['titulo']}' -> Referência: '$referenciaJogo' | Tipo pedido: '$tipo'");
        
        // 🔧 FILTRAR REEMBOLSO: Apenas quando +0.5GOL foi pedido especificamente
        if ($tipo === '+0.5GOL' || stripos($tipo, '+0.5') !== false) {
            if ($row['resultado'] === 'REEMBOLSO' || $row['resultado'] === 'reembolso') {
                escreverLog("🔍 TIME1 FILTRADO: é REEMBOLSO e +0.5GOL foi pedido");
                continue;
            }
        }
        
        if (!deveMostrarResultado($referenciaJogo, $tipo)) {
            escreverLog("🔍 TIME1 FILTRADO: não passou na validação de tipo");
            continue;
        }
        escreverLog("🔍 TIME1 INCLUÍDO: passou na validação");
        
        $historico_time1[] = [
            'resultado' => $row['resultado'],
            'data_criacao' => $row['data_criacao'],
            'time_1' => $row['time_1'],
            'time_2' => $row['time_2'],
            'placar_1' => $row['placar_1'],
            'placar_2' => $row['placar_2'],
            'titulo' => $row['titulo'],
            'tipo_aposta' => $row['tipo_aposta'],
            'referencia_extraida' => $referenciaJogo,  // ✅ ADICIONAR REFERÊNCIA EXTRAÍDA
            'time_filtrado' => $time1  // ✅ ADICIONAR O TIME QUE FOI FILTRADO
        ];
    }
    $stmt1->close();

    // ✅ BUSCAR ÚLTIMOS JOGOS DO TIME 2 (sem filtro SQL - será feito em PHP)
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
                LOWER(time_1) LIKE CONCAT('%', LOWER(?), '%') OR LOWER(time_2) LIKE CONCAT('%', LOWER(?), '%')
            )
            AND (LOWER(tipo_aposta) LIKE '%GOL%' OR LOWER(tipo_aposta) LIKE '%CANTO%')
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
        
        // ✅ FILTRAR EM PHP USANDO extrairReferencia() - MESMO MÉTODO DO JavaScript
        $referenciaJogo = extrairReferencia($row['titulo']);
        escreverLog("🔍 TIME2 Título: '{$row['titulo']}' -> Referência: '$referenciaJogo' | Tipo pedido: '$tipo'");
        
        // 🔧 FILTRAR REEMBOLSO: Apenas quando +0.5GOL foi pedido especificamente
        if ($tipo === '+0.5GOL' || stripos($tipo, '+0.5') !== false) {
            if ($row['resultado'] === 'REEMBOLSO' || $row['resultado'] === 'reembolso') {
                escreverLog("🔍 TIME2 FILTRADO: é REEMBOLSO e +0.5GOL foi pedido");
                continue;
            }
        }
        
        if (!deveMostrarResultado($referenciaJogo, $tipo)) {
            escreverLog("🔍 TIME2 FILTRADO: não passou na validação de tipo");
            continue;
        }
        escreverLog("🔍 TIME2 INCLUÍDO: passou na validação");
        
        $historico_time2[] = [
            'resultado' => $row['resultado'],
            'data_criacao' => $row['data_criacao'],
            'time_1' => $row['time_1'],
            'time_2' => $row['time_2'],
            'placar_1' => $row['placar_1'],
            'placar_2' => $row['placar_2'],
            'titulo' => $row['titulo'],
            'tipo_aposta' => $row['tipo_aposta'],
            'referencia_extraida' => $referenciaJogo,  // ✅ ADICIONAR REFERÊNCIA EXTRAÍDA
            'time_filtrado' => $time2  // ✅ ADICIONAR O TIME QUE FOI FILTRADO
        ];
    }
    $stmt2->close();

    // ✅ SINCRONIZAR RESULTADOS - Se um jogo foi GREEN/RED/REEMBOLSO, ambos os times devem ver o mesmo resultado
    // Isso é importante porque quando Everton x Fulham termina GREEN, tanto Everton quanto Fulham devem mostrar GREEN
    sincronizarResultados($historico_time1, $historico_time2);

    // ✅ RETORNAR SUCESSO
    http_response_code(200);
    error_log("🔍 RESPOSTA: time1=" . count($historico_time1) . ", time2=" . count($historico_time2) . ", tipo='$tipo'");
    echo json_encode([
        'success' => true,
        'time1_historico' => $historico_time1,
        'time2_historico' => $historico_time2,
        'total_time1' => count($historico_time1),
        'total_time2' => count($historico_time2),
        'tipo' => $tipo,
        'debug' => [
            'tipo_recebido' => $tipo,
            'total_analisados_time1' => 'ver logs',
            'total_analisados_time2' => 'ver logs'
        ]
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
 * FUNÇÃO: Deve Mostrar Resultado?
 * ================================================================
 * 
 * Compara a referência extraída do titulo com o tipo pedido.
 * Retorna true APENAS se o resultado deve ser mostrado.
 * 
 * Exemplo:
 * - Se tipo pedido é "+0.5GOL" e referencia é "+0.5GOL" -> true
 * - Se tipo pedido é "+0.5GOL" e referencia é "+1GOL" -> false
 * - Se tipo pedido é "+1GOL" e referencia é "+1GOL" -> true
 * - Se tipo pedido é "CANTOS" e referencia é "+1CANTOS" -> true
 */
function deveMostrarResultado($referenciaJogo, $tipoPedido) {
    escreverLog("\n");
    escreverLog("═══════════════════════════════════════════════════════════");
    escreverLog("🔍 FUNÇÃO deveMostrarResultado CHAMADA");
    escreverLog("   referenciaJogo: '$referenciaJogo'");
    escreverLog("   tipoPedido: '$tipoPedido'");
    
    // Se não conseguiu extrair referência do jogo, REJEITAR (não aceitar tudo)
    if (empty($referenciaJogo)) {
        escreverLog("   ❌ REJEITAR: referenciaJogo vazio");
        escreverLog("═══════════════════════════════════════════════════════════");
        return false;
    }
    
    // 🔧 EXTRAIR TIPO (CANTOS, GOL, etc)
    $tipo_ref_cleaned = str_replace(['⚽', '⛳', '️', ' '], '', $referenciaJogo);
    $tipo_pedido_cleaned = str_replace(['⚽', '⛳', '️', ' '], '', $tipoPedido);
    $tipo_ref_upper = strtoupper($tipo_ref_cleaned);
    $tipo_pedido_upper = strtoupper($tipo_pedido_cleaned);
    
    escreverLog("   Tipo ref (cleaned): '$tipo_ref_cleaned' → '$tipo_ref_upper'");
    escreverLog("   Tipo pedido (cleaned): '$tipo_pedido_cleaned' → '$tipo_pedido_upper'");
    
    // 🔧 VERIFICAR SE TIPO BATE (GOL com GOL, CANTOS com CANTOS)
    $ref_eh_cantos = stripos($tipo_ref_upper, 'CANTOS') !== false || stripos($tipo_ref_upper, 'ESCANTEIO') !== false;
    $pedido_eh_cantos = stripos($tipo_pedido_upper, 'CANTOS') !== false || stripos($tipo_pedido_upper, 'ESCANTEIO') !== false;
    
    escreverLog("   Ref é CANTOS? " . ($ref_eh_cantos ? 'SIM' : 'NÃO'));
    escreverLog("   Pedido é CANTOS? " . ($pedido_eh_cantos ? 'SIM' : 'NÃO'));
    
    // Se um é CANTOS e outro não, rejeitar
    if ($ref_eh_cantos !== $pedido_eh_cantos) {
        escreverLog("   ❌ REJEITAR: Tipo não bate (um é CANTOS, outro não)");
        escreverLog("═══════════════════════════════════════════════════════════");
        return false;
    }
    escreverLog("   ✅ Tipo bate (ambos são GOL OU ambos são CANTOS)");
    
    // 🔧 EXTRAIR VALOR NUMÉRICO DO TIPO PEDIDO (ex: "+0.5GOL" → 0.5)
    $valor_tipo_pedido = null;
    $matches_pedido = [];
    if (preg_match('/[\+\-]?([\d\.]+)/', $tipoPedido, $matches_pedido)) {
        $valor_tipo_pedido = floatval($matches_pedido[1]);
        escreverLog("   Valor pedido extraído: '$matches_pedido[1]' → " . $valor_tipo_pedido);
    } else {
        escreverLog("   ❌ Não conseguiu extrair valor pedido com regex");
    }
    
    // 🔧 EXTRAIR VALOR NUMÉRICO DA REFERÊNCIA DO JOGO (ex: "+0.5⚽GOL" → 0.5)
    $valor_referencia_jogo = null;
    $matches_ref = [];
    if (preg_match('/[\+\-]?([\d\.]+)/', $referenciaJogo, $matches_ref)) {
        $valor_referencia_jogo = floatval($matches_ref[1]);
        escreverLog("   Valor referência extraído: '$matches_ref[1]' → " . $valor_referencia_jogo);
    } else {
        escreverLog("   ❌ Não conseguiu extrair valor referência com regex");
    }
    
    // 🔧 COMPARAÇÃO NUMÉRICA EXATA
    if ($valor_tipo_pedido !== null && $valor_referencia_jogo !== null) {
        $diferenca = abs($valor_referencia_jogo - $valor_tipo_pedido);
        escreverLog("   Diferença: |$valor_referencia_jogo - $valor_tipo_pedido| = $diferenca");
        escreverLog("   Tolerância: 0.001");
        
        $resultado = $diferenca < 0.001; // Tolerância de 0.001
        
        if ($resultado) {
            escreverLog("   ✅ ACEITAR: Valores batem!");
        } else {
            escreverLog("   ❌ REJEITAR: Valores não batem (diferença > 0.001)");
        }
        
        escreverLog("═══════════════════════════════════════════════════════════");
        return $resultado;
    }
    
    // Se não conseguiu extrair valores, REJEITAR
    escreverLog("   ❌ REJEITAR: Não conseguiu extrair valores numéricos");
    escreverLog("═══════════════════════════════════════════════════════════");
    return false;
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
