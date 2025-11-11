<?php
/**
 * Verificar se arquivo webhook existe
 */

echo "=== VERIFICAÇÃO DE ARQUIVOS ===\n\n";

$possiblePaths = [
    '/api/telegram-webhook.php',
    '/gestao/gestao_banca/api/telegram-webhook.php',
    '/gestao_banca/api/telegram-webhook.php',
    '../api/telegram-webhook.php',
    __DIR__ . '/api/telegram-webhook.php',
];

foreach ($possiblePaths as $path) {
    echo "Verificando: {$path}\n";
    if (file_exists($path)) {
        echo "  ✅ ENCONTRADO\n";
        $size = filesize($path);
        echo "  Tamanho: {$size} bytes\n";
    } else {
        echo "  ❌ NÃO ENCONTRADO\n";
    }
}

echo "\n";
echo "=== DIRETÓRIOS E ARQUIVOS ATUAIS ===\n\n";
echo "Diretório atual (__DIR__): " . __DIR__ . "\n";
echo "Diretório de trabalho (getcwd): " . getcwd() . "\n\n";

// Listar arquivos
echo "Arquivos na pasta atual:\n";
$files = scandir(__DIR__);
foreach ($files as $file) {
    if (!in_array($file, ['.', '..'])) {
        if (is_dir(__DIR__ . '/' . $file)) {
            echo "  [DIR] {$file}\n";
        } else {
            echo "  {$file}\n";
        }
    }
}

echo "\n";
if (is_dir(__DIR__ . '/api')) {
    echo "Arquivos na pasta /api:\n";
    $apiFiles = scandir(__DIR__ . '/api');
    foreach ($apiFiles as $file) {
        if (!in_array($file, ['.', '..'])) {
            echo "  {$file}\n";
        }
    }
} else {
    echo "❌ Pasta /api não existe!\n";
}

?>
