<?php
include("config.php");

if (!$conexao) {
  die("Conexão não está definida.");
}

header('Content-Type: application/json');

$idEntrada = $_GET['id'];

// Consulta dados da entrada com informações do mentor
$sql = "SELECT vm.id, vm.id_mentores, vm.green, vm.red, vm.valor_green, vm.valor_red, vm.data_criacao,
               m.nome AS nome_mentor, m.foto AS foto_mentor
        FROM valor_mentores vm
        INNER JOIN mentores m ON vm.id_mentores = m.id
        WHERE vm.id = ?";
$stmt = $conexao->prepare($sql);
$stmt->bind_param("i", $idEntrada);
$stmt->execute();

$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
  // Define campo 'opcao' e valor com base nos dados
  $opcao = null;
  if ($row['green'] > 0) {
    $opcao = "green";
    $valor = $row['valor_green'];
  } elseif ($row['red'] > 0) {
    $opcao = "red";
    $valor = $row['valor_red'];
  } else {
    $valor = 0;
  }

  echo json_encode([
    "id" => $row["id"],
    "id_mentor" => $row["id_mentores"],
    "valor" => number_format($valor, 2, '.', ''),
    "opcao" => $opcao,
    "nome" => $row["nome_mentor"],
    "foto" => $row["foto_mentor"]
  ]);
} else {
  echo json_encode([]);
}
?>
