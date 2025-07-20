

<?php
session_start();

// 1. Conexão com o banco de dados
$dbHost = 'localhost';
$dbUsername = 'root';
$dbPassword = '';
$dbname = 'formulario-carlos';

$conexao = new mysqli($dbHost, $dbUsername, $dbPassword, $dbname);

if ($conexao->connect_error) {
    die("Erro na conexão: " . $conexao->connect_error);
}

// 2. Pega os dados do formulário
$email = $conexao->real_escape_string($_POST['email']);
$senha = $_POST['senha'];

// 3. Busca o usuário no banco
$sql = "SELECT * FROM usuarios WHERE email = '$email'";
$result = $conexao->query($sql);

if ($result->num_rows > 0) {
    $usuario = $result->fetch_assoc();

    // 4. Verifica a senha
    if (password_verify($senha, $usuario['senha'])) {
        // 5. Salva o ID do usuário na sessão
        $_SESSION['usuario_id'] = $usuario['id'];
        header("Location: painel-controle.php"); // Redirecione conforme preferir
        exit();
    } else {
        // Senha incorreta
        echo "<script>alert('Senha Incorreta. Tente Novamente.'); window.location.href = 'login.php';</script>";
        exit();
    }
} else {
    // E-mail não encontrado
    echo "<script>alert('E-mail Não Cadastrado. Faça seu cadastro.'); window.location.href = 'formulario.php';</script>";
    exit();
}
?>







