<?php
clearstatcache();
/**
 * ================================================================
 * WEBHOOK DO TELEGRAM - VERSÃO SIMPLIFICADA
 * ================================================================
 * 
 * Recebe mensagens do Telegram e salva no banco automaticamente
 * Funciona com channel_post E messages normais
 * 
 * ================================================================
 */

// ✅ SEM SESSION_START - PERMITE ACESSO DO TELEGRAM
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 0);

// ✅ CONFIGURAR FUSO HORÁRIO
date_default_timezone_set('America/Sao_Paulo');

// ✅ INCLUIR CONFIGURAÇÕES
require_once '../telegram-config.php';
require_once '../config.php';

// ✅ GARANTIR QUE CONEXÃO ESTÁ ATIVA
$conexao = obterConexao();
if (!$conexao) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit;
}

// ✅ CRIAR PASTA DE LOGS SE NÃO EXISTIR
$logFile = __DIR__ . '/../logs/telegram-webhook.log';
if (!is_dir(dirname($logFile))) {
    mkdir(dirname($logFile), 0755, true);
}

// ✅ FUNÇÃO PARA LOGAR MENSAGENS
function logarWebhook($mensagem) {
    global $logFile;
    file_put_contents($logFile, $mensagem . "\n", FILE_APPEND);
}

// ✅ RECEBER E REGISTRAR TODOS OS DADOS
$input = json_decode(file_get_contents('php://input'), true);
file_put_contents($logFile, "\n" . str_repeat("=", 80) . "\n", FILE_APPEND);
file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Webhook acionado (VERSÃO NOVA COMPLETA)\n", FILE_APPEND);
file_put_contents($logFile, "Dados recebidos: " . json_encode($input) . "\n", FILE_APPEND);

try {
    
    // ✅ VALIDAR SE RECEBEU DADOS
    if (!$input) {
        throw new Exception("Nenhum dado recebido do Telegram");
    }
    
    // ✅ BUSCAR MENSAGEM - PODE SER channel_post OU message
    $message = null;
    $messageType = null;
    
    if (isset($input['channel_post'])) {
        $message = $input['channel_post'];
        $messageType = 'channel_post';
        file_put_contents($logFile, "✅ Tipo: CHANNEL_POST\n", FILE_APPEND);
    } elseif (isset($input['message'])) {
        $message = $input['message'];
        $messageType = 'message';
        file_put_contents($logFile, "✅ Tipo: MESSAGE\n", FILE_APPEND);
    } else {
        file_put_contents($logFile, "⏭️ Tipo de update não suportado - ignorado\n", FILE_APPEND);
        http_response_code(200);
        echo json_encode(['status' => 'ok', 'reason' => 'unsupported-type']);
        exit;
    }
    
    // ✅ VALIDAR MENSAGEM
    if (!$message) {
        throw new Exception("Mensagem é null");
    }
    
    // ✅ EXTRAIR TEXTO DA MENSAGEM
    $messageText = '';
    if (isset($message['text']) && !empty($message['text'])) {
        $messageText = $message['text'];
    } elseif (isset($message['caption']) && !empty($message['caption'])) {
        $messageText = $message['caption'];
    }
    
    file_put_contents($logFile, "Texto extraído: " . substr($messageText, 0, 100) . "...\n", FILE_APPEND);
    
    if (empty($messageText)) {
        file_put_contents($logFile, "⏭️ Mensagem vazia - ignorada\n", FILE_APPEND);
        http_response_code(200);
        echo json_encode(['status' => 'ok', 'reason' => 'empty-message']);
        exit;
    }
    
    // ✅ VERIFICAR TIPO DE MENSAGEM
    $ehOportunidade = (strpos($messageText, "Oportunidade!") !== false);
    $ehResultado = (strpos($messageText, "Resultado") !== false);
    
    file_put_contents($logFile, "É oportunidade? " . ($ehOportunidade ? "SIM" : "NÃO") . "\n", FILE_APPEND);
    file_put_contents($logFile, "É resultado? " . ($ehResultado ? "SIM" : "NÃO") . "\n", FILE_APPEND);
    
    if (!$ehOportunidade && !$ehResultado) {
        file_put_contents($logFile, "⏭️ Formato inválido - não contém palavras-chave\n", FILE_APPEND);
        http_response_code(200);
        echo json_encode(['status' => 'ok', 'reason' => 'invalid-format']);
        exit;
    }
    
    // ✅ EXTRAIR DADOS DA MENSAGEM
    $telegramMessageId = isset($message['message_id']) ? intval($message['message_id']) : 0;
    $messageDate = isset($message['date']) ? intval($message['date']) : time();
    $messageHour = date('H:i:s', $messageDate);
    
    file_put_contents($logFile, "📨 Mensagem válida recebida\n", FILE_APPEND);
    file_put_contents($logFile, "   ID: " . $telegramMessageId . "\n", FILE_APPEND);
    file_put_contents($logFile, "   Hora: " . $messageHour . "\n", FILE_APPEND);
    file_put_contents($logFile, "   Tipo: " . ($ehOportunidade ? "OPORTUNIDADE" : ($ehResultado ? "RESULTADO" : "OUTRO")) . "\n", FILE_APPEND);
    
    // ✅ PROCESSAR DE ACORDO COM O TIPO
    if ($ehOportunidade) {
        // ✅ SALVAR OPORTUNIDADE
        file_put_contents($logFile, "💾 Salvando oportunidade...\n", FILE_APPEND);
        try {
            $dadosMensagem = extrairDadosMensagem($messageText, $messageHour, $telegramMessageId);
            salvarNosBancoDados($dadosMensagem);
            file_put_contents($logFile, "✅ Oportunidade salva com sucesso\n\n", FILE_APPEND);
        } catch (Exception $e) {
            file_put_contents($logFile, "❌ ERRO AO SALVAR OPORTUNIDADE: " . $e->getMessage() . "\n\n", FILE_APPEND);
            throw $e;
        }
        
    } else if ($ehResultado) {
        // ✅ PROCESSAR RESULTADO
        file_put_contents($logFile, "🎯 Processando resultado...\n", FILE_APPEND);
        try {
            $processResult = processarResultado($messageText, $messageHour, $telegramMessageId);
            if ($processResult) {
                file_put_contents($logFile, "✅ Resultado processado com sucesso\n\n", FILE_APPEND);
            } else {
                file_put_contents($logFile, "⚠️ Resultado não pode ser processado (dados incompletos)\n\n", FILE_APPEND);
            }
        } catch (Exception $e) {
            file_put_contents($logFile, "❌ ERRO AO PROCESSAR RESULTADO: " . $e->getMessage() . "\n\n", FILE_APPEND);
            throw $e;
        }
    }
    
    // ✅ RESPONDER AO TELEGRAM COM OK
    http_response_code(200);
    echo json_encode(['status' => 'ok', 'message_id' => $telegramMessageId]);

} catch (Exception $e) {
    file_put_contents($logFile, "❌ ERRO: " . $e->getMessage() . "\n", FILE_APPEND);
    
    http_response_code(200); // Telegram exige 200 OK mesmo em erro
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

function salvarNosBancoDados($dadosMensagem) {
    global $conexao, $logFile;
    
    try {
        file_put_contents($logFile, "📝 Iniciando salvamento nos dados:\n", FILE_APPEND);
        file_put_contents($logFile, "   - valor_over: " . $dadosMensagem['valor_over'] . "\n", FILE_APPEND);
        
        // ✅ VERIFICAR E RECONECTAR SE NECESSÁRIO
        $conexao = obterConexao();
        if (!$conexao) {
            throw new Exception("Falha ao conectar ao banco de dados");
        }
        
        file_put_contents($logFile, "✅ Conexão com banco verificada e ativa\n", FILE_APPEND);
        
        $query = "INSERT INTO bote (telegram_message_id, mensagem_completa, titulo, tipo_aposta, time_1, time_2, placar_1, placar_2, escanteios_1, escanteios_2, valor_over, odds, tipo_odds, hora_mensagem, status_aposta, resultado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conexao->prepare($query);
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conexao->error);
        }
        
        file_put_contents($logFile, "✅ Query preparada com sucesso\n", FILE_APPEND);
        
        $resultado = NULL;
        $telegram_id = intval($dadosMensagem['telegram_message_id']);
        $placar_1 = intval($dadosMensagem['placar_1']);
        $placar_2 = intval($dadosMensagem['placar_2']);
        $escanteios_1 = intval($dadosMensagem['escanteios_1']);
        $escanteios_2 = intval($dadosMensagem['escanteios_2']);
        $valor_over = floatval($dadosMensagem['valor_over']);
        $odds = floatval($dadosMensagem['odds']);
        
        file_put_contents($logFile, "DEBUG: valor_over=" . $valor_over . " (tipo: " . gettype($valor_over) . ")\n", FILE_APPEND);
        
        // Truncar strings
        $msg = substr($dadosMensagem['mensagem_completa'], 0, 5000);
        $tit = substr($dadosMensagem['titulo'], 0, 255);
        $tipo = substr($dadosMensagem['tipo_aposta'], 0, 50);
        $t1 = substr($dadosMensagem['time_1'], 0, 100);
        $t2 = substr($dadosMensagem['time_2'], 0, 100);
        $tipo_odds = substr($dadosMensagem['tipo_odds'], 0, 50);
        
        file_put_contents($logFile, "DEBUG: Pronto para bind_param\n", FILE_APPEND);
        file_put_contents($logFile, "DEBUG: Parâmetros - telegram_id={$telegram_id}, valor_over={$valor_over}, odds={$odds}, result={$resultado}\n", FILE_APPEND);
        
        // i=telegram_id, s=msg, s=tit, s=tipo, s=t1, s=t2, i=placar_1, i=placar_2, i=escanteios_1, i=escanteios_2, d=valor_over, d=odds, s=tipo_odds, s=hora, s=status, s=resultado (16 params = 16 chars)
        if (!$stmt->bind_param("isssssiiiddsssss", $telegram_id, $msg, $tit, $tipo, $t1, $t2, $placar_1, $placar_2, $escanteios_1, $escanteios_2, $valor_over, $odds, $tipo_odds, $dadosMensagem['hora_mensagem'], $dadosMensagem['status_aposta'], $resultado)) {
            throw new Exception("Bind failed: " . $stmt->error);
        }
        
        file_put_contents($logFile, "✅ bind_param executado com sucesso\n", FILE_APPEND);
        
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        file_put_contents($logFile, "✅ Insert executado com sucesso - ID: " . $conexao->insert_id . "\n", FILE_APPEND);
        
        $stmt->close();
        return true;
        
    } catch (Exception $e) {
        file_put_contents($logFile, "❌ ERRO: " . $e->getMessage() . "\n", FILE_APPEND);
        throw $e;
    }
}

function extrairDadosMensagem($rawText, $msgTime, $telegramMessageId) {
    global $logFile;
    
    $lines = array_map('trim', explode("\n", $rawText));
    $lines = array_filter($lines);
    
    $titulo = "";
    $tipo_aposta = "";
    $time_1 = "";
    $time_2 = "";
    $placar_1 = 0;
    $placar_2 = 0;
    $escanteios_1 = 0;
    $escanteios_2 = 0;
    $valor_over = 0;
    $odds = 0;
    $tipo_odds = "";
    
    foreach ($lines as $line) {
        if (strpos($line, '📊') !== false) {
            $titulo = trim(str_replace(['📊', '🚨'], '', $line));
            if (preg_match('/\+(\d+\.?\d*)/', $titulo, $m)) {
                $valor_over = floatval($m[1]);
            }
            if (strpos($titulo, 'CANTOS') !== false) {
                $tipo_aposta = "CANTOS";
            } elseif (strpos($titulo, 'GOL') !== false) {
                $tipo_aposta = "GOL";
            }
        }
        
        if (preg_match('/OVER\s*\(\s*\+(\d+\.?\d*)/i', $line, $m)) {
            $valor_over = floatval($m[1]);
            file_put_contents($logFile, "✅ OVER detectado: valor extraído = " . $valor_over . "\n", FILE_APPEND);
            
            if (preg_match('/(GOLS?|GOL|CANTOS?)/i', $line, $mt)) {
                $tipo_aposta = (strpos(strtoupper($mt[1]), 'GOL') !== false) ? "GOL" : "CANTOS";
            }
            if (empty($titulo)) {
                $titulo = trim($line);
            }
        }
        
        if (strpos($line, 'x') !== false && (strpos($line, '(H)') !== false || strpos($line, '(A)') !== false)) {
            $parts = explode('x', $line);
            if (count($parts) >= 2) {
                $time_1 = trim(preg_replace('/\([^)]*\)/', '', $parts[0]));
                $time_2 = trim(preg_replace('/\([^)]*\)/', '', $parts[1]));
            }
        }
        
        if (strpos($line, 'Gols over') !== false && preg_match('/Gols over\s*[\+\-]?[\d\.]*\s*:\s*([\d\.]+)/i', $line, $m)) {
            $odds = floatval($m[1]);
            $tipo_odds = "Gols Odds";
        }
    }
    
    file_put_contents($logFile, "✅ Extração concluída: valor_over=" . $valor_over . ", odds=" . $odds . "\n", FILE_APPEND);
    
    return [
        'telegram_message_id' => $telegramMessageId,
        'mensagem_completa' => $rawText,
        'titulo' => $titulo,
        'tipo_aposta' => $tipo_aposta ?: "GOL",
        'time_1' => $time_1 ?: "N/A",
        'time_2' => $time_2 ?: "N/A",
        'placar_1' => $placar_1,
        'placar_2' => $placar_2,
        'escanteios_1' => $escanteios_1,
        'escanteios_2' => $escanteios_2,
        'valor_over' => $valor_over,
        'odds' => $odds,
        'tipo_odds' => $tipo_odds ?: "N/A",
        'hora_mensagem' => $msgTime,
        'status_aposta' => "ATIVA"
    ];
}

function processarResultado($resultadoText, $msgTime, $telegramMessageId) {
    global $conexao, $logFile;
    
    try {
        file_put_contents($logFile, "📝 [RESULTADO] Iniciando processamento\n", FILE_APPEND);
        
        // ✅ VERIFICAR E RECONECTAR SE NECESSÁRIO
        $conexao = obterConexao();
        if (!$conexao) {
            throw new Exception("Falha ao conectar ao banco de dados");
        }
        
        file_put_contents($logFile, "✅ Conexão com banco verificada e ativa\n", FILE_APPEND);
        
        $lines = array_map('trim', explode("\n", $resultadoText));
        $lines = array_filter($lines);
        
        file_put_contents($logFile, "   Linhas extraídas: " . count($lines) . "\n", FILE_APPEND);
        
        $time_1 = "";
        $time_2 = "";
        $tipo_aposta = "";
        $tipo_resultado = ""; // GOLS ou CANTOS
        $resultado = "";
        
        foreach ($lines as $line) {
            file_put_contents($logFile, "   Processando linha: " . substr($line, 0, 50) . "...\n", FILE_APPEND);
            
            if (strpos($line, '⚽') !== false && strpos($line, 'x') !== false) {
                $line_limpa = str_replace(['⚽', '⚽️'], '', $line);
                $parts = explode('x', $line_limpa);
                if (count($parts) >= 2) {
                    $time_1 = trim(preg_replace('/\([^)]*\)/', '', $parts[0]));
                    $time_2 = trim(preg_replace('/\([^)]*\)/', '', $parts[1]));
                    file_put_contents($logFile, "   ✅ Times encontrados: {$time_1} x {$time_2}\n", FILE_APPEND);
                }
            }
            
            // Tenta encontrar OVER com parênteses (formato oportunidade)
            if (preg_match('/OVER\s*\(\s*([\+\-]?[\d\.]+)\s*(⚽️?|⛳️?)\s*(GOLS?|CANTOS?)\s*\)/i', $line, $m)) {
                $numero = $m[1];
                $tipo = strtoupper($m[3]);
                if (strpos($tipo, 'GOL') !== false) {
                    $tipo_aposta = 'Gols over ' . $numero;
                    $tipo_resultado = 'GOLS';
                    file_put_contents($logFile, "   ✅ Tipo aposta encontrado (com parênteses): {$tipo_aposta} - Tipo: GOLS\n", FILE_APPEND);
                } elseif (strpos($tipo, 'CANTO') !== false) {
                    $tipo_aposta = 'Cantos over ' . $numero;
                    $tipo_resultado = 'CANTOS';
                    file_put_contents($logFile, "   ✅ Tipo aposta encontrado (com parênteses): {$tipo_aposta} - Tipo: CANTOS\n", FILE_APPEND);
                }
            }
            // Tenta encontrar "Gols over +0.5" (formato resultado)
            else if (preg_match('/Gols\s+over\s+([\+\-]?[\d\.]+)/i', $line, $m)) {
                $numero = $m[1];
                $tipo_aposta = 'Gols over ' . $numero;
                $tipo_resultado = 'GOLS';
                file_put_contents($logFile, "   ✅ Tipo aposta encontrado (sem parênteses): {$tipo_aposta} - Tipo: GOLS\n", FILE_APPEND);
            }
            // Tenta encontrar "Cantos over +0.5" (formato resultado)
            else if (preg_match('/Cantos\s+over\s+([\+\-]?[\d\.]+)/i', $line, $m)) {
                $numero = $m[1];
                $tipo_aposta = 'Cantos over ' . $numero;
                $tipo_resultado = 'CANTOS';
                file_put_contents($logFile, "   ✅ Tipo aposta encontrado (sem parênteses): {$tipo_aposta} - Tipo: CANTOS\n", FILE_APPEND);
            }
            // Tenta encontrar "Escanteios over +0.5" (formato resultado alternativo)
            else if (preg_match('/Escanteios\s+over\s+([\+\-]?[\d\.]+)/i', $line, $m)) {
                $numero = $m[1];
                $tipo_aposta = 'Cantos over ' . $numero;
                $tipo_resultado = 'CANTOS';
                file_put_contents($logFile, "   ✅ Tipo aposta encontrado (Escanteios): {$tipo_aposta} - Tipo: CANTOS\n", FILE_APPEND);
            }
            
            if (preg_match('/(GREEN|RED|REEMBOLSO)/i', $line, $m)) {
                $resultado = strtoupper($m[1]);
                file_put_contents($logFile, "   ✅ Resultado encontrado: {$resultado}\n", FILE_APPEND);
            }
        }
        
        file_put_contents($logFile, "   Resumo extraído:\n", FILE_APPEND);
        file_put_contents($logFile, "     - time_1: '{$time_1}'\n", FILE_APPEND);
        file_put_contents($logFile, "     - time_2: '{$time_2}'\n", FILE_APPEND);
        file_put_contents($logFile, "     - tipo_aposta: '{$tipo_aposta}'\n", FILE_APPEND);
        file_put_contents($logFile, "     - tipo_resultado: '{$tipo_resultado}'\n", FILE_APPEND);
        file_put_contents($logFile, "     - resultado: '{$resultado}'\n", FILE_APPEND);
        
        if (empty($time_1) || empty($time_2) || empty($resultado)) {
            file_put_contents($logFile, "⚠️ Resultado incompleto: time_1=" . (empty($time_1) ? "vazio" : "OK") . ", time_2=" . (empty($time_2) ? "vazio" : "OK") . ", resultado=" . (empty($resultado) ? "vazio" : "OK") . "\n", FILE_APPEND);
            return false;
        }
        
        if (preg_match('/[\+\-]?([\d\.]+)/', $tipo_aposta, $m)) {
            $valor_resultado = floatval($m[1]);
            file_put_contents($logFile, "✅ Valor resultado extraído: {$valor_resultado}\n", FILE_APPEND);
        } else {
            file_put_contents($logFile, "❌ Não consegui extrair valor de: {$tipo_aposta}\n", FILE_APPEND);
            return false;
        }
        
        file_put_contents($logFile, "🔍 Procurando aposta com valor_over=" . $valor_resultado . ", tipo=" . $tipo_resultado . " e times: '{$time_1}' x '{$time_2}'\n", FILE_APPEND);
        
        $search = "SELECT id, valor_over, tipo_aposta FROM bote WHERE status_aposta='ATIVA' AND resultado IS NULL AND ((time_1 LIKE ? AND time_2 LIKE ?) OR (time_1 LIKE ? AND time_2 LIKE ?)) ORDER BY id DESC LIMIT 20";
        
        file_put_contents($logFile, "   Preparando search query...\n", FILE_APPEND);
        
        if (!$searchStmt = $conexao->prepare($search)) {
            throw new Exception("Prepare search failed: " . $conexao->error);
        }
        
        $t1 = '%' . $time_1 . '%';
        $t2 = '%' . $time_2 . '%';
        
        file_put_contents($logFile, "   Bind search params: t1=%{$time_1}%, t2=%{$time_2}%\n", FILE_APPEND);
        
        if (!$searchStmt->bind_param('ssss', $t1, $t2, $t2, $t1)) {
            throw new Exception("Bind search failed: " . $searchStmt->error);
        }
        
        if (!$searchStmt->execute()) {
            throw new Exception("Execute search failed: " . $searchStmt->error);
        }
        
        $res = $searchStmt->get_result();
        $totalRows = $res->num_rows;
        
        file_put_contents($logFile, "   ✅ Search retornou " . $totalRows . " registros\n", FILE_APPEND);
        
        $aposta = null;
        while ($row = $res->fetch_assoc()) {
            $vo = floatval($row['valor_over']);
            $tipo_aposta_db = strtoupper($row['tipo_aposta']);
            
            // Verificar se TIPO e VALOR coincidem
            $tipoMatch = false;
            if ($tipo_resultado === 'GOLS' && strpos($tipo_aposta_db, 'GOL') !== false) {
                $tipoMatch = true;
            } elseif ($tipo_resultado === 'CANTOS' && strpos($tipo_aposta_db, 'CANTO') !== false) {
                $tipoMatch = true;
            }
            
            file_put_contents($logFile, "   Comparando: valor_over DB={$vo}, resultado={$valor_resultado}, tipo_db={$tipo_aposta_db}, tipo_resultado={$tipo_resultado}, match=" . ($tipoMatch ? "SIM" : "NÃO") . "\n", FILE_APPEND);
            
            if ($tipoMatch && abs($vo - $valor_resultado) < 0.001) {
                $aposta = $row;
                file_put_contents($logFile, "   ✅ MATCH ENCONTRADO! ID=" . $aposta['id'] . " (tipo e valor corretos)\n", FILE_APPEND);
                break;
            }
        }
        $searchStmt->close();
        
        if (!$aposta) {
            file_put_contents($logFile, "❌ Nenhuma aposta encontrada com valor_over={$valor_resultado} e tipo={$tipo_resultado}\n", FILE_APPEND);
            return false;
        }
        
        file_put_contents($logFile, "📝 Atualizando aposta ID={$aposta['id']} com resultado={$resultado}\n", FILE_APPEND);
        
        $updateStmt = $conexao->prepare("UPDATE bote SET resultado=?, status_aposta=CASE WHEN ?='GREEN' THEN 'GANHA' WHEN ?='RED' THEN 'PERDIDA' WHEN ?='REEMBOLSO' THEN 'CANCELADA' ELSE 'ATIVA' END, updated_at=NOW() WHERE id=?");
        
        if (!$updateStmt) {
            throw new Exception("Prepare update failed: " . $conexao->error);
        }
        
        if (!$updateStmt->bind_param('ssssi', $resultado, $resultado, $resultado, $resultado, $aposta['id'])) {
            throw new Exception("Bind update failed: " . $updateStmt->error);
        }
        
        if (!$updateStmt->execute()) {
            throw new Exception("Execute update failed: " . $updateStmt->error);
        }
        
        $updateStmt->close();
        
        file_put_contents($logFile, "✅✅ Resultado processado com sucesso: ID={$aposta['id']}, Resultado={$resultado}, Tipo={$tipo_resultado}\n", FILE_APPEND);
        return true;
        
    } catch (Exception $e) {
        file_put_contents($logFile, "❌ Erro: " . $e->getMessage() . "\n", FILE_APPEND);
        return false;
    }
}

?>
