<?php
include("config.php");

if (!$conexao) {
  die("Conexão não está definida.");
}

header('Content-Type: application/json');

// Defina o fuso horário padrão do PHP (não afeta o banco, só operações locais)
date_default_timezone_set('America/Bahia');

$id = $_GET['id'];
$dataBahia = new DateTime('now', new DateTimeZone('America/Bahia'));
$dataFormatada = $dataBahia->format('Y-m-d');

// Consulta simples — sem conversão SQL, toda conversão é feita no PHP
$sql = "SELECT 
          id, 
          green, 
          red, 
          valor_green, 
          valor_red, 
          data_criacao 
        FROM valor_mentores 
        WHERE id_mentores = ? AND DATE(data_criacao) = ?";

$stmt = $conexao->prepare($sql);
$stmt->bind_param("is", $id, $dataFormatada);
$stmt->execute();

$result = $stmt->get_result();
$entradas = [];

while ($row = $result->fetch_assoc()) {
  // Converte de UTC para fuso local
  $dataUTC = new DateTime($row['data_criacao'], new DateTimeZone('UTC'));
  $dataBahia = clone $dataUTC;
  $dataBahia->setTimezone(new DateTimeZone('America/Bahia'));

  // Formatos para frontend
  $row['horario_utc'] = $dataUTC->format(DateTime::ATOM);       // Ex: "2025-07-27T11:03:00Z"
  $row['horario_bahia'] = $dataBahia->format('Y-m-d H:i:s');    // Ex: "2025-07-27 08:03:00"

  $entradas[] = $row;
}

echo json_encode($entradas);
?>

