<?php
$dbHost = 'localhost';
$dbUsername = 'root';
$dbPassword = '';
$dbname = 'formulario-carlos';

$conexao = new mysqli($dbHost, $dbUsername, $dbPassword, $dbname);

if ($conexao->connect_error) {
  exit("❌ Falha na conexão.");
}

$id = $_POST['id'] ?? null;

if ($id) {
  $stmt = $conexao->prepare("DELETE FROM valor_mentores WHERE id = ?");
  $stmt->bind_param("i", $id);

  if ($stmt->execute()) {
    echo "sucesso: Entrada excluída com êxito.";

  } else {
    echo "❌ Erro ao excluir: " . $stmt->error;
  }

  $stmt->close();
} else {
  echo "⚠️ ID não recebido.";
}

$conexao->close();
?>

