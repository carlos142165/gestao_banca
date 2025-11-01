<?php
/**
 * Script para simular envios de mensagens do Telegram
 * Útil para testes sem depender do canal real
 * 
 * Acesse: http://seu-site/simular-telegram.php?acao=oportunidade
 */

session_start();

const DB_FILE = __DIR__ . '/dados_telegram.json';

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
 * Extrai informações de uma mensagem
 */
function extrairInfos($texto) {
    $info = [
        'jogo' => '',
        'escanteis' => '',
        'stake' => '',
        'odd' => '',
        'tipo' => '',
        'resultado' => 'PENDENTE',
    ];
    
    if (preg_match('/⚽️\s+(.+?)\s+\((?:ao vivo|encerrado)/i', $texto, $matches)) {
        $info['jogo'] = trim($matches[1]);
    }
    
    if (preg_match('/⛳️\s+Escanteios:\s+(\d+\s*-\s*\d+)/i', $texto, $matches)) {
        $info['escanteis'] = trim($matches[1]);
    }
    
    if (preg_match('/Stake:\s+([\d.]+%?)/i', $texto, $matches)) {
        $info['stake'] = trim($matches[1]);
    }
    
    if (preg_match('/ODD:\s+([\d.]+)/i', $texto, $matches)) {
        $info['odd'] = trim($matches[1]);
    }
    
    if (preg_match('/(OVER|UNDER)/i', $texto, $matches)) {
        $info['tipo'] = strtoupper($matches[1]);
    }
    
    if (preg_match('/(GREEN|RED|REEMBOLSO)/i', $texto, $matches)) {
        $info['resultado'] = strtoupper($matches[1]);
    }
    
    return $info;
}

/**
 * Simula uma oportunidade
 */
function simularOportunidade() {
    $dados = carregarDados();
    
    $id = time();
    $texto = `Oportunidade! 🚨
📊 🚨 OVER ( +1⛳️ ASIÁTICO ) Underdog
⚽️ Junior (H) x Independiente Santa Fe (A) (ao vivo)
⏰ Tempo: 83'
⛳️ Escanteios: 6 - 6
Stake: 1%
ODD: 1.5`;

    $info = extrairInfos($texto);
    
    $dados[$id] = [
        'id' => $id,
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
    
    salvarDados($dados);
    
    return [
        'sucesso' => true,
        'acao' => 'oportunidade',
        'id' => $id,
        'mensagem' => $dados[$id],
    ];
}

/**
 * Simula um resultado GREEN
 */
function simularGreen() {
    $dados = carregarDados();
    
    if (empty($dados)) {
        return [
            'sucesso' => false,
            'erro' => 'Nenhuma oportunidade para atualizar',
        ];
    }
    
    // Pegar primeira oportunidade PENDENTE
    $id = null;
    foreach ($dados as $msgId => $msg) {
        if ($msg['resultado'] === 'PENDENTE') {
            $id = $msgId;
            break;
        }
    }
    
    if (!$id) {
        return [
            'sucesso' => false,
            'erro' => 'Nenhuma oportunidade PENDENTE para atualizar',
        ];
    }
    
    $dados[$id]['resultado'] = 'GREEN';
    $dados[$id]['data_resultado'] = date('Y-m-d H:i:s');
    
    salvarDados($dados);
    
    return [
        'sucesso' => true,
        'acao' => 'green',
        'id' => $id,
        'mensagem' => $dados[$id],
    ];
}

/**
 * Simula um resultado RED
 */
function simularRed() {
    $dados = carregarDados();
    
    if (empty($dados)) {
        return [
            'sucesso' => false,
            'erro' => 'Nenhuma oportunidade para atualizar',
        ];
    }
    
    $id = null;
    foreach ($dados as $msgId => $msg) {
        if ($msg['resultado'] === 'PENDENTE') {
            $id = $msgId;
            break;
        }
    }
    
    if (!$id) {
        return [
            'sucesso' => false,
            'erro' => 'Nenhuma oportunidade PENDENTE para atualizar',
        ];
    }
    
    $dados[$id]['resultado'] = 'RED';
    $dados[$id]['data_resultado'] = date('Y-m-d H:i:s');
    
    salvarDados($dados);
    
    return [
        'sucesso' => true,
        'acao' => 'red',
        'id' => $id,
        'mensagem' => $dados[$id],
    ];
}

/**
 * Simula um resultado REEMBOLSO
 */
function simularReembolso() {
    $dados = carregarDados();
    
    if (empty($dados)) {
        return [
            'sucesso' => false,
            'erro' => 'Nenhuma oportunidade para atualizar',
        ];
    }
    
    $id = null;
    foreach ($dados as $msgId => $msg) {
        if ($msg['resultado'] === 'PENDENTE') {
            $id = $msgId;
            break;
        }
    }
    
    if (!$id) {
        return [
            'sucesso' => false,
            'erro' => 'Nenhuma oportunidade PENDENTE para atualizar',
        ];
    }
    
    $dados[$id]['resultado'] = 'REEMBOLSO';
    $dados[$id]['data_resultado'] = date('Y-m-d H:i:s');
    
    salvarDados($dados);
    
    return [
        'sucesso' => true,
        'acao' => 'reembolso',
        'id' => $id,
        'mensagem' => $dados[$id],
    ];
}

/**
 * Processa a ação
 */
$acao = $_GET['acao'] ?? $_POST['acao'] ?? null;
$resultado = null;

if ($acao === 'oportunidade') {
    $resultado = simularOportunidade();
} elseif ($acao === 'green') {
    $resultado = simularGreen();
} elseif ($acao === 'red') {
    $resultado = simularRed();
} elseif ($acao === 'reembolso') {
    $resultado = simularReembolso();
} else {
    $resultado = [
        'sucesso' => false,
        'erro' => 'Ação não reconhecida',
        'acoes_disponiveis' => [
            'oportunidade',
            'green',
            'red',
            'reembolso',
        ],
    ];
}

header('Content-Type: application/json');
echo json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
