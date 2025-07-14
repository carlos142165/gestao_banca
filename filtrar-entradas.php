<?php
include("config.php");

if (!$conexao) {
    
  die("Conexão não está definida.");
}

header('Content-Type: application/json');

$id = $_GET['id'];
$data = date("Y-m-d");

$sql = "SELECT id, green, red, valor_green, valor_red 
        FROM valor_mentores 
        WHERE id_mentores = ? AND DATE(data_criacao) = ?";
$stmt = $conexao->prepare($sql);
$stmt->bind_param("is", $id, $data);
$stmt->execute();

$result = $stmt->get_result();
$entradas = [];
while ($row = $result->fetch_assoc()) {
  $entradas[] = $row;
}
echo json_encode($entradas);
?>


