<?php
/**
 * TESTE VISUAL - MOSTRAR BADGE DE PLANO
 * =====================================
 * Página de teste com o badge do plano visível
 */

require_once 'config.php';
require_once 'carregar_sessao.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$id_usuario = $_SESSION['usuario_id'];

// Buscar dados do plano
try {
    $query = "
        SELECT 
            u.id,
            u.email,
            u.id_plano,
            u.data_fim_assinatura,
            p.nome as plano_nome,
            p.icone as plano_icone,
            p.cor_tema as plano_cor
        FROM usuarios u
        LEFT JOIN planos p ON u.id_plano = p.id
        WHERE u.id = ?
        LIMIT 1
    ";
    
    $stmt = $conexao->prepare($query);
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    $usuario = $result->fetch_assoc();
    $stmt->close();
    
    if (!$usuario['plano_nome']) {
        $usuario['plano_nome'] = 'GRATUITO';
        $usuario['plano_icone'] = 'fas fa-gift';
        $usuario['plano_cor'] = '#95a5a6';
    }
    
    // Calcular dias restantes
    $dias_restantes = null;
    if ($usuario['data_fim_assinatura']) {
        $data_fim = new DateTime($usuario['data_fim_assinatura']);
        $hoje = new DateTime();
        $diferenca = $data_fim->diff($hoje);
        $dias_restantes = $diferenca->days;
    }
    
} catch (Exception $e) {
    $usuario = null;
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste Visual - Plano do Usuário</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
        }
        .header {
            background: #2c3e50;
            color: white;
            padding: 20px;
            text-align: center;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .info {
            background: #ecf0f1;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🧪 Teste Visual - Plano do Usuário</h1>
        <p>Usuário: <?php echo $usuario['email']; ?></p>
    </div>

    <div id="badge-plano-usuario"></div>

    <div class="container">
        <div class="info">
            <h3>📊 Dados do Usuário:</h3>
            <pre><?php echo json_encode($usuario, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE); ?></pre>
        </div>

        <div class="info">
            <h3>🧪 Teste do Script:</h3>
            <p>Abra o console (F12) e veja se aparecem logs começando com 📍</p>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <script src="js/plano-display.js"></script>
</body>
</html>
