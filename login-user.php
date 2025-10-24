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

// Verifica se é uma requisição AJAX
$isAjax = isset($_POST['ajax']) && $_POST['ajax'] === '1';

// 3. Busca o usuário no banco
$sql = "SELECT * FROM usuarios WHERE email = '$email'";
$result = $conexao->query($sql);

if ($result->num_rows > 0) {
    $usuario = $result->fetch_assoc();

    // 4. Verifica a senha
    if (password_verify($senha, $usuario['senha'])) {
        // 5. Salva o ID do usuário na sessão
        $_SESSION['usuario_id'] = $usuario['id'];
        
        // Se for AJAX, retorna "sucesso"
        if ($isAjax) {
            echo "sucesso";
            exit();
        }
        
        // Caso contrário, redireciona normalmente
        header("Location: gestao-diaria.php");
        exit();
    } else {
        // Senha incorreta
        if ($isAjax) {
            echo "Senha Incorreta";
            exit();
        }
        echo "<script>alert('Senha Incorreta. Tente Novamente.'); window.location.href = 'login.php';</script>";
        exit();
    }
} else {
    // E-mail não encontrado
    if ($isAjax) {
        echo "E-mail Não Cadastrado";
        exit();
    }
    echo "<script>alert('E-mail Não Cadastrado. Faça seu cadastro.'); window.location.href = 'formulario.php';</script>";
    exit();
}
?>







