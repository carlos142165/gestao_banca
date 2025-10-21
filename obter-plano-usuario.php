<?php
/**
 * OBTER PLANO DO USUÁRIO
 * =====================
 * Retorna o plano atual do usuário com todas as informações
 */

// Limpar qualquer output anterior
ob_start();

// Definir header JSON primeiro
header('Content-Type: application/json; charset=utf-8');

// Limpar buffer
ob_clean();

// Agora carregar config e sessão
require_once 'config.php';
require_once 'carregar_sessao.php';

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Usuário não logado'
    ]);
    exit;
}

$id_usuario = $_SESSION['usuario_id'];

try {
    // Obter dados do usuário e seu plano
    $query = "
        SELECT 
            u.id,
            u.email,
            u.id_plano,
            u.status_assinatura,
            u.data_fim_assinatura,
            p.id as plano_id,
            p.nome as plano_nome,
            p.icone as plano_icone,
            p.cor_tema as plano_cor,
            p.mentores_limite,
            p.entradas_diarias,
            p.preco_mes,
            p.preco_ano
        FROM usuarios u
        LEFT JOIN planos p ON u.id_plano = p.id
        WHERE u.id = ?
        LIMIT 1
    ";
    
    $stmt = $conexao->prepare($query);
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    $usuario = $result->fetch_assoc();
    $stmt->close();
    
    if (!$usuario) {
        echo json_encode([
            'sucesso' => false,
            'mensagem' => 'Usuário não encontrado'
        ]);
        exit;
    }
    
    // Se não tiver plano, atribuir o gratuito
    if (!$usuario['plano_id']) {
        $usuario['id_plano'] = 1;
        $usuario['plano_nome'] = 'GRATUITO';
        $usuario['plano_icone'] = 'fas fa-gift';
        $usuario['plano_cor'] = '#95a5a6';
    }
    
    // Calcular dias restantes da assinatura
    $dias_restantes = null;
    if ($usuario['data_fim_assinatura']) {
        $data_fim = new DateTime($usuario['data_fim_assinatura']);
        $hoje = new DateTime();
        $diferenca = $data_fim->diff($hoje);
        $dias_restantes = $diferenca->days;
    }
    
    echo json_encode([
        'sucesso' => true,
        'usuario' => [
            'id' => $usuario['id'],
            'email' => $usuario['email']
        ],
        'plano' => [
            'id' => $usuario['plano_id'],
            'nome' => $usuario['plano_nome'],
            'icone' => $usuario['plano_icone'],
            'cor' => $usuario['plano_cor'],
            'mentores_limite' => $usuario['mentores_limite'],
            'entradas_diarias' => $usuario['entradas_diarias'],
            'preco_mes' => $usuario['preco_mes'],
            'preco_ano' => $usuario['preco_ano'],
            'status' => $usuario['status_assinatura'],
            'data_fim' => $usuario['data_fim_assinatura'],
            'dias_restantes' => $dias_restantes
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'sucesso' => false,
        'mensagem' => $e->getMessage(),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
}

ob_end_flush();
?>
