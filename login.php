<?php
session_start();
$dbHost = 'localhost';
$dbUsername = 'root';
$dbPassword = '';
$dbname = 'formulario-carlos';

$conexao = new mysqli($dbHost, $dbUsername, $dbPassword, $dbname);
if ($conexao->connect_error) {
    die("Erro na conexão: " . $conexao->connect_error);
}

$email = $conexao->real_escape_string($_POST['email']);
$senha = $_POST['senha'];

$sql = "SELECT * FROM usuarios WHERE email = '$email'";
$result = $conexao->query($sql);

if ($result->num_rows > 0) {
    $usuario = $result->fetch_assoc();

    if (password_verify($senha, $usuario['senha'])) {
        $_SESSION['usuario'] = $usuario['nome'];
        header("Location: home.html");
        exit();
    } else {
        header("Location: login.html?erro=senha");
        exit();
    }
} else {
    header("Location: login.html?erro=1&email=" . urlencode($email));
}
?>







