<?php
/**
 * ✅ VERIFICAR SESSÃO CRIADA
 */

session_start();

?>
<!DOCTYPE html>
<html>
<head>
    <title>Verificar Sessão</title>
    <style>
        body { font-family: Arial; margin: 50px; }
        .status { padding: 20px; border-radius: 5px; }
        .ok { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <h1>Verificação de Sessão</h1>
    
    <?php
    if (isset($_SESSION['usuario_id'])) {
        echo "<div class='status ok'>";
        echo "<h2>✅ SESSÃO ATIVA</h2>";
        echo "Session ID: " . session_id() . "<br>";
        echo "Usuario ID: " . $_SESSION['usuario_id'] . "<br>";
        echo "Timestamp: " . $_SESSION['teste'] . "<br>";
        echo "</div>";
        
        echo "<hr>";
        echo "<h3>Próximos passos:</h3>";
        echo "<ol>";
        echo "<li>Tente fazer login real em: <a href='login.php'>login.php</a></li>";
        echo "<li>Se o login funcionar, você será redirecionado para <a href='gestao-diaria.php'>gestao-diaria.php</a></li>";
        echo "</ol>";
    } else {
        echo "<div class='status error'>";
        echo "<h2>❌ SESSÃO VAZIA</h2>";
        echo "Session ID: " . session_id() . "<br>";
        echo "<p>Nenhuma sessão foi criada. Volte e faça login primeiro:</p>";
        echo "<a href='test-session-login.php'>← Voltar para teste</a>";
        echo "</div>";
    }
    ?>
</body>
</html>
