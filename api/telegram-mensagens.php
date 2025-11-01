<?php
// ✅ SEM SESSION_START - PERMITE ACESSO PÚBLICO
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Configurar timeout maior
set_time_limit(40);

// ✅ CONFIGURAR FUSO HORÁRIO PARA BRASIL (São Paulo)
date_default_timezone_set('America/Sao_Paulo');

require_once '../telegram-config.php';

// Definir pasta de cache
$cacheDir = __DIR__ . '/../cache';
if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0755, true);
}

$action = isset($_GET['action']) ? $_GET['action'] : '';

try {
    if ($action === 'get-messages') {
        getMessagesFromToday();
    } elseif ($action === 'poll') {
        pollNewMessages();
    } else {
        echo json_encode(['success' => false, 'message' => 'Ação inválida', 'action' => $action]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Exceção: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}

function getMessagesFromToday() {
    global $cacheDir;
    
    $today = date('Y-m-d');
    $cacheFile = $cacheDir . '/telegram_' . $today . '.json';
    $lockFile = $cacheDir . '/telegram.lock';
    
    // ✅ USAR CACHE se existir e for recente (menos de 10 segundos)
    if (file_exists($cacheFile)) {
        $fileAge = time() - filemtime($cacheFile);
        if ($fileAge < 10) {
            $cached = file_get_contents($cacheFile);
            echo $cached;
            return;
        }
    }
    
    // ✅ LOCK para evitar múltiplas requisições simultâneas
    $lockHandle = fopen($lockFile, 'w');
    if (!flock($lockHandle, LOCK_EX | LOCK_NB)) {
        // Se não conseguir lock, SEMPRE retornar cache (nunca erro)
        if (file_exists($cacheFile)) {
            $cached = json_decode(file_get_contents($cacheFile), true);
            if ($cached && $cached['success']) {
                echo file_get_contents($cacheFile);
            } else {
                echo json_encode(['success' => true, 'messages' => [], 'total' => 0]);
            }
        } else {
            echo json_encode(['success' => true, 'messages' => [], 'total' => 0]);
        }
        fclose($lockHandle);
        return;
    }
    
    // ✅ FAZER REQUISIÇÃO AO TELEGRAM
    $url = TELEGRAM_API_URL . '/getUpdates?limit=100&timeout=30';
    
    error_log("🔍 Conectando em: " . $url);
    
    $context = stream_context_create([
        'http' => [
            'timeout' => 35,
            'ignore_errors' => true
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    
    if ($response === false) {
        error_log("❌ Falha ao conectar em: $url");
        flock($lockHandle, LOCK_UN);
        fclose($lockHandle);
        
        if (file_exists($cacheFile)) {
            echo file_get_contents($cacheFile);
        } else {
            echo json_encode(['success' => true, 'messages' => [], 'total' => 0]);
        }
        return;
    }
    
    $data = json_decode($response, true);
    
    if ($data === null) {
        error_log("❌ JSON inválido: " . substr($response, 0, 200));
        flock($lockHandle, LOCK_UN);
        fclose($lockHandle);
        
        if (file_exists($cacheFile)) {
            echo file_get_contents($cacheFile);
        } else {
            echo json_encode(['success' => true, 'messages' => [], 'total' => 0]);
        }
        return;
    }
    
    if (!isset($data['ok']) || !$data['ok']) {
        error_log("❌ Telegram retornou erro: " . json_encode($data));
        flock($lockHandle, LOCK_UN);
        fclose($lockHandle);
        
        if (file_exists($cacheFile)) {
            echo file_get_contents($cacheFile);
        } else {
            echo json_encode(['success' => true, 'messages' => [], 'total' => 0]);
        }
        return;
    }
    
    $messages = [];
    $channelId = intval(TELEGRAM_CHANNEL_ID);
    
    error_log("✅ Conectado! Procurando mensagens do canal: $channelId para: $today");
    
    if (isset($data['result']) && is_array($data['result'])) {
        error_log("📊 Total de updates: " . count($data['result']));
        
        foreach ($data['result'] as $update) {
            if (isset($update['channel_post'])) {
                $message = $update['channel_post'];
                $messageChannelId = intval($message['chat']['id']);
                
                if ($messageChannelId == $channelId) {
                    $messageDate = date('Y-m-d', $message['date']);
                    
                    if ($messageDate === $today) {
                        $messageText = '';
                        
                        if (isset($message['text']) && !empty($message['text'])) {
                            $messageText = $message['text'];
                        } elseif (isset($message['caption']) && !empty($message['caption'])) {
                            $messageText = $message['caption'];
                        }
                        
                        if (!empty($messageText)) {
                            $messages[] = [
                                'id' => $message['message_id'],
                                'text' => $messageText,
                                'timestamp' => $message['date'],
                                'time' => date('H:i:s', $message['date']),
                                'date' => date('d/m/Y', $message['date']),
                                'update_id' => $update['update_id']
                            ];
                        }
                    }
                }
            }
        }
    }
    
    error_log("✅ Encontradas " . count($messages) . " mensagens");
    
    usort($messages, function($a, $b) {
        return $a['timestamp'] - $b['timestamp'];
    });
    
    $result = json_encode([
        'success' => true,
        'messages' => $messages,
        'total' => count($messages)
    ]);
    
    file_put_contents($cacheFile, $result);
    
    flock($lockHandle, LOCK_UN);
    fclose($lockHandle);
    
    echo $result;
}

function pollNewMessages() {
    global $cacheDir;
    
    $lastUpdateId = isset($_GET['last_update']) ? intval($_GET['last_update']) : 0;
    $today = date('Y-m-d');
    $cacheFile = $cacheDir . '/telegram_' . $today . '.json';
    $lockFile = $cacheDir . '/telegram.lock';
    $offsetFile = $cacheDir . '/telegram_offset.txt';
    
    $savedOffset = 0;
    if (file_exists($offsetFile)) {
        $savedOffset = intval(file_get_contents($offsetFile));
    }
    
    $lockHandle = fopen($lockFile, 'w');
    if (!flock($lockHandle, LOCK_EX | LOCK_NB)) {
        if (file_exists($cacheFile)) {
            $cached = json_decode(file_get_contents($cacheFile), true);
            if ($cached && $cached['success']) {
                $newMessages = array_filter($cached['messages'], function($msg) use ($lastUpdateId) {
                    return $msg['update_id'] > $lastUpdateId;
                });
                
                usort($newMessages, function($a, $b) {
                    return $a['timestamp'] - $b['timestamp'];
                });
                
                $maxUpdateId = $lastUpdateId;
                foreach ($newMessages as $msg) {
                    $maxUpdateId = max($maxUpdateId, $msg['update_id']);
                }
                
                echo json_encode([
                    'success' => true,
                    'messages' => array_values($newMessages),
                    'last_update' => $maxUpdateId
                ]);
            }
        } else {
            echo json_encode(['success' => true, 'messages' => [], 'last_update' => $lastUpdateId]);
        }
        fclose($lockHandle);
        return;
    }
    
    $url = TELEGRAM_API_URL . '/getUpdates?offset=' . ($savedOffset + 1) . '&limit=100&timeout=30';
    
    error_log("🔍 Fazendo polling offset: " . ($savedOffset + 1));
    
    $context = stream_context_create([
        'http' => ['timeout' => 35, 'ignore_errors' => true]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    
    if ($response === false) {
        error_log("❌ Falha no polling");
        flock($lockHandle, LOCK_UN);
        fclose($lockHandle);
        
        echo json_encode(['success' => true, 'messages' => [], 'last_update' => $lastUpdateId]);
        return;
    }
    
    $data = json_decode($response, true);
    
    if (!isset($data['ok']) || !$data['ok']) {
        error_log("❌ Telegram retornou erro no polling: " . json_encode($data));
        flock($lockHandle, LOCK_UN);
        fclose($lockHandle);
        
        echo json_encode(['success' => true, 'messages' => [], 'last_update' => $lastUpdateId]);
        return;
    }
    
    $newMessages = [];
    $maxUpdateId = $savedOffset;
    $channelId = intval(TELEGRAM_CHANNEL_ID);
    
    if (isset($data['result']) && is_array($data['result'])) {
        error_log("📊 Polling retornou: " . count($data['result']) . " updates");
        
        foreach ($data['result'] as $update) {
            if (isset($update['channel_post'])) {
                $message = $update['channel_post'];
                $messageChannelId = intval($message['chat']['id']);
                
                if ($messageChannelId == $channelId) {
                    $messageDate = date('Y-m-d', $message['date']);
                    
                    if ($messageDate === $today) {
                        $messageText = '';
                        
                        if (isset($message['text']) && !empty($message['text'])) {
                            $messageText = $message['text'];
                        } elseif (isset($message['caption']) && !empty($message['caption'])) {
                            $messageText = $message['caption'];
                        }
                        
                        if (!empty($messageText)) {
                            $newMessages[] = [
                                'id' => $message['message_id'],
                                'text' => $messageText,
                                'timestamp' => $message['date'],
                                'time' => date('H:i:s', $message['date']),
                                'date' => date('d/m/Y', $message['date']),
                                'update_id' => $update['update_id']
                            ];
                            
                            $maxUpdateId = max($maxUpdateId, $update['update_id']);
                        }
                    }
                }
            }
        }
        
        file_put_contents($offsetFile, $maxUpdateId);
    }
    
    error_log("✅ Poll retornou " . count($newMessages) . " mensagens novas");
    
    if (file_exists($cacheFile)) {
        $cached = json_decode(file_get_contents($cacheFile), true);
        if ($cached && $cached['success'] && !empty($newMessages)) {
            $allMessages = array_merge($cached['messages'], $newMessages);
            
            $seen = [];
            $unique = [];
            foreach ($allMessages as $msg) {
                if (!isset($seen[$msg['id']])) {
                    $seen[$msg['id']] = true;
                    $unique[] = $msg;
                }
            }
            
            usort($unique, function($a, $b) {
                return $a['timestamp'] - $b['timestamp'];
            });
            
            $cached['messages'] = $unique;
            $cached['total'] = count($unique);
            file_put_contents($cacheFile, json_encode($cached));
        }
    }
    
    flock($lockHandle, LOCK_UN);
    fclose($lockHandle);
    
    $filtered = array_filter($newMessages, function($msg) use ($lastUpdateId) {
        return $msg['update_id'] > $lastUpdateId;
    });
    
    usort($filtered, function($a, $b) {
        return $a['timestamp'] - $b['timestamp'];
    });
    
    $maxNewId = $lastUpdateId;
    foreach ($filtered as $msg) {
        $maxNewId = max($maxNewId, $msg['update_id']);
    }
    
    echo json_encode([
        'success' => true,
        'messages' => array_values($filtered),
        'last_update' => $maxNewId
    ]);
}
