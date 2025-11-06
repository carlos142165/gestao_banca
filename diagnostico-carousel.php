<?php
/**
 * 🔍 SCRIPT DE DIAGNÓSTICO - CAROUSEL BLOCOS
 * 
 * Use este script para verificar se tudo está configurado corretamente
 * Acesse: https://seusite.com/gestao/gestao_banca/diagnostico-carousel.php
 * 
 * Este script verifica:
 * - Existência dos arquivos CSS e JS
 * - Permissões dos arquivos
 * - Sintaxe do JavaScript
 * - Carregamento correto dos arquivos
 */

// Cores para terminal
$verde = "\033[92m";
$vermelho = "\033[91m";
$amarelo = "\033[93m";
$azul = "\033[94m";
$reset = "\033[0m";

// HTML para navegador
$html_verde = "✅";
$html_vermelho = "❌";
$html_amarelo = "⚠️";
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔍 Diagnóstico - Carousel Blocos</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .header p {
            opacity: 0.9;
            font-size: 14px;
        }
        
        .content {
            padding: 30px;
        }
        
        .section {
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }
        
        .section h2 {
            color: #333;
            font-size: 18px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .check-item {
            padding: 12px;
            margin-bottom: 10px;
            background: white;
            border-radius: 6px;
            display: flex;
            align-items: center;
            gap: 12px;
            border: 1px solid #e0e0e0;
        }
        
        .check-item.success {
            border-left: 4px solid #4caf50;
            background: #f1f8f4;
        }
        
        .check-item.error {
            border-left: 4px solid #f44336;
            background: #fef1f0;
        }
        
        .check-item.warning {
            border-left: 4px solid #ff9800;
            background: #fff8f3;
        }
        
        .icon {
            font-size: 20px;
            min-width: 25px;
            text-align: center;
        }
        
        .label {
            flex: 1;
            color: #333;
            font-weight: 500;
        }
        
        .value {
            color: #666;
            font-size: 13px;
            font-family: 'Courier New', monospace;
            max-width: 400px;
            word-break: break-all;
        }
        
        .code-block {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 15px;
            border-radius: 6px;
            overflow-x: auto;
            margin-top: 10px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            line-height: 1.4;
        }
        
        .summary {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }
        
        .summary h3 {
            color: #1976d2;
            margin-bottom: 10px;
        }
        
        .summary p {
            color: #555;
            line-height: 1.6;
        }
        
        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
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
            background: #f0f0f0;
            color: #333;
            border: 1px solid #ddd;
        }
        
        .btn-secondary:hover {
            background: #e0e0e0;
        }
        
        @media (max-width: 600px) {
            .header h1 {
                font-size: 20px;
            }
            
            .content {
                padding: 15px;
            }
            
            .section {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 Diagnóstico - Carousel Blocos</h1>
            <p>Verificação de arquivos e configurações</p>
        </div>
        
        <div class="content">
            <?php
            // Verificações
            $checks = [];
            
            // 1. Verificar diretório
            $base_dir = __DIR__;
            $css_path = $base_dir . '/css/carousel-blocos.css';
            $js_path = $base_dir . '/js/carousel-blocos.js';
            
            // Check 1: Arquivo CSS
            if (file_exists($css_path)) {
                $css_size = filesize($css_path);
                $css_perms = substr(sprintf('%o', fileperms($css_path)), -4);
                $checks['css'] = [
                    'status' => 'success',
                    'message' => 'Arquivo CSS encontrado',
                    'details' => "Tamanho: {$css_size} bytes | Permissões: {$css_perms}"
                ];
            } else {
                $checks['css'] = [
                    'status' => 'error',
                    'message' => 'Arquivo CSS NÃO encontrado',
                    'details' => "Caminho: {$css_path}"
                ];
            }
            
            // Check 2: Arquivo JS
            if (file_exists($js_path)) {
                $js_size = filesize($js_path);
                $js_perms = substr(sprintf('%o', fileperms($js_path)), -4);
                $checks['js'] = [
                    'status' => 'success',
                    'message' => 'Arquivo JS encontrado',
                    'details' => "Tamanho: {$js_size} bytes | Permissões: {$js_perms}"
                ];
            } else {
                $checks['js'] = [
                    'status' => 'error',
                    'message' => 'Arquivo JS NÃO encontrado',
                    'details' => "Caminho: {$js_path}"
                ];
            }
            
            // Check 3: Arquivo PHP
            $php_path = $base_dir . '/bot_aovivo.php';
            if (file_exists($php_path)) {
                $php_content = file_get_contents($php_path);
                if (strpos($php_content, 'carousel-blocos.css') !== false && 
                    strpos($php_content, 'carousel-blocos.js') !== false) {
                    $checks['php'] = [
                        'status' => 'success',
                        'message' => 'bot_aovivo.php está configurado corretamente',
                        'details' => 'Links de CSS e JS presentes'
                    ];
                } else {
                    $checks['php'] = [
                        'status' => 'error',
                        'message' => 'bot_aovivo.php não contém os links necessários',
                        'details' => 'Faltam referências ao carousel'
                    ];
                }
            } else {
                $checks['php'] = [
                    'status' => 'error',
                    'message' => 'Arquivo bot_aovivo.php NÃO encontrado',
                    'details' => "Caminho: {$php_path}"
                ];
            }
            
            // Check 4: Diretórios
            $css_dir = $base_dir . '/css';
            $js_dir = $base_dir . '/js';
            
            if (is_dir($css_dir)) {
                $checks['css_dir'] = [
                    'status' => 'success',
                    'message' => 'Diretório /css existe',
                    'details' => "Caminho: {$css_dir}"
                ];
            } else {
                $checks['css_dir'] = [
                    'status' => 'error',
                    'message' => 'Diretório /css NÃO existe',
                    'details' => "Caminho: {$css_dir}"
                ];
            }
            
            if (is_dir($js_dir)) {
                $checks['js_dir'] = [
                    'status' => 'success',
                    'message' => 'Diretório /js existe',
                    'details' => "Caminho: {$js_dir}"
                ];
            } else {
                $checks['js_dir'] = [
                    'status' => 'error',
                    'message' => 'Diretório /js NÃO existe',
                    'details' => "Caminho: {$js_dir}"
                ];
            }
            
            // Renderizar checks
            ?>
            <div class="section">
                <h2>📁 Verificação de Arquivos</h2>
                
                <?php foreach ($checks as $key => $check): ?>
                    <div class="check-item <?php echo $check['status']; ?>">
                        <div class="icon">
                            <?php
                            if ($check['status'] === 'success') {
                                echo '✅';
                            } elseif ($check['status'] === 'error') {
                                echo '❌';
                            } else {
                                echo '⚠️';
                            }
                            ?>
                        </div>
                        <div>
                            <div class="label"><?php echo $check['message']; ?></div>
                            <div class="value"><?php echo $check['details']; ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="section">
                <h2>⚙️ Informações do Sistema</h2>
                
                <div class="check-item success">
                    <div class="icon">ℹ️</div>
                    <div>
                        <div class="label">Versão do PHP</div>
                        <div class="value"><?php echo phpversion(); ?></div>
                    </div>
                </div>
                
                <div class="check-item success">
                    <div class="icon">ℹ️</div>
                    <div>
                        <div class="label">Diretório Base</div>
                        <div class="value"><?php echo __DIR__; ?></div>
                    </div>
                </div>
                
                <div class="check-item success">
                    <div class="icon">ℹ️</div>
                    <div>
                        <div class="label">URL da Página</div>
                        <div class="value"><?php echo 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']; ?></div>
                    </div>
                </div>
                
                <div class="check-item success">
                    <div class="icon">ℹ️</div>
                    <div>
                        <div class="label">User Agent</div>
                        <div class="value"><?php echo $_SERVER['HTTP_USER_AGENT']; ?></div>
                    </div>
                </div>
            </div>
            
            <div class="section">
                <h2>📋 Próximos Passos</h2>
                
                <?php
                $has_errors = false;
                $has_warnings = false;
                
                foreach ($checks as $check) {
                    if ($check['status'] === 'error') $has_errors = true;
                    if ($check['status'] === 'warning') $has_warnings = true;
                }
                ?>
                
                <?php if ($has_errors): ?>
                    <div class="summary" style="border-left-color: #f44336; background: #ffebee;">
                        <h3 style="color: #c62828;">❌ Problemas Detectados</h3>
                        <p>Alguns arquivos estão faltando. Execute estes passos:</p>
                        <ol style="margin-left: 20px; color: #555;">
                            <li>Verifique se os arquivos foram enviados corretamente para a Hostinger</li>
                            <li>Confirme a estrutura de pastas: <code>/css/carousel-blocos.css</code> e <code>/js/carousel-blocos.js</code></li>
                            <li>Verifique as permissões dos arquivos (devem ser 644)</li>
                            <li>Tente fazer upload novamente usando FTP ou cPanel File Manager</li>
                            <li>Aguarde alguns minutos para o cache do servidor atualizar</li>
                        </ol>
                    </div>
                <?php else: ?>
                    <div class="summary" style="border-left-color: #4caf50; background: #f1f8f4;">
                        <h3 style="color: #2e7d32;">✅ Tudo Configurado!</h3>
                        <p>Todos os arquivos estão no lugar. Agora:</p>
                        <ol style="margin-left: 20px; color: #555;">
                            <li>Limpe o cache do seu navegador (Ctrl+Shift+Delete)</li>
                            <li>Acesse <code>bot_aovivo.php</code> e abra o DevTools (F12)</li>
                            <li>Vá para a aba "Console" e procure por mensagens do carousel</li>
                            <li>Redimensione a janela para testar responsividade</li>
                            <li>Se funcionar em desktop (1024px+), tente em mobile ou tablet</li>
                        </ol>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="button-group">
                <a href="bot_aovivo.php" class="btn btn-primary">🔗 Voltar para bot_aovivo.php</a>
                <a href="gestao-diaria.php" class="btn btn-secondary">📊 Verificar gestao-diaria.php</a>
                <button class="btn btn-secondary" onclick="location.reload()">🔄 Recarregar Diagnóstico</button>
            </div>
        </div>
    </div>
    
    <script>
        console.log('🔍 Diagnóstico do Carousel Blocos');
        console.log('Status:', {
            arquivos: <?php echo json_encode($checks); ?>
        });
    </script>
</body>
</html>
