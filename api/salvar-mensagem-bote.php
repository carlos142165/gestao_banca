<?php
/**
 * API para salvar mensagens do Telegram no banco de dados
 * Arquivo: api/salvar-mensagem-bote.php
 * 
 * Esta função é chamada cada vez que uma mensagem válida é formatada
 */

header('Content-Type: application/json; charset=utf-8');

// Incluir configuração do banco de dados
include '../config.php';

// Para compatibilidade, usar $conexao como $conn
$conn = $conexao;

/**
 * Função para salvar mensagem no banco de dados
 * 
 * @param array $dadosMensagem Array com todos os dados extraídos da mensagem
 * @return array Array com status e mensagem de resposta
 */
function salvarMensagemBote($dadosMensagem) {
    global $conn;
    
    try {
        // Preparar statement para evitar SQL Injection
        $stmt = $conn->prepare("
            INSERT INTO bote (
                telegram_message_id,
                mensagem_completa,
                titulo,
                tipo_aposta,
                time_1,
                time_2,
                placar_1,
                placar_2,
                escanteios_1,
                escanteios_2,
                valor_over,
                odds,
                tipo_odds,
                hora_mensagem,
                status_aposta
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )
            ON DUPLICATE KEY UPDATE
                data_criacao = CURRENT_TIMESTAMP
        ");
        
        if (!$stmt) {
            throw new Exception("Erro ao preparar statement: " . $conn->error);
        }
        
        // Bind dos parâmetros
        $stmt->bind_param(
            "isssssiiiiddsss",
            $dadosMensagem['telegram_message_id'],
            $dadosMensagem['mensagem_completa'],
            $dadosMensagem['titulo'],
            $dadosMensagem['tipo_aposta'],
            $dadosMensagem['time_1'],
            $dadosMensagem['time_2'],
            $dadosMensagem['placar_1'],
            $dadosMensagem['placar_2'],
            $dadosMensagem['escanteios_1'],
            $dadosMensagem['escanteios_2'],
            $dadosMensagem['valor_over'],
            $dadosMensagem['odds'],
            $dadosMensagem['tipo_odds'],
            $dadosMensagem['hora_mensagem'],
            $dadosMensagem['status_aposta']
        );
        
        // Executar
        if ($stmt->execute()) {
            return [
                'sucesso' => true,
                'mensagem' => 'Mensagem salva com sucesso no banco de dados',
                'id' => $conn->insert_id
            ];
        } else {
            throw new Exception("Erro ao executar insert: " . $stmt->error);
        }
        
        $stmt->close();
        
    } catch (Exception $e) {
        return [
            'sucesso' => false,
            'mensagem' => 'Erro ao salvar: ' . $e->getMessage()
        ];
    }
}

/**
 * Endpoint POST: Receber dados da mensagem e salvar
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Receber dados JSON
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode([
            'sucesso' => false,
            'mensagem' => 'Dados JSON inválidos'
        ]);
        exit;
    }
    
    // Chamar função para salvar
    $resultado = salvarMensagemBote($input);
    
    http_response_code($resultado['sucesso'] ? 200 : 400);
    echo json_encode($resultado);
    exit;
}

/**
 * Endpoint GET: Listar todas as mensagens salvas
 */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    
    $action = $_GET['action'] ?? '';
    
    // Listar mensagens
    if ($action === 'listar') {
        try {
            $limite = intval($_GET['limite'] ?? 50);
            $offset = intval($_GET['offset'] ?? 0);
            
            $result = $conn->query("
                SELECT * FROM bote 
                ORDER BY data_criacao DESC 
                LIMIT $offset, $limite
            ");
            
            $mensagens = [];
            while ($row = $result->fetch_assoc()) {
                $mensagens[] = $row;
            }
            
            // Total de registros
            $total = $conn->query("SELECT COUNT(*) as total FROM bote");
            $totalRow = $total->fetch_assoc();
            
            http_response_code(200);
            echo json_encode([
                'sucesso' => true,
                'total' => $totalRow['total'],
                'limite' => $limite,
                'offset' => $offset,
                'dados' => $mensagens
            ]);
            
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'sucesso' => false,
                'mensagem' => $e->getMessage()
            ]);
        }
        exit;
    }
    
    // Estatísticas
    if ($action === 'estatisticas') {
        try {
            $stats = $conn->query("
                SELECT 
                    COUNT(*) as total_mensagens,
                    SUM(CASE WHEN status_aposta = 'ATIVA' THEN 1 ELSE 0 END) as ativas,
                    SUM(CASE WHEN status_aposta = 'GANHA' THEN 1 ELSE 0 END) as ganhas,
                    SUM(CASE WHEN status_aposta = 'PERDIDA' THEN 1 ELSE 0 END) as perdidas,
                    COUNT(DISTINCT tipo_aposta) as tipos_distintos
                FROM bote
            ");
            
            $dados = $stats->fetch_assoc();
            
            http_response_code(200);
            echo json_encode([
                'sucesso' => true,
                'dados' => $dados
            ]);
            
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'sucesso' => false,
                'mensagem' => $e->getMessage()
            ]);
        }
        exit;
    }
}

http_response_code(405);
echo json_encode([
    'sucesso' => false,
    'mensagem' => 'Método HTTP não permitido'
]);
?>
