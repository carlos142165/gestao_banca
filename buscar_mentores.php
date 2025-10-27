<?php
session_start();
header('Content-Type: application/json');

// Incluir configurações centralizadas do banco de dados
require_once __DIR__ . '/config.php';

// Usar a conexão global do config.php
$conn = $conexao;

if ($conn->connect_error) {
    echo json_encode(["erro" => "Falha na conexão"]);
    exit();
}

// Pega o ID do usuário logado
$usuario_id = isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : null;

if (!$usuario_id) {
    echo json_encode(["erro" => "Usuário não logado"]);
    exit();
}

// Consulta os mentores
$sql = "SELECT nome FROM mentores WHERE id_usuario = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();

$mentores = [];
while ($row = $result->fetch_assoc()) {
    $mentores[] = $row['nome'];
}

echo json_encode($mentores);
?>
