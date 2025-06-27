<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Recuperar Senha</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #f2f5f9;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .form-container {
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        .form-container h2 {
            margin-bottom: 20px;
            color: #333;
        }

        .form-container input {
            width: 100%;
            padding: 12px;
            margin: 8px 0;
            font-size: 15px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }

        .form-container button {
            width: 100%;
            background-color: #007bff;
            color: white;
            padding: 12px;
            margin-top: 10px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .form-container button:hover {
            background-color: #0056b3;
        }

        a {
            color: #007bff;
        }
    </style>
</head>
<body>
<?php
$pdo = new PDO("mysql:host=localhost;dbname=formulario-carlos", "root", "");
session_start();

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["email"])) {
    $email = $_POST["email"];
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        $token = bin2hex(random_bytes(32));
        $expira = date("Y-m-d H:i:s", strtotime("+1 hour"));
        $stmt = $pdo->prepare("UPDATE usuarios SET token_recuperacao = ?, token_expira = ? WHERE email = ?");
        $stmt->execute([$token, $expira, $email]);

        $protocolo = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";
        $host = $_SERVER['HTTP_HOST'];
        $caminho = dirname($_SERVER['PHP_SELF']);
        $link = $protocolo . $host . $caminho . "/recuperar_senha.php?token=$token";

        echo "<div class='form-container'>";
        echo "Link de recuperação:<br><a href='$link'>$link</a><br>(simulando envio de email)";
        echo "</div>";
    } else {
        echo "<div class='form-container'>Email não Cadastrado.</div>";
    }
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["token"])) {
    $nova = $_POST["nova_senha"];
    $confirma = $_POST["confirma_senha"];
    $token = $_POST["token"];

    if ($nova !== $confirma) {
        die("<div class='form-container'>As senhas não coincidem.</div>");
    }

    $hash = password_hash($nova, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE usuarios SET senha = ?, token_recuperacao = NULL, token_expira = NULL WHERE token_recuperacao = ?");
    $stmt->execute([$hash, $token]);

    echo "<div class='form-container'>Senha alterada com sucesso!</div>";
    exit;
}

echo "<div class='form-container'>";
if (isset($_GET["token"])) {
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE token_recuperacao = ? AND token_expira > NOW()");
    $stmt->execute([$_GET["token"]]);
    $user = $stmt->fetch();

    if ($user) {
        ?>
        <h2>Nova Senha</h2>
        <form method="POST">
            <input type="hidden" name="token" value="<?= $_GET["token"] ?>">
            <input type="password" name="nova_senha" placeholder="Nova senha" required>
            <input type="password" name="confirma_senha" placeholder="Confirmar senha" required>
            <button type="submit">Salvar nova senha</button>
        </form>
        <?php
    } else {
        echo "Token inválido ou expirado.";
    }
} else {
    ?>
    <h2>Recuperar Senha</h2>
    <form method="POST">
        <input type="email" name="email" placeholder="Seu email" required>
        <button type="submit">Enviar link</button>
    </form>
    <?php
}
echo "</div>";
?>
</body>
</html>
