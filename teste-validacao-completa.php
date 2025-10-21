<?php
/**
 * TESTE COMPLETO DO SISTEMA DE VALIDAÇÃO
 * Este arquivo testa se o sistema de validação de limites está funcionando corretamente
 */

session_start();
require_once 'config.php';
require_once 'config_mercadopago.php';

// Definir um ID de usuário para teste (altere conforme necessário)
$id_usuario_teste = $_GET['user_id'] ?? 1;

// DEBUG: Verificar conexão
echo "<h2>🔍 TESTE DE VALIDAÇÃO DO SISTEMA DE PLANOS</h2>";
echo "<hr>";

// 1. Verificar conexão com banco
echo "<h3>1. Verificação de Conexão:</h3>";
if ($conexao->connect_error) {
    echo "<span style='color: red;'>❌ Erro ao conectar: " . $conexao->connect_error . "</span>";
    die();
} else {
    echo "<span style='color: green;'>✅ Conexão OK</span><br>";
}

// 2. Obter dados do usuário
echo "<h3>2. Dados do Usuário:</h3>";
$stmt = $conexao->prepare("SELECT id, nome, email, id_plano FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $id_usuario_teste);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<span style='color: red;'>❌ Usuário não encontrado (ID: $id_usuario_teste)</span>";
    die();
}

$usuario = $result->fetch_assoc();
$stmt->close();

echo "ID: " . $usuario['id'] . "<br>";
echo "Nome: " . $usuario['nome'] . "<br>";
echo "Email: " . $usuario['email'] . "<br>";
echo "ID Plano: " . $usuario['id_plano'] . "<br>";

// 3. Obter dados do plano
echo "<h3>3. Dados do Plano:</h3>";
$stmt = $conexao->prepare("SELECT * FROM planos WHERE id = ?");
$stmt->bind_param("i", $usuario['id_plano']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<span style='color: orange;'>⚠️ Plano não encontrado, usando GRATUITO</span><br>";
    $stmt = $conexao->prepare("SELECT * FROM planos WHERE nome = 'GRATUITO'");
    $stmt->execute();
    $result = $stmt->get_result();
}

$plano = $result->fetch_assoc();
$stmt->close();

echo "Nome do Plano: " . $plano['nome'] . "<br>";
echo "Limite de Mentores: " . $plano['mentores_limite'] . "<br>";
echo "Limite de Entradas Diárias: " . $plano['entradas_diarias'] . "<br>";

// 4. Contar mentores atuais
echo "<h3>4. Contagem de Mentores:</h3>";
$stmt = $conexao->prepare("SELECT COUNT(*) as total FROM mentores WHERE id_usuario = ?");
$stmt->bind_param("i", $id_usuario_teste);
$stmt->execute();
$result = $stmt->get_result();
$mentores = $result->fetch_assoc();
$stmt->close();

echo "Mentores atuais: " . $mentores['total'] . "<br>";
echo "Limite permitido: " . $plano['mentores_limite'] . "<br>";

if ($mentores['total'] < $plano['mentores_limite']) {
    echo "<span style='color: green;'>✅ Usuário PODE adicionar mentor</span><br>";
} else {
    echo "<span style='color: red;'>❌ Usuário NÃO PODE adicionar mentor (limite atingido)</span><br>";
}

// 5. Testar função verificarLimiteMentores()
echo "<h3>5. Teste da Função verificarLimiteMentores():</h3>";
$pode_adicionar = MercadoPagoManager::verificarLimiteMentores($id_usuario_teste, $usuario['id_plano']);
if ($pode_adicionar) {
    echo "<span style='color: green;'>✅ Função retornou TRUE (pode adicionar)</span><br>";
} else {
    echo "<span style='color: red;'>❌ Função retornou FALSE (não pode adicionar)</span><br>";
}

// 6. Contar entradas de hoje
echo "<h3>6. Contagem de Entradas de Hoje:</h3>";
$stmt = $conexao->prepare("SELECT COUNT(*) as total FROM valor_mentores WHERE id_usuario = ? AND DATE(data_criacao) = CURDATE()");
$stmt->bind_param("i", $id_usuario_teste);
$stmt->execute();
$result = $stmt->get_result();
$entradas = $result->fetch_assoc();
$stmt->close();

echo "Entradas hoje: " . $entradas['total'] . "<br>";
echo "Limite diário: " . $plano['entradas_diarias'] . "<br>";

if ($entradas['total'] < $plano['entradas_diarias'] || $plano['entradas_diarias'] >= 999) {
    echo "<span style='color: green;'>✅ Usuário PODE adicionar entrada</span><br>";
} else {
    echo "<span style='color: red;'>❌ Usuário NÃO PODE adicionar entrada (limite atingido)</span><br>";
}

// 7. Testar função verificarLimiteEntradas()
echo "<h3>7. Teste da Função verificarLimiteEntradas():</h3>";
$pode_entrada = MercadoPagoManager::verificarLimiteEntradas($id_usuario_teste, $usuario['id_plano']);
if ($pode_entrada) {
    echo "<span style='color: green;'>✅ Função retornou TRUE (pode adicionar entrada)</span><br>";
} else {
    echo "<span style='color: red;'>❌ Função retornou FALSE (não pode adicionar entrada)</span><br>";
}

// 8. Listar mentores do usuário
echo "<h3>8. Mentores do Usuário:</h3>";
$stmt = $conexao->prepare("SELECT id, nome FROM mentores WHERE id_usuario = ? ORDER BY id DESC");
$stmt->bind_param("i", $id_usuario_teste);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "Nenhum mentor cadastrado<br>";
} else {
    echo "<ul>";
    while ($row = $result->fetch_assoc()) {
        echo "<li>ID: " . $row['id'] . " - Nome: " . $row['nome'] . "</li>";
    }
    echo "</ul>";
}
$stmt->close();

echo "<hr>";
echo "<h3>✅ Teste Completo</h3>";
echo "<p>Para testar com outro usuário, acesse: <code>?user_id=ID_DO_USUARIO</code></p>";

?>
