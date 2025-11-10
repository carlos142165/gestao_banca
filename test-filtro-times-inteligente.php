<?php
/**
 * TESTE: Filtro Inteligente de Times (com ou sem sigla)
 * 
 * Demonstra como o novo filtro consegue encontrar times
 * mesmo quando o banco tem "EC Santos" e você procura por "Santos"
 */

// ✅ FUNÇÃO: Extrair nome do time sem sigla inicial
function extrairNomeTime($timeCompleto) {
    // Se tem espaço, pega a parte depois da sigla (geralmente a sigla é a primeira parte)
    // Exemplo: "EC Santos" -> "Santos"
    $partes = explode(' ', trim($timeCompleto), 2);
    if (count($partes) > 1) {
        // Verificar se a primeira parte é uma sigla (até 3 caracteres, sem números)
        if (strlen($partes[0]) <= 3 && !preg_match('/\d/', $partes[0])) {
            return trim($partes[1]);
        }
    }
    return trim($timeCompleto);
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Teste - Filtro Inteligente de Times</title>
    <style>
        body { font-family: Arial; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 3px solid #0066cc; padding-bottom: 10px; }
        .test-case { margin: 20px 0; padding: 15px; border-left: 4px solid #0066cc; background: #f9f9f9; }
        .test-case strong { color: #0066cc; }
        .input { color: #666; font-family: monospace; background: #fff; padding: 8px; border-radius: 4px; }
        .output { color: #090; font-family: monospace; background: #f0fff0; padding: 8px; border-radius: 4px; margin-top: 8px; }
        .status { padding: 8px; border-radius: 4px; margin-top: 8px; }
        .status.match { background: #e8f5e9; color: #2e7d32; }
        .status.nomatch { background: #ffebee; color: #c62828; }
        table { width: 100%; margin-top: 20px; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #0066cc; color: white; }
        tr:hover { background: #f5f5f5; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Teste: Filtro Inteligente de Times</h1>
        
        <div class="test-case">
            <strong>Objetivo:</strong> Encontrar times mesmo com siglas diferentes ou variações
        </div>

        <h2>Exemplos de Transformação</h2>

        <?php
        $exemplos = [
            'EC Santos' => 'Santos',
            'Santos' => 'Santos',
            'Internacional' => 'Internacional',
            'SC Internacional' => 'Internacional',
            'Grêmio' => 'Grêmio',
            'SC Grêmio' => 'Grêmio',
            'São Paulo' => 'São Paulo',
            'São Paulo FC' => 'São Paulo', // Não vira "FC" porque "São" não é uma sigla curta com 2-3 letras
            'Real Madrid' => 'Real Madrid',
            'CF Real Madrid' => 'Real Madrid',
            'Everton' => 'Everton',
            'EC Bahia' => 'Bahia',
            'Bahia' => 'Bahia',
        ];
        
        echo '<table>';
        echo '<tr><th>Entrada (banco de dados)</th><th>Saída (busca por)</th><th>Status</th></tr>';
        
        foreach ($exemplos as $entrada => $esperado) {
            $resultado = extrairNomeTime($entrada);
            $status = ($resultado === $esperado) ? '✅ Correto' : '❌ Erro';
            $classe = ($resultado === $esperado) ? 'match' : 'nomatch';
            
            echo "<tr>";
            echo "<td><code>$entrada</code></td>";
            echo "<td><code>$resultado</code></td>";
            echo "<td class='status $classe'>$status (esperado: <code>$esperado</code>)</td>";
            echo "</tr>";
        }
        
        echo '</table>';
        ?>

        <h2>Como Funciona a Busca</h2>
        
        <div class="test-case">
            <strong>Cenário 1:</strong> Usuário clica em "Santos" (sem sigla)
            <div class="input">📝 Entrada: "Santos"</div>
            <div class="output">🔍 SQL busca por: "santos" LIKE "%santos%" (case-insensitive)</div>
            <div class="status match">✅ Encontra "EC Santos" no banco porque "santos" está em "%ec santos%"</div>
        </div>

        <div class="test-case">
            <strong>Cenário 2:</strong> Entrada tem sigla "EC Santos"
            <div class="input">📝 Entrada do usuário: "EC Santos"</div>
            <div class="output">
                🔍 Extrai: "Santos"<br>
                🔍 SQL busca por: "santos" E também por "ec santos" LIKE "%santos%" (ambos)
            </div>
            <div class="status match">✅ Encontra "EC Santos" (busca direta) ou "Santos" (busca sem sigla)</div>
        </div>

        <div class="test-case">
            <strong>Cenário 3:</strong> Sigla mais longa (ex: "CF Real Madrid" - "CF" é sigla)
            <div class="input">📝 Entrada: "CF Real Madrid"</div>
            <div class="output">
                🔍 Extrai: "Real Madrid" (CF é sigla, tem 2 caracteres)<br>
                🔍 SQL busca por: "real madrid" LIKE "%real madrid%"
            </div>
            <div class="status match">✅ Encontra registros com "Real Madrid"</div>
        </div>

        <h2>Lógica SQL Usada</h2>
        <pre style="background: #f0f0f0; padding: 15px; border-radius: 8px; overflow-x: auto;">
WHERE (
    LOWER(time_1) LIKE CONCAT('%', LOWER(?), '%')           -- busca time completo
    OR LOWER(time_1) LIKE CONCAT('%', LOWER(?), '%')        -- busca sem sigla
    OR LOWER(time_2) LIKE CONCAT('%', LOWER(?), '%')        -- busca time completo
    OR LOWER(time_2) LIKE CONCAT('%', LOWER(?), '%')        -- busca sem sigla
)
        </pre>

        <h2>Parâmetros Vinculados</h2>
        <table>
            <tr>
                <th>Parâmetro 1</th>
                <td><code>$time1</code> = time completo do usuário (ex: "EC Santos")</td>
            </tr>
            <tr>
                <th>Parâmetro 2</th>
                <td><code>$time1_nome</code> = nome sem sigla (ex: "Santos")</td>
            </tr>
            <tr>
                <th>Parâmetro 3</th>
                <td><code>$time2</code> = time2 completo (ex: "Internacional")</td>
            </tr>
            <tr>
                <th>Parâmetro 4</th>
                <td><code>$time2_nome</code> = time2 sem sigla (ex: "Internacional")</td>
            </tr>
        </table>

        <div style="margin-top: 30px; padding: 15px; background: #e3f2fd; border-radius: 8px;">
            <strong>✅ Resultado Final:</strong><br>
            Agora o filtro encontrará "Santos" mesmo que o banco tenha registros como:
            <ul>
                <li>Santos</li>
                <li>EC Santos</li>
                <li>santos</li>
                <li>EC SANTOS</li>
                <li>Anything with santos</li>
            </ul>
            Tudo graças à comparação case-insensitive com LOWER() e LIKE com wildcards.
        </div>
    </div>
</body>
</html>
