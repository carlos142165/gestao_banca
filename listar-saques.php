<?php
require_once 'config.php';
session_start();

// Verifica se o usuário está logado
$id_usuario = $_SESSION['usuario_id'] ?? null;
if (!$id_usuario) {
  echo json_encode([]);
  exit;
}

// Corrigido: nome da coluna "data_registro"
$sql = "SELECT id, saque, data_registro FROM controle 
        WHERE id_usuario = ? AND saque > 0 ORDER BY data_registro DESC";

$stmt = $conexao->prepare($sql);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();

$result = $stmt->get_result();
$dados = [];

while ($row = $result->fetch_assoc()) {
  $row['saque'] = number_format($row['saque'], 2, ',', '.');
  $row['data'] = date('d/m/Y H:i', strtotime($row['data_registro']));
  $dados[] = $row;
}

// Retorna os dados em JSON para o frontend
echo json_encode($dados);
?>

