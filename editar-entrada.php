<?php
include("config.php");

$id = $_POST['id'];
$green = $_POST['green'];
$red = $_POST['red'];
$valor_green = $_POST['valor_green'];
$valor_red = $_POST['valor_red'];

$sql = "UPDATE valor_mentores SET green = ?, red = ?, valor_green = ?, valor_red = ? WHERE id = ?";
$stmt = $conexao->prepare($sql);
$stmt->bind_param("iiddi", $green, $red, $valor_green, $valor_red, $id);

echo $stmt->execute() ? "✅ Alteração salva com sucesso!" : "❌ Erro ao salvar.";
?>
