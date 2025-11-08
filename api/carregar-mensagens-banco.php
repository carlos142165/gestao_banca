<?php
/**
 * ================================================================
 * API PARA CARREGAR MENSAGENS DO BANCO DE DADOS
 * ================================================================
 * 
 * Esta API carrega as mensagens que foram salvas na tabela 'bote'
 * em vez de buscar direto do Telegram.
 * 
 * FLUXO:
 * Telegram (envia mensagem)
 *   ↓
 * Webhook/API (telegram-webhook.php)
 *   ↓
 * Banco de dados (tabela: bote)
 *   ↓
 * Esta API (carregar-mensagens-banco.php)
 *   ↓
 * Frontend (bot_aovivo.php - BLOCO 1)
 * 
 * ================================================================
 */

// ✅ SEM SESSION_START - PERMITE ACESSO PÚBLICO
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
error_reporting(E_ALL);
ini_set('display_errors', 0);

// ✅ CONFIGURAR FUSO HORÁRIO PARA BRASIL (São Paulo)
date_default_timezone_set('America/Sao_Paulo');

// ✅ INCLUIR CONFIGURAÇÃO DO BANCO
if (!file_exists('../config.php')) {
    http_response_code(500);
    die(json_encode(['success' => false, 'error' => 'config.php não encontrado']));
}
require_once '../config.php';

// Verificar se $conexao foi criada
if (!isset($conexao) || !$conexao) {
    http_response_code(500);
    die(json_encode(['success' => false, 'error' => 'Conexão com banco não estabelecida']));
}

$action = isset($_GET['action']) ? $_GET['action'] : 'get-messages';

try {
    switch ($action) {
        case 'get-messages':
            getMessagesFromDatabase();
            break;
        
        case 'poll':
            pollNewMessages();
            break;
        
        case 'get-by-date':
            getMessagesByDate();
            break;
        
        default:
            throw new Exception('Ação inválida: ' . $action);
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Erro: ' . $e->getMessage(),
        'error' => $e->getMessage()
    ]);
}

/**
 * ================================================================
 * FUNÇÃO: Carregar mensagens de HOJE do banco de dados
 * ================================================================
 * 
 * GET: /api/carregar-mensagens-banco.php?action=get-messages
 * 
 * Retorna: {
 *   success: true,
 *   messages: [...],
 *   total: 10,
 *   last_update: 12345
 * }
 */
function getMessagesFromDatabase() {
    global $conexao;
    
    try {
        // ✅ BUSCAR APENAS MENSAGENS DE HOJE
        $query = "
            SELECT 
                id,
                telegram_message_id,
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
                resultado,
                mensagem_completa,
                data_criacao,
                UNIX_TIMESTAMP(data_criacao) as timestamp
            FROM bote
            WHERE DATE(data_criacao) = CURDATE()
            ORDER BY data_criacao DESC
            LIMIT 100
        ";
        
        $stmt = $conexao->prepare($query);
        
        if (!$stmt) {
            throw new Exception("Erro ao preparar statement: " . $conexao->error);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $messages = [];
        $lastId = 0;
        
        while ($row = $result->fetch_assoc()) {
            // ✅ EXTRAIR VALOR OVER/UNDER DO TÍTULO OU MENSAGEM
            $overUnderMatch = null;
            if (preg_match('/([+\-]?\d+\.?\d*)\s*(?:GOLS?|⚽|GOL|CANTOS?)/i', $row['titulo'] ?: $row['mensagem_completa'], $matches)) {
                $overUnderMatch = $matches[1];
            }
            
            $messages[] = [
                'id' => intval($row['telegram_message_id'] ?: $row['id']),
                'text' => $row['mensagem_completa'],
                'timestamp' => intval($row['timestamp']),
                'time' => $row['hora_mensagem'] ?: date('H:i:s', intval($row['timestamp'])),
                'date' => date('d/m/Y', intval($row['timestamp'])),
                'update_id' => intval($row['id']),
                'title' => $row['titulo'],
                'type' => $row['tipo_aposta'],
                'status' => $row['status_aposta'],
                'resultado' => $row['resultado'],
                'time_1' => $row['time_1'],
                'time_2' => $row['time_2'],
                'valor_over' => $row['valor_over'],
                'over_under_value' => $overUnderMatch
            ];
            
            $lastId = max($lastId, intval($row['id']));
        }
        
        $stmt->close();
        
        error_log("✅ Carregadas " . count($messages) . " mensagens do banco");
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'messages' => $messages,
            'total' => count($messages),
            'last_update' => $lastId,
            'source' => 'database'
        ]);
        
    } catch (Exception $e) {
        throw new Exception("Erro ao buscar mensagens: " . $e->getMessage());
    }
}

/**
 * ================================================================
 * FUNÇÃO: Polling de NOVAS/ATUALIZADAS mensagens no banco
 * ================================================================
 * 
 * GET: /api/carregar-mensagens-banco.php?action=poll&last_check=2025-02-01%2012:00:00
 * 
 * Retorna mensagens CRIADAS ou MODIFICADAS depois de last_check
 */
function pollNewMessages() {
    global $conexao;
    
    $lastCheck = isset($_GET['last_check']) ? $_GET['last_check'] : null;
    $lastUpdateId = isset($_GET['last_update']) ? intval($_GET['last_update']) : 0;
    
    try {
        // ✅ POLLING PARA HOJE APENAS
        // Buscar mensagens de hoje com ID maior que o último visto
        // OU que foram atualizadas (resultado mudou)
        $query = "
            SELECT 
                id,
                telegram_message_id,
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
                resultado,
                mensagem_completa,
                data_criacao,
                UNIX_TIMESTAMP(data_criacao) as timestamp
            FROM bote
            WHERE DATE(data_criacao) = CURDATE()
            ORDER BY data_criacao DESC
            LIMIT 100
        ";
        
        $stmt = $conexao->prepare($query);
        if (!$stmt) {
            throw new Exception("Erro ao preparar statement: " . $conexao->error);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $newMessages = [];
        $maxId = $lastUpdateId;
        $maxUpdatedAt = date('Y-m-d H:i:s');
        
        while ($row = $result->fetch_assoc()) {
            // ✅ EXTRAIR VALOR OVER/UNDER DO TÍTULO OU MENSAGEM
            $overUnderMatch = null;
            if (preg_match('/([+\-]?\d+\.?\d*)\s*(?:GOLS?|⚽|GOL|CANTOS?)/i', $row['titulo'] ?: $row['mensagem_completa'], $matches)) {
                $overUnderMatch = $matches[1];
            }
            
            $newMessages[] = [
                'id' => intval($row['telegram_message_id'] ?: $row['id']),
                'text' => $row['mensagem_completa'],
                'timestamp' => intval($row['timestamp']),
                'time' => $row['hora_mensagem'] ?: date('H:i:s', intval($row['timestamp'])),
                'date' => date('d/m/Y', intval($row['timestamp'])),
                'update_id' => intval($row['id']),
                'title' => $row['titulo'],
                'type' => $row['tipo_aposta'],
                'status' => $row['status_aposta'],
                'resultado' => $row['resultado'],
                'time_1' => $row['time_1'],
                'time_2' => $row['time_2'],
                'valor_over' => $row['valor_over'],
                'over_under_value' => $overUnderMatch,
                'updated_at' => null
            ];
            
            $maxId = max($maxId, intval($row['id']));
        }
        
        $stmt->close();
        
        if (count($newMessages) > 0) {
            error_log("🔔 Polling de HOJE: Encontradas " . count($newMessages) . " mensagens");
        }
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'messages' => $newMessages,
            'last_update' => $maxId,
            'last_check' => $maxUpdatedAt,
            'polling_mode' => 'today-only',
            'source' => 'database'
        ]);
        
    } catch (Exception $e) {
        throw new Exception("Erro ao fazer polling: " . $e->getMessage());
    }
}

/**
 * ================================================================
 * FUNÇÃO: Buscar mensagens por data específica
 * ================================================================
 * 
 * GET: /api/carregar-mensagens-banco.php?action=get-by-date&date=2025-11-02
 */
function getMessagesByDate() {
    global $conexao;
    
    $date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
    
    // ✅ VALIDAR FORMATO DA DATA
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        throw new Exception("Formato de data inválido. Use: YYYY-MM-DD");
    }
    
    try {
        $query = "
            SELECT 
                id,
                telegram_message_id,
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
                resultado,
                mensagem_completa,
                data_criacao,
                UNIX_TIMESTAMP(data_criacao) as timestamp
            FROM bote
            WHERE DATE(data_criacao) = ?
            ORDER BY data_criacao ASC
        ";
        
        $stmt = $conexao->prepare($query);
        
        if (!$stmt) {
            throw new Exception("Erro ao preparar statement: " . $conexao->error);
        }
        
        $stmt->bind_param("s", $date);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $messages = [];
        
        while ($row = $result->fetch_assoc()) {
            // ✅ EXTRAIR VALOR OVER/UNDER DO TÍTULO OU MENSAGEM
            $overUnderMatch = null;
            if (preg_match('/([+\-]?\d+\.?\d*)\s*(?:GOLS?|⚽|GOL|CANTOS?)/i', $row['titulo'] ?: $row['mensagem_completa'], $matches)) {
                $overUnderMatch = $matches[1];
            }
            
            $messages[] = [
                'id' => intval($row['telegram_message_id'] ?: $row['id']),
                'text' => $row['mensagem_completa'],
                'timestamp' => intval($row['timestamp']),
                'time' => $row['hora_mensagem'] ?: date('H:i:s', intval($row['timestamp'])),
                'date' => date('d/m/Y', intval($row['timestamp'])),
                'update_id' => intval($row['id']),
                'title' => $row['titulo'],
                'type' => $row['tipo_aposta'],
                'status' => $row['status_aposta'],
                'resultado' => $row['resultado'],
                'time_1' => $row['time_1'],
                'time_2' => $row['time_2'],
                'valor_over' => $row['valor_over'],
                'over_under_value' => $overUnderMatch
            ];
        }
        
        $stmt->close();
        
        error_log("✅ Carregadas " . count($messages) . " mensagens do banco para data: " . $date);
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'messages' => $messages,
            'date' => $date,
            'total' => count($messages),
            'source' => 'database'
        ]);
        
    } catch (Exception $e) {
        throw new Exception("Erro ao buscar mensagens por data: " . $e->getMessage());
    }
}

?>
