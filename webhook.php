<?php

include_once('config.php');

$payload = json_decode(file_get_contents("php://input"), true);
$idUsuario = $payload['id_usuario'] ?? null;

if ($idUsuario) {
    $inicio = date('Y-m-d H:i:s');
    $fim = date('Y-m-d H:i:s', strtotime('+30 days'));

    mysqli_query($conexao, "
        UPDATE usuarios SET
            status_assinatura = 'ativa',
            data_inicio_assinatura = '$inicio',
            data_fim_assinatura = '$fim'
        WHERE id = '$idUsuario'
    ");
}


?>


