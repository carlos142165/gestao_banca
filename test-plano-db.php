<?php
/**
 * TESTE DE BANCO DE DADOS
 * =======================
 * Verifica os dados do banco de dados
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
    // Teste 1: Verificar usuário
    $query1 = "SELECT id, email, id_plano, status_assinatura, data_fim_assinatura FROM usuarios WHERE id = ?";
    $stmt1 = $conexao->prepare($query1);
    $stmt1->bind_param("i", $id_usuario);
    $stmt1->execute();
    $result1 = $stmt1->get_result();
    $usuario = $result1->fetch_assoc();
    $stmt1->close();

    if (!$usuario) {
        echo json_encode([
            'sucesso' => false,
            'mensagem' => 'Usuário não encontrado no banco',
            'id_buscado' => $id_usuario
        ]);
        exit;
    }

    // Teste 2: Verificar plano
    $query2 = "SELECT id, nome, icone, cor_tema, mentores_limite, entradas_diarias FROM planos WHERE id = ?";
    $stmt2 = $conexao->prepare($query2);
    
    $plano = null;
    if ($usuario['id_plano']) {
        $stmt2->bind_param("i", $usuario['id_plano']);
        $stmt2->execute();
        $result2 = $stmt2->get_result();
        $plano = $result2->fetch_assoc();
    }
    $stmt2->close();

    echo json_encode([
        'sucesso' => true,
        'usuario' => $usuario,
        'plano' => $plano,
        'debug' => [
            'id_usuario' => $id_usuario,
            'id_plano_do_usuario' => $usuario['id_plano'],
            'plano_encontrado' => $plano ? true : false
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
