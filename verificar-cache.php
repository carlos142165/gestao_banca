<?php
date_default_timezone_set('America/Sao_Paulo');

$cacheDir = __DIR__ . '/cache';
$today = date('Y-m-d');
$cacheFile = $cacheDir . '/telegram_' . $today . '.json';
$offsetFile = $cacheDir . '/telegram_offset.txt';

echo "===== VERIFICAÇÃO DE CACHE =====\n\n";

echo "📁 Pasta de cache: $cacheDir\n";
echo "   Existe? " . (is_dir($cacheDir) ? "SIM ✅" : "NÃO ❌") . "\n";

if (is_dir($cacheDir)) {
    echo "\n📄 Arquivos no cache:\n";
    $files = scandir($cacheDir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            $path = $cacheDir . '/' . $file;
            $size = filesize($path);
            $mtime = date('Y-m-d H:i:s', filemtime($path));
            echo "   - $file ($size bytes, modificado em $mtime)\n";
        }
    }
}

echo "\n📝 Cache do dia ($today):\n";
echo "   Arquivo: $cacheFile\n";
echo "   Existe? " . (file_exists($cacheFile) ? "SIM ✅" : "NÃO ❌") . "\n";

if (file_exists($cacheFile)) {
    $age = time() - filemtime($cacheFile);
    echo "   Idade: $age segundos\n";
    echo "   Conteúdo:\n";
    $content = json_decode(file_get_contents($cacheFile), true);
    echo "   {\n";
    echo "     success: " . ($content['success'] ? 'true' : 'false') . "\n";
    echo "     mensagens: " . count($content['messages']) . "\n";
    echo "     total: " . $content['total'] . "\n";
    if (!empty($content['messages'])) {
        echo "     Primeira: " . $content['messages'][0]['time'] . " - " . substr($content['messages'][0]['text'], 0, 50) . "\n";
        echo "     Última: " . $content['messages'][count($content['messages'])-1]['time'] . " - " . substr($content['messages'][count($content['messages'])-1]['text'], 0, 50) . "\n";
    }
    echo "   }\n";
}

echo "\n📍 Offset salvo:\n";
echo "   Arquivo: $offsetFile\n";
echo "   Existe? " . (file_exists($offsetFile) ? "SIM ✅" : "NÃO ❌") . "\n";

if (file_exists($offsetFile)) {
    $offset = file_get_contents($offsetFile);
    echo "   Valor: $offset\n";
}

echo "\n";
?>
