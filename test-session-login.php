<?php
/**
 * ✅ TESTE RÁPIDO DE LOGIN - VALIDAR SESSÃO
 */

session_start();

// Simular login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['usuario_id'] = 23; // ID do Carlos
    $_SESSION['teste'] = time();
    
    echo "<h1>✅ Login Simulado com Sucesso!</h1>";
    echo "Session ID: " . session_id() . "<br>";
    echo "Usuario ID: " . $_SESSION['usuario_id'] . "<br>";
    echo "Timestamp: " . $_SESSION['teste'] . "<br>";
    echo "<br>";
    echo "<a href='test-session-check.php'>Verificar Sessão na Próxima Página →</a>";
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Teste de Sessão</title>
    <style>
        body { font-family: Arial; margin: 50px; }
        form { padding: 20px; background: #f0f0f0; border-radius: 5px; max-width: 300px; }
        button { padding: 10px 20px; background: #667eea; color: white; border: none; cursor: pointer; border-radius: 3px; }
    </style>
</head>
<body>
    <h1>Teste de Login & Sessão</h1>
    
    <form method="POST">
        <p>Clique para simular login do Carlos (ID: 23)</p>
        <button type="submit">Fazer Login</button>
    </form>
    
    <hr>
    <p>Este teste verifica se as sessões estão funcionando corretamente em produção.</p>
</body>
</html>
