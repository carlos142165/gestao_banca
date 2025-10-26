<?php
/**
 * Debug Detalhado - Assinaturas
 * 
 * Mostra EXATAMENTE quais usuários estão sendo contados
 * como MENSAL e ANUAL
 */

require_once 'config.php';
require_once 'carregar_sessao.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_id'] != 23) {
    header('Location: login.php');
    exit;
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug - Assinaturas</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        h1 {
            color: #667eea;
            margin-bottom: 30px;
        }
        
        .info-box {
            background: #e3f2fd;
            border: 2px solid #2196F3;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            color: #1565c0;
        }
        
        .data-box {
            background: #f9f9f9;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background: #667eea;
            color: white;
            font-weight: 600;
        }
        
        tr:hover {
            background: #f5f5f5;
        }
        
        .status-mensal {
            background: #fff3cd;
            padding: 3px 8px;
            border-radius: 4px;
            font-weight: bold;
            color: #856404;
        }
        
        .status-anual {
            background: #d1ecf1;
            padding: 3px 8px;
            border-radius: 4px;
            font-weight: bold;
            color: #0c5460;
        }
        
        .sql-box {
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 15px;
            border-radius: 8px;
            font-family: monospace;
            overflow-x: auto;
            margin: 15px 0;
            font-size: 12px;
            line-height: 1.6;
        }
        
        .resultado {
            font-size: 18px;
            font-weight: bold;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }
        
        .resultado.mensal {
            background: #fff3cd;
            color: #856404;
            border: 2px solid #ffc107;
        }
        
        .resultado.anual {
            background: #d1ecf1;
            color: #0c5460;
            border: 2px solid #17a2b8;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Debug Detalhado - Assinaturas Mensais vs Anuais</h1>
        
        <div class="info-box">
            <strong>Data/Hora Atual do Servidor:</strong> 
            <?php 
            $result = $conexao->query("SELECT NOW() as agora");
            $row = $result->fetch_assoc();
            echo $row['agora'];
            ?>
        </div>
        
        <!-- Seção 1: Todos os usuários com assinatura -->
        <div class="data-box">
            <h2>📋 Todos os Usuários com Assinatura (data_fim_assinatura IS NOT NULL e id_plano IS NOT NULL)</h2>
            
            <?php
            $result = $conexao->query("
                SELECT 
                    u.id,
                    u.nome,
                    u.id_plano,
                    p.nome as plano,
                    u.data_fim_assinatura,
                    DATEDIFF(u.data_fim_assinatura, NOW()) as dias_restantes,
                    CASE 
                        WHEN u.data_fim_assinatura <= NOW() THEN 'EXPIRADO'
                        WHEN u.data_fim_assinatura <= DATE_ADD(NOW(), INTERVAL 30 DAY) THEN 'MENSAL (próx 30 dias)'
                        ELSE 'ANUAL (>30 dias)'
                    END as categoria
                FROM usuarios u
                LEFT JOIN planos p ON u.id_plano = p.id
                WHERE u.data_fim_assinatura IS NOT NULL
                AND u.id_plano IS NOT NULL
                ORDER BY u.data_fim_assinatura ASC
            ");
            
            if ($result && $result->num_rows > 0) {
                echo '<table>';
                echo '<tr>';
                echo '<th>ID</th>';
                echo '<th>Nome</th>';
                echo '<th>Plano</th>';
                echo '<th>Data Fim</th>';
                echo '<th>Dias Restantes</th>';
                echo '<th>Categoria</th>';
                echo '</tr>';
                
                while ($row = $result->fetch_assoc()) {
                    $categoria = $row['categoria'];
                    $classe = '';
                    if (strpos($categoria, 'MENSAL') !== false) {
                        $classe = 'status-mensal';
                    } elseif (strpos($categoria, 'ANUAL') !== false) {
                        $classe = 'status-anual';
                    }
                    
                    echo '<tr>';
                    echo '<td>' . $row['id'] . '</td>';
                    echo '<td>' . $row['nome'] . '</td>';
                    echo '<td>' . ($row['plano'] ?? 'N/A') . '</td>';
                    echo '<td>' . $row['data_fim_assinatura'] . '</td>';
                    echo '<td>' . $row['dias_restantes'] . ' dias</td>';
                    echo '<td><span class="' . $classe . '">' . $categoria . '</span></td>';
                    echo '</tr>';
                }
                echo '</table>';
            }
            ?>
        </div>
        
        <!-- Seção 2: Cálculo MENSAL -->
        <div class="data-box">
            <h2>📅 Contando MENSAIS</h2>
            
            <p><strong>Definição:</strong> data_fim_assinatura > NOW() AND data_fim_assinatura <= NOW() + 30 dias</p>
            
            <div class="sql-box">
SELECT COUNT(*) as count FROM usuarios <br>
WHERE data_fim_assinatura IS NOT NULL <br>
AND id_plano IS NOT NULL<br>
AND data_fim_assinatura > NOW()<br>
AND data_fim_assinatura <= DATE_ADD(NOW(), INTERVAL 30 DAY)
            </div>
            
            <?php
            $result = $conexao->query("
                SELECT COUNT(*) as count FROM usuarios 
                WHERE data_fim_assinatura IS NOT NULL 
                AND id_plano IS NOT NULL
                AND data_fim_assinatura > NOW()
                AND data_fim_assinatura <= DATE_ADD(NOW(), INTERVAL 30 DAY)
            ");
            $mensal_count = $result->fetch_assoc()['count'];
            echo '<div class="resultado mensal">Total MENSAL: <strong>' . $mensal_count . '</strong></div>';
            ?>
            
            <h3>Usuários enquadrados como MENSAL:</h3>
            <?php
            $result = $conexao->query("
                SELECT 
                    u.id,
                    u.nome,
                    p.nome as plano,
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
            
            if ($result && $result->num_rows > 0) {
                echo '<table>';
                echo '<tr>';
                echo '<th>ID</th>';
                echo '<th>Nome</th>';
                echo '<th>Plano</th>';
                echo '<th>Vence em</th>';
                echo '<th>Dias</th>';
                echo '</tr>';
                
                while ($row = $result->fetch_assoc()) {
                    echo '<tr>';
                    echo '<td>' . $row['id'] . '</td>';
                    echo '<td>' . $row['nome'] . '</td>';
                    echo '<td>' . ($row['plano'] ?? 'N/A') . '</td>';
                    echo '<td>' . $row['data_fim_assinatura'] . '</td>';
                    echo '<td><span class="status-mensal">' . $row['dias_restantes'] . ' dias</span></td>';
                    echo '</tr>';
                }
                echo '</table>';
            } else {
                echo '<p style="color: #666;">Nenhum usuário enquadrado como MENSAL</p>';
            }
            ?>
        </div>
        
        <!-- Seção 3: Cálculo ANUAL -->
        <div class="data-box">
            <h2>⏰ Contando ANUAIS</h2>
            
            <p><strong>Definição:</strong> data_fim_assinatura > NOW() + 30 dias</p>
            
            <div class="sql-box">
SELECT COUNT(*) as count FROM usuarios <br>
WHERE data_fim_assinatura IS NOT NULL <br>
AND id_plano IS NOT NULL<br>
AND data_fim_assinatura > DATE_ADD(NOW(), INTERVAL 30 DAY)
            </div>
            
            <?php
            $result = $conexao->query("
                SELECT COUNT(*) as count FROM usuarios 
                WHERE data_fim_assinatura IS NOT NULL 
                AND id_plano IS NOT NULL
                AND data_fim_assinatura > DATE_ADD(NOW(), INTERVAL 30 DAY)
            ");
            $anual_count = $result->fetch_assoc()['count'];
            echo '<div class="resultado anual">Total ANUAL: <strong>' . $anual_count . '</strong></div>';
            ?>
            
            <h3>Usuários enquadrados como ANUAL:</h3>
            <?php
            $result = $conexao->query("
                SELECT 
                    u.id,
                    u.nome,
                    p.nome as plano,
                    u.data_fim_assinatura,
                    DATEDIFF(u.data_fim_assinatura, NOW()) as dias_restantes
                FROM usuarios u
                LEFT JOIN planos p ON u.id_plano = p.id
                WHERE u.data_fim_assinatura IS NOT NULL 
                AND u.id_plano IS NOT NULL
                AND u.data_fim_assinatura > DATE_ADD(NOW(), INTERVAL 30 DAY)
                ORDER BY u.data_fim_assinatura ASC
            ");
            
            if ($result && $result->num_rows > 0) {
                echo '<table>';
                echo '<tr>';
                echo '<th>ID</th>';
                echo '<th>Nome</th>';
                echo '<th>Plano</th>';
                echo '<th>Vence em</th>';
                echo '<th>Dias</th>';
                echo '</tr>';
                
                while ($row = $result->fetch_assoc()) {
                    echo '<tr>';
                    echo '<td>' . $row['id'] . '</td>';
                    echo '<td>' . $row['nome'] . '</td>';
                    echo '<td>' . ($row['plano'] ?? 'N/A') . '</td>';
                    echo '<td>' . $row['data_fim_assinatura'] . '</td>';
                    echo '<td><span class="status-anual">' . $row['dias_restantes'] . ' dias</span></td>';
                    echo '</tr>';
                }
                echo '</table>';
            } else {
                echo '<p style="color: #666;">Nenhum usuário enquadrado como ANUAL</p>';
            }
            ?>
        </div>
        
        <!-- Resumo -->
        <div class="data-box">
            <h2>📊 Resumo Final</h2>
            <table>
                <tr>
                    <td><strong>Total de Assinaturas Ativas:</strong></td>
                    <td><?php echo ($mensal_count + $anual_count); ?></td>
                </tr>
                <tr>
                    <td><strong>MENSAL (próximos 30 dias):</strong></td>
                    <td><span class="status-mensal"><?php echo $mensal_count; ?></span></td>
                </tr>
                <tr>
                    <td><strong>ANUAL (depois de 30 dias):</strong></td>
                    <td><span class="status-anual"><?php echo $anual_count; ?></span></td>
                </tr>
            </table>
        </div>
        
        <div style="text-align: center; margin-top: 30px;">
            <a href="administrativa.php" style="display: inline-block; padding: 12px 24px; background: #667eea; color: white; text-decoration: none; border-radius: 8px;">
                ← Voltar para Administrativa
            </a>
        </div>
    </div>
</body>
</html>
