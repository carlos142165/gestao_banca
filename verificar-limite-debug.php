<?php
/**
 * DEBUG DETALHADO - verificar-limite.php
 * ======================================
 */

require_once 'config.php';
require_once 'carregar_sessao.php';
require_once 'config_mercadopago.php';

header('Content-Type: application/json');

$id_usuario = $_SESSION['usuario_id'] ?? null;
$acao = $_GET['acao'] ?? 'mentor';

$debug = [
    'timestamp' => date('Y-m-d H:i:s'),
    'usuario_id' => $id_usuario,
    'acao' => $acao,
    'etapas' => []
];

try {
    // ETAPA 1: Verificar se usuário está autenticado
    $debug['etapas'][] = [
        'etapa' => 'Verificação de autenticação',
        'status' => $id_usuario ? 'OK' : 'ERRO',
        'usuario_id' => $id_usuario
    ];
    
    if (!$id_usuario) {
        throw new Exception('Usuário não autenticado');
    }
    
    // ETAPA 2: Obter plano atual
    $debug['etapas'][] = [
        'etapa' => 'Chamando obterPlanoAtual()',
        'status' => 'INICIADO'
    ];
    
    $plano = MercadoPagoManager::obterPlanoAtual($id_usuario);
    
    $debug['etapas'][] = [
        'etapa' => 'Resultado de obterPlanoAtual()',
        'status' => $plano ? 'OK' : 'NULO',
        'plano' => $plano
    ];
    
    if (!$plano) {
        throw new Exception('Plano é NULL - obterPlanoAtual() falhou');
    }
    
    if (!isset($plano['id'])) {
        throw new Exception('Plano sem ID - estrutura inválida');
    }
    
    $debug['etapas'][] = [
        'etapa' => 'Plano obtido com sucesso',
        'status' => 'OK',
        'plano_nome' => $plano['nome'],
        'plano_id' => $plano['id']
    ];
    
    // ETAPA 3: Verificar limite baseado na ação
    if ($acao === 'mentor') {
        $debug['etapas'][] = [
            'etapa' => 'Verificando limite de mentores',
            'plano_id' => $plano['id'],
            'limite' => $plano['mentores_limite'] ?? 'ND'
        ];
        
        $pode_prosseguir = MercadoPagoManager::verificarLimiteMentores($id_usuario, $plano['id']);
        
        $debug['etapas'][] = [
            'etapa' => 'Resultado de verificarLimiteMentores()',
            'pode_prosseguir' => $pode_prosseguir
        ];
        
        $mensagem = !$pode_prosseguir 
            ? "Você atingiu o limite de mentores no plano {$plano['nome']}. Faça upgrade!"
            : '';
            
    } else if ($acao === 'entrada') {
        $debug['etapas'][] = [
            'etapa' => 'Verificando limite de entradas',
            'plano_id' => $plano['id'],
            'limite' => $plano['entradas_diarias'] ?? 'ND'
        ];
        
        $pode_prosseguir = MercadoPagoManager::verificarLimiteEntradas($id_usuario, $plano['id']);
        
        $debug['etapas'][] = [
            'etapa' => 'Resultado de verificarLimiteEntradas()',
            'pode_prosseguir' => $pode_prosseguir
        ];
        
        $mensagem = !$pode_prosseguir 
            ? "Você atingiu o limite de entradas diárias no plano {$plano['nome']}. Faça upgrade!"
            : '';
    } else {
        throw new Exception('Ação inválida: ' . $acao);
    }
    
    $debug['resultado'] = 'SUCESSO';
    $debug['pode_prosseguir'] = $pode_prosseguir;
    
    echo json_encode([
        'success' => true,
        'pode_prosseguir' => $pode_prosseguir,
        'plano_atual' => $plano['nome'] ?? 'DESCONHECIDO',
        'mensagem' => $mensagem,
        'debug' => $debug  // ← DEBUG COMPLETO
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    $debug['resultado'] = 'ERRO';
    $debug['erro'] = $e->getMessage();
    $debug['etapas'][] = [
        'etapa' => 'EXCEÇÃO CAPTURADA',
        'erro' => $e->getMessage(),
        'arquivo' => $e->getFile(),
        'linha' => $e->getLine()
    ];
    
    echo json_encode([
        'success' => false,
        'pode_prosseguir' => true, // Fail-safe
        'mensagem' => $e->getMessage(),
        'debug' => $debug
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
?>
