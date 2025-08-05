<?php
require_once 'config.php';
require_once 'carregar_sessao.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false]);
    exit();
}

$id_usuario = $_SESSION['usuario_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $acao = $data['acao'] ?? '';

    if ($acao === 'resetar') {
        $stmt1 = $conexao->prepare("DELETE FROM controle WHERE id_usuario = ?");
        $stmt1->bind_param("i", $id_usuario);
        $stmt1->execute();
        $stmt1->close();

        $stmt2 = $conexao->prepare("DELETE FROM valor_mentores WHERE id_usuario = ?");
        $stmt2->bind_param("i", $id_usuario);
        $stmt2->execute();
        $stmt2->close();

        echo json_encode(['success' => true]);
        exit();
    }

    $valor = abs(floatval($data['valor'] ?? 0));
    $diaria = floatval($data['diaria'] ?? 0);
    $unidade = intval($data['unidade'] ?? 0);
    $odds = isset($data['odds']) ? floatval(str_replace(',', '.', $data['odds'])) : 0;

    if ($valor <= 0 || !in_array($acao, ['deposito', 'saque', 'cadastrar'])) {
        echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
        exit();
    }

    if ($acao === 'deposito' || $acao === 'cadastrar') {
        $stmt = $conexao->prepare("
            INSERT INTO controle (id_usuario, deposito, diaria, unidade, odds, data_registro)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param("iddid", $id_usuario, $valor, $diaria, $unidade, $odds);
    } elseif ($acao === 'saque') {
        $stmt = $conexao->prepare("
            INSERT INTO controle (id_usuario, saque, diaria, unidade, odds, data_registro)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param("iddid", $id_usuario, $valor, $diaria, $unidade, $odds);
    }

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao salvar no banco']);
    }

    $stmt->close();
    exit();
}

// ✅ Requisição GET: retorna dados da banca
$stmt = $conexao->prepare("SELECT SUM(deposito) FROM controle WHERE id_usuario = ? AND deposito > 0");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$stmt->bind_result($total_deposito);
$stmt->fetch();
$stmt->close();
$total_deposito = $total_deposito ?? 0;

$stmt = $conexao->prepare("SELECT SUM(saque) FROM controle WHERE id_usuario = ? AND saque > 0");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$stmt->bind_result($total_saque);
$stmt->fetch();
$stmt->close();
$total_saque = $total_saque ?? 0;

// ✅ Cálculo do lucro: valor_green - valor_red
$stmt = $conexao->prepare("
    SELECT 
        COALESCE(SUM(valor_green), 0) AS total_green,
        COALESCE(SUM(valor_red), 0) AS total_red
    FROM valor_mentores
    WHERE id_usuario = ?
");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$stmt->bind_result($total_green, $total_red);
$stmt->fetch();
$stmt->close();

$total_green = $total_green ?? 0;
$total_red = $total_red ?? 0;
$lucro = $total_green - $total_red;

// ✅ Novo cálculo da banca: depósito - saque + lucro
$saldo_banca = $total_deposito - $total_saque + $lucro;
$mostrar_radios = $total_deposito > 0;

$stmt = $conexao->prepare("
    SELECT diaria FROM controle
    WHERE id_usuario = ? AND diaria IS NOT NULL
    ORDER BY id DESC LIMIT 1
");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$stmt->bind_result($ultima_diaria);
$stmt->fetch();
$stmt->close();

$stmt = $conexao->prepare("
    SELECT unidade FROM controle
    WHERE id_usuario = ? AND unidade IS NOT NULL
    ORDER BY id DESC LIMIT 1
");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$stmt->bind_result($ultima_unidade);
$stmt->fetch();
$stmt->close();

echo json_encode([
    'success' => true,
    'deposito' => number_format($total_deposito, 2, '.', ''),
    'saque' => number_format($total_saque, 2, '.', ''),
    'banca' => number_format($saldo_banca, 2, '.', ''),
    'lucro' => number_format($lucro, 2, '.', ''),
    'mostrar_radios' => $mostrar_radios,
    'diaria' => number_format($ultima_diaria ?? 0, 2, '.', ''),
    'unidade' => intval($ultima_unidade ?? 0)
]);














