<?php
session_start();
require_once 'config.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// ⚠️ Segurança básica
$id_usuario = $_SESSION['usuario_id'] ?? null;
$id_mentor  = $_POST['id_mentor'] ?? null;
$valor_str  = $_POST['valor'] ?? null;
$opcao      = $_POST['opcao'] ?? null;

// 🧼 Limpeza e validação do valor
// Remove tudo que não for número ou vírgula/ponto
$valor_limpo = str_replace(['R$', '.', ','], ['', '', '.'], $valor_str);
$valor_float = is_numeric($valor_limpo) ? floatval($valor_limpo) : null;

// 🎯 Validação final
if (!$id_usuario || !$id_mentor || !$valor_float || !$opcao) {
  echo "❌ Dados incompletos ou inválidos.";
  exit;
}

// ✅ Flags para colunas
$green = $opcao === 'green' ? 1 : 0;
$red   = $opcao === 'red'   ? 1 : 0;

$valor_green = $green ? $valor_float : null;
$valor_red   = $red ? $valor_float : null;
$data_criacao = date('Y-m-d H:i:s');

// 💾 Preparar o INSERT
$stmt = $conexao->prepare("INSERT INTO valor_mentores 
  (id_usuario, id_mentores, green, red, valor_green, valor_red, data_criacao)
  VALUES (?, ?, ?, ?, ?, ?, ?)");

$stmt->bind_param("iiiisss", $id_usuario, $id_mentor, $green, $red, $valor_green, $valor_red, $data_criacao);

try {
  $stmt->execute();
  echo "✅ Cadastro feito com sucesso!";
} catch (Exception $e) {
  echo "❌ Erro no banco: " . $e->getMessage();
}
?>


