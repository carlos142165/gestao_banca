<?php
/**
 * Diagnóstico - Verificar Assinaturas Mensais
 * 
 * Este arquivo mostra detalhes sobre como as assinaturas
 * estão sendo contabilizadas no banco de dados
 */

require_once 'config.php';
require_once 'carregar_sessao.php';

// Verificar se está logado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$id_usuario = $_SESSION['usuario_id'];

// Apenas admin pode acessar
if ($id_usuario != 23) {
    header('Location: home.php');
    exit;
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico - Assinaturas</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        }
        
        h1 {
            color: #667eea;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .secao {
            margin-bottom: 40px;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }
        
        .secao h2 {
            color: #333;
            margin-bottom: 15px;
            font-size: 18px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
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
        
        .status-ok {
            color: #27ae60;
            font-weight: bold;
        }
        
        .status-erro {
            color: #e74c3c;
            font-weight: bold;
        }
        
        .sql-box {
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 15px;
            border-radius: 8px;
            font-family: monospace;
            font-size: 12px;
            overflow-x: auto;
            margin-top: 10px;
        }
        
        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2196F3;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 15px;
            color: #1565c0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>
            <i class="fas fa-stethoscope"></i>
            Diagnóstico de Assinaturas
        </h1>
        
        <?php
        // Seção 1: Verificar Usuários com Assinatura
        echo '<div class="secao">';
        echo '<h2>📋 Usuários com Assinatura (id_plano IS NOT NULL)</h2>';
        
        $result = $conexao->query("
            SELECT 
                u.id,
                u.nome,
                p.nome as plano,
                u.data_fim_assinatura,
                DATEDIFF(u.data_fim_assinatura, NOW()) as dias_restantes
            FROM usuarios u
            LEFT JOIN planos p ON u.id_plano = p.id
            WHERE u.id_plano IS NOT NULL
            ORDER BY u.data_fim_assinatura DESC
        ");
        
        if ($result && $result->num_rows > 0) {
            echo '<table>';
            echo '<tr>';
            echo '<th>ID</th>';
            echo '<th>Nome</th>';
            echo '<th>Plano</th>';
            echo '<th>Data Fim</th>';
            echo '<th>Dias Restantes</th>';
            echo '</tr>';
            
            while ($row = $result->fetch_assoc()) {
                $dias = $row['dias_restantes'] ?? 0;
                $status = ($dias > 0) ? '<span class="status-ok">✅ ATIVO</span>' : '<span class="status-erro">❌ EXPIRADO</span>';
                
                echo '<tr>';
                echo '<td>' . $row['id'] . '</td>';
                echo '<td>' . $row['nome'] . '</td>';
                echo '<td>' . ($row['plano'] ?? 'Nenhum') . '</td>';
                echo '<td>' . ($row['data_fim_assinatura'] ?? 'N/A') . '</td>';
                echo '<td>' . $status . ' (' . $dias . ' dias)</td>';
                echo '</tr>';
            }
            
            echo '</table>';
        } else {
            echo '<p style="color: #e74c3c;"><strong>⚠️ Nenhum usuário com assinatura encontrado!</strong></p>';
        }
        
        echo '</div>';
        
        // Seção 2: Contagem por Tipo
        echo '<div class="secao">';
        echo '<h2>📊 Contagem de Assinaturas por Tipo</h2>';
        
        echo '<div class="info-box">';
        echo '💡 <strong>ANUAIS:</strong> Vencimento no próximo ano (YEAR(data_fim) > YEAR(NOW()))<br>';
        echo '💡 <strong>MENSAIS:</strong> Vencimento nos próximos 30 dias (NOW() <= data_fim <= NOW() + 30 DIAS)';
        echo '</div>';
        
        // Verificar Anuais
        echo '<h3 style="margin-top: 20px; margin-bottom: 10px;">🔵 Assinaturas ANUAIS:</h3>';
        echo '<div class="sql-box">';
        echo 'SELECT COUNT(*) FROM usuarios<br>';
        echo 'WHERE data_fim_assinatura IS NOT NULL<br>';
        echo 'AND id_plano IS NOT NULL<br>';
        echo 'AND YEAR(data_fim_assinatura) > YEAR(NOW())';
        echo '</div>';
        
        $result = $conexao->query("
            SELECT COUNT(*) as count FROM usuarios 
            WHERE data_fim_assinatura IS NOT NULL 
            AND id_plano IS NOT NULL
            AND YEAR(data_fim_assinatura) > YEAR(NOW())
        ");
        $anuais = $result->fetch_assoc()['count'];
        echo '<p style="margin-top: 10px;"><strong>Total ANUAL:</strong> <span class="status-ok">' . $anuais . '</span></p>';
        
        // Verificar Mensais
        echo '<h3 style="margin-top: 20px; margin-bottom: 10px;">🟢 Assinaturas MENSAIS (próximos 30 dias):</h3>';
        echo '<div class="sql-box">';
        echo 'SELECT COUNT(*) FROM usuarios<br>';
        echo 'WHERE data_fim_assinatura IS NOT NULL<br>';
        echo 'AND id_plano IS NOT NULL<br>';
        echo 'AND data_fim_assinatura >= NOW()<br>';
        echo 'AND data_fim_assinatura <= DATE_ADD(NOW(), INTERVAL 30 DAY)';
        echo '</div>';
        
        $result = $conexao->query("
            SELECT COUNT(*) as count FROM usuarios 
            WHERE data_fim_assinatura IS NOT NULL 
            AND id_plano IS NOT NULL
            AND data_fim_assinatura >= NOW()
            AND data_fim_assinatura <= DATE_ADD(NOW(), INTERVAL 30 DAY)
        ");
        $mensais = $result->fetch_assoc()['count'];
        echo '<p style="margin-top: 10px;"><strong>Total MENSAL:</strong> <span class="status-ok">' . $mensais . '</span></p>';
        
        echo '</div>';
        
        // Seção 3: Usuários que expiram nos próximos 30 dias
        echo '<div class="secao">';
        echo '<h2>🗓️ Usuários que Expiram nos Próximos 30 Dias</h2>';
        
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
            AND u.data_fim_assinatura >= NOW()
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
            echo '<th>Dias Restantes</th>';
            echo '</tr>';
            
            while ($row = $result->fetch_assoc()) {
                echo '<tr>';
                echo '<td>' . $row['id'] . '</td>';
                echo '<td>' . $row['nome'] . '</td>';
                echo '<td>' . $row['plano'] . '</td>';
                echo '<td>' . $row['data_fim_assinatura'] . '</td>';
                echo '<td><span class="status-ok">⏱️ ' . $row['dias_restantes'] . ' dias</span></td>';
                echo '</tr>';
            }
            
            echo '</table>';
        } else {
            echo '<p style="color: #999;">Nenhum usuário vencendo nos próximos 30 dias</p>';
        }
        
        echo '</div>';
        
        // Seção 4: Data Atual do Servidor
        echo '<div class="secao">';
        echo '<h2>🕐 Informações do Servidor</h2>';
        
        $result = $conexao->query("SELECT NOW() as agora, DATE_ADD(NOW(), INTERVAL 30 DAY) as proximos_30");
        $row = $result->fetch_assoc();
        
        echo '<p><strong>Data/Hora Atual do Servidor:</strong> ' . $row['agora'] . '</p>';
        echo '<p><strong>Data em +30 dias:</strong> ' . $row['proximos_30'] . '</p>';
        
        echo '</div>';
        
        // Seção 5: Distribuição
        echo '<div class="secao">';
        echo '<h2>📈 Distribuição de Usuários</h2>';
        
        $result = $conexao->query("
            SELECT 
                CASE 
                    WHEN id_plano IS NULL THEN 'Gratuito'
                    WHEN data_fim_assinatura IS NULL THEN 'Sem Assinatura'
                    WHEN data_fim_assinatura < NOW() THEN 'Expirado'
                    WHEN data_fim_assinatura <= DATE_ADD(NOW(), INTERVAL 30 DAY) THEN 'Mensal'
                    WHEN YEAR(data_fim_assinatura) > YEAR(NOW()) THEN 'Anual'
                    ELSE 'Outro'
                END as tipo,
                COUNT(*) as total
            FROM usuarios
            GROUP BY tipo
        ");
        
        echo '<table>';
        echo '<tr>';
        echo '<th>Tipo</th>';
        echo '<th>Quantidade</th>';
        echo '</tr>';
        
        while ($row = $result->fetch_assoc()) {
            echo '<tr>';
            echo '<td>' . $row['tipo'] . '</td>';
            echo '<td><strong>' . $row['total'] . '</strong></td>';
            echo '</tr>';
        }
        
        echo '</table>';
        
        echo '</div>';
        ?>
        
        <div style="text-align: center; margin-top: 40px;">
            <a href="administrativa.php" style="display: inline-block; padding: 12px 24px; background: #667eea; color: white; text-decoration: none; border-radius: 8px;">
                ← Voltar para Administrativa
            </a>
        </div>
    </div>
</body>
</html>
