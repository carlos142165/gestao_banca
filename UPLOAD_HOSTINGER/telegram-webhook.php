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
        processarResultado($messageText, $messageHour, $telegramMessageId);
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
        $lines = array_map('trim', explode("\n", $resultadoText));
        $lines = array_filter($lines);
        
        $time_1 = "";
        $time_2 = "";
        $tipo_aposta = "";
        $resultado = "";
        
        foreach ($lines as $line) {
            if (strpos($line, '⚽') !== false && strpos($line, 'x') !== false) {
                $line_limpa = str_replace(['⚽', '⚽️'], '', $line);
                $parts = explode('x', $line_limpa);
                if (count($parts) >= 2) {
                    $time_1 = trim(preg_replace('/\([^)]*\)/', '', $parts[0]));
                    $time_2 = trim(preg_replace('/\([^)]*\)/', '', $parts[1]));
                }
            }
            
            if (preg_match('/OVER\s*\(\s*([\+\-]?[\d\.]+)\s*(⚽️?|⛳️?)\s*(GOLS?|CANTOS?)\s*\)/i', $line, $m)) {
                $numero = $m[1];
                if (strpos(strtoupper($m[3]), 'GOL') !== false) {
                    $tipo_aposta = 'Gols over ' . $numero;
                }
            }
            
            if (preg_match('/(GREEN|RED|REEMBOLSO)/i', $line, $m)) {
                $resultado = strtoupper($m[1]);
            }
        }
        
        if (empty($time_1) || empty($time_2) || empty($resultado)) {
            file_put_contents($logFile, "⚠️ Resultado incompleto\n", FILE_APPEND);
            return false;
        }
        
        if (preg_match('/[\+\-]?([\d\.]+)/', $tipo_aposta, $m)) {
            $valor_resultado = floatval($m[1]);
        } else {
            return false;
        }
        
        file_put_contents($logFile, "🔍 Procurando aposta com valor_over=" . $valor_resultado . "\n", FILE_APPEND);
        
        $search = "SELECT id, valor_over FROM bote WHERE status_aposta='ATIVA' AND resultado IS NULL AND ((time_1 LIKE ? AND time_2 LIKE ?) OR (time_1 LIKE ? AND time_2 LIKE ?)) ORDER BY id DESC LIMIT 20";
        $searchStmt = $conexao->prepare($search);
        $t1 = '%' . $time_1 . '%';
        $t2 = '%' . $time_2 . '%';
        $searchStmt->bind_param('ssss', $t1, $t2, $t2, $t1);
        $searchStmt->execute();
        $res = $searchStmt->get_result();
        
        $aposta = null;
        while ($row = $res->fetch_assoc()) {
            $vo = floatval($row['valor_over']);
            if (abs($vo - $valor_resultado) < 0.001) {
                $aposta = $row;
                break;
            }
        }
        $searchStmt->close();
        
        if (!$aposta) {
            file_put_contents($logFile, "❌ Nenhuma aposta encontrada\n", FILE_APPEND);
            return false;
        }
        
        $updateStmt = $conexao->prepare("UPDATE bote SET resultado=?, status_aposta=CASE WHEN ?='GREEN' THEN 'GANHA' WHEN ?='RED' THEN 'PERDIDA' WHEN ?='REEMBOLSO' THEN 'CANCELADA' ELSE 'ATIVA' END, updated_at=NOW() WHERE id=?");
        $updateStmt->bind_param('ssssi', $resultado, $resultado, $resultado, $resultado, $aposta['id']);
        $updateStmt->execute();
        $updateStmt->close();
        
        file_put_contents($logFile, "✅ Resultado processado: " . $resultado . "\n", FILE_APPEND);
        return true;
        
    } catch (Exception $e) {
        file_put_contents($logFile, "❌ Erro: " . $e->getMessage() . "\n", FILE_APPEND);
        return false;
    }
}

?>
