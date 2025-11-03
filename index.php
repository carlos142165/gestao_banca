<?php
session_start();

// Verificar se usuário está autenticado
if (isset($_SESSION['usuario_id'])) {
    // Usuário autenticado, redireciona para home
    header("Location: home.php");
} else {
    // Usuário não autenticado, redireciona para login
    header("Location:  home.php");
}
exit();
?>
