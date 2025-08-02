<?php
require_once 'config.php';

$id = $_POST['id'] ?? null;
if (!$id) exit("ID não informado.");

$stmt = $conexao->prepare("DELETE FROM controle WHERE id = ? AND saque > 0");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
  echo "Saque Excluído Com Sucesso!.";
} else {
  echo "Erro ao excluir.";
}
?>
