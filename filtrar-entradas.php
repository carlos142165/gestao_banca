<?php
include("config.php");

if (!$conexao) {
  die("Conexão não está definida.");
}

header('Content-Type: application/json');

// Fuso horário do backend
date_default_timezone_set('America/Bahia');

$id = $_GET['id'];
$data = date("Y-m-d");

// Query com conversão de fuso direto no SQL
$sql = "SELECT 
          id, 
          green, 
          red, 
          valor_green, 
          valor_red, 
          DATE_FORMAT(CONVERT_TZ(data_criacao, '+00:00', '-05:00'), '%Y-%m-%d %H:%i:%s') AS data_criacao
        FROM valor_mentores 
        WHERE id_mentores = ? AND DATE(CONVERT_TZ(data_criacao, '+00:00', '-05:00')) = ?";

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
