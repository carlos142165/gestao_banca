<?php
session_start();
require_once 'config.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// ⚠️ Dados recebidos
$id_usuario      = $_SESSION['usuario_id'] ?? null;
$id_mentor       = $_POST['id_mentor'] ?? null;
$valor_raw       = trim($_POST['valor'] ?? '');
$opcao           = $_POST['opcao'] ?? null;
$user_time_zone  = $_POST['user_time_zone'] ?? 'UTC'; // Fuso detectado via JS
$data_local      = $_POST['data_local'] ?? null;       // Data ISO do navegador (em UTC)

// 🕒 Verifica timezone recebido
if (!in_array($user_time_zone, timezone_identifiers_list())) {
  $user_time_zone = 'UTC';
}

// 🕘 Cria data/hora local com segurança (corrigido!)
$data_criacao = null;
try {
  if (!empty($data_local)) {
    // Interpreta ISO como UTC corretamente antes de converter
    $dt = new DateTime($data_local, new DateTimeZone('UTC'));
    $dt->setTimezone(new DateTimeZone($user_time_zone));
    $data_criacao = $dt->format('Y-m-d H:i:s');
  } else {
    date_default_timezone_set($user_time_zone);
    $data_criacao = date('Y-m-d H:i:s');
  }
} catch (Exception $e) {
  error_log("Erro ao processar data_local: " . $e->getMessage());
  date_default_timezone_set($user_time_zone);
  $data_criacao = date('Y-m-d H:i:s');
}

// 🐞 Logs de depuração
file_put_contents('log_debug.txt', "⚠️ data_local recebido: " . var_export($data_local, true) . "\n", FILE_APPEND);
file_put_contents('log_debug.txt', "📍 Timezone aplicado: " . $user_time_zone . "\n", FILE_APPEND);
file_put_contents('log_debug.txt', "⏰ Data final usada: " . $data_criacao . "\n", FILE_APPEND);

// 💰 Valida valor
$valor_float = is_numeric($valor_raw) ? floatval($valor_raw) : null;
if (!$id_usuario || !$id_mentor || $valor_float === null || !$opcao) {
  echo json_encode([
    'tipo' => 'erro',
    'mensagem' => '❌ Dados incompletos ou inválidos.'
  ]);
  exit;
}

// ✅ Flags
$green = $opcao === 'green' ? 1 : 0;
$red   = $opcao === 'red'   ? 1 : 0;
$valor_green = $green ? $valor_float : null;
$valor_red   = $red ? $valor_float : null;

// 🧮 Cálculo da banca total
try {
  $query = $conexao->prepare("SELECT SUM(deposito) FROM controle WHERE id_usuario = ?");
  $query->bind_param("i", $id_usuario);
  $query->execute();
  $soma_depositos = $query->get_result()->fetch_row()[0] ?? 0;

  $query = $conexao->prepare("SELECT SUM(saque) FROM controle WHERE id_usuario = ?");
  $query->bind_param("i", $id_usuario);
  $query->execute();
  $soma_saque = $query->get_result()->fetch_row()[0] ?? 0;

  $query = $conexao->prepare("SELECT SUM(valor_green), SUM(valor_red) FROM valor_mentores WHERE id_usuario = ?");
  $query->bind_param("i", $id_usuario);
  $query->execute();
  $res = $query->get_result()->fetch_row();
  $valor_green_total = $res[0] ?? 0;
  $valor_red_total   = $res[1] ?? 0;

  $saldo_mentores = $valor_green_total - $valor_red_total;
  $banca_total = $soma_depositos - $soma_saque + $saldo_mentores;

  if ($red && $valor_red > $banca_total) {
    echo json_encode([
      'tipo' => 'aviso',
      'mensagem' => '⚠️ Saldo da banca insuficiente, Faça um depósito!'
    ]);
    exit;
  }
} catch (Exception $e) {
  echo json_encode([
    'tipo' => 'erro',
    'mensagem' => '❌ Erro ao consultar banca: ' . $e->getMessage()
  ]);
  exit;
}

// ✅ Inserção
try {
  $stmt = $conexao->prepare("INSERT INTO valor_mentores 
    (id_usuario, id_mentores, green, red, valor_green, valor_red, data_criacao)
    VALUES (?, ?, ?, ?, ?, ?, ?)");
  $stmt->bind_param("iiiisss", $id_usuario, $id_mentor, $green, $red, $valor_green, $valor_red, $data_criacao);
  $stmt->execute();

  echo json_encode([
    'tipo' => 'sucesso',
    'mensagem' => '✅ Cadastro feito com sucesso!'
  ]);
} catch (Exception $e) {
  echo json_encode([
    'tipo' => 'erro',
    'mensagem' => '❌ Erro no banco: ' . $e->getMessage()
  ]);
}
?>










