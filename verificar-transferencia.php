<?php
// ============================================
// VERIFICADOR DE TRANSFERÊNCIA - Abra no navegador para confirmar
// ============================================

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

$arquivo = __FILE__;
$timestamp = filemtime($arquivo);
$dataBrasil = date("d/m/Y H:i:s", $timestamp);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>✓ Verificador de Transferência</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .success {
            color: #27ae60;
            font-weight: bold;
            font-size: 20px;
            margin-bottom: 20px;
        }
        .info-box {
            background: #ecf0f1;
            padding: 15px;
            border-left: 4px solid #3498db;
            margin: 10px 0;
            border-radius: 4px;
        }
        .label {
            font-weight: bold;
            color: #2c3e50;
        }
        .value {
            color: #34495e;
            font-family: monospace;
            margin-left: 10px;
        }
        .action-box {
            background: #e8f8f5;
            padding: 20px;
            border-left: 4px solid #27ae60;
            margin: 20px 0;
            border-radius: 4px;
        }
        .action-title {
            color: #27ae60;
            font-weight: bold;
            font-size: 18px;
            margin-bottom: 10px;
        }
        .step {
            margin: 10px 0;
            line-height: 1.6;
        }
        .code {
            background: #2c3e50;
            color: #ecf0f1;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
            font-family: monospace;
            font-size: 12px;
            overflow-x: auto;
        }
        .warning {
            background: #fef5e7;
            padding: 15px;
            border-left: 4px solid #f39c12;
            margin: 20px 0;
            border-radius: 4px;
            color: #d68910;
        }
        button {
            background: #3498db;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            margin: 5px;
            font-size: 14px;
        }
        button:hover {
            background: #2980b9;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="success">
            ✓ TRANSFERÊNCIA CONFIRMADA!
        </div>

        <div class="info-box">
            <span class="label">📅 Última modificação:</span>
            <span class="value"><?php echo $dataBrasil; ?></span>
        </div>

        <div class="info-box">
            <span class="label">🌍 Ambiente:</span>
            <span class="value"><?php echo ENVIRONMENT; ?></span>
        </div>

        <div class="info-box">
            <span class="label">💾 Banco de dados:</span>
            <span class="value"><?php echo DB_NAME; ?></span>
        </div>

        <div class="info-box">
            <span class="label">🖥️ Host:</span>
            <span class="value"><?php echo DB_HOST; ?></span>
        </div>

        <div class="info-box">
            <span class="label">📝 Arquivo:</span>
            <span class="value"><?php echo basename($arquivo); ?></span>
        </div>

        <div class="action-box">
            <div class="action-title">✅ O que fazer agora:</div>
            <div class="step">
                <strong>1. Se vê esta página:</strong> Transferência funcionou! ✓
            </div>
            <div class="step">
                <strong>2. Se arquivo Python/CSS/JS não atualiza:</strong> É cache do navegador
            </div>
            <div class="step">
                <strong>3. Para forçar atualização:</strong> Use estes atalhos:
            </div>
            <div class="code">
Windows/Linux: Ctrl + Shift + R<br>
Mac: Cmd + Shift + R<br>
Ou: Ctrl + F5
            </div>
            <div class="step">
                <strong>4. Modo incógnito:</strong> Abre em modo privado/anônimo para testar sem cache
            </div>
        </div>

        <div class="warning">
            <strong>⚠️ Se NÃO vê esta página:</strong><br>
            ❌ Arquivo não foi transferido<br>
            ❌ Transferiu para pasta errada<br>
            ❌ URL do site está incorreta<br>
            <br>
            Verifique no cPanel → File Manager → public_html
        </div>

        <div style="margin-top: 30px; text-align: center;">
            <button onclick="location.reload()">🔄 Recarregar</button>
            <button onclick="window.history.back()">← Voltar</button>
            <button onclick="testarOutros()">🧪 Testar outros arquivos</button>
        </div>

        <script>
            function testarOutros() {
                const testes = [
                    { nome: 'Index', url: 'index.php' },
                    { nome: 'Config', url: 'config.php' },
                    { nome: 'Home', url: 'home.php' }
                ];
                
                console.log('URLs para testar:');
                testes.forEach(t => {
                    console.log(`${t.nome}: ${window.location.origin}/${t.url}`);
                });
                
                alert('Abra o console (F12) para ver URLs para testar');
            }
        </script>
    </div>
</body>
</html>
