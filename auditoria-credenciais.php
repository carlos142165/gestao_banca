<?php
/**
 * SCRIPT DE AUDITORIA - Encontrar Credenciais Hardcoded
 * 
 * Este script procura por arquivos PHP que ainda têm
 * credenciais hardcoded em vez de usar config.php
 * 
 * USE: Acesse este arquivo via browser para ver o relatório
 */

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html lang='pt-br'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Auditoria - Credenciais Hardcoded</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #007bff;
            padding-bottom: 10px;
        }
        .info {
            background-color: #e7f3ff;
            border-left: 4px solid #007bff;
            padding: 12px;
            margin: 10px 0;
            border-radius: 4px;
        }
        .warning {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 12px;
            margin: 10px 0;
            border-radius: 4px;
        }
        .success {
            background-color: #d4edda;
            border-left: 4px solid #28a745;
            padding: 12px;
            margin: 10px 0;
            border-radius: 4px;
        }
        .file-list {
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            margin: 15px 0;
        }
        .file-item {
            padding: 10px;
            background: white;
            margin: 8px 0;
            border-left: 3px solid #ff9800;
            padding-left: 12px;
            border-radius: 2px;
        }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin: 20px 0;
        }
        .stat-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        .stat-box.clean {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        }
        .stat-number {
            font-size: 32px;
            font-weight: bold;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        table th {
            background: #007bff;
            color: white;
            padding: 12px;
            text-align: left;
        }
        table td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }
        table tr:hover {
            background: #f5f5f5;
        }
    </style>
</head>
<body>
<div class='container'>
    <h1>🔍 Auditoria - Buscar Credenciais Hardcoded</h1>
    
    <div class='info'>
        <strong>Objetivo:</strong> Encontrar arquivos PHP que ainda possuem credenciais do banco de dados hardcoded (em vez de usar config.php)
    </div>
";

// Padrões a procurar
$patterns = [
    'mysqli_connect' => "mysqli_connect(",
    'new mysqli' => "new mysqli",
    'PDO database' => 'dbname=formulario-carlos',
    'dbname hardcoded' => "\$dbname = '",
    'db password' => "\$dbPassword = '",
];

$directory = __DIR__;
$results = [];
$totalFiles = 0;
$filesWithIssues = 0;

// Função recursiva para buscar arquivos
function searchFiles($dir, $patterns, &$results, &$totalFiles) {
    $files = scandir($dir);
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        
        $filePath = $dir . DIRECTORY_SEPARATOR . $file;
        
        if (is_dir($filePath)) {
            // Não buscar em diretórios específicos
            if (!in_array($file, ['vendor', 'node_modules', '.git'])) {
                searchFiles($filePath, $patterns, $results, $totalFiles);
            }
        } elseif (pathinfo($filePath, PATHINFO_EXTENSION) === 'php') {
            $totalFiles++;
            $content = file_get_contents($filePath);
            
            foreach ($patterns as $patternName => $pattern) {
                if (strpos($content, $pattern) !== false) {
                    if (!isset($results[$filePath])) {
                        $results[$filePath] = [];
                    }
                    $results[$filePath][] = $patternName;
                }
            }
        }
    }
}

searchFiles($directory, $patterns, $results, $totalFiles);
$filesWithIssues = count($results);

// Estatísticas
echo "
<div class='stats'>
    <div class='stat-box'>
        <div class='stat-number'>$totalFiles</div>
        <div>Arquivos PHP Analisados</div>
    </div>
    <div class='stat-box'>
        <div class='stat-number'>" . $filesWithIssues . "</div>
        <div>Arquivos com Credenciais</div>
    </div>
    <div class='stat-box " . ($filesWithIssues === 0 ? 'clean' : '') . "'>
        <div class='stat-number'>" . ($totalFiles - $filesWithIssues) . "</div>
        <div>Arquivos Limpos ✓</div>
    </div>
</div>
";

if ($filesWithIssues === 0) {
    echo "<div class='success'>
        <strong>✅ Excelente!</strong> Nenhum arquivo com credenciais hardcoded encontrado!
        Todos os arquivos estão usando a configuração centralizada do config.php
    </div>";
} else {
    echo "<div class='warning'>
        <strong>⚠️ Atenção:</strong> Foram encontrados " . $filesWithIssues . " arquivo(s) com credenciais hardcoded.
        Veja a lista abaixo e considere atualizá-los para usar config.php
    </div>";
    
    echo "<h2>📋 Arquivos com Credenciais Detectadas</h2>";
    echo "<table>
        <thead>
            <tr>
                <th>Arquivo</th>
                <th>Tipo de Credencial</th>
                <th>Ação Recomendada</th>
            </tr>
        </thead>
        <tbody>";
    
    foreach ($results as $filePath => $patterns) {
        $relPath = str_replace($directory, '', $filePath);
        echo "<tr>
            <td><code>$relPath</code></td>
            <td>" . implode(', ', $patterns) . "</td>
            <td>Atualizar para usar config.php</td>
        </tr>";
    }
    
    echo "</tbody>
    </table>";
    
    echo "<div class='info'>
        <strong>Como corrigir:</strong><br>
        1. Abra o arquivo<br>
        2. Remova as linhas com credenciais hardcoded<br>
        3. Adicione no topo: <code>require_once __DIR__ . '/config.php';</code><br>
        4. Use a variável global <code>\$conexao</code> ou as constantes <code>DB_*</code>
    </div>";
}

echo "
    <h2>📝 Padrões Procurados</h2>
    <table>
        <thead>
            <tr>
                <th>Padrão</th>
                <th>O que detecta</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><code>mysqli_connect(</code></td>
                <td>Função mysqli_connect com credenciais</td>
            </tr>
            <tr>
                <td><code>new mysqli</code></td>
                <td>Instância de mysqli com credenciais</td>
            </tr>
            <tr>
                <td><code>dbname=formulario-carlos</code></td>
                <td>Referência ao banco de dados antigo</td>
            </tr>
            <tr>
                <td><code>\$dbname = '</code></td>
                <td>Variável de nome do banco hardcoded</td>
            </tr>
            <tr>
                <td><code>\$dbPassword = '</code></td>
                <td>Variável de senha hardcoded</td>
            </tr>
        </tbody>
    </table>

    <div class='info' style='margin-top: 30px;'>
        <strong>💡 Dica:</strong> Esta auditoria foi executada em: <code>" . date('d/m/Y H:i:s') . "</code><br>
        Re-execute este arquivo regularmente para garantir que não haja credenciais hardcoded.
    </div>
</div>
</body>
</html>";
?>
