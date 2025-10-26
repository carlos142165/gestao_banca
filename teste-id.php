<?php
session_start();

// Verificar se está logado
if (!isset($_SESSION['usuario_id'])) {
    echo "❌ NÃO ESTÁ LOGADO";
    echo "<br><br>";
    echo "Faça login primeiro!";
    exit;
}

$id = $_SESSION['usuario_id'];
echo "✅ LOGADO COM ID: " . htmlspecialchars($id);
echo "<br><br>";

if ($id === 23) {
    echo "✅ ID CORRETO (23)";
    echo "<br>";
    echo "A guia DEVE aparecer no menu!";
} else {
    echo "❌ ID INCORRETO";
    echo "<br>";
    echo "Você está logado com ID: " . htmlspecialchars($id);
    echo "<br>";
    echo "Precisa estar com ID: 23";
}

echo "<br><br>";
echo "<a href='gestao-diaria.php'>Voltar para Gestão de Banca</a>";
?>
