<?php
session_start();
include('conexao.php'); // se necessário, inclua sua conexão

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['erro' => 'Usuário não logado']);
    exit;
}

$idUsuario = $_SESSION['usuario_id'];

$sql = "SELECT SUM(valor_green) AS total_green, SUM(valor_red) AS total_red 
        FROM valor_mentores 
        WHERE id_usuario = ?";
$stmt = $conexao->prepare($sql);
$stmt->bind_param("i", $idUsuario);
$stmt->execute();
$resultado = $stmt->get_result();
$row = $resultado->fetch_assoc();

$saldo = $row['total_green'] - $row['total_red'];
$saldoFormatado = number_format($saldo, 2, ',', '.');

echo json_encode(['saldo' => $saldoFormatado]);
?>
