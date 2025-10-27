<?php
// ==================================================================================================================== 
// ========================== ÁREA ADMINISTRATIVA - ARQUIVO ÚNICO ==========================
// ==================================================================================================================== 


require_once 'config.php';
require_once 'carregar_sessao.php';
require_once 'admin-ids-config.php';

// ==================================================================================================================== 
// ========================== VERIFICAÇÃO DE ACESSO ==========================
// ==================================================================================================================== 

// Apenas ID 23 pode acessar a área administrativa
$id_usuario = $_SESSION['usuario_id'] ?? null;

// Se não for ID 23, redireciona (descomente quando tiver certeza de estar com ID 23)
// if ($id_usuario !== 23) {
//     header('Location: home.php');
//     exit;
// }

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
        'assinaturas_anuais_breakdown' => [
            'prata' => 0,
            'ouro' => 0,
            'diamante' => 0
        ],
        'assinaturas_mensais_breakdown' => [
            'prata' => 0,
            'ouro' => 0,
            'diamante' => 0
        ],
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
        // 🎯 Usar a coluna tipo_ciclo diretamente para contar
        
        // 📅 MENSAIS: tipo_ciclo = 'mensal'
        $result = $conexao->query("
            SELECT COUNT(*) as count FROM usuarios 
            WHERE data_fim_assinatura IS NOT NULL 
            AND id_plano IS NOT NULL
            AND tipo_ciclo = 'mensal'
        ");
        $stats['assinaturas_mensais'] = $result->fetch_assoc()['count'] ?? 0;
        
        // ⏰ ANUAIS: tipo_ciclo = 'anual'
        $result = $conexao->query("
            SELECT COUNT(*) as count FROM usuarios 
            WHERE data_fim_assinatura IS NOT NULL 
            AND id_plano IS NOT NULL
            AND tipo_ciclo = 'anual'
        ");
        $stats['assinaturas_anuais'] = $result->fetch_assoc()['count'] ?? 0;
        
        // 📊 BREAKDOWN DE PLANOS POR CICLO - MENSAIS
        $result = $conexao->query("
            SELECT p.nome, COUNT(u.id) as count 
            FROM usuarios u
            JOIN planos p ON u.id_plano = p.id
            WHERE u.data_fim_assinatura IS NOT NULL 
            AND u.id_plano IS NOT NULL
            AND u.tipo_ciclo = 'mensal'
            GROUP BY u.id_plano
        ");
        
        while ($row = $result->fetch_assoc()) {
            $plano = strtolower($row['nome'] ?? '');
            if (strpos($plano, 'prata') !== false) {
                $stats['assinaturas_mensais_breakdown']['prata'] = $row['count'];
            } elseif (strpos($plano, 'ouro') !== false) {
                $stats['assinaturas_mensais_breakdown']['ouro'] = $row['count'];
            } elseif (strpos($plano, 'diamante') !== false) {
                $stats['assinaturas_mensais_breakdown']['diamante'] = $row['count'];
            }
        }
        
        // 📊 BREAKDOWN DE PLANOS POR CICLO - ANUAIS
        $result = $conexao->query("
            SELECT p.nome, COUNT(u.id) as count 
            FROM usuarios u
            JOIN planos p ON u.id_plano = p.id
            WHERE u.data_fim_assinatura IS NOT NULL 
            AND u.id_plano IS NOT NULL
            AND u.tipo_ciclo = 'anual'
            GROUP BY u.id_plano
        ");
        
        while ($row = $result->fetch_assoc()) {
            $plano = strtolower($row['nome'] ?? '');
            if (strpos($plano, 'prata') !== false) {
                $stats['assinaturas_anuais_breakdown']['prata'] = $row['count'];
            } elseif (strpos($plano, 'ouro') !== false) {
                $stats['assinaturas_anuais_breakdown']['ouro'] = $row['count'];
            } elseif (strpos($plano, 'diamante') !== false) {
                $stats['assinaturas_anuais_breakdown']['diamante'] = $row['count'];
            }
        }
        
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
    <link rel="stylesheet" href="css/celebracao-plano.css"> <!-- CSS da celebração de plano global -->
    
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
        
        .card-stat-breakdown {
            font-size: 13px;
            font-weight: 600;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 12px;
            margin-top: 12px;
            padding-top: 12px;
            padding-bottom: 8px;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            flex-wrap: wrap;
        }
        
        .breakdown-item {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            white-space: nowrap;
        }
        
        .breakdown-item.prata {
            color: #fff;
            background-color: rgba(192, 57, 43, 0.3);
        }
        
        .breakdown-item.ouro {
            color: #fff;
            background-color: rgba(243, 156, 18, 0.3);
        }
        
        .breakdown-item.diamante {
            color: #fff;
            background-color: rgba(41, 128, 185, 0.3);
        }
        
        .breakdown-separator {
            color: rgba(255, 255, 255, 0.7);
            font-weight: 300;
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
        
        /* ==================================================================================================================== */
        /* ========================== MODAL ==========================                                */
        /* ==================================================================================================================== */
        
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            animation: fade-in 0.3s ease-out;
        }
        
        .modal-overlay.ativo {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .modal-conteudo {
            background: white;
            border-radius: 16px;
            padding: 30px;
            max-width: 600px;
            width: 90%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: fade-in 0.3s ease-out;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--cor-borda);
        }
        
        .modal-header h2 {
            font-size: 22px;
            color: var(--cor-principal);
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 28px;
            color: var(--cor-texto-claro);
            cursor: pointer;
            padding: 0;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            transition: var(--transicao);
        }
        
        .modal-close:hover {
            background: var(--cor-fundo);
            color: var(--cor-texto);
        }
        
        .modal-body {
            margin-bottom: 25px;
        }
        
        .input-group {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .input-group input,
        .input-group select {
            flex: 1;
            min-width: 120px;
            padding: 12px;
            border: 2px solid var(--cor-borda);
            border-radius: 8px;
            font-size: 14px;
            transition: var(--transicao);
            font-family: 'Rajdhani', monospace;
        }
        
        .input-group input:focus {
            outline: none;
            border-color: var(--cor-principal);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .input-group select:focus {
            outline: none;
            border-color: var(--cor-principal);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn-adicionar-id {
            padding: 12px 24px;
            background: var(--cor-principal);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: var(--transicao);
            flex-shrink: 0;
            white-space: nowrap;
        }
        
        .btn-adicionar-id:hover {
            background: var(--cor-secundaria);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        
        .btn-adicionar-id:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .lista-admin-ids {
            background: var(--cor-fundo);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            max-height: 300px;
            overflow-y: auto;
        }
        
        .lista-admin-ids::-webkit-scrollbar {
            width: 6px;
        }
        
        .lista-admin-ids::-webkit-scrollbar-track {
            background: var(--cor-borda);
            border-radius: 3px;
        }
        
        .lista-admin-ids::-webkit-scrollbar-thumb {
            background: var(--cor-principal);
            border-radius: 3px;
        }
        
        .lista-admin-ids h3 {
            margin: 0 0 12px 0;
            font-size: 14px;
            color: var(--cor-texto-claro);
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        
        .admin-id-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: white;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 8px;
            border-left: 3px solid var(--cor-principal);
            transition: var(--transicao);
        }
        
        .admin-id-item:last-child {
            margin-bottom: 0;
        }
        
        .admin-id-item:hover {
            background: var(--cor-borda);
            transform: translateX(5px);
        }
        
        .admin-id-numero {
            font-size: 16px;
            font-weight: 700;
            color: var(--cor-principal);
            font-family: 'Rajdhani', monospace;
        }
        
        .btn-remover-id {
            padding: 6px 12px;
            background: rgba(239, 68, 68, 0.1);
            color: var(--cor-perigo);
            border: 1px solid var(--cor-perigo);
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            transition: var(--transicao);
        }
        
        .btn-remover-id:hover {
            background: var(--cor-perigo);
            color: white;
        }
        
        .modal-footer {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        
        .btn-fechar-modal {
            padding: 12px 24px;
            background: var(--cor-borda);
            color: var(--cor-texto);
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: var(--transicao);
        }
        
        .btn-fechar-modal:hover {
            background: var(--cor-fundo);
        }
        
        .toast-notificacao {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 8px;
            color: white;
            font-weight: 600;
            z-index: 2000;
            animation: slide-in 0.3s ease-out;
            display: none;
        }
        
        .toast-notificacao.ativo {
            display: block;
        }
        
        .toast-notificacao.sucesso {
            background: var(--cor-sucesso);
        }
        
        .toast-notificacao.erro {
            background: var(--cor-perigo);
        }
        
        @keyframes slide-in {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes slide-out {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(400px);
                opacity: 0;
            }
        }
        
        .toast-notificacao.saindo {
            animation: slide-out 0.3s ease-out;
        }
        
        .btn-gerenciar-admins {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            background: var(--cor-sucesso);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: var(--transicao);
        }
        
        .btn-gerenciar-admins:hover {
            background: #059669;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(16, 185, 129, 0.3);
        }
        
        @media (max-width: 480px) {
            .modal-conteudo {
                padding: 20px;
            }
            
            .modal-header {
                margin-bottom: 20px;
            }
            
            .modal-header h2 {
                font-size: 18px;
            }
            
            .input-group {
                flex-direction: column;
            }

            .input-group input,
            .input-group select,
            .btn-adicionar-id {
                width: 100%;
                flex-shrink: 0;
            }
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
    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
        <button class="btn-gerenciar-admins" id="btn-abrir-modal-admins">
            <i class="fas fa-crown"></i>
            Usuários Vitalício
        </button>
        <button class="btn-gerenciar-admins" id="btn-abrir-modal-bonus" style="background: #f59e0b;">
            <i class="fas fa-gift"></i>
            Bonus de Assinatura
        </button>
        <a href="gestao-diaria.php" class="btn-voltar">
            <i class="fas fa-arrow-left"></i>
            Voltar
        </a>
    </div>
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
                <div class="card-stat-breakdown">
                    <span class="breakdown-item prata">PRATA: <?php echo $stats['assinaturas_anuais_breakdown']['prata']; ?></span>
                    <span class="breakdown-separator">-</span>
                    <span class="breakdown-item ouro">OURO: <?php echo $stats['assinaturas_anuais_breakdown']['ouro']; ?></span>
                    <span class="breakdown-separator">-</span>
                    <span class="breakdown-item diamante">DIAMANTE: <?php echo $stats['assinaturas_anuais_breakdown']['diamante']; ?></span>
                </div>
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
                <div class="card-stat-breakdown">
                    <span class="breakdown-item prata">PRATA: <?php echo $stats['assinaturas_mensais_breakdown']['prata']; ?></span>
                    <span class="breakdown-separator">-</span>
                    <span class="breakdown-item ouro">OURO: <?php echo $stats['assinaturas_mensais_breakdown']['ouro']; ?></span>
                    <span class="breakdown-separator">-</span>
                    <span class="breakdown-item diamante">DIAMANTE: <?php echo $stats['assinaturas_mensais_breakdown']['diamante']; ?></span>
                </div>
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

<!-- ==================================================================================================================== -->
<!-- ========================== MODAL DE GERENCIAMENTO DE ADMINS ==========================                                -->
<!-- ==================================================================================================================== -->

<div class="modal-overlay" id="modal-gerenciar-admins">
    <div class="modal-conteudo">
        <div class="modal-header">
            <h2>
                <i class="fas fa-crown"></i>
                Usuários Vitalício
            </h2>
            <button class="modal-close" id="btn-fechar-modal">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="modal-body">
            <div class="input-group">
                <input 
                    type="number" 
                    id="input-novo-id" 
                    placeholder="Digite o ID do novo usuário vitalício"
                    min="1"
                >
                <button class="btn-adicionar-id" id="btn-adicionar-id-admin">
                    <i class="fas fa-plus"></i>
                    Adicionar
                </button>
            </div>
            
            <div class="lista-admin-ids">
                <h3>
                    <i class="fas fa-list"></i>
                    Usuários Vitalício Cadastrados
                </h3>
                <div id="lista-ids-container">
                    <p style="color: var(--cor-texto-claro); text-align: center; padding: 20px;">
                        <i class="fas fa-spinner fa-spin"></i>
                        Carregando...
                    </p>
                </div>
            </div>
        </div>
        
        <div class="modal-footer">
            <button class="btn-fechar-modal" id="btn-fechar-modal-footer">
                Fechar
            </button>
        </div>
    </div>
</div>

<!-- ==================================================================================================================== -->
<!-- ========================== MODAL DE BONUS DE ASSINATURA ==========================                                -->
<!-- ==================================================================================================================== -->

<div class="modal-overlay" id="modal-gerenciar-bonus">
    <div class="modal-conteudo">
        <div class="modal-header">
            <h2>
                <i class="fas fa-gift"></i>
                Bonus de Assinatura
            </h2>
            <button class="modal-close" id="btn-fechar-modal-bonus">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="modal-body">
            <div class="input-group">
                <input 
                    type="number" 
                    id="input-id-bonus" 
                    placeholder="Digite o ID do usuário"
                    min="1"
                >
                <select id="select-duracao-bonus" style="padding: 12px; border: 2px solid var(--cor-borda); border-radius: 8px; font-size: 14px;">
                    <option value="">Selecione a duração</option>
                    <option value="mensal">Mensal</option>
                    <option value="anual">Anual</option>
                </select>
                <select id="select-plano-bonus" style="padding: 12px; border: 2px solid var(--cor-borda); border-radius: 8px; font-size: 14px;">
                    <option value="">Selecione o plano</option>
                    <option value="prata">Prata</option>
                    <option value="ouro">Ouro</option>
                    <option value="diamante">Diamante</option>
                </select>
                <button class="btn-adicionar-id" id="btn-adicionar-bonus">
                    <i class="fas fa-plus"></i>
                    Adicionar
                </button>
            </div>
            
            <div class="lista-admin-ids">
                <h3>
                    <i class="fas fa-list"></i>
                    Bônus Ativos
                </h3>
                <div id="lista-bonus-container">
                    <p style="color: var(--cor-texto-claro); text-align: center; padding: 20px;">
                        <i class="fas fa-spinner fa-spin"></i>
                        Carregando...
                    </p>
                </div>
            </div>
        </div>
        
        <div class="modal-footer">
            <button class="btn-fechar-modal" id="btn-fechar-modal-bonus-footer">
                Fechar
            </button>
        </div>
    </div>
</div>

<!-- ==================================================================================================================== -->
<!-- ========================== TOAST DE NOTIFICAÇÃO ==========================                                -->
<!-- ==================================================================================================================== -->

<div class="toast-notificacao" id="toast-notificacao">
    <span id="toast-mensagem"></span>
</div>

<script>
    // ==================================================================================================================== 
    // ========================== GERENCIAMENTO DE ADMINS ==========================
    // ==================================================================================================================== 
    
    const modalAdmins = document.getElementById('modal-gerenciar-admins');
    const btnAbrirModal = document.getElementById('btn-abrir-modal-admins');
    const btnFecharModal = document.getElementById('btn-fechar-modal');
    const btnFecharModalFooter = document.getElementById('btn-fechar-modal-footer');
    const btnAdicionarId = document.getElementById('btn-adicionar-id-admin');
    const inputNovoId = document.getElementById('input-novo-id');
    const listaIdsContainer = document.getElementById('lista-ids-container');
    const toast = document.getElementById('toast-notificacao');
    const toastMensagem = document.getElementById('toast-mensagem');
    
    // Abrir modal
    btnAbrirModal.addEventListener('click', () => {
        modalAdmins.classList.add('ativo');
        inputNovoId.focus(); // Focar no input
        carregarAdminIds();
    });
    
    // Fechar modal
    btnFecharModal.addEventListener('click', () => {
        modalAdmins.classList.remove('ativo');
    });
    
    btnFecharModalFooter.addEventListener('click', () => {
        modalAdmins.classList.remove('ativo');
    });
    
    // Fechar ao clicar fora do modal
    modalAdmins.addEventListener('click', (e) => {
        if (e.target === modalAdmins) {
            modalAdmins.classList.remove('ativo');
        }
    });
    
    // Adicionar novo ID
    btnAdicionarId.addEventListener('click', () => {
        const novoId = inputNovoId.value.trim();
        
        if (!novoId) {
            mostrarToast('Por favor, digite um ID', 'erro');
            return;
        }
        
        if (isNaN(novoId) || novoId <= 0) {
            mostrarToast('ID deve ser um número positivo', 'erro');
            return;
        }
        
        adicionarAdminId(novoId);
    });
    
    // Permitir Enter no input
    inputNovoId.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            btnAdicionarId.click();
        }
    });
    
    // Função para carregar IDs de admin
    function carregarAdminIds() {
        fetch('admin-ids-config.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'acao=obter'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderizarListaIds(data.ids);
            } else {
                mostrarToast('Erro ao carregar IDs: ' + data.mensagem, 'erro');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            mostrarToast('Erro ao carregar IDs', 'erro');
        });
    }
    
    // Função para renderizar lista de IDs
    function renderizarListaIds(ids) {
        if (ids.length === 0) {
            listaIdsContainer.innerHTML = `
                <p style="color: var(--cor-texto-claro); text-align: center; padding: 20px;">
                    <i class="fas fa-inbox"></i>
                    Nenhum administrador cadastrado
                </p>
            `;
            return;
        }
        
        // Buscar nomes dos usuários
        fetch('obter-nomes-usuarios.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'ids=' + JSON.stringify(ids)
        })
        .then(response => response.json())
        .then(usuarios => {
            const usuariosMap = {};
            usuarios.forEach(u => {
                usuariosMap[u.id] = u.nome;
            });
            
            listaIdsContainer.innerHTML = ids.map(id => {
                const nome = usuariosMap[id] || 'Usuário desconhecido';
                return `
                    <div class="admin-id-item">
                        <div>
                            <span class="admin-id-numero">ID #${id}</span>
                            <br><small style="color: var(--cor-texto-claro);">${nome}</small>
                        </div>
                        <button class="btn-remover-id" onclick="removerAdminId(${id})">
                            <i class="fas fa-trash"></i>
                            Remover
                        </button>
                    </div>
                `;
            }).join('');
        })
        .catch(error => {
            console.error('Erro ao buscar nomes:', error);
            // Fallback: mostrar apenas IDs
            listaIdsContainer.innerHTML = ids.map(id => `
                <div class="admin-id-item">
                    <span class="admin-id-numero">ID #${id}</span>
                    <button class="btn-remover-id" onclick="removerAdminId(${id})">
                        <i class="fas fa-trash"></i>
                        Remover
                    </button>
                </div>
            `).join('');
        });
    }
    
    // Função para adicionar novo ID
    function adicionarAdminId(novoId) {
        btnAdicionarId.disabled = true;
        
        fetch('admin-ids-config.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'acao=adicionar&novo_id=' + novoId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarToast(data.mensagem, 'sucesso');
                inputNovoId.value = '';
                renderizarListaIds(data.ids);
            } else {
                mostrarToast(data.mensagem, 'erro');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            mostrarToast('Erro ao adicionar ID', 'erro');
        })
        .finally(() => {
            btnAdicionarId.disabled = false;
            inputNovoId.focus();
        });
    }
    
    // Função para remover ID
    function removerAdminId(id) {
        if (!confirm('Tem certeza que deseja remover o admin ID #' + id + '?')) {
            return;
        }
        
        fetch('admin-ids-config.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'acao=remover&id_remover=' + id
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarToast(data.mensagem, 'sucesso');
                renderizarListaIds(data.ids);
            } else {
                mostrarToast(data.mensagem, 'erro');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            mostrarToast('Erro ao remover ID', 'erro');
        });
    }
    
    // Função para mostrar toast
    function mostrarToast(mensagem, tipo = 'sucesso') {
        toastMensagem.textContent = mensagem;
        toast.classList.remove('sucesso', 'erro', 'saindo');
        toast.classList.add('ativo', tipo);
        
        setTimeout(() => {
            toast.classList.add('saindo');
            setTimeout(() => {
                toast.classList.remove('ativo', 'saindo');
            }, 300);
        }, 3000);
    }
    
    // ==================================================================================================================== 
    // ========================== GERENCIAMENTO DE BONUS ==========================
    // ==================================================================================================================== 
    
    const modalBonus = document.getElementById('modal-gerenciar-bonus');
    const btnAbrirModalBonus = document.getElementById('btn-abrir-modal-bonus');
    const btnFecharModalBonus = document.getElementById('btn-fechar-modal-bonus');
    const btnFecharModalBonusFooter = document.getElementById('btn-fechar-modal-bonus-footer');
    const btnAdicionarBonus = document.getElementById('btn-adicionar-bonus');
    const inputIdBonus = document.getElementById('input-id-bonus');
    const selectDuracaoBonus = document.getElementById('select-duracao-bonus');
    const selectPlanoBonus = document.getElementById('select-plano-bonus');
    const listaBonusContainer = document.getElementById('lista-bonus-container');
    
    // Abrir modal
    btnAbrirModalBonus.addEventListener('click', () => {
        modalBonus.classList.add('ativo');
        carregarBonus();
    });
    
    // Fechar modal
    btnFecharModalBonus.addEventListener('click', () => {
        modalBonus.classList.remove('ativo');
    });
    
    btnFecharModalBonusFooter.addEventListener('click', () => {
        modalBonus.classList.remove('ativo');
    });
    
    // Fechar ao clicar fora
    modalBonus.addEventListener('click', (e) => {
        if (e.target === modalBonus) {
            modalBonus.classList.remove('ativo');
        }
    });
    
    // Adicionar bonus
    btnAdicionarBonus.addEventListener('click', () => {
        const id = inputIdBonus.value.trim();
        const duracao = selectDuracaoBonus.value;
        const plano = selectPlanoBonus.value;
        
        if (!id || !duracao || !plano) {
            mostrarToast('Preencha todos os campos', 'erro');
            return;
        }
        
        adicionarBonus(id, duracao, plano);
    });
    
    // Carregar bônus
    function carregarBonus() {
        fetch('gerenciar-bonus-assinatura.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'acao=obter'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderizarListaBonus(data.bonus);
            } else {
                mostrarToast('Erro ao carregar: ' + data.mensagem, 'erro');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            mostrarToast('Erro ao carregar bônus', 'erro');
        });
    }
    
    // Renderizar lista de bônus
    function renderizarListaBonus(bonus) {
        if (!bonus || bonus.length === 0) {
            listaBonusContainer.innerHTML = `
                <p style="color: var(--cor-texto-claro); text-align: center; padding: 20px;">
                    <i class="fas fa-inbox"></i>
                    Nenhum bônus ativo
                </p>
            `;
            return;
        }
        
        listaBonusContainer.innerHTML = bonus.map(b => `
            <div class="admin-id-item">
                <div>
                    <span class="admin-id-numero">ID #${b.usuario_id} - ${b.nome}</span>
                    <br><small style="color: var(--cor-texto-claro);">
                        Plano ${b.plano} - ${b.duracao === 'mensal' ? 'Mensal' : 'Anual'} | Vence: ${new Date(b.data_fim).toLocaleDateString('pt-BR')}
                    </small>
                </div>
                <button class="btn-remover-id" onclick="removerBonus(${b.id})">
                    <i class="fas fa-trash"></i>
                    Remover
                </button>
            </div>
        `).join('');
    }
    
    // Adicionar novo bônus
    function adicionarBonus(id, duracao, plano) {
        btnAdicionarBonus.disabled = true;
        
        fetch('gerenciar-bonus-assinatura.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'acao=adicionar&usuario_id=' + id + '&duracao=' + duracao + '&plano=' + plano
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarToast(data.mensagem, 'sucesso');
                inputIdBonus.value = '';
                selectDuracaoBonus.value = '';
                selectPlanoBonus.value = '';
                carregarBonus();
            } else {
                mostrarToast(data.mensagem, 'erro');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            mostrarToast('Erro ao adicionar bônus', 'erro');
        })
        .finally(() => {
            btnAdicionarBonus.disabled = false;
        });
    }
    
    // Remover bônus
    function removerBonus(id) {
        if (!confirm('Tem certeza que deseja remover este bônus?')) {
            return;
        }
        
        fetch('gerenciar-bonus-assinatura.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'acao=remover&id=' + id
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarToast(data.mensagem, 'sucesso');
                carregarBonus();
            } else {
                mostrarToast(data.mensagem, 'erro');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            mostrarToast('Erro ao remover bônus', 'erro');
        });
    }
    
    // ==================================================================================================================== 
    // ========================== JAVASCRIPT ORIGINAL ==========================
    // ==================================================================================================================== 
    
    // Atualizar dados a cada 30 segundos
    setInterval(function() {
        location.reload();
    }, 30000);
    
    // Log de acesso administrativo
    console.log('%c🔐 Área Administrativa Acessada', 'color: #667eea; font-size: 16px; font-weight: bold;');
</script>

<!-- Sistema Global de Celebração de Plano -->
<script src="js/celebracao-plano.js" defer></script>

</body>
</html>
