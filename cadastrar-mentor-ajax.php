<?php
// cadastrar-mentor-ajax.php
// Endpoint leve para cadastrar/editar mentores via AJAX (retorna JSON rapidamente)

session_start();
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id']) || empty($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit();
}

$usuario_id = intval($_SESSION['usuario_id']);
$acao = $_POST['acao'] ?? '';
$nome = trim($_POST['nome'] ?? '');
$mentor_id = isset($_POST['mentor_id']) ? intval($_POST['mentor_id']) : 0;

if (!in_array($acao, ['cadastrar_mentor', 'editar_mentor'])) {
    echo json_encode(['success' => false, 'message' => 'Ação inválida']);
    exit();
}

if (empty($nome) || strlen($nome) < 2) {
    echo json_encode(['success' => false, 'message' => 'Nome inválido']);
    exit();
}

$foto_nome = isset($_POST['foto_atual']) ? $_POST['foto_atual'] : 'avatar-padrao.png';

// Processo de upload (opcional)
if (isset($_FILES['foto']) && isset($_FILES['foto']['tmp_name']) && is_uploaded_file($_FILES['foto']['tmp_name'])) {
    $extensao = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','gif','webp'];
    if (!in_array($extensao, $allowed)) {
        echo json_encode(['success' => false, 'message' => 'Formato de imagem inválido']);
        exit();
    }

    if ($_FILES['foto']['size'] > 5 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'Arquivo muito grande (max 5MB)']);
        exit();
    }

    $check = getimagesize($_FILES['foto']['tmp_name']);
    if ($check === false) {
        echo json_encode(['success' => false, 'message' => 'Arquivo não é uma imagem válida']);
        exit();
    }

    $foto_nome = uniqid() . '.' . $extensao;
    if (!is_dir('uploads')) mkdir('uploads', 0755, true);
    if (!move_uploaded_file($_FILES['foto']['tmp_name'], 'uploads/' . $foto_nome)) {
        echo json_encode(['success' => false, 'message' => 'Falha ao salvar arquivo']);
        exit();
    }
}

try {
    if ($acao === 'cadastrar_mentor') {
        // evitar duplicados
        $stmt = $conexao->prepare('SELECT id FROM mentores WHERE nome = ? AND id_usuario = ? LIMIT 1');
        $stmt->bind_param('si', $nome, $usuario_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Já existe um mentor com este nome']);
            exit();
        }

        $stmt = $conexao->prepare('INSERT INTO mentores (id_usuario, foto, nome, data_criacao) VALUES (?, ?, ?, NOW())');
        $stmt->bind_param('iss', $usuario_id, $foto_nome, $nome);
        $ok = $stmt->execute();
        $lastId = $conexao->insert_id;
        if ($ok) {
            $stmt_info = $conexao->prepare('SELECT id, nome, foto FROM mentores WHERE id = ? LIMIT 1');
            $stmt_info->bind_param('i', $lastId);
            $stmt_info->execute();
            $mentor = $stmt_info->get_result()->fetch_assoc();
            echo json_encode(['success' => true, 'mensagem' => 'Mentor cadastrado com sucesso', 'mentor' => $mentor]);
            exit();
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao inserir mentor']);
            exit();
        }
    } else {
        // editar
        if ($mentor_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID do mentor inválido']);
            exit();
        }

        // verificar propriedade
        $stmt_check = $conexao->prepare('SELECT id, foto FROM mentores WHERE id = ? AND id_usuario = ? LIMIT 1');
        $stmt_check->bind_param('ii', $mentor_id, $usuario_id);
        $stmt_check->execute();
        $res = $stmt_check->get_result();
        if ($res->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Mentor não encontrado ou não autorizado']);
            exit();
        }
        $existing = $res->fetch_assoc();

        $stmt = $conexao->prepare('UPDATE mentores SET nome = ?, foto = ? WHERE id = ? AND id_usuario = ?');
        $stmt->bind_param('ssii', $nome, $foto_nome, $mentor_id, $usuario_id);
        $ok = $stmt->execute();
        if ($ok) {
            $stmt_info = $conexao->prepare('SELECT id, nome, foto FROM mentores WHERE id = ? LIMIT 1');
            $stmt_info->bind_param('i', $mentor_id);
            $stmt_info->execute();
            $mentor = $stmt_info->get_result()->fetch_assoc();
            echo json_encode(['success' => true, 'mensagem' => 'Mentor atualizado com sucesso', 'mentor' => $mentor]);
            exit();
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao atualizar mentor']);
            exit();
        }
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
    exit();
}

