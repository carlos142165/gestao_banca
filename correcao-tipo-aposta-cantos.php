<?php
/**
 * SOLUÇÃO FINAL: Corrigir tipo_aposta para CANTOS no banco de dados
 * 
 * Este script identifica e corrige todos os registros que deveriam ser CANTOS
 * mas estão marcados incorretamente no banco de dados
 */

header('Content-Type: text/html; charset=utf-8');
require_once 'config.php';
$conexao->set_charset("utf8mb4");

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Correção de tipo_aposta CANTOS</title>
    <style>
        body { font-family: Arial; margin: 20px; background: #f5f5f5; }
        .box { background: white; padding: 20px; margin: 10px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .success { color: #4CAF50; font-weight: bold; }
        .error { color: #f44336; font-weight: bold; }
        .info { color: #2196F3; font-weight: bold; }
        .warning { color: #FF9800; font-weight: bold; }
        button { padding: 10px 20px; margin: 5px; cursor: pointer; font-size: 14px; border-radius: 4px; border: none; }
        .btn-info { background: #2196F3; color: white; }
        .btn-danger { background: #f44336; color: white; }
        .btn-success { background: #4CAF50; color: white; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f9f9f9; font-weight: bold; }
        .highlight-red { background: #ffebee; }
        .highlight-green { background: #e8f5e9; }
        .status-box { padding: 15px; margin: 10px 0; border-radius: 4px; }
    </style>
</head>
<body>
    <h1>🔧 Correção do Campo tipo_aposta para CANTOS</h1>

    <div class="box">
        <h2>📊 DIAGNÓSTICO</h2>
        
        <?php
        // PASSO 1: Diagnosticar quantos registros estão errados
        $sql_diagnostico = "SELECT 
            COUNT(*) as total_cantos_possivel,
            SUM(CASE WHEN LOWER(tipo_aposta) = 'cantos' THEN 1 ELSE 0 END) as corretos,
            SUM(CASE WHEN LOWER(tipo_aposta) != 'cantos' THEN 1 ELSE 0 END) as incorretos
        FROM bote 
        WHERE (
            titulo LIKE '%⛳%' OR titulo LIKE '%🚩%' 
            OR LOWER(titulo) LIKE '%cantos%' OR LOWER(titulo) LIKE '%canto%'
            OR LOWER(titulo) LIKE '%escanteio%' OR LOWER(titulo) LIKE '%escantei%'
        )";

        $result = $conexao->query($sql_diagnostico);
        $diagnostico = $result->fetch_assoc();
        
        echo "<table>";
        echo "<tr><th>Métrica</th><th>Valor</th><th>Status</th></tr>";
        echo "<tr>";
        echo "<td>Total de registros que DEVERIAM ser CANTOS</td>";
        echo "<td class='info'>" . $diagnostico['total_cantos_possivel'] . "</td>";
        echo "<td>" . ($diagnostico['total_cantos_possivel'] > 0 ? "✅ Encontrados" : "❌ Nenhum") . "</td>";
        echo "</tr>";
        
        echo "<tr class='highlight-green'>";
        echo "<td>Registros com tipo_aposta = CANTOS (Correto)</td>";
        echo "<td class='success'>" . ($diagnostico['corretos'] ?? 0) . "</td>";
        echo "<td>✅ Já correto</td>";
        echo "</tr>";
        
        echo "<tr class='highlight-red'>";
        echo "<td>Registros com tipo_aposta ERRADO (Incorreto)</td>";
        echo "<td class='error'>" . ($diagnostico['incorretos'] ?? 0) . "</td>";
        echo "<td>" . (($diagnostico['incorretos'] ?? 0) > 0 ? "❌ PRECISA CORRIGIR" : "✅ Sem erros") . "</td>";
        echo "</tr>";
        echo "</table>";
        
        $precisa_corrigir = ($diagnostico['incorretos'] ?? 0) > 0;
        ?>
    </div>

    <div class="box">
        <h2>🔍 EXEMPLOS DE REGISTROS ERRADOS</h2>
        
        <?php
        // Mostrar exemplos de registros errados
        $sql_exemplos = "SELECT 
            id,
            titulo,
            tipo_aposta,
            data_criacao
        FROM bote 
        WHERE (
            titulo LIKE '%⛳%' OR titulo LIKE '%🚩%' 
            OR LOWER(titulo) LIKE '%cantos%' OR LOWER(titulo) LIKE '%canto%'
        )
        AND (tipo_aposta IS NULL OR tipo_aposta = '' OR LOWER(tipo_aposta) != 'cantos')
        LIMIT 5";

        $result = $conexao->query($sql_exemplos);
        
        if ($result->num_rows > 0) {
            echo "<p class='warning'>⚠️ Exemplos de registros com tipo_aposta errado:</p>";
            echo "<table>";
            echo "<tr><th>ID</th><th>Título</th><th>tipo_aposta (atual)</th><th>Deveria ser</th></tr>";
            
            while ($row = $result->fetch_assoc()) {
                echo "<tr class='highlight-red'>";
                echo "<td>" . $row['id'] . "</td>";
                echo "<td>" . htmlspecialchars(substr($row['titulo'], 0, 60)) . "</td>";
                echo "<td><strong>" . htmlspecialchars($row['tipo_aposta'] ?? '[VAZIO]') . "</strong></td>";
                echo "<td class='success'>CANTOS</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p class='success'>✅ Nenhum registro errado encontrado!</p>";
        }
        ?>
    </div>

    <?php if ($precisa_corrigir): ?>
    <div class="box">
        <h2>🔧 EXECUTAR CORREÇÃO</h2>
        <p class='warning'>⚠️ Clique no botão abaixo para corrigir automaticamente todos os registros:</p>
        
        <button class="btn-danger" onclick="corrigirTipoAposta()">
            🔧 CORRIGIR TIPO_APOSTA AGORA
        </button>
        
        <div id="resultado-correcao" style="margin-top: 20px;"></div>
    </div>

    <script>
    function corrigirTipoAposta() {
        const botao = event.target;
        botao.disabled = true;
        botao.textContent = "Corrigindo...";
        
        fetch('api/corrigir-tipo-aposta-api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ acao: 'corrigir_cantos' })
        })
        .then(r => r.json())
        .then(data => {
            const div = document.getElementById('resultado-correcao');
            
            if (data.success) {
                div.innerHTML = `
                    <div class="status-box" style="background: #e8f5e9; border-left: 4px solid #4CAF50;">
                        <p class="success">✅ CORREÇÃO CONCLUÍDA COM SUCESSO!</p>
                        <p><strong>Registros atualizados:</strong> ${data.registros_atualizados}</p>
                        <p><strong>Total de CANTOS corretos agora:</strong> ${data.total_correto}</p>
                    </div>
                `;
                botao.textContent = "✅ CORRIGIDO!";
            } else {
                div.innerHTML = `
                    <div class="status-box" style="background: #ffebee; border-left: 4px solid #f44336;">
                        <p class="error">❌ ERRO ao corrigir!</p>
                        <p>${data.erro}</p>
                    </div>
                `;
                botao.disabled = false;
                botao.textContent = "🔧 CORRIGIR TIPO_APOSTA AGORA";
            }
        })
        .catch(e => {
            document.getElementById('resultado-correcao').innerHTML = `
                <div class="status-box" style="background: #ffebee; border-left: 4px solid #f44336;">
                    <p class="error">❌ ERRO na requisição!</p>
                    <p>${e.message}</p>
                </div>
            `;
            botao.disabled = false;
            botao.textContent = "🔧 CORRIGIR TIPO_APOSTA AGORA";
        });
    }
    </script>
    <?php else: ?>
    <div class="box" style="background: #e8f5e9; border-left: 4px solid #4CAF50;">
        <h2 class="success">✅ SEM PROBLEMAS!</h2>
        <p>Todos os registros de CANTOS estão com tipo_aposta correto.</p>
    </div>
    <?php endif; ?>

    <div class="box">
        <h2>ℹ️ PRÓXIMOS PASSOS</h2>
        <ol>
            <li>Se houver registros errados, clique em "CORRIGIR TIPO_APOSTA AGORA"</li>
            <li>Após a correção, abra bot_aovivo.php e teste novamente</li>
            <li>Clique em um card de CANTOS e verifique se o filtro está correto</li>
            <li>O modal deve mostrar "Tipo: CANTOS" em vez de "Tipo: GOLS"</li>
        </ol>
    </div>

</body>
</html>

<?php
$conexao->close();
?>
