<?php
// 🔧 CONFIGURAÇÕES DE CONEXÃO COM O BANCO DE DADOS
define('DB_HOST', '127.0.0.1');
define('DB_USERNAME', 'u857325944_formu');
define('DB_PASSWORD', 'JkF4B7N1');
define('DB_NAME', 'u857325944_formu');

// Cabeçalho JSON
header('Content-Type: application/json');

try {
    // 🔗 Conectar ao banco de dados
    $conexao = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
    
    // ✅ Verificar conexão
    if ($conexao->connect_error) {
        throw new Exception("Erro de conexão: " . $conexao->connect_error);
    }
    
    // 📅 Obter data de hoje (formato Y-m-d)
    $data_hoje = date('Y-m-d');
    
    // 🔍 DEBUG: Listar todas as tabelas
    $debug_info = [];
    $tabelas = $conexao->query("SHOW TABLES");
    $debug_info['tabelas_disponiveis'] = [];
    while ($row = $tabelas->fetch_row()) {
        $debug_info['tabelas_disponiveis'][] = $row[0];
    }
    
    // 🔍 DEBUG: Se encontrou tabela "bote", mostrar colunas
    if (in_array('bote', $debug_info['tabelas_disponiveis'])) {
        $colunas = $conexao->query("DESCRIBE bote");
        $debug_info['colunas_bote'] = [];
        while ($row = $colunas->fetch_assoc()) {
            $debug_info['colunas_bote'][] = $row['Field'];
        }
        
        // 🟢 Contar mensagens GREEN de hoje
        $query_green = "SELECT COUNT(*) as total_green FROM bote 
                        WHERE resultado = 'GREEN' 
                        AND DATE(data_criacao) = '$data_hoje'";
        
        $resultado_green = $conexao->query($query_green);
        if ($resultado_green) {
            $row_green = $resultado_green->fetch_assoc();
            $total_green = $row_green['total_green'] ?? 0;
        } else {
            $total_green = 0;
            $debug_info['erro_green'] = $conexao->error;
        }
        
        // 🔴 Contar mensagens RED de hoje
        $query_red = "SELECT COUNT(*) as total_red FROM bote 
                      WHERE resultado = 'RED' 
                      AND DATE(data_criacao) = '$data_hoje'";
        
        $resultado_red = $conexao->query($query_red);
        if ($resultado_red) {
            $row_red = $resultado_red->fetch_assoc();
            $total_red = $row_red['total_red'] ?? 0;
        } else {
            $total_red = 0;
            $debug_info['erro_red'] = $conexao->error;
        }
        
        // 🎯 Retornar dados em JSON com DEBUG
        echo json_encode([
            'success' => true,
            'green' => intval($total_green),
            'red' => intval($total_red),
            'data' => $data_hoje,
            'message' => "Placar do dia: $total_green Green, $total_red Red",
            'debug' => $debug_info
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Tabela bote não encontrada',
            'debug' => $debug_info,
            'green' => 0,
            'red' => 0
        ]);
    }
    
    $conexao->close();
    
} catch (Exception $e) {
    // ❌ Retornar erro
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'green' => 0,
        'red' => 0
    ]);
}
?>
