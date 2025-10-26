<?php
/**
 * Verificação - 31 Dias vs 30 Dias
 */

require_once 'config.php';
require_once 'carregar_sessao.php';

$result = $conexao->query("SELECT NOW() as agora, DATE_ADD(NOW(), INTERVAL 30 DAY) as dia_30, DATE_ADD(NOW(), INTERVAL 31 DAY) as dia_31");
$datas = $result->fetch_assoc();

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Verificação 31 Dias</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
        }
        .box {
            background: #e3f2fd;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
            border-left: 4px solid #2196F3;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border: 1px solid #ddd;
        }
        th {
            background: #667eea;
            color: white;
        }
        .mensal {
            background: #c8e6c9;
        }
        .anual {
            background: #bbdefb;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>✅ Verificação - 31 Dias vs 30 Dias</h1>
        
        <div class="box">
            <strong>Hoje:</strong> <?php echo $datas['agora']; ?><br>
            <strong>Hoje + 30 dias:</strong> <?php echo $datas['dia_30']; ?><br>
            <strong>Hoje + 31 dias:</strong> <?php echo $datas['dia_31']; ?>
        </div>
        
        <h2>Com 30 DIAS (ANTIGO - ERRADO):</h2>
        <?php
        $result = $conexao->query("
            SELECT 
                u.id,
                u.nome,
                u.data_fim_assinatura,
                DATEDIFF(u.data_fim_assinatura, NOW()) as dias
            FROM usuarios u
            WHERE u.data_fim_assinatura IS NOT NULL 
            AND u.id_plano IS NOT NULL
            AND u.data_fim_assinatura > NOW()
            AND u.data_fim_assinatura <= DATE_ADD(NOW(), INTERVAL 30 DAY)
        ");
        
        echo '<table><tr><th>ID</th><th>Nome</th><th>Vence em</th><th>Dias</th></tr>';
        $count_30 = 0;
        while ($row = $result->fetch_assoc()) {
            $count_30++;
            echo '<tr class="mensal"><td>' . $row['id'] . '</td><td>' . $row['nome'] . '</td><td>' . $row['data_fim_assinatura'] . '</td><td>' . $row['dias'] . '</td></tr>';
        }
        echo '</table>';
        echo '<div class="box">Total com 30 dias: <strong>' . $count_30 . '</strong></div>';
        ?>
        
        <h2>Com 31 DIAS (NOVO - CORRETO):</h2>
        <?php
        $result = $conexao->query("
            SELECT 
                u.id,
                u.nome,
                u.data_fim_assinatura,
                DATEDIFF(u.data_fim_assinatura, NOW()) as dias
            FROM usuarios u
            WHERE u.data_fim_assinatura IS NOT NULL 
            AND u.id_plano IS NOT NULL
            AND u.data_fim_assinatura > NOW()
            AND u.data_fim_assinatura <= DATE_ADD(NOW(), INTERVAL 31 DAY)
        ");
        
        echo '<table><tr><th>ID</th><th>Nome</th><th>Vence em</th><th>Dias</th></tr>';
        $count_31 = 0;
        while ($row = $result->fetch_assoc()) {
            $count_31++;
            echo '<tr class="mensal"><td>' . $row['id'] . '</td><td>' . $row['nome'] . '</td><td>' . $row['data_fim_assinatura'] . '</td><td>' . $row['dias'] . '</td></tr>';
        }
        echo '</table>';
        echo '<div class="box">Total com 31 dias: <strong>' . $count_31 . '</strong></div>';
        ?>
        
        <div style="text-align: center; margin-top: 30px;">
            <a href="administrativa.php" style="display: inline-block; padding: 12px 24px; background: #667eea; color: white; text-decoration: none; border-radius: 8px;">
                ← Voltar para Administrativa
            </a>
        </div>
    </div>
</body>
</html>
