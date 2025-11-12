<?php
// ✅ ARQUIVO obter-und.php - RETORNA A UNIDADE (UND) ATUAL DO BANCO

header('Content-Type: application/json');

require_once 'config.php';
require_once 'carregar_sessao.php';

try {
    // Verificar se usuário está logado
    $id_usuario = $_SESSION['usuario_id'] ?? null;
    if (!$id_usuario) {
        echo json_encode([
            'success' => false,
            'message' => 'Usuário não logado',
            'valor' => 0,
            'valor_formatado' => 'R$ 0,00'
        ]);
        exit;
    }

    // ✅ Obter conexão com verificação
    $conexao = obterConexao();
    
    if (!$conexao) {
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao conectar ao banco de dados',
            'valor' => 0,
            'valor_formatado' => 'R$ 0,00'
        ]);
        exit;
    }

    // Buscar a última unidade (UND) registrada do usuário na tabela controle
    $stmt = $conexao->prepare("
        SELECT und FROM controle
        WHERE id_usuario = ? AND und IS NOT NULL AND und > 0
        ORDER BY id DESC LIMIT 1
    ");
    
    if (!$stmt) {
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao preparar query: ' . $conexao->error,
            'valor' => 0,
            'valor_formatado' => 'R$ 0,00'
        ]);
        exit;
    }

    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $stmt->bind_result($und);
    $stmt->fetch();
    $stmt->close();

    // Se encontrou valor, formatar e retornar
    if ($und !== null && $und > 0) {
        $valor_formatado = number_format($und, 2, ',', '.');
        $valor_formatado = 'R$ ' . $valor_formatado;
        
        echo json_encode([
            'success' => true,
            'message' => 'UND obtido com sucesso',
            'valor' => floatval($und),
            'valor_formatado' => $valor_formatado
        ]);
    } else {
        // Se não encontrar, retornar valor padrão
        echo json_encode([
            'success' => false,
            'message' => 'Nenhuma UND encontrada',
            'valor' => 0,
            'valor_formatado' => 'R$ 0,00'
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro: ' . $e->getMessage(),
        'valor' => 0,
        'valor_formatado' => 'R$ 0,00'
    ]);
}
?>
