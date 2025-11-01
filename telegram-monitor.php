<?php
/**
 * Monitor de Mensagens Telegram em Tempo Real
 * Busca mensagens do canal e retorna as pendentes/com resultado
 */

session_start();

header('Content-Type: application/json');

// Credenciais do Telegram
const TELEGRAM_TOKEN = '8549099161:AAFKDDdeaFpwz9I4CkaqIwCIFOCleQZMEr8';
const TELEGRAM_CHANNEL_ID = '-1002047004959';
const TELEGRAM_API_URL = 'https://api.telegram.org/bot' . TELEGRAM_TOKEN;

// Banco de dados para armazenar mensagens
const DB_FILE = __DIR__ . '/dados_telegram.json';

/**
 * Obtém mensagens do Telegram
 */
function obterMensagensDoCanal($limite = 100) {
    $url = TELEGRAM_API_URL . '/getUpdates?limit=' . $limite . '&allowed_updates=message';
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true);
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
        'ODD:',
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
        'texto_completo' => $texto,
        'resultado' => 'PENDENTE',
    ];
    
    // Extrai o jogo (⚽️ XXXX x YYYY)
    if (preg_match('/⚽️\s+(.+?)\s+\((?:ao vivo|encerrado)\)/i', $texto, $matches)) {
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
    
    // Identifica o tipo (OVER, UNDER, etc)
    if (preg_match('/(OVER|UNDER|GREEN|RED|REEMBOLSO)/i', $texto, $matches)) {
        $info['tipo'] = strtoupper($matches[1]);
    }
    
    // Busca resultado
    if (preg_match('/(GREEN|RED|REEMBOLSO)/i', $texto, $matches)) {
        $info['resultado'] = strtoupper($matches[1]);
    }
    
    return $info;
}

/**
 * Carrega dados salvos do JSON
 */
function carregarDadosSalvos() {
    if (file_exists(DB_FILE)) {
        $conteudo = file_get_contents(DB_FILE);
        $dados = json_decode($conteudo, true);
        return is_array($dados) ? $dados : [];
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
 * Adiciona uma nova mensagem
 */
function adicionarMensagem($info, $messageId) {
    $dados = carregarDadosSalvos();
    
    $dados[$messageId] = [
        'id' => $messageId,
        'data_chegada' => date('Y-m-d H:i:s'),
        'jogo' => $info['jogo'],
        'escanteis' => $info['escanteis'],
        'stake' => $info['stake'],
        'odd' => $info['odd'],
        'tipo' => $info['tipo'],
        'resultado' => 'PENDENTE',
        'data_resultado' => null,
    ];
    
    salvarDados($dados);
    return $dados[$messageId];
}

/**
 * Atualiza resultado de uma mensagem
 */
function atualizarResultado($messageId, $resultado) {
    $dados = carregarDadosSalvos();
    
    if (isset($dados[$messageId])) {
        $dados[$messageId]['resultado'] = strtoupper($resultado);
        $dados[$messageId]['data_resultado'] = date('Y-m-d H:i:s');
        salvarDados($dados);
        return true;
    }
    
    return false;
}

/**
 * Retorna mensagens formatadas
 */
function obterMensagensFormatadas() {
    $dados = carregarDadosSalvos();
    
    // Ordenar por data de chegada (mais recentes primeiro)
    usort($dados, function($a, $b) {
        return strtotime($b['data_chegada']) - strtotime($a['data_chegada']);
    });
    
    // Retorna apenas as 10 mais recentes
    return array_slice($dados, 0, 10);
}

/**
 * Processa ação (AJAX)
 */
function processarAcao() {
    $acao = $_POST['acao'] ?? '';
    
    if ($acao === 'obter_mensagens') {
        echo json_encode([
            'sucesso' => true,
            'mensagens' => obterMensagensFormatadas(),
        ]);
        exit;
    }
    
    if ($acao === 'sincronizar') {
        $resposta = obterMensagensDoCanal();
        
        if ($resposta && isset($resposta['result'])) {
            $novas = 0;
            
            foreach ($resposta['result'] as $update) {
                if (isset($update['message'])) {
                    $msg = $update['message'];
                    $texto = $msg['text'] ?? '';
                    
                    // Verifica se é oportunidade válida
                    if (ehOportunidadeValida($texto)) {
                        $info = extrairInfosMensagem($texto);
                        
                        // Verifica se já existe
                        $dados = carregarDadosSalvos();
                        if (!isset($dados[$msg['message_id']])) {
                            adicionarMensagem($info, $msg['message_id']);
                            $novas++;
                        } else {
                            // Atualiza se encontrou resultado
                            if (preg_match('/(GREEN|RED|REEMBOLSO)/i', $texto, $matches)) {
                                atualizarResultado($msg['message_id'], $matches[1]);
                            }
                        }
                    }
                }
            }
            
            echo json_encode([
                'sucesso' => true,
                'novas_mensagens' => $novas,
                'mensagens' => obterMensagensFormatadas(),
            ]);
        } else {
            echo json_encode([
                'sucesso' => false,
                'erro' => 'Falha ao conectar com Telegram',
            ]);
        }
        exit;
    }
    
    if ($acao === 'limpar_tudo') {
        salvarDados([]);
        echo json_encode([
            'sucesso' => true,
            'mensagem' => 'Dados limpos',
        ]);
        exit;
    }
}

// Processa AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    processarAcao();
}

// API padrão: retorna mensagens formatadas
echo json_encode([
    'sucesso' => true,
    'mensagens' => obterMensagensFormatadas(),
    'total' => count(obterMensagensFormatadas()),
]);
