<?php
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

// ✅ RECEBER E REGISTRAR TODOS OS DADOS
$input = json_decode(file_get_contents('php://input'), true);
file_put_contents($logFile, "\n" . str_repeat("=", 80) . "\n", FILE_APPEND);
file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Webhook acionado\n", FILE_APPEND);
file_put_contents($logFile, "Ambiente: " . (defined('ENVIRONMENT') ? ENVIRONMENT : 'desconhecido') . "\n", FILE_APPEND);
file_put_contents($logFile, "Banco: " . DB_NAME . " | Host: " . DB_HOST . "\n", FILE_APPEND);
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
    
    // ✅ VERIFICAR TIPO DE MENSAGEM (sem validação de canal por enquanto)
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
        $dadosMensagem = extrairDadosMensagem($messageText, $messageHour, $telegramMessageId);
        salvarNosBancoDados($dadosMensagem);
        file_put_contents($logFile, "✅ Oportunidade salva com sucesso\n\n", FILE_APPEND);
        
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

/**
 * ================================================================
 * FUNÇÃO: Processar mensagem de resultado
 * ================================================================
 * Extrai os dados do resultado e atualiza a aposta correspondente
 * ✅ AGORA: Compara o TIPO DE APOSTA antes de aplicar o resultado
 */
function processarResultado($resultadoText, $msgTime, $telegramMessageId) {
    global $conexao, $logFile;
    
    try {
        // ✅ EXTRAIR DADOS DO RESULTADO
        // Exemplo:
        // Resultado disponível!
        // ⚽️ Nantes (H) x Metz (A) (ao vivo)
        // Gols over +0.5 - ODD: 2.005 - GREEN✅
        
        $lines = array_map('trim', explode("\n", $resultadoText));
        $lines = array_filter($lines);
        
        $time_1 = "";
        $time_2 = "";
        $tipo_aposta = "";
        $valor_over = "";
        $resultado = "";
        
        foreach ($lines as $line) {
            // Extrair times (linha com ⚽️ e 'x')
            if (strpos($line, '⚽') !== false && strpos($line, 'x') !== false) {
                // Exemplo: ⚽️ Nantes (H) x Metz (A) (ao vivo)
                $line_limpa = str_replace('⚽', '', $line);
                $line_limpa = str_replace('⚽️', '', $line_limpa);
                
                $parts = explode('x', $line_limpa);
                if (count($parts) >= 2) {
                    $time_1 = trim(preg_replace('/\([^)]*\)/', '', $parts[0]));
                    $time_2 = trim(preg_replace('/\([^)]*\)/', '', $parts[1]));
                }
            }
            
            // Extrair tipo de aposta, valor e resultado
            // Exemplo: Gols over +0.5 - ODD: 2.005 - GREEN✅
            if (strpos($line, 'over') !== false) {
                // Extrair tipo (Gols, Escanteios, Cantos, etc) e o valor (+0.5, +1, etc)
                if (preg_match('/(Gols|Escanteios|Cantos)\s+over\s+([\+\-]?[\d\.]+)/i', $line, $matches)) {
                    $tipo_base = trim(strtoupper($matches[1]));
                    $valor_over = trim($matches[2]); // Ex: +0.5, +1, +2.5
                    
                    // Classificar o tipo de aposta (simplificado)
                    if (stripos($tipo_base, 'GOL') !== false) {
                        $tipo_aposta = 'GOL';
                    } elseif (stripos($tipo_base, 'ESCANTEIO') !== false || stripos($tipo_base, 'CANTO') !== false) {
                        $tipo_aposta = 'CANTOS';
                    }
                }
                
                // Extrair resultado (GREEN, RED, REEMBOLSO)
                if (preg_match('/(GREEN|RED|REEMBOLSO)/i', $line, $matches)) {
                    $resultado = strtoupper($matches[1]);
                }
            }
        }
        
        if (empty($time_1) || empty($time_2) || empty($resultado)) {
            file_put_contents($logFile, "⚠️ Não foi possível extrair dados do resultado\n", FILE_APPEND);
            file_put_contents($logFile, "   Times: $time_1 x $time_2\n", FILE_APPEND);
            file_put_contents($logFile, "   Resultado: $resultado\n", FILE_APPEND);
            return false;
        }
        
        file_put_contents($logFile, "   Times extraídos: $time_1 vs $time_2\n", FILE_APPEND);
        file_put_contents($logFile, "   Tipo de aposta extraído: $tipo_aposta\n", FILE_APPEND);
        file_put_contents($logFile, "   Valor over (RAW): '$valor_over'\n", FILE_APPEND);
        file_put_contents($logFile, "   Valor over (LENGTH): " . strlen($valor_over) . "\n", FILE_APPEND);
        file_put_contents($logFile, "   Resultado extraído: $resultado\n", FILE_APPEND);
        
        // ✅ BUSCAR APOSTA NÃO RESOLVIDA - VALIDAÇÃO RIGOROSA
        // SÓ APLICA RESULTADO SE TIPO + VALOR COMBINAREM EXATAMENTE
        $aposta = null;
        
        file_put_contents($logFile, "🔍 VALIDAÇÃO RIGOROSA: Procurando aposta com tipo=$tipo_aposta e valor=$valor_over\n", FILE_APPEND);
        
        // 🎯 BUSCAR APENAS APOSTAS QUE COMBINAM TIPO + VALOR EXATAMENTE
        $searchQuery = "
            SELECT id, titulo, tipo_odds, valor_over
            FROM bote
            WHERE 
                status_aposta = 'ATIVA'
                AND resultado IS NULL
                AND data_criacao >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                AND (
                    (time_1 LIKE ? AND time_2 LIKE ?)
                    OR (time_1 LIKE ? AND time_2 LIKE ?)
                )
            ORDER BY data_criacao DESC
            LIMIT 50
        ";
        
        $searchStmt = $conexao->prepare($searchQuery);
        
        if ($searchStmt) {
            $time1_search = '%' . $time_1 . '%';
            $time2_search = '%' . $time_2 . '%';
            
            $searchStmt->bind_param('ssss', 
                $time1_search, $time2_search, 
                $time2_search, $time1_search
            );
            $searchStmt->execute();
            $searchResult = $searchStmt->get_result();
            
            // Normalizar valor extraído (remover +, e espaços - MANTER o ponto decimal)
            $valor_normalizado = trim(str_replace(['+', ' '], '', $valor_over));
            
            file_put_contents($logFile, "   Valor normalizado da mensagem: '$valor_normalizado'\n", FILE_APPEND);
            
            // Procurar por matching EXATO de tipo E valor
            while ($row = $searchResult->fetch_assoc()) {
                $titulo_banco = strtoupper($row['titulo']);
                // Normalizar valor do banco (remover +, parênteses, espaços - MANTER o ponto decimal)
                $valor_banco = trim(str_replace(['+', '-', '(', ')', ' '], '', $row['valor_over'] ?: '0'));
                
                file_put_contents($logFile, "   Testando ID " . $row['id'] . ": Titulo='" . $row['titulo'] . "' | Valor_banco='$valor_banco'\n", FILE_APPEND);
                
                // Verificar se TIPO combina
                $tipo_combina = false;
                if ($tipo_aposta === 'GOL' && strpos($titulo_banco, 'GOL') !== false) {
                    $tipo_combina = true;
                } elseif ($tipo_aposta === 'CANTOS' && strpos($titulo_banco, 'CANTO') !== false) {
                    $tipo_combina = true;
                }
                
                // Verificar se VALOR combina - COMPARAÇÃO FLEXÍVEL
                // Tenta: igualdade exata, floatval (0.5 == 0.5), ou com formatação
                $valor_combina = false;
                
                // Tentativa 1: Comparação de strings exata
                if ($valor_banco === $valor_normalizado) {
                    $valor_combina = true;
                    file_put_contents($logFile, "      ✅ Match exato de string: '$valor_banco' === '$valor_normalizado'\n", FILE_APPEND);
                }
                
                // Tentativa 2: Comparação como float (ignora diferenças de formatação)
                if (!$valor_combina && floatval($valor_banco) == floatval($valor_normalizado)) {
                    $valor_combina = true;
                    file_put_contents($logFile, "      ✅ Match como float: " . floatval($valor_banco) . " == " . floatval($valor_normalizado) . "\n", FILE_APPEND);
                }
                
                // Tentativa 3: Remove casa decimal se for .0 (1.0 -> 1)
                if (!$valor_combina) {
                    $valor_banco_sem_zero = str_replace('.0', '', $valor_banco);
                    $valor_msg_sem_zero = str_replace('.0', '', $valor_normalizado);
                    if ($valor_banco_sem_zero === $valor_msg_sem_zero) {
                        $valor_combina = true;
                        file_put_contents($logFile, "      ✅ Match removendo .0: '$valor_banco_sem_zero' === '$valor_msg_sem_zero'\n", FILE_APPEND);
                    }
                }
                
                file_put_contents($logFile, "      Tipo combina? " . ($tipo_combina ? "SIM" : "NÃO") . " | Valor combina? " . ($valor_combina ? "SIM" : "NÃO ($valor_banco vs $valor_normalizado)") . "\n", FILE_APPEND);
                
                // SÓ USAR SE TIPO E VALOR COMBINAREM
                if ($tipo_combina && $valor_combina) {
                    $aposta = $row;
                    file_put_contents($logFile, "✅ MATCH EXATO ENCONTRADO: ID " . $aposta['id'] . " - Tipo: " . $aposta['titulo'] . " - Valor: " . $aposta['valor_over'] . "\n", FILE_APPEND);
                    break;
                }
            }
            
            $searchStmt->close();
        }
        
        if (!$aposta) {
            file_put_contents($logFile, "❌ NENHUM MATCH: Nenhuma aposta encontrada com tipo=$tipo_aposta E valor=$valor_over para $time_1 x $time_2\n", FILE_APPEND);
            file_put_contents($logFile, "📋 RESUMO DA BUSCA:\n", FILE_APPEND);
            file_put_contents($logFile, "   - Times procurados: '$time_1' ou '$time_2'\n", FILE_APPEND);
            file_put_contents($logFile, "   - Tipo procurado: $tipo_aposta\n", FILE_APPEND);
            file_put_contents($logFile, "   - Valor procurado: '$valor_over' (normalizado: '$valor_normalizado')\n", FILE_APPEND);
            file_put_contents($logFile, "⏭️ RESULTADO NÃO APLICADO\n\n", FILE_APPEND);
            return false;
        }
        
        // ✅ ATUALIZAR RESULTADO
        $updateQuery = "
            UPDATE bote
            SET 
                resultado = ?,
                status_aposta = CASE 
                    WHEN ? = 'GREEN' THEN 'GANHA'
                    WHEN ? = 'RED' THEN 'PERDIDA'
                    WHEN ? = 'REEMBOLSO' THEN 'CANCELADA'
                    ELSE 'ATIVA'
                END,
                updated_at = NOW()
            WHERE id = ?
        ";
        
        $updateStmt = $conexao->prepare($updateQuery);
        
        if (!$updateStmt) {
            throw new Exception("Erro ao preparar update: " . $conexao->error);
        }
        
        $updateStmt->bind_param('ssssi', $resultado, $resultado, $resultado, $resultado, $aposta['id']);
        
        if (!$updateStmt->execute()) {
            throw new Exception("Erro ao atualizar: " . $updateStmt->error);
        }
        
        $updateStmt->close();
        
        file_put_contents($logFile, "💾 Resultado atualizado: $resultado para aposta ID " . $aposta['id'] . "\n", FILE_APPEND);
        file_put_contents($logFile, "✅ Resultado processado com sucesso\n\n", FILE_APPEND);
        
        return true;
        
    } catch (Exception $e) {
        file_put_contents($logFile, "❌ Erro ao processar resultado: " . $e->getMessage() . "\n", FILE_APPEND);
        return false;
    }
}

/**
 * ================================================================
 * FUNÇÃO: Extrair dados estruturados da mensagem
 * ================================================================
 */
function extrairDadosMensagem($rawText, $msgTime, $telegramMessageId) {
    $lines = array_map('trim', explode("\n", $rawText));
    $lines = array_filter($lines); // Remove linhas vazias
    
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
    
    // ✅ PARSEAR LINHA POR LINHA
    foreach ($lines as $line) {
        
        // Título (linha com 📊)
        if (strpos($line, '📊') !== false) {
            $titulo = trim(str_replace('📊', '', str_replace('🚨', '', $line)));
            
            // Extrair tipo de aposta do título
            if (strpos($titulo, 'CANTOS') !== false) {
                $tipo_aposta = "CANTOS";
            } elseif (strpos($titulo, 'GOLS') !== false) {
                $tipo_aposta = "GOLS";
            } elseif (strpos($titulo, 'GOL') !== false) {
                $tipo_aposta = "GOL";
            }
            
            // Extrair valor_over do título (ex: +2.5, +1, etc)
            // Remove emojis antes de fazer o regex
            $titulo_limpo = preg_replace('/[\x{1F300}-\x{1F9FF}]/u', '', $titulo);
            if (preg_match('/\+(\d+\.?\d*)/', $titulo_limpo, $matches)) {
                $valor_over = trim($matches[1]); // Manter como STRING para comparação exata
            }
        }
        
        // Escanteios (⛳️)
        if (strpos($line, '⛳') !== false || strpos($line, 'Escanteios:') !== false) {
            if (preg_match('/(\d+)\s*-\s*(\d+)/', $line, $matches)) {
                $escanteios_1 = intval($matches[1]);
                $escanteios_2 = intval($matches[2]);
            }
        }
        
        // Times (X e (H)/(A))
        if (strpos($line, 'x') !== false && (strpos($line, '(H)') !== false || strpos($line, '(A)') !== false)) {
            $parts = explode('x', $line);
            if (count($parts) >= 2) {
                $time_1 = trim(preg_replace('/\([^)]*\)/', '', $parts[0]));
                $time_2 = trim(preg_replace('/\([^)]*\)/', '', $parts[1]));
            }
        }
        
        // Placar
        if (strpos($line, 'Placar:') !== false) {
            if (preg_match('/(\d+)\s*-\s*(\d+)/', $line, $matches)) {
                $placar_1 = intval($matches[1]);
                $placar_2 = intval($matches[2]);
            }
        }
        
        // Odds - Gols
        if (strpos($line, 'Gols over') !== false) {
            if (preg_match('/Gols over\s*[\+\-]?[\d\.]*\s*:\s*([\d\.]+)/i', $line, $matches)) {
                $odds = floatval($matches[1]);
                $tipo_odds = "Gols Odds";
            }
        }
        
        // Odds - Escanteios
        if (strpos($line, 'Escanteios over') !== false) {
            if (preg_match('/Escanteios over\s*[\+\-]?[\d\.]*\s*:\s*([\d\.]+)/i', $line, $matches)) {
                $odds = floatval($matches[1]);
                $tipo_odds = "Escanteios Odds";
            }
        }
    }
    
    return [
        'telegram_message_id' => $telegramMessageId,
        'mensagem_completa' => $rawText,
        'titulo' => $titulo,
        'tipo_aposta' => $tipo_aposta ?: "DESCONHECIDO",
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

/**
 * ================================================================
 * FUNÇÃO: Salvar mensagem no banco de dados
 * ================================================================
 */
function salvarNosBancoDados($dadosMensagem) {
    global $conexao, $logFile;
    
    try {
        file_put_contents($logFile, "📝 Iniciando salvamento nos dados:\n", FILE_APPEND);
        file_put_contents($logFile, "   - Banco: " . DB_NAME . " | Host: " . DB_HOST . "\n", FILE_APPEND);
        file_put_contents($logFile, "   - Conexão: " . ($conexao ? "✅ OK" : "❌ ERRO") . "\n", FILE_APPEND);
        file_put_contents($logFile, "   - Título: " . substr($dadosMensagem['titulo'], 0, 50) . "\n", FILE_APPEND);
        file_put_contents($logFile, "   - Time 1: " . $dadosMensagem['time_1'] . "\n", FILE_APPEND);
        file_put_contents($logFile, "   - Time 2: " . $dadosMensagem['time_2'] . "\n", FILE_APPEND);
        file_put_contents($logFile, "   - Tipo: " . $dadosMensagem['tipo_aposta'] . "\n", FILE_APPEND);
        
        // ✅ INSERT SEM DUPLICATE KEY (mais simples)
        $query = "
            INSERT INTO bote (
                telegram_message_id,
                mensagem_completa,
                titulo,
                tipo_aposta,
                time_1,
                time_2,
                placar_1,
                placar_2,
                escanteios_1,
                escanteios_2,
                valor_over,
                odds,
                tipo_odds,
                hora_mensagem,
                status_aposta,
                resultado
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )
        ";
        
        $stmt = $conexao->prepare($query);
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conexao->error);
        }
        
        // ✅ BIND DOS PARÂMETROS (16 parâmetros)
        $resultado = NULL; // Inicialmente NULL
        
        $bindResult = $stmt->bind_param(
            "isssssiiiiiddsss",
            $dadosMensagem['telegram_message_id'],
            $dadosMensagem['mensagem_completa'],
            $dadosMensagem['titulo'],
            $dadosMensagem['tipo_aposta'],
            $dadosMensagem['time_1'],
            $dadosMensagem['time_2'],
            $dadosMensagem['placar_1'],
            $dadosMensagem['placar_2'],
            $dadosMensagem['escanteios_1'],
            $dadosMensagem['escanteios_2'],
            $dadosMensagem['valor_over'],
            $dadosMensagem['odds'],
            $dadosMensagem['tipo_odds'],
            $dadosMensagem['hora_mensagem'],
            $dadosMensagem['status_aposta'],
            $resultado
        );
        
        if (!$bindResult) {
            throw new Exception("Bind failed: " . $stmt->error);
        }
        
        // ✅ EXECUTAR INSERT
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        $insertedId = $conexao->insert_id;
        file_put_contents($logFile, "✅ Registrado ID: $insertedId (linhas afetadas: " . $stmt->affected_rows . ")\n", FILE_APPEND);
        file_put_contents($logFile, "✅ Banco: " . DB_NAME . " | Host: " . DB_HOST . "\n", FILE_APPEND);
        file_put_contents($logFile, "✅ Query executada com sucesso\n", FILE_APPEND);
        
        $stmt->close();
        
        return true;
        
    } catch (Exception $e) {
        file_put_contents($logFile, "❌ Erro ao salvar: " . $e->getMessage() . "\n\n", FILE_APPEND);
        throw $e;
    }
}

?>
