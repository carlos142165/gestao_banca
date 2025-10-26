<?php
require_once 'config.php';
require_once 'carregar_sessao.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

$acao = $_POST['acao'] ?? null;

if (!$acao) {
    echo json_encode(['success' => false, 'mensagem' => 'Ação não especificada']);
    exit;
}

try {
    if ($acao === 'obter') {
        // Obter todos os usuários com bônus ativos (data_fim_assinatura > hoje)
        $result = $conexao->query("
            SELECT u.id, u.nome, p.nome as plano, u.data_fim_assinatura
            FROM usuarios u
            JOIN planos p ON u.id_plano = p.id
            WHERE u.data_fim_assinatura > NOW()
            AND p.nome IN ('prata', 'ouro', 'diamante')
            ORDER BY u.data_fim_assinatura DESC
        ");
        
        if (!$result) {
            throw new Exception("Erro na consulta: " . $conexao->error);
        }
        
        $bonus = [];
        while ($row = $result->fetch_assoc()) {
            // Calcular duração baseado na data de expiração
            // 📅 MENSAL: Vence em até 30 dias (próximos 30 dias)
            // 📅 ANUAL: Vence depois de 30 dias (próximo ano ou além)
            $data_fim = new DateTime($row['data_fim_assinatura']);
            $data_agora = new DateTime();
            $dias_ate_vencer = $data_fim->diff($data_agora)->days;
            
            $duracao = $dias_ate_vencer <= 30 ? 'mensal' : 'anual';
            
            $bonus[] = [
                'id' => (int)$row['id'],
                'usuario_id' => (int)$row['id'],
                'nome' => $row['nome'],
                'plano' => $row['plano'],
                'duracao' => $duracao,
                'data_fim' => $row['data_fim_assinatura']
            ];
        }
        
        echo json_encode([
            'success' => true,
            'bonus' => $bonus
        ]);
        
    } elseif ($acao === 'adicionar') {
        $usuario_id = (int)($_POST['usuario_id'] ?? 0);
        $duracao = $_POST['duracao'] ?? null;
        $plano = $_POST['plano'] ?? null;
        
        if (!$usuario_id || !$duracao || !$plano) {
            throw new Exception('Dados inválidos');
        }
        
        // Validar duracao e plano
        if (!in_array($duracao, ['mensal', 'anual'])) {
            throw new Exception('Duração inválida');
        }
        
        if (!in_array($plano, ['prata', 'ouro', 'diamante'])) {
            throw new Exception('Plano inválido');
        }
        
        // Verificar se usuário existe
        $check = $conexao->query("SELECT id FROM usuarios WHERE id = $usuario_id");
        if ($check->num_rows === 0) {
            throw new Exception('Usuário não encontrado');
        }
        
        // Calcular data de expiração baseado na duração
        $data_expiracao = $duracao === 'mensal' 
            ? date('Y-m-d', strtotime('+1 month'))
            : date('Y-m-d', strtotime('+1 year'));
        
        // Atualizar plano do usuário
        $conexao->query("
            UPDATE usuarios 
            SET id_plano = (SELECT id FROM planos WHERE nome = '$plano' LIMIT 1),
                data_fim_assinatura = '$data_expiracao'
            WHERE id = $usuario_id
        ");
        
        if ($conexao->error) {
            throw new Exception("Erro ao atualizar usuário: " . $conexao->error);
        }
        
        echo json_encode([
            'success' => true,
            'mensagem' => "Bônus adicionado com sucesso para o usuário ID #$usuario_id. Vencimento: $data_expiracao"
        ]);
        
    } elseif ($acao === 'remover') {
        $usuario_id = (int)($_POST['id'] ?? 0);
        
        if (!$usuario_id) {
            throw new Exception('ID inválido');
        }
        
        // Verificar se usuário existe
        $check = $conexao->query("SELECT id FROM usuarios WHERE id = $usuario_id");
        if ($check->num_rows === 0) {
            throw new Exception('Usuário não encontrado');
        }
        
        // Reverter usuário para plano gratuito
        $conexao->query("
            UPDATE usuarios 
            SET id_plano = (SELECT id FROM planos WHERE nome = 'gratuito' LIMIT 1),
                data_fim_assinatura = NULL
            WHERE id = $usuario_id
        ");
        
        if ($conexao->error) {
            throw new Exception("Erro ao remover bônus: " . $conexao->error);
        }
        
        echo json_encode([
            'success' => true,
            'mensagem' => "Bônus removido com sucesso. Usuário revertido para plano gratuito."
        ]);
        
    } else {
        throw new Exception('Ação inválida');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'mensagem' => $e->getMessage()
    ]);
}
?>
