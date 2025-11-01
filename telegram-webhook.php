<?php
/**
 * Webhook do Telegram para receber mensagens em tempo real
 * Processa mensagens de forma assíncrona sem bloquear
 */

session_start();

const TELEGRAM_TOKEN = '8549099161:AAFKDDdeaFpwz9I4CkaqIwCIFOCleQZMEr8';
const TELEGRAM_CHANNEL_ID = '-1002047004959';
const TELEGRAM_API_URL = 'https://api.telegram.org/bot' . TELEGRAM_TOKEN;
const DB_FILE = __DIR__ . '/dados_telegram.json';

/**
 * Obtém o update do Telegram
 */
function obterUpdate() {
    $update = json_decode(file_get_contents('php://input'), true);
    return $update;
}

/**
 * Verifica se a mensagem é uma oportunidade válida
 */
function ehOportunidadeValida($texto) {
    $indicadores = [
        'Oportunidade! 🚨',
        '📊 🚨',
        '⚽️',
        'Stake:',
        'Escanteios:',
    ];
    
    $encontrados = 0;
    foreach ($indicadores as $ind) {
        if (stripos($texto, $ind) !== false) {
            $encontrados++;
        }
    }
    
    return $encontrados >= 3;
}

/**
 * Extrai informações da mensagem
 */
function extrairInfosMensagem($texto) {
    $info = [
        'jogo' => '',
        'escanteis' => '',
        'stake' => '',
        'odd' => '',
        'tipo' => '',
        'resultado' => 'PENDENTE',
    ];
    
    // Extrai o jogo
    if (preg_match('/⚽️\s+(.+?)\s+\((?:ao vivo|encerrado)/i', $texto, $matches)) {
        $info['jogo'] = trim($matches[1]);
    }
    
    // Extrai escanteios
    if (preg_match('/⛳️\s+Escanteios:\s+(\d+\s*-\s*\d+)/i', $texto, $matches)) {
        $info['escanteis'] = trim($matches[1]);
    }
    
    // Extrai stake
    if (preg_match('/Stake:\s+([\d.]+%?)/i', $texto, $matches)) {
        $info['stake'] = trim($matches[1]);
    }
    
    // Extrai ODD
    if (preg_match('/ODD:\s+([\d.]+)/i', $texto, $matches)) {
        $info['odd'] = trim($matches[1]);
    }
    
    // Identifica o tipo
    if (preg_match('/(OVER|UNDER)/i', $texto, $matches)) {
        $info['tipo'] = strtoupper($matches[1]);
    }
    
    // Busca resultado
    if (preg_match('/(GREEN|RED|REEMBOLSO)/i', $texto, $matches)) {
        $info['resultado'] = strtoupper($matches[1]);
    }
    
    return $info;
}

/**
 * Carrega dados do JSON
 */
function carregarDados() {
    if (file_exists(DB_FILE)) {
        $conteudo = file_get_contents(DB_FILE);
        return json_decode($conteudo, true) ?: [];
    }
    return [];
}

/**
 * Salva dados no JSON
 */
function salvarDados($dados) {
    file_put_contents(DB_FILE, json_encode($dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
}

/**
 * Processa o webhook
 */
function processarWebhook() {
    $update = obterUpdate();
    
    if (!$update || !isset($update['message'])) {
        http_response_code(200);
        echo json_encode(['ok' => true]);
        return;
    }
    
    $msg = $update['message'];
    $texto = $msg['text'] ?? '';
    $msgId = $msg['message_id'] ?? null;
    
    // Verificar se é oportunidade válida
    if (!ehOportunidadeValida($texto)) {
        http_response_code(200);
        echo json_encode(['ok' => true, 'motivo' => 'Mensagem não é oportunidade válida']);
        return;
    }
    
    // Extrair informações
    $info = extrairInfosMensagem($texto);
    
    // Carregar dados existentes
    $dados = carregarDados();
    
    if (!isset($dados[$msgId])) {
        // NOVA MENSAGEM
        $dados[$msgId] = [
            'id' => $msgId,
            'data_chegada' => date('Y-m-d H:i:s'),
            'jogo' => $info['jogo'],
            'escanteis' => $info['escanteis'],
            'stake' => $info['stake'],
            'odd' => $info['odd'],
            'tipo' => $info['tipo'],
            'resultado' => 'PENDENTE',
            'data_resultado' => null,
            'texto_original' => substr($texto, 0, 500),
        ];
    } else {
        // ATUALIZAR COM RESULTADO
        if ($info['resultado'] !== 'PENDENTE') {
            $dados[$msgId]['resultado'] = $info['resultado'];
            $dados[$msgId]['data_resultado'] = date('Y-m-d H:i:s');
        }
    }
    
    // Salvar dados
    salvarDados($dados);
    
    // Log
    error_log('[' . date('Y-m-d H:i:s') . '] Webhook processado: ' . $msgId . ' - ' . $info['resultado']);
    
    http_response_code(200);
    echo json_encode(['ok' => true, 'processado' => true]);
}

// Processar o webhook
processarWebhook();
