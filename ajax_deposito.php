<?php
require_once 'config.php';
require_once 'carregar_sessao.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sessão inválida']);
    exit();
}

$id_usuario = intval($_SESSION['usuario_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);

    $acao = $data['acao'] ?? '';
    $valor = abs(floatval($data['valor'] ?? 0));
    $diaria = floatval($data['diaria'] ?? 0);
    $unidade = intval($data['unidade'] ?? 0);
    $odds = isset($data['odds']) ? floatval(str_replace(',', '.', $data['odds'])) : 0;
    $nome = trim($data['nome'] ?? '');

    if ($acao === 'resetar') {
        $stmt1 = $conexao->prepare("DELETE FROM controle WHERE id_usuario = ?");
        $stmt1->bind_param("i", $id_usuario);
        $stmt1->execute();
        $stmt1->close();

        $stmt2 = $conexao->prepare("DELETE FROM valor_mentores WHERE id_usuario = ?");
        $stmt2->bind_param("i", $id_usuario);
        $stmt2->execute();
        $stmt2->close();

        echo json_encode(['success' => true, 'message' => 'Dados resetados com sucesso']);
        exit();
    }

    if ($acao === 'alterar') {
    $stmt = $conexao->prepare("
        UPDATE controle SET diaria = ?, unidade = ?, odds = ?
        WHERE id_usuario = ? ORDER BY id DESC LIMIT 1
    ");
    $stmt->bind_param("didi", $diaria, $unidade, $odds, $id_usuario);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Dados alterados com sucesso']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao alterar dados']);
    }
    $stmt->close();
    exit();
}

if ($valor <= 0 || !in_array($acao, ['deposito', 'saque', 'cadastrar'])) {
    echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
    exit();
}


    $query = "";
    if ($acao === 'deposito' || $acao === 'cadastrar') {
        $query = "INSERT INTO controle (id_usuario, deposito, diaria, unidade, odds, data_registro) VALUES (?, ?, ?, ?, ?, NOW())";
    } elseif ($acao === 'saque') {
        $query = "INSERT INTO controle (id_usuario, saque, diaria, unidade, odds, data_registro) VALUES (?, ?, ?, ?, ?, NOW())";
    }

    $stmt = $conexao->prepare($query);
    $stmt->bind_param("iddid", $id_usuario, $valor, $diaria, $unidade, $odds);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Operação realizada com sucesso']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao salvar no banco']);
    }
    $stmt->close();
    exit();
}

// ✅ Requisição GET: retorna dados da banca
function getSoma($conexao, $campo, $id_usuario) {
    $stmt = $conexao->prepare("SELECT SUM($campo) FROM controle WHERE id_usuario = ? AND $campo > 0");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $stmt->bind_result($total);
    $stmt->fetch();
    $stmt->close();
    return $total ?? 0;
}

$total_deposito = getSoma($conexao, 'deposito', $id_usuario);
$total_saque = getSoma($conexao, 'saque', $id_usuario);

// ✅ Cálculo do lucro
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

$lucro = $total_green - $total_red;
$saldo_banca = $total_deposito - $total_saque + $lucro;
$mostrar_radios = $total_deposito > 0;

function getUltimoCampo($conexao, $campo, $id_usuario) {
    $stmt = $conexao->prepare("
        SELECT $campo FROM controle
        WHERE id_usuario = ? AND $campo IS NOT NULL
        ORDER BY id DESC LIMIT 1
    ");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $stmt->bind_result($valor);
    $stmt->fetch();
    $stmt->close();
    return $valor;
}

$ultima_diaria = getUltimoCampo($conexao, 'diaria', $id_usuario);
$ultima_unidade = getUltimoCampo($conexao, 'unidade', $id_usuario);

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


