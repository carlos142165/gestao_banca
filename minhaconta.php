<?php
/**
 * ============================================
 * SISTEMA DE GERENCIAMENTO DE CONTA DO USUÁRIO
 * ============================================
 * 
 * Arquivo centralizado para todas as operações
 * do modal "Minha Conta"
 * 
 * Endpoints:
 * - GET ?acao=obter_dados          → Buscar dados do usuário
 * - POST acao=atualizar_nome       → Atualizar nome
 * - POST acao=atualizar_email      → Atualizar email
 * - POST acao=atualizar_telefone   → Atualizar telefone
 * - POST acao=atualizar_senha      → Atualizar senha
 * - POST acao=excluir_conta        → Excluir conta do usuário
 * 
 * @author Sistema Gestão Banca
 * @version 1.0
 */

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Verificar autenticação
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Não autenticado']);
    exit();
}

$id_usuario = $_SESSION['usuario_id'];
$acao = $_GET['acao'] ?? $_POST['acao'] ?? null;

// ============================================
// FUNÇÃO: Obter Dados do Usuário
// ============================================
// Retorna: id, nome, email, telefone, plano
// Uso: GET minhaconta.php?acao=obter_dados
if ($acao === 'obter_dados') {
    try {
        $check = $conexao->query("SHOW COLUMNS FROM usuarios LIKE 'plano'");
        $tem_plano = $check && $check->num_rows > 0;
        
        $sql = $tem_plano 
            ? "SELECT id, nome, email, telefone, plano FROM usuarios WHERE id = ?"
            : "SELECT id, nome, email, telefone, 'Gratuito' as plano FROM usuarios WHERE id = ?";
        
        $stmt = $conexao->prepare($sql);
        if (!$stmt) {
            throw new Exception("Erro ao preparar query: " . $conexao->error);
        }
        
        $stmt->bind_param("i", $id_usuario);
        if (!$stmt->execute()) {
            throw new Exception("Erro ao executar query: " . $stmt->error);
        }
        
        $resultado = $stmt->get_result();
        
        if ($resultado->num_rows > 0) {
            $usuario = $resultado->fetch_assoc();
            http_response_code(200);
            echo json_encode(['success' => true, 'usuario' => $usuario]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Usuário não encontrado']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erro ao buscar dados: ' . $e->getMessage()]);
    }
    exit();
}

// ============================================
// FUNÇÃO: Atualizar Nome do Usuário
// ============================================
// Validação: 2-100 caracteres
// Uso: POST minhaconta.php com acao=atualizar_nome&nome=novo_nome
if ($acao === 'atualizar_nome') {
    $nome = trim($_POST['nome'] ?? '');
    
    if (empty($nome) || strlen($nome) < 2 || strlen($nome) > 100) {
        echo json_encode(['success' => false, 'message' => 'Nome inválido (2-100 caracteres)']);
        exit();
    }
    
    try {
        $stmt = $conexao->prepare("UPDATE usuarios SET nome = ? WHERE id = ?");
        $stmt->bind_param("si", $nome, $id_usuario);
        $stmt->execute();
        echo json_encode(['success' => true, 'message' => 'Nome atualizado com sucesso']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erro ao atualizar nome: ' . $e->getMessage()]);
    }
    exit();
}

// ============================================
// FUNÇÃO: Atualizar Email do Usuário
// ============================================
// Validação: Email válido, não pode estar duplicado
// Uso: POST minhaconta.php com acao=atualizar_email&email=novo_email
if ($acao === 'atualizar_email') {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Email inválido']);
        exit();
    }
    
    try {
        $stmt_check = $conexao->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
        $stmt_check->bind_param("si", $email, $id_usuario);
        $stmt_check->execute();
        
        if ($stmt_check->get_result()->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Email já está em uso']);
            exit();
        }
        
        $stmt = $conexao->prepare("UPDATE usuarios SET email = ? WHERE id = ?");
        $stmt->bind_param("si", $email, $id_usuario);
        $stmt->execute();
        echo json_encode(['success' => true, 'message' => 'Email atualizado com sucesso']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erro ao atualizar email: ' . $e->getMessage()]);
    }
    exit();
}

// ============================================
// FUNÇÃO: Atualizar Telefone do Usuário
// ============================================
// Validação: Mínimo 10 dígitos numéricos
// Uso: POST minhaconta.php com acao=atualizar_telefone&telefone=novo_telefone
if ($acao === 'atualizar_telefone') {
    $telefone = trim($_POST['telefone'] ?? '');
    $telefone_limpo = preg_replace('/\D/', '', $telefone);
    
    if (strlen($telefone_limpo) < 10) {
        echo json_encode(['success' => false, 'message' => 'Telefone deve ter pelo menos 10 dígitos']);
        exit();
    }
    
    try {
        $stmt = $conexao->prepare("UPDATE usuarios SET telefone = ? WHERE id = ?");
        $stmt->bind_param("si", $telefone, $id_usuario);
        $stmt->execute();
        echo json_encode(['success' => true, 'message' => 'Telefone atualizado com sucesso']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erro ao atualizar telefone: ' . $e->getMessage()]);
    }
    exit();
}

// ============================================
// FUNÇÃO: Atualizar Senha do Usuário
// ============================================
// Validação: Senha atual correta, nova senha 6+ caracteres, confirmação igual
// Usa password_hash com PASSWORD_DEFAULT
// Uso: POST minhaconta.php com acao=atualizar_senha&senha_atual=...&senha_nova=...&senha_confirma=...
if ($acao === 'atualizar_senha') {
    $senha_atual = $_POST['senha_atual'] ?? '';
    $senha_nova = $_POST['senha_nova'] ?? '';
    $senha_confirma = $_POST['senha_confirma'] ?? '';
    
    if (empty($senha_atual) || empty($senha_nova) || empty($senha_confirma)) {
        echo json_encode(['success' => false, 'message' => 'Todos os campos de senha são obrigatórios']);
        exit();
    }
    
    if (strlen($senha_nova) < 6) {
        echo json_encode(['success' => false, 'message' => 'Nova senha deve ter pelo menos 6 caracteres']);
        exit();
    }
    
    if ($senha_nova !== $senha_confirma) {
        echo json_encode(['success' => false, 'message' => 'As senhas não conferem']);
        exit();
    }
    
    try {
        $stmt = $conexao->prepare("SELECT senha FROM usuarios WHERE id = ?");
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $usuario = $resultado->fetch_assoc();
        
        if (!password_verify($senha_atual, $usuario['senha'])) {
            echo json_encode(['success' => false, 'message' => 'Senha atual incorreta']);
            exit();
        }
        
        $senha_hash = password_hash($senha_nova, PASSWORD_DEFAULT);
        $stmt = $conexao->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
        $stmt->bind_param("si", $senha_hash, $id_usuario);
        $stmt->execute();
        echo json_encode(['success' => true, 'message' => 'Senha atualizada com sucesso']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erro ao atualizar senha: ' . $e->getMessage()]);
    }
    exit();
}

// ============================================
// FUNÇÃO: Excluir Conta do Usuário
// ============================================
// Validação: Campo confirmação deve conter "SIM"
// Usa transaction para garantir ACID compliance
// Deleta: valor_mentores, mentores, controle, usuário
// Uso: POST minhaconta.php com acao=excluir_conta&confirmacao=SIM
if ($acao === 'excluir_conta') {
    $confirmacao = $_POST['confirmacao'] ?? '';
    
    if ($confirmacao !== 'SIM') {
        echo json_encode(['success' => false, 'message' => 'Confirmação não válida']);
        exit();
    }
    
    try {
        $conexao->begin_transaction();
        
        // Deletar valores dos mentores
        $stmt = $conexao->prepare("DELETE FROM valor_mentores WHERE id_usuario = ?");
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        
        // Deletar mentores
        $stmt = $conexao->prepare("DELETE FROM mentores WHERE id_usuario = ?");
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        
        // Deletar controle
        $stmt = $conexao->prepare("DELETE FROM controle WHERE id_usuario = ?");
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        
        // Deletar usuário
        $stmt = $conexao->prepare("DELETE FROM usuarios WHERE id = ?");
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        
        $conexao->commit();
        session_destroy();
        
        echo json_encode([
            'success' => true,
            'message' => 'Conta excluída com sucesso',
            'redirect' => 'home.php'
        ]);
    } catch (Exception $e) {
        $conexao->rollback();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erro ao excluir conta: ' . $e->getMessage()]);
    }
    exit();
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Ação não especificada']);
?>
