<?php
// Arquivo MÍNIMO - apenas cria os arquivos necessários
date_default_timezone_set('America/Sao_Paulo');

$resultado = [];

// 1. Criar logs
if (!is_dir('logs')) {
    @mkdir('logs', 0755, true);
    $resultado[] = "✅ Pasta logs criada";
} else {
    $resultado[] = "✅ Pasta logs existe";
}

// 2. Criar .htaccess raiz
$ht1 = '# Remover redirects
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} -f
RewriteCond %{REQUEST_FILENAME} ^/gestao_banca/api/
RewriteRule ^ - [L]
RewriteCond %{REQUEST_URI} ^/gestao_banca/api/telegram-webhook\.php$
RewriteRule ^ - [L]
</IfModule>';

if (@file_put_contents('.htaccess', $ht1)) {
    $resultado[] = "✅ .htaccess criado na raiz";
} else {
    $resultado[] = "❌ Erro ao criar .htaccess na raiz";
}

// 3. Criar .htaccess api
$ht2 = '# API webhook
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteCond %{REQUEST_URI} ^/gestao_banca/api/telegram-webhook\.php$
RewriteRule ^ - [L]
RewriteCond %{REQUEST_FILENAME} -f
RewriteCond %{REQUEST_FILENAME} ^/gestao_banca/api/
RewriteRule ^ - [L]
</IfModule>';

if (@file_put_contents('api/.htaccess', $ht2)) {
    $resultado[] = "✅ .htaccess criado em /api/";
} else {
    $resultado[] = "❌ Erro ao criar .htaccess em /api/";
}

// 4. Testar banco
if (@require_once 'config.php') {
    $conexao = @new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
    if (!$conexao->connect_error) {
        $resultado[] = "✅ Banco conectado";
        $res = $conexao->query("SELECT COUNT(*) as total FROM bote");
        if ($res) {
            $row = $res->fetch_assoc();
            $resultado[] = "📊 Mensagens no banco: " . $row['total'];
        }
        $conexao->close();
    } else {
        $resultado[] = "❌ Erro no banco";
    }
} else {
    $resultado[] = "❌ config.php não encontrado";
}

?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Setup</title>
<style>
body { font-family: Arial; margin: 30px; background: #f5f5f5; }
.box { background: white; padding: 30px; border-radius: 5px; max-width: 600px; margin: 0 auto; }
h1 { color: #333; }
p { font-size: 16px; line-height: 1.6; color: #666; }
.msg { padding: 10px; margin: 10px 0; background: #e8f5e9; color: #2e7d32; border-left: 4px solid #2e7d32; }
</style>
</head>
<body>

<div class="box">
<h1>✅ Setup Realizado</h1>

<?php foreach($resultado as $msg): ?>
<div class="msg"><?php echo $msg; ?></div>
<?php endforeach; ?>

<hr>

<h2>Próximos passos:</h2>
<ol>
<li>Limpe cookies do navegador (Ctrl+Shift+Delete)</li>
<li>Envie uma mensagem no Telegram no formato:</li>
</ol>

<pre style="background: #fff3cd; padding: 15px; border-radius: 5px;">
Oportunidade! 🚨
📊 OVER ( +2.5 ⚽GOLS )
Flamengo (H) x Botafogo (A)
Placar: 1 - 0
⛳ Escanteios: 5 - 3
Gols over +2.5 : 1.75
</pre>

<p>3. Acesse: <a href="teste-webhook.php">teste-webhook.php</a></p>
<p>4. Deve aparecer sua mensagem na tabela!</p>

</div>

</body>
</html>
