<?php
// ✅ LIMPAR OUTPUT BUFFER (remove espaços em branco extras)
ob_clean();
ob_start();

session_start();
header('Content-Type: application/json; charset=utf-8');

// ✅ TRATAMENTO DE ERROS - Converte warnings/notices em JSON
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro: ' . $errstr, 'debug' => "$errfile:$errline"]);
    exit();
});

// Incluir configuração do banco
require_once __DIR__ . '/config.php';

// ✅ VERIFICAR AUTENTICAÇÃO
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit();
}

// ✅ VERIFICAR AUTORIZAÇÃO (apenas usuário ID 23 pode salvar apostas)
$usuario_id = intval($_SESSION['usuario_id']);
if ($usuario_id !== 23) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Você não tem permissão para salvar apostas']);
    exit();
}

// ✅ OBTER CONEXÃO
$conexao = obterConexao();
if (!$conexao) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao conectar com o banco de dados']);
    exit();
}

// ✅ CAMPOS QUE VÊEM DO FORMULÁRIO
$campos_formulario = [
    'mensagem_completa',
    'titulo',
    'tipo_aposta',
    'time_1',
    'time_2',
    'placar_1',
    'placar_2',
    'escanteios_1',
    'escanteios_2',
    'valor_over',      // ✅ ADICIONADO
    'odds',
    'tipo_odds',       // ✅ ADICIONADO
    'tempo_minuto',
    'odds_inicial_casa',
    'odds_inicial_empate',
    'odds_inicial_fora',
    'ataques_perigosos_1',
    'ataques_perigosos_2',
    'cartoes_amarelos_1',
    'cartoes_amarelos_2',
    'cartoes_vermelhos_1',
    'cartoes_vermelhos_2',
    'chutes_lado_1',
    'chutes_lado_2',
    'chutes_alvo_1',
    'chutes_alvo_2',
    'posse_bola_1',
    'posse_bola_2'
];

// ✅ COLETAR DADOS SIMPLES
$dados = [];
foreach ($campos_formulario as $campo) {
    $dados[$campo] = isset($_POST[$campo]) ? $_POST[$campo] : '';
}

// ✅ VALIDAR CAMPOS OBRIGATÓRIOS
if (empty($dados['mensagem_completa']) || empty($dados['titulo'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Mensagem Completa e Título são obrigatórios']);
    exit();
}

// ✅ ADICIONAR CAMPOS AUTOMÁTICOS
$dados['hora_mensagem'] = date('H:i:s');  // Hora atual em formato HH:MM:SS
$dados['status_aposta'] = 'ativa';        // Status padrão
$dados['resultado'] = null;               // Resultado vazio inicialmente

// ⚠️ NÃO INCLUIR telegram_message_id - será gerado automaticamente pelo banco com AUTO_INCREMENT
// Se o campo não tiver AUTO_INCREMENT, o banco o deixará como NULL, que é permitido

// ✅ CONSTRUIR INSERÇÃO
$colunas = [];
$placeholders = [];
$tipos = '';
$valores = [];

// Adicionar campos do formulário (SEM telegram_message_id - será gerado automaticamente pelo banco)
foreach ($dados as $campo => $valor) {
    $colunas[] = "`" . $campo . "`";
    $placeholders[] = "?";
    
    // Determinar tipo
    if (in_array($campo, ['placar_1', 'placar_2', 'escanteios_1', 'escanteios_2', 'tempo_minuto',
                         'ataques_perigosos_1', 'ataques_perigosos_2', 'cartoes_amarelos_1',
                         'cartoes_amarelos_2', 'cartoes_vermelhos_1', 'cartoes_vermelhos_2',
                         'chutes_lado_1', 'chutes_lado_2', 'chutes_alvo_1', 'chutes_alvo_2',
                         'posse_bola_1', 'posse_bola_2'])) {
        $tipos .= 'i';
        $valores[] = empty($valor) ? 0 : intval($valor);
    } elseif (in_array($campo, ['odds', 'odds_inicial_casa', 'odds_inicial_empate', 'odds_inicial_fora', 'valor_over'])) {
        $tipos .= 'd';
        $valores[] = empty($valor) ? 0 : floatval($valor);
    } elseif ($campo === 'resultado') {
        // resultado pode ser NULL
        $tipos .= 's';
        $valores[] = empty($valor) ? null : $valor;
    } else {
        $tipos .= 's';
        $valores[] = $valor;
    }
}

// ✅ CONSTRUIR SQL
$sql = "INSERT INTO bote (" . implode(', ', $colunas) . ") VALUES (" . implode(', ', $placeholders) . ")";

error_log("SQL: " . $sql);
error_log("TIPOS: " . $tipos);
error_log("VALORES: " . json_encode($valores));

// ✅ PREPARAR E EXECUTAR
$stmt = $conexao->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    error_log("❌ Erro prepare: " . $conexao->error);
    echo json_encode(['success' => false, 'message' => 'Erro ao preparar query: ' . $conexao->error]);
    exit();
}

// Bind parameters
if (!$stmt->bind_param($tipos, ...$valores)) {
    http_response_code(500);
    error_log("❌ Erro bind: " . $stmt->error);
    echo json_encode(['success' => false, 'message' => 'Erro ao processar dados: ' . $stmt->error]);
    exit();
}

// Executar
if ($stmt->execute()) {
    error_log("✅ Aposta salva! ID: " . $stmt->insert_id);
    echo json_encode(['success' => true, 'message' => '✅ Aposta salva com sucesso!', 'aposta_id' => $stmt->insert_id]);
} else {
    // Se erro for duplicate key em telegram_message_id, tenta sem esse campo
    $erro = $stmt->error;
    if (strpos($erro, 'telegram_message_id') !== false && strpos($erro, 'Duplicate') !== false) {
        error_log("⚠️ Erro de duplicate telegram_message_id, tentando sem esse campo...");
        
        // Remover telegram_message_id dos dados
        $colunas_sem_tg = array_filter($colunas, function($col) {
            return $col !== '`telegram_message_id`';
        });
        $placeholders_sem_tg = array_slice($placeholders, 0, count($colunas_sem_tg));
        
        // Recriar SQL sem telegram_message_id
        $sql_sem_tg = "INSERT INTO bote (" . implode(', ', $colunas_sem_tg) . ") VALUES (" . implode(', ', $placeholders_sem_tg) . ")";
        
        $stmt2 = $conexao->prepare($sql_sem_tg);
        if ($stmt2 && $stmt2->bind_param(substr($tipos, 0, count($colunas_sem_tg)), ...array_slice($valores, 0, count($colunas_sem_tg)))) {
            if ($stmt2->execute()) {
                error_log("✅ Aposta salva (sem telegram_message_id)! ID: " . $stmt2->insert_id);
                echo json_encode(['success' => true, 'message' => '✅ Aposta salva com sucesso!', 'aposta_id' => $stmt2->insert_id]);
                $stmt2->close();
            } else {
                http_response_code(500);
                error_log("❌ Erro execute (segunda tentativa): " . $stmt2->error);
                echo json_encode(['success' => false, 'message' => 'Erro ao salvar: ' . $stmt2->error]);
                $stmt2->close();
            }
        } else {
            http_response_code(500);
            error_log("❌ Erro na segunda tentativa: " . $conexao->error);
            echo json_encode(['success' => false, 'message' => 'Erro ao salvar: ' . $conexao->error]);
        }
    } else {
        http_response_code(500);
        error_log("❌ Erro execute: " . $stmt->error);
        echo json_encode(['success' => false, 'message' => 'Erro ao salvar: ' . $stmt->error]);
    }
}

$stmt->close();
$conexao->close();

// ✅ LIMPAR OUTPUT BUFFER E ENVIAR RESPOSTA
ob_end_flush();
?>
