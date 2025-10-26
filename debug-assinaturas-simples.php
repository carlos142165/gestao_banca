<?php
/**
 * Debug Simples - Apenas Assinaturas
 * Mostra CLARAMENTE os dados
 */

require_once 'config.php';
require_once 'carregar_sessao.php';

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Simples - Assinaturas</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
        }
        
        h2 {
            margin: 30px 0 15px 0;
            color: #333;
            border-bottom: 3px solid #667eea;
            padding-bottom: 10px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border: 1px solid #ddd;
        }
        
        th {
            background: #667eea;
            color: white;
            font-weight: bold;
        }
        
        tr:nth-child(even) {
            background: #f9f9f9;
        }
        
        .info {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            border-left: 4px solid #2196F3;
        }
        
        .resultado {
            background: #fff9c4;
            padding: 15px;
            border-radius: 4px;
            margin: 10px 0;
            font-weight: bold;
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
        <h1>🔍 Debug Simples - Assinaturas</h1>
        
        <div class="info">
            <strong>Data/Hora Atual:</strong> 
            <?php 
            $result = $conexao->query("SELECT NOW() as agora, DATE_ADD(NOW(), INTERVAL 30 DAY) as daqui_30_dias");
            $row = $result->fetch_assoc();
            echo "Hoje: <strong>" . $row['agora'] . "</strong><br>";
            echo "Data de 30 dias: <strong>" . $row['daqui_30_dias'] . "</strong>";
            ?>
        </div>
        
        <!-- TODOS OS USUÁRIOS COM ASSINATURA -->
        <h2>📋 Todos os Usuários COM Assinatura</h2>
        <p>(data_fim_assinatura IS NOT NULL AND id_plano IS NOT NULL)</p>
        
        <?php
        $result = $conexao->query("
            SELECT 
                u.id,
                u.nome,
                p.nome as nome_plano,
                u.data_fim_assinatura,
                DATEDIFF(u.data_fim_assinatura, NOW()) as dias_restantes
            FROM usuarios u
            LEFT JOIN planos p ON u.id_plano = p.id
            WHERE u.data_fim_assinatura IS NOT NULL
            AND u.id_plano IS NOT NULL
            ORDER BY u.data_fim_assinatura ASC
        ");
        
        echo '<table>';
        echo '<tr>';
        echo '<th>ID</th>';
        echo '<th>Nome</th>';
        echo '<th>Plano</th>';
        echo '<th>Data Fim Assinatura</th>';
        echo '<th>Dias Restantes</th>';
        echo '</tr>';
        
        $total_com_assinatura = 0;
        while ($row = $result->fetch_assoc()) {
            $total_com_assinatura++;
            echo '<tr>';
            echo '<td>' . $row['id'] . '</td>';
            echo '<td>' . $row['nome'] . '</td>';
            echo '<td>' . $row['nome_plano'] . '</td>';
            echo '<td>' . $row['data_fim_assinatura'] . '</td>';
            echo '<td>' . $row['dias_restantes'] . '</td>';
            echo '</tr>';
        }
        echo '</table>';
        echo '<div class="resultado">Total de usuários com assinatura: ' . $total_com_assinatura . '</div>';
        ?>
        
        <!-- CONTAGEM MENSAL -->
        <h2>📅 MENSAIS (próximos 30 dias)</h2>
        <p>Condição: data_fim_assinatura > NOW() AND data_fim_assinatura <= DATE_ADD(NOW(), INTERVAL 30 DAY)</p>
        
        <?php
        $result = $conexao->query("
            SELECT 
                u.id,
                u.nome,
                p.nome as nome_plano,
                u.data_fim_assinatura,
                DATEDIFF(u.data_fim_assinatura, NOW()) as dias_restantes
            FROM usuarios u
            LEFT JOIN planos p ON u.id_plano = p.id
            WHERE u.data_fim_assinatura IS NOT NULL
            AND u.id_plano IS NOT NULL
            AND u.data_fim_assinatura > NOW()
            AND u.data_fim_assinatura <= DATE_ADD(NOW(), INTERVAL 30 DAY)
            ORDER BY u.data_fim_assinatura ASC
        ");
        
        echo '<table>';
        echo '<tr>';
        echo '<th>ID</th>';
        echo '<th>Nome</th>';
        echo '<th>Plano</th>';
        echo '<th>Data Fim Assinatura</th>';
        echo '<th>Dias Restantes</th>';
        echo '</tr>';
        
        $total_mensal = 0;
        while ($row = $result->fetch_assoc()) {
            $total_mensal++;
            echo '<tr class="mensal">';
            echo '<td>' . $row['id'] . '</td>';
            echo '<td>' . $row['nome'] . '</td>';
            echo '<td>' . $row['nome_plano'] . '</td>';
            echo '<td>' . $row['data_fim_assinatura'] . '</td>';
            echo '<td>' . $row['dias_restantes'] . '</td>';
            echo '</tr>';
        }
        echo '</table>';
        echo '<div class="resultado mensal">Total MENSAL: ' . $total_mensal . '</div>';
        ?>
        
        <!-- CONTAGEM ANUAL -->
        <h2>⏰ ANUAIS (depois de 30 dias)</h2>
        <p>Condição: data_fim_assinatura > DATE_ADD(NOW(), INTERVAL 30 DAY)</p>
        
        <?php
        $result = $conexao->query("
            SELECT 
                u.id,
                u.nome,
                p.nome as nome_plano,
                u.data_fim_assinatura,
                DATEDIFF(u.data_fim_assinatura, NOW()) as dias_restantes
            FROM usuarios u
            LEFT JOIN planos p ON u.id_plano = p.id
            WHERE u.data_fim_assinatura IS NOT NULL
            AND u.id_plano IS NOT NULL
            AND u.data_fim_assinatura > DATE_ADD(NOW(), INTERVAL 30 DAY)
            ORDER BY u.data_fim_assinatura ASC
        ");
        
        echo '<table>';
        echo '<tr>';
        echo '<th>ID</th>';
        echo '<th>Nome</th>';
        echo '<th>Plano</th>';
        echo '<th>Data Fim Assinatura</th>';
        echo '<th>Dias Restantes</th>';
        echo '</tr>';
        
        $total_anual = 0;
        while ($row = $result->fetch_assoc()) {
            $total_anual++;
            echo '<tr class="anual">';
            echo '<td>' . $row['id'] . '</td>';
            echo '<td>' . $row['nome'] . '</td>';
            echo '<td>' . $row['nome_plano'] . '</td>';
            echo '<td>' . $row['data_fim_assinatura'] . '</td>';
            echo '<td>' . $row['dias_restantes'] . '</td>';
            echo '</tr>';
        }
        echo '</table>';
        echo '<div class="resultado anual">Total ANUAL: ' . $total_anual . '</div>';
        ?>
        
        <!-- RESUMO -->
        <h2>📊 Resumo Final</h2>
        <table>
            <tr>
                <th>Métrica</th>
                <th>Quantidade</th>
            </tr>
            <tr class="mensal">
                <td>Assinaturas MENSAIS</td>
                <td><strong><?php echo $total_mensal; ?></strong></td>
            </tr>
            <tr class="anual">
                <td>Assinaturas ANUAIS</td>
                <td><strong><?php echo $total_anual; ?></strong></td>
            </tr>
            <tr>
                <td>Total de Assinaturas</td>
                <td><strong><?php echo ($total_mensal + $total_anual); ?></strong></td>
            </tr>
        </table>
        
        <div style="text-align: center; margin-top: 30px;">
            <a href="administrativa.php" style="display: inline-block; padding: 12px 24px; background: #667eea; color: white; text-decoration: none; border-radius: 8px;">
                ← Voltar para Administrativa
            </a>
        </div>
    </div>
</body>
</html>
