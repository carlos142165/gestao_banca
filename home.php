


<?php
session_start();
include_once('config.php');

$id = $_SESSION['id'] ?? null;

if (!$id) {
    header("Location: login.html");
    exit;
}

$result = mysqli_query($conexao, "SELECT data_cadastro, status_assinatura, data_fim_assinatura FROM usuarios WHERE id = '$id'");
$usuario = mysqli_fetch_assoc($result);

$agora = time();
$cadastro = strtotime($usuario['data_cadastro']);
$assinaturaAtiva = $usuario['status_assinatura'] === 'ativa' && strtotime($usuario['data_fim_assinatura']) > $agora;
$dentroDoTrial = $usuario['status_assinatura'] === 'trial' && ($agora - $cadastro < 86400);

if (!$assinaturaAtiva && !$dentroDoTrial) {
    echo "<script>
        alert('Seu acesso expirou! Faça uma assinatura para continuar.');
        window.location.href = 'assinatura.php';
    </script>";
    exit;
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>

    <h1>Area dos Usuarios Assinantes</h1>
    
</body>
</html>