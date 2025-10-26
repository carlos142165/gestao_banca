<?php
/**
 * Verificação de Compatibilidade - Sistema de Celebração Global
 * 
 * Este arquivo verifica se todos os componentes estão corretamente instalados
 * e se o sistema está pronto para funcionar.
 */

session_start();

$verificacoes = [];
$tudo_ok = true;

// ============================================
// 1. Verificar se arquivo principal existe
// ============================================
$arquivo_js = 'js/celebracao-plano.js';
$verificacoes['Script JS'] = [
    'status' => file_exists($arquivo_js) ? '✅' : '❌',
    'descricao' => file_exists($arquivo_js) ? 'Arquivo encontrado' : 'Arquivo não encontrado',
    'arquivo' => $arquivo_js
];
if (!file_exists($arquivo_js)) $tudo_ok = false;

// ============================================
// 2. Verificar se arquivo CSS existe
// ============================================
$arquivo_css = 'css/celebracao-plano.css';
$verificacoes['CSS'] = [
    'status' => file_exists($arquivo_css) ? '✅' : '❌',
    'descricao' => file_exists($arquivo_css) ? 'Arquivo encontrado' : 'Arquivo não encontrado',
    'arquivo' => $arquivo_css
];
if (!file_exists($arquivo_css)) $tudo_ok = false;

// ============================================
// 3. Verificar páginas que carregam o sistema
// ============================================
$paginas_principais = ['home.php', 'gestao-diaria.php', 'administrativa.php', 'conta.php'];

foreach ($paginas_principais as $pagina) {
    if (file_exists($pagina)) {
        $conteudo = file_get_contents($pagina);
        
        $tem_css = strpos($conteudo, 'celebracao-plano.css') !== false;
        $tem_js = strpos($conteudo, 'celebracao-plano.js') !== false;
        
        if ($pagina === 'conta.php') {
            // Conta.php precisa ter ambos
            $status = ($tem_css && $tem_js) ? '✅' : '⚠️';
            $descricao = ($tem_css && $tem_js) ? 'CSS e JS carregados' : 'Faltam componentes';
        } else {
            // Outras podem ter apenas JS
            $status = $tem_js ? '✅' : '⚠️';
            $descricao = $tem_js ? 'Script carregado' : 'Script não carregado';
        }
        
        $verificacoes["Página: $pagina"] = [
            'status' => $status,
            'descricao' => $descricao,
            'tem_css' => $tem_css,
            'tem_js' => $tem_js
        ];
        
        if ($status === '⚠️') $tudo_ok = false;
    }
}

// ============================================
// 4. Verificar arquivo minha-conta.php
// ============================================
$arquivo_api = 'minha-conta.php';
if (file_exists($arquivo_api)) {
    $conteudo = file_get_contents($arquivo_api);
    $tem_endpoint = strpos($conteudo, 'obter_dados') !== false;
    
    $verificacoes['API: minha-conta.php'] = [
        'status' => $tem_endpoint ? '✅' : '❌',
        'descricao' => $tem_endpoint ? 'Endpoint obter_dados existe' : 'Endpoint não encontrado',
    ];
    
    if (!$tem_endpoint) $tudo_ok = false;
}

// ============================================
// 5. Verificar arquivo de testes
// ============================================
$arquivo_teste = 'teste-celebracao-global.php';
$verificacoes['Arquivo de Testes'] = [
    'status' => file_exists($arquivo_teste) ? '✅' : '⚠️',
    'descricao' => file_exists($arquivo_teste) ? 'Disponível em /teste-celebracao-global.php' : 'Não encontrado',
];

// ============================================
// 6. Verificar tamanho dos arquivos
// ============================================
if (file_exists($arquivo_js)) {
    $tamanho_js = filesize($arquivo_js);
    $tamanho_kb_js = round($tamanho_js / 1024, 2);
    $verificacoes['Tamanho JS'] = [
        'status' => $tamanho_js > 1000 ? '✅' : '⚠️',
        'descricao' => "Arquivo tem {$tamanho_kb_js} KB",
    ];
}

if (file_exists($arquivo_css)) {
    $tamanho_css = filesize($arquivo_css);
    $tamanho_kb_css = round($tamanho_css / 1024, 2);
    $verificacoes['Tamanho CSS'] = [
        'status' => $tamanho_css > 1000 ? '✅' : '⚠️',
        'descricao' => "Arquivo tem {$tamanho_kb_css} KB",
    ];
}

// ============================================
// 7. Verificar sessão do usuário
// ============================================
$usuario_logado = isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id']);
$verificacoes['Sessão do Usuário'] = [
    'status' => $usuario_logado ? '✅' : '⚠️',
    'descricao' => $usuario_logado ? "ID: {$_SESSION['usuario_id']}" : 'Não está logado',
];

// ============================================
// HTML Response
// ============================================
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificação - Sistema de Celebração</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        
        h1 {
            color: #667eea;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .subtitle {
            color: #999;
            margin-bottom: 30px;
            font-size: 14px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 30px;
        }
        
        .status-badge.ok {
            background: #d4edda;
            color: #155724;
        }
        
        .status-badge.erro {
            background: #f8d7da;
            color: #721c24;
        }
        
        .verificacoes-grid {
            display: grid;
            gap: 15px;
        }
        
        .verificacao-item {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 8px;
            border-left: 4px solid #667eea;
            transition: all 0.3s ease;
        }
        
        .verificacao-item:hover {
            background: #f0f0f0;
            transform: translateX(5px);
        }
        
        .verificacao-status {
            font-size: 24px;
            min-width: 30px;
            text-align: center;
        }
        
        .verificacao-conteudo {
            flex: 1;
        }
        
        .verificacao-titulo {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }
        
        .verificacao-descricao {
            color: #666;
            font-size: 13px;
            margin-bottom: 5px;
        }
        
        .verificacao-arquivo {
            color: #999;
            font-size: 12px;
            font-family: monospace;
            background: white;
            padding: 5px 8px;
            border-radius: 4px;
            display: inline-block;
        }
        
        .detalhes-expandivel {
            margin-top: 30px;
            padding: 20px;
            background: #f5f5f5;
            border-radius: 8px;
            border: 1px solid #ddd;
        }
        
        .detalhes-expandivel h3 {
            color: #667eea;
            margin-bottom: 10px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            user-select: none;
        }
        
        .detalhes-expandivel h3:hover {
            color: #764ba2;
        }
        
        .detalhes-conteudo {
            display: none;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
        }
        
        .detalhes-conteudo.expandido {
            display: block;
        }
        
        .codigo-bloco {
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 15px;
            border-radius: 8px;
            overflow-x: auto;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            line-height: 1.5;
            margin-top: 10px;
        }
        
        .acoes {
            margin-top: 30px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: #999;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #777;
        }
        
        .alerta {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            align-items: flex-start;
        }
        
        .alerta.sucesso {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .alerta.erro {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        .alerta.aviso {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>
            <i class="fas fa-stethoscope"></i>
            Verificação do Sistema de Celebração
        </h1>
        <p class="subtitle">Diagnóstico dos componentes necessários</p>
        
        <!-- Status Geral -->
        <div class="status-badge <?php echo $tudo_ok ? 'ok' : 'erro'; ?>">
            <?php echo $tudo_ok ? '✅ SISTEMA OK' : '❌ ALGUNS PROBLEMAS DETECTADOS'; ?>
        </div>
        
        <!-- Verificações -->
        <div class="verificacoes-grid">
            <?php foreach ($verificacoes as $titulo => $info): ?>
            <div class="verificacao-item">
                <div class="verificacao-status"><?php echo $info['status']; ?></div>
                <div class="verificacao-conteudo">
                    <div class="verificacao-titulo"><?php echo $titulo; ?></div>
                    <div class="verificacao-descricao"><?php echo $info['descricao']; ?></div>
                    <?php if (!empty($info['arquivo'])): ?>
                    <div class="verificacao-arquivo">📄 <?php echo $info['arquivo']; ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Próximos Passos -->
        <div class="detalhes-expandivel">
            <h3 onclick="this.parentElement.querySelector('.detalhes-conteudo').classList.toggle('expandido')">
                <i class="fas fa-chevron-down"></i>
                <span>Como Usar o Sistema</span>
            </h3>
            <div class="detalhes-conteudo">
                <p style="margin-bottom: 15px;">O sistema de celebração funciona automaticamente em:</p>
                <ul style="margin-left: 20px; margin-bottom: 15px;">
                    <li><strong>home.php</strong> - Celebra ao fazer login</li>
                    <li><strong>gestao-diaria.php</strong> - Celebra quando acessa o dashboard</li>
                    <li><strong>administrativa.php</strong> - Celebra na área admin</li>
                    <li><strong>conta.php</strong> - Celebra ao abrir conta</li>
                </ul>
                
                <p style="margin-bottom: 10px;"><strong>O que é necessário:</strong></p>
                <div class="codigo-bloco">
1. Arquivo JS: js/celebracao-plano.js
2. Arquivo CSS: css/celebracao-plano.css
3. API: minha-conta.php?acao=obter_dados
4. Banco de dados com campos: id_plano, data_fim_assinatura
5. Tabela planos com planos disponíveis
                </div>
            </div>
        </div>
        
        <!-- Testes -->
        <div class="detalhes-expandivel">
            <h3 onclick="this.parentElement.querySelector('.detalhes-conteudo').classList.toggle('expandido')">
                <i class="fas fa-flask"></i>
                <span>Ferramentas de Teste</span>
            </h3>
            <div class="detalhes-conteudo">
                <p style="margin-bottom: 15px;">Use estas ferramentas para testar o sistema:</p>
                
                <div class="acoes">
                    <a href="teste-celebracao-global.php" class="btn btn-primary" target="_blank">
                        <i class="fas fa-play"></i> Ir para Página de Teste
                    </a>
                    <a href="javascript:location.reload()" class="btn btn-secondary">
                        <i class="fas fa-sync"></i> Recarregar Esta Página
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Problemas Conhecidos -->
        <div class="detalhes-expandivel">
            <h3 onclick="this.parentElement.querySelector('.detalhes-conteudo').classList.toggle('expandido')">
                <i class="fas fa-exclamation-triangle"></i>
                <span>Se Algo Não Estiver Funcionando</span>
            </h3>
            <div class="detalhes-conteudo">
                <p style="margin-bottom: 15px;"><strong>1. Verifique o Console (F12)</strong></p>
                <p style="margin-bottom: 15px;">Abra DevTools (F12), vá até a aba "Console" e procure por:
                    <ul style="margin-left: 20px;">
                        <li>🎉 Sistema de celebração inicializado</li>
                        <li>❌ Erros de carregamento</li>
                        <li>❌ Erros 404 em arquivos</li>
                    </ul>
                </p>
                
                <p style="margin-bottom: 15px;"><strong>2. Verifique o localStorage</strong></p>
                <p style="margin-bottom: 15px;">No DevTools, vá em Storage → LocalStorage → seu site
                    <ul style="margin-left: 20px;">
                        <li>Deve ter: <code style="background: #f0f0f0; padding: 3px 6px;">plano_usuario_atual</code></li>
                    </ul>
                </p>
                
                <p style="margin-bottom: 15px;"><strong>3. Limpe o Cache</strong></p>
                <div class="codigo-bloco">
// No console do DevTools (F12):
localStorage.clear();
sessionStorage.clear();
location.reload();
                </div>
                
                <p style="margin-bottom: 15px;"><strong>4. Verifique a Conexão do Banco</strong></p>
                <p>Certifique-se de que:</p>
                <ul style="margin-left: 20px;">
                    <li>A tabela <code style="background: #f0f0f0; padding: 3px 6px;">planos</code> existe</li>
                    <li>A tabela <code style="background: #f0f0f0; padding: 3px 6px;">usuarios</code> tem <code style="background: #f0f0f0; padding: 3px 6px;">id_plano</code></li>
                    <li>A tabela <code style="background: #f0f0f0; padding: 3px 6px;">usuarios</code> tem <code style="background: #f0f0f0; padding: 3px 6px;">data_fim_assinatura</code></li>
                </ul>
            </div>
        </div>
        
        <!-- Rodapé -->
        <div style="margin-top: 40px; padding-top: 20px; border-top: 2px solid #eee; text-align: center; color: #999; font-size: 12px;">
            <p>Sistema de Celebração Global v1.0</p>
            <p>Desenvolvido para máxima compatibilidade e facilidade de uso</p>
            <a href="CELEBRACAO-GLOBAL-README.md" style="color: #667eea; text-decoration: none;">📖 Ver Documentação Completa</a>
        </div>
    </div>
</body>
</html>
