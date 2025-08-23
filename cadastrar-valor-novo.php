<?php
// cadastrar-valor-novo.php
session_start();
require_once 'config.php';

// Configurar para retornar JSON
header('Content-Type: application/json; charset=utf-8');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Obter dados JSON do corpo da requisição
$input = file_get_contents('php://input');
$dados = json_decode($input, true);

// Validar entrada JSON
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode([
        'tipo' => 'erro',
        'mensagem' => '❌ Dados JSON inválidos.'
    ]);
    exit;
}

// Validar sessão do usuário
$id_usuario = $_SESSION['usuario_id'] ?? null;
if (!$id_usuario) {
    echo json_encode([
        'tipo' => 'erro',
        'mensagem' => '❌ Usuário não autenticado.'
    ]);
    exit;
}

// Extrair dados recebidos
$id_mentor = $dados['id_mentor'] ?? null;
$tipo_operacao = $dados['tipo_operacao'] ?? null;
$valor_green = $dados['valor_green'] ?? null;
$valor_red = $dados['valor_red'] ?? null;

// Validações básicas
if (!$id_mentor || !$tipo_operacao) {
    echo json_encode([
        'tipo' => 'erro',
        'mensagem' => '❌ Dados obrigatórios não informados.'
    ]);
    exit;
}

// Validar tipo de operação
if (!in_array($tipo_operacao, ['cash', 'green', 'red'])) {
    echo json_encode([
        'tipo' => 'erro',
        'mensagem' => '❌ Tipo de operação inválido.'
    ]);
    exit;
}

// Validar valores
if ($valor_green !== null && $valor_green < 0) {
    echo json_encode([
        'tipo' => 'erro',
        'mensagem' => '❌ Valor green não pode ser negativo.'
    ]);
    exit;
}

if ($valor_red !== null && $valor_red < 0) {
    echo json_encode([
        'tipo' => 'erro',
        'mensagem' => '❌ Valor red não pode ser negativo.'
    ]);
    exit;
}

// Verificar se há valor para inserir
if ($valor_green === null && $valor_red === null) {
    echo json_encode([
        'tipo' => 'aviso',
        'mensagem' => '⚠️ Nenhum valor foi informado para cadastro.'
    ]);
    exit;
}

if (($valor_green !== null && $valor_green <= 0) && ($valor_red !== null && $valor_red <= 0)) {
    echo json_encode([
        'tipo' => 'aviso',
        'mensagem' => '⚠️ Informe um valor maior que zero.'
    ]);
    exit;
}

try {
    // Verificar se o mentor existe e pertence ao usuário
    $stmt = $conexao->prepare("SELECT nome FROM mentores WHERE id = ? AND id_usuario = ?");
    $stmt->bind_param("ii", $id_mentor, $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode([
            'tipo' => 'erro',
            'mensagem' => '❌ Mentor não encontrado ou não pertence ao usuário.'
        ]);
        exit;
    }
    
    $mentor = $result->fetch_assoc();
    $nome_mentor = $mentor['nome'];

    // Se for operação Red, verificar banca disponível
    if ($valor_red !== null && $valor_red > 0) {
        // Calcular banca atual
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
        $valor_red_total = $res[1] ?? 0;

        $saldo_mentores = $valor_green_total - $valor_red_total;
        $banca_total = $soma_depositos - $soma_saque + $saldo_mentores;

        if ($valor_red > $banca_total) {
            echo json_encode([
                'tipo' => 'aviso',
                'mensagem' => '⚠️ Saldo da banca insuficiente! Banca atual: ' . number_format($banca_total, 2, ',', '.') . '. Faça um depósito!'
            ]);
            exit;
        }
    }

    // Preparar dados para inserção
    $green = 0;
    $red = 0;
    
    if ($valor_green !== null && $valor_green > 0) {
        $green = 1;
        $valor_red = null; // Garantir que só um valor seja inserido
    }
    
    if ($valor_red !== null && $valor_red > 0) {
        $red = 1;
        $valor_green = null; // Garantir que só um valor seja inserido
    }

    // Inserir no banco de dados
    $stmt = $conexao->prepare("
        INSERT INTO valor_mentores 
        (id_usuario, id_mentores, green, red, valor_green, valor_red, data_criacao)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $stmt->bind_param("iiiiss", 
        $id_usuario, 
        $id_mentor, 
        $green, 
        $red, 
        $valor_green, 
        $valor_red
    );
    
    $stmt->execute();

    // Preparar mensagem de sucesso
    $tipo_msg = '';
    $valor_msg = '';
    
    if ($valor_green !== null && $valor_green > 0) {
        $tipo_msg = 'Positivo';
        $valor_msg = 'R$ ' . number_format($valor_green, 2, ',', '.');
    } elseif ($valor_red !== null && $valor_red > 0) {
        $tipo_msg = 'Negativo';
        $valor_msg = 'R$ ' . number_format($valor_red, 2, ',', '.');
    }

    $mensagem_sucesso = "✅ Cadastro realizado com sucesso!\n";
    $mensagem_sucesso .= "Mentor: {$nome_mentor}\n";
    $mensagem_sucesso .= "Tipo: {$tipo_operacao} ({$tipo_msg})\n";
    $mensagem_sucesso .= "Valor: {$valor_msg}";

    echo json_encode([
        'tipo' => 'sucesso',
        'mensagem' => $mensagem_sucesso,
        'dados' => [
            'mentor' => $nome_mentor,
            'tipo_operacao' => $tipo_operacao,
            'valor_green' => $valor_green,
            'valor_red' => $valor_red
        ]
    ]);

} catch (Exception $e) {
    // Log do erro para debug
    error_log("Erro no cadastro novo sistema: " . $e->getMessage());
    
    echo json_encode([
        'tipo' => 'erro',
        'mensagem' => '❌ Erro interno do servidor: ' . $e->getMessage()
    ]);
}
?>