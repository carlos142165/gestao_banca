<?php
/**
 * Inicializa os arquivos necessários para o Monitor Telegram
 * Execute este arquivo uma vez para garantir que tudo está pronto
 */

$dados_file = __DIR__ . '/dados_telegram.json';

// Verificar se arquivo existe
if (!file_exists($dados_file)) {
    // Criar arquivo vazio
    file_put_contents($dados_file, json_encode([], JSON_PRETTY_PRINT), LOCK_EX);
    echo "✅ Arquivo dados_telegram.json criado\n";
} else {
    echo "✅ Arquivo dados_telegram.json já existe\n";
}

// Verificar permissões
if (is_writable($dados_file)) {
    echo "✅ Arquivo tem permissão de escrita\n";
} else {
    echo "⚠️  Aviso: Arquivo pode não ter permissão de escrita\n";
    echo "   Execute: chmod 644 " . $dados_file . "\n";
}

// Verificar se diretórios CSS e JS existem
$css_dir = __DIR__ . '/css';
$js_dir = __DIR__ . '/js';

if (!is_dir($css_dir)) {
    mkdir($css_dir, 0755, true);
    echo "✅ Diretório css criado\n";
} else {
    echo "✅ Diretório css existe\n";
}

if (!is_dir($js_dir)) {
    mkdir($js_dir, 0755, true);
    echo "✅ Diretório js criado\n";
} else {
    echo "✅ Diretório js existe\n";
}

// Verificar arquivos necessários
$arquivos_necessarios = [
    'css/oportunidades-telegram.css',
    'js/monitor-telegram.js',
    'telegram-monitor.php',
    'telegram-webhook.php',
    'teste-telegram-monitor.html',
];

echo "\n📋 Verificando arquivos:\n";
foreach ($arquivos_necessarios as $arquivo) {
    $caminho = __DIR__ . '/' . $arquivo;
    if (file_exists($caminho)) {
        echo "  ✅ $arquivo\n";
    } else {
        echo "  ❌ $arquivo (NÃO ENCONTRADO)\n";
    }
}

echo "\n🎉 Inicialização concluída!\n";
echo "\n📝 Próximos passos:\n";
echo "1. Acesse: http://seu-site/teste-telegram-monitor.html\n";
echo "2. Teste a sincronização\n";
echo "3. Vá para: http://seu-site/bot_aovivo.php\n";
echo "4. Verifique o Bloco 1 para ver as oportunidades\n";

echo "\n💡 Para enviar mensagens de teste:\n";
echo "- Abra o canal: https://t.me/-1002047004959\n";
echo "- Copie o formato de oportunidade do README\n";
echo "- Envie a mensagem\n";
