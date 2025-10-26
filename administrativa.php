<?php
// ==================================================================================================================== 
// ========================== ÁREA ADMINISTRATIVA - ARQUIVO ÚNICO ==========================
// ==================================================================================================================== 

session_start();
require_once 'config.php';
require_once 'carregar_sessao.php';

// ==================================================================================================================== 
// ========================== VERIFICAÇÃO DE ACESSO ==========================
// ==================================================================================================================== 

// IDs com acesso administrativo
$ADMIN_IDS = [23]; // Apenas ID 23 tem acesso

$id_usuario = $_SESSION['usuario_id'] ?? null;

// Verificar se é admin
if (!in_array($id_usuario, $ADMIN_IDS)) {
    header('Location: home.php');
    exit;
}

// ==================================================================================================================== 
// ========================== FUNÇÕES DE DADOS ==========================
// ==================================================================================================================== 

function obterEstatisticas() {
    global $conexao;
    
    $stats = [
        'total_usuarios' => 0,
        'usuarios_plano_gratuito' => 0,
        'usuarios_plano_prata' => 0,
        'usuarios_plano_ouro' => 0,
        'usuarios_plano_diamante' => 0,
        'visitas_ativas' => 0,
        'assinaturas_anuais' => 0,
        'assinaturas_mensais' => 0,
        'usuarios_ativos_24h' => 0,
    ];
    
    try {
        // Total de usuários
        $result = $conexao->query("SELECT COUNT(*) as count FROM usuarios");
        $stats['total_usuarios'] = $result->fetch_assoc()['count'];
        
        // Usuários por plano
        $result = $conexao->query("
            SELECT p.nome, COUNT(u.id) as count 
            FROM usuarios u
            LEFT JOIN planos p ON u.id_plano = p.id
            GROUP BY u.id_plano
        ");
        
        while ($row = $result->fetch_assoc()) {
            $plano = strtolower($row['nome'] ?? '');
            if (strpos($plano, 'gratuito') !== false) {
                $stats['usuarios_plano_gratuito'] = $row['count'];
            } elseif (strpos($plano, 'prata') !== false) {
                $stats['usuarios_plano_prata'] = $row['count'];
            } elseif (strpos($plano, 'ouro') !== false) {
                $stats['usuarios_plano_ouro'] = $row['count'];
            } elseif (strpos($plano, 'diamante') !== false) {
                $stats['usuarios_plano_diamante'] = $row['count'];
            }
        }
        
        // Assinaturas anuais e mensais
        $result = $conexao->query("
            SELECT COUNT(*) as count FROM usuarios 
            WHERE data_fim_assinatura IS NOT NULL 
            AND YEAR(data_fim_assinatura) > YEAR(NOW())
        ");
        $stats['assinaturas_anuais'] = $result->fetch_assoc()['count'];
        
        $result = $conexao->query("
            SELECT COUNT(*) as count FROM usuarios 
            WHERE data_fim_assinatura IS NOT NULL 
            AND MONTH(data_fim_assinatura) = MONTH(NOW())
            AND YEAR(data_fim_assinatura) = YEAR(NOW())
        ");
        $stats['assinaturas_mensais'] = $result->fetch_assoc()['count'];
        
        // Usuários ativos nas últimas 24h
        $result = $conexao->query("
            SELECT COUNT(DISTINCT usuario_id) as count FROM admin_logs 
            WHERE data_criacao >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ");
        $stats['usuarios_ativos_24h'] = $result->fetch_assoc()['count'] ?? 0;
        
    } catch (Exception $e) {
        error_log("Erro ao obter estatísticas: " . $e->getMessage());
    }
    
    return $stats;
}

// Obter estatísticas
$stats = obterEstatisticas();

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Área Administrativa</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* ==================================================================================================================== */
        /* ========================== RESET E VARIÁVEIS ==========================                                */
        /* ==================================================================================================================== */
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --cor-principal: #667eea;
            --cor-secundaria: #764ba2;
            --cor-sucesso: #10b981;
            --cor-aviso: #f59e0b;
            --cor-perigo: #ef4444;
            --cor-fundo: #f8fafc;
            --cor-borda: #e2e8f0;
            --cor-texto: #1e293b;
            --cor-texto-claro: #64748b;
            --sombra-leve: 0 1px 3px rgba(0, 0, 0, 0.1);
            --sombra-media: 0 4px 12px rgba(0, 0, 0, 0.15);
            --transicao: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        body {
            font-family: 'Rajdhani', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, var(--cor-principal) 0%, var(--cor-secundaria) 100%);
            min-height: 100vh;
            padding: 20px;
            color: var(--cor-texto);
        }
        
        /* ==================================================================================================================== */
        /* ========================== HEADER ==========================                                */
        /* ==================================================================================================================== */
        
        .header-admin {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: var(--sombra-media);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .header-admin h1 {
            font-size: 28px;
            font-weight: 700;
            color: var(--cor-principal);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .header-admin h1 i {
            font-size: 32px;
        }
        
        .btn-voltar {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: var(--cor-fundo);
            color: var(--cor-texto);
            border: 2px solid var(--cor-borda);
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: var(--transicao);
            text-decoration: none;
        }
        
        .btn-voltar:hover {
            background: var(--cor-principal);
            color: white;
            border-color: var(--cor-principal);
        }
        
        /* ==================================================================================================================== */
        /* ========================== CONTAINER ==========================                                */
        /* ==================================================================================================================== */
        
        .container-admin {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        /* ==================================================================================================================== */
        /* ========================== GRID DE CARDS ==========================                                */
        /* ==================================================================================================================== */
        
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .card-stat {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: var(--sombra-media);
            transition: var(--transicao);
            border-top: 4px solid var(--cor-principal);
            position: relative;
            overflow: hidden;
        }
        
        .card-stat:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }
        
        .card-stat.total {
            --cor-principal: #667eea;
        }
        
        .card-stat.gratuito {
            --cor-principal: #6366f1;
        }
        
        .card-stat.prata {
            --cor-principal: #a78bfa;
        }
        
        .card-stat.ouro {
            --cor-principal: #fbbf24;
        }
        
        .card-stat.diamante {
            --cor-principal: #ec4899;
        }
        
        .card-stat.visitas {
            --cor-principal: #10b981;
        }
        
        .card-stat.anual {
            --cor-principal: #3b82f6;
        }
        
        .card-stat.mensal {
            --cor-principal: #f59e0b;
        }
        
        .card-stat.ativo {
            --cor-principal: #06b6d4;
        }
        
        .card-stat::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200px;
            height: 200px;
            background: rgba(102, 126, 234, 0.1);
            border-radius: 50%;
        }
        
        .card-stat-content {
            position: relative;
            z-index: 1;
        }
        
        .card-stat-label {
            font-size: 13px;
            color: var(--cor-texto-claro);
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.5px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .card-stat-label i {
            font-size: 16px;
            color: var(--cor-principal);
        }
        
        .card-stat-valor {
            font-size: 32px;
            font-weight: 700;
            color: var(--cor-principal);
            margin-bottom: 8px;
        }
        
        .card-stat-subtext {
            font-size: 12px;
            color: var(--cor-texto-claro);
        }
        
        /* ==================================================================================================================== */
        /* ========================== SEÇÃO DE RESUMO ==========================                                */
        /* ==================================================================================================================== */
        
        .secao-resumo {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: var(--sombra-media);
            margin-bottom: 30px;
        }
        
        .secao-resumo h2 {
            font-size: 22px;
            color: var(--cor-principal);
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--cor-borda);
        }
        
        .secao-resumo h2 i {
            font-size: 26px;
        }
        
        .resumo-tabela {
            width: 100%;
            border-collapse: collapse;
        }
        
        .resumo-tabela tr {
            border-bottom: 1px solid var(--cor-borda);
            transition: var(--transicao);
        }
        
        .resumo-tabela tr:hover {
            background: var(--cor-fundo);
        }
        
        .resumo-tabela tr:last-child {
            border-bottom: none;
        }
        
        .resumo-tabela td {
            padding: 15px 0;
            font-size: 14px;
        }
        
        .resumo-tabela td:first-child {
            color: var(--cor-texto-claro);
            font-weight: 600;
        }
        
        .resumo-tabela td:last-child {
            text-align: right;
            font-weight: 700;
            color: var(--cor-principal);
            font-size: 18px;
        }
        
        /* ==================================================================================================================== */
        /* ========================== BADGE ==========================                                */
        /* ==================================================================================================================== */
        
        .badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .badge-sucesso {
            background: rgba(16, 185, 129, 0.1);
            color: var(--cor-sucesso);
        }
        
        .badge-aviso {
            background: rgba(245, 158, 11, 0.1);
            color: var(--cor-aviso);
        }
        
        /* ==================================================================================================================== */
        /* ========================== RESPONSIVO ==========================                                */
        /* ==================================================================================================================== */
        
        @media (max-width: 768px) {
            .header-admin {
                flex-direction: column;
                text-align: center;
            }
            
            .header-admin h1 {
                width: 100%;
            }
            
            .header-admin .btn-voltar {
                width: 100%;
                justify-content: center;
            }
            
            .cards-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 15px;
            }
            
            .card-stat {
                padding: 20px;
            }
            
            .card-stat-valor {
                font-size: 24px;
            }
            
            .secao-resumo {
                padding: 20px;
            }
            
            .secao-resumo h2 {
                font-size: 18px;
            }
            
            .resumo-tabela td {
                padding: 12px 0;
            }
            
            .resumo-tabela td:last-child {
                font-size: 16px;
            }
        }
        
        @media (max-width: 480px) {
            body {
                padding: 10px;
            }
            
            .header-admin {
                padding: 15px;
                margin-bottom: 20px;
            }
            
            .header-admin h1 {
                font-size: 22px;
            }
            
            .cards-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }
            
            .card-stat {
                padding: 15px;
            }
            
            .card-stat-valor {
                font-size: 28px;
            }
            
            .secao-resumo {
                padding: 15px;
                margin-bottom: 20px;
            }
            
            .secao-resumo h2 {
                font-size: 16px;
            }
        }
        
        /* ==================================================================================================================== */
        /* ========================== ANIMAÇÕES ==========================                                */
        /* ==================================================================================================================== */
        
        @keyframes fade-in {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .card-stat {
            animation: fade-in 0.5s ease-out forwards;
        }
        
        .card-stat:nth-child(1) { animation-delay: 0.1s; }
        .card-stat:nth-child(2) { animation-delay: 0.2s; }
        .card-stat:nth-child(3) { animation-delay: 0.3s; }
        .card-stat:nth-child(4) { animation-delay: 0.4s; }
        .card-stat:nth-child(5) { animation-delay: 0.5s; }
        .card-stat:nth-child(6) { animation-delay: 0.6s; }
        .card-stat:nth-child(7) { animation-delay: 0.7s; }
        .card-stat:nth-child(8) { animation-delay: 0.8s; }
        .card-stat:nth-child(9) { animation-delay: 0.9s; }
        
        .secao-resumo {
            animation: fade-in 0.7s ease-out;
        }
    </style>
</head>
<body>

<!-- ==================================================================================================================== -->
<!-- ========================== HEADER ==========================                                -->
<!-- ==================================================================================================================== -->

<div class="header-admin">
    <h1>
        <i class="fas fa-chart-line"></i>
        Área Administrativa
    </h1>
    <a href="gestao-diaria.php" class="btn-voltar">
        <i class="fas fa-arrow-left"></i>
        Voltar
    </a>
</div>

<!-- ==================================================================================================================== -->
<!-- ========================== CONTAINER ==========================                                -->
<!-- ==================================================================================================================== -->

<div class="container-admin">
    
    <!-- ==================================================================================================================== -->
    <!-- ========================== GRID DE ESTATÍSTICAS ==========================                                -->
    <!-- ==================================================================================================================== -->
    
    <div class="cards-grid">
        <!-- Total de usuários -->
        <div class="card-stat total">
            <div class="card-stat-content">
                <div class="card-stat-label">
                    <i class="fas fa-users"></i>
                    Total de Usuários
                </div>
                <div class="card-stat-valor"><?php echo $stats['total_usuarios']; ?></div>
                <div class="card-stat-subtext">Todos os usuários cadastrados</div>
            </div>
        </div>
        
        <!-- Plano Gratuito -->
        <div class="card-stat gratuito">
            <div class="card-stat-content">
                <div class="card-stat-label">
                    <i class="fas fa-gift"></i>
                    Plano Gratuito
                </div>
                <div class="card-stat-valor"><?php echo $stats['usuarios_plano_gratuito']; ?></div>
                <div class="card-stat-subtext">
                    <span class="badge badge-sucesso">
                        <?php echo round(($stats['usuarios_plano_gratuito'] / max($stats['total_usuarios'], 1)) * 100); ?>%
                    </span>
                </div>
            </div>
        </div>
        
        <!-- Plano Prata -->
        <div class="card-stat prata">
            <div class="card-stat-content">
                <div class="card-stat-label">
                    <i class="fas fa-star"></i>
                    Plano Prata
                </div>
                <div class="card-stat-valor"><?php echo $stats['usuarios_plano_prata']; ?></div>
                <div class="card-stat-subtext">
                    <span class="badge badge-aviso">
                        <?php echo round(($stats['usuarios_plano_prata'] / max($stats['total_usuarios'], 1)) * 100); ?>%
                    </span>
                </div>
            </div>
        </div>
        
        <!-- Plano Ouro -->
        <div class="card-stat ouro">
            <div class="card-stat-content">
                <div class="card-stat-label">
                    <i class="fas fa-crown"></i>
                    Plano Ouro
                </div>
                <div class="card-stat-valor"><?php echo $stats['usuarios_plano_ouro']; ?></div>
                <div class="card-stat-subtext">
                    <span class="badge badge-sucesso">
                        <?php echo round(($stats['usuarios_plano_ouro'] / max($stats['total_usuarios'], 1)) * 100); ?>%
                    </span>
                </div>
            </div>
        </div>
        
        <!-- Plano Diamante -->
        <div class="card-stat diamante">
            <div class="card-stat-content">
                <div class="card-stat-label">
                    <i class="fas fa-gem"></i>
                    Plano Diamante
                </div>
                <div class="card-stat-valor"><?php echo $stats['usuarios_plano_diamante']; ?></div>
                <div class="card-stat-subtext">
                    <span class="badge badge-sucesso">
                        <?php echo round(($stats['usuarios_plano_diamante'] / max($stats['total_usuarios'], 1)) * 100); ?>%
                    </span>
                </div>
            </div>
        </div>
        
        <!-- Visitas Ativas -->
        <div class="card-stat visitas">
            <div class="card-stat-content">
                <div class="card-stat-label">
                    <i class="fas fa-globe"></i>
                    Usuários Online
                </div>
                <div class="card-stat-valor"><?php echo $stats['usuarios_ativos_24h']; ?></div>
                <div class="card-stat-subtext">Ativos nas últimas 24h</div>
            </div>
        </div>
        
        <!-- Assinaturas Anuais -->
        <div class="card-stat anual">
            <div class="card-stat-content">
                <div class="card-stat-label">
                    <i class="fas fa-calendar-days"></i>
                    Assinaturas Anuais
                </div>
                <div class="card-stat-valor"><?php echo $stats['assinaturas_anuais']; ?></div>
                <div class="card-stat-subtext">Planos de 12 meses</div>
            </div>
        </div>
        
        <!-- Assinaturas Mensais -->
        <div class="card-stat mensal">
            <div class="card-stat-content">
                <div class="card-stat-label">
                    <i class="fas fa-calendar-alt"></i>
                    Assinaturas Mensais
                </div>
                <div class="card-stat-valor"><?php echo $stats['assinaturas_mensais']; ?></div>
                <div class="card-stat-subtext">Planos de 1 mês</div>
            </div>
        </div>
        
        <!-- Receita Total -->
        <div class="card-stat ativo">
            <div class="card-stat-content">
                <div class="card-stat-label">
                    <i class="fas fa-chart-pie"></i>
                    Taxa de Conversão
                </div>
                <div class="card-stat-valor">
                    <?php 
                        $pagos = $stats['assinaturas_anuais'] + $stats['assinaturas_mensais'];
                        $conversao = round(($pagos / max($stats['total_usuarios'], 1)) * 100);
                        echo $conversao . '%';
                    ?>
                </div>
                <div class="card-stat-subtext">Usuários com pagamento</div>
            </div>
        </div>
    </div>
    
    <!-- ==================================================================================================================== -->
    <!-- ========================== RESUMO COMPLETO ==========================                                -->
    <!-- ==================================================================================================================== -->
    
    <div class="secao-resumo">
        <h2>
            <i class="fas fa-table"></i>
            Resumo Geral do Sistema
        </h2>
        
        <table class="resumo-tabela">
            <tr>
                <td>📊 Total de Usuários</td>
                <td><?php echo $stats['total_usuarios']; ?></td>
            </tr>
            <tr>
                <td>🎁 Usuários em Plano Gratuito</td>
                <td><?php echo $stats['usuarios_plano_gratuito']; ?></td>
            </tr>
            <tr>
                <td>⭐ Usuários em Plano Prata</td>
                <td><?php echo $stats['usuarios_plano_prata']; ?></td>
            </tr>
            <tr>
                <td>👑 Usuários em Plano Ouro</td>
                <td><?php echo $stats['usuarios_plano_ouro']; ?></td>
            </tr>
            <tr>
                <td>💎 Usuários em Plano Diamante</td>
                <td><?php echo $stats['usuarios_plano_diamante']; ?></td>
            </tr>
            <tr>
                <td>🌐 Usuários Ativos (24h)</td>
                <td><?php echo $stats['usuarios_ativos_24h']; ?></td>
            </tr>
            <tr>
                <td>📅 Assinaturas Anuais</td>
                <td><?php echo $stats['assinaturas_anuais']; ?></td>
            </tr>
            <tr>
                <td>📆 Assinaturas Mensais</td>
                <td><?php echo $stats['assinaturas_mensais']; ?></td>
            </tr>
            <tr>
                <td>💰 Total de Assinaturas Pagas</td>
                <td><?php echo ($stats['assinaturas_anuais'] + $stats['assinaturas_mensais']); ?></td>
            </tr>
            <tr>
                <td>📈 Taxa de Conversão</td>
                <td><?php echo round((($stats['assinaturas_anuais'] + $stats['assinaturas_mensais']) / max($stats['total_usuarios'], 1)) * 100); ?>%</td>
            </tr>
        </table>
    </div>
</div>

<script>
    // ==================================================================================================================== 
    // ========================== JAVASCRIPT ==========================
    // ==================================================================================================================== 
    
    // Atualizar dados a cada 30 segundos
    setInterval(function() {
        location.reload();
    }, 30000);
    
    // Log de acesso administrativo
    console.log('%c🔐 Área Administrativa Acessada', 'color: #667eea; font-size: 16px; font-weight: bold;');
</script>

</body>
</html>
