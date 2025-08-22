<?php 
include("config.php");

if (!$conexao) {
    die("Conexão não está definida.");
}

header('Content-Type: application/json');

// Defina o fuso horário padrão do PHP
date_default_timezone_set('America/Bahia');

$id = $_GET['id'] ?? 0;
$tipo = $_GET['tipo'] ?? 'dia'; // ✅ NOVO: Parâmetro de período

$dataBahia = new DateTime('now', new DateTimeZone('America/Bahia'));

// ✅ NOVO: Determinar filtro de data baseado no período
switch ($tipo) {
    case 'mes':
        // Filtro para o mês atual
        $sql = "SELECT 
                    id,
                    green,
                    red,
                    valor_green,
                    valor_red,
                    data_criacao
                FROM valor_mentores 
                WHERE id_mentores = ? 
                AND MONTH(data_criacao) = MONTH(CURRENT_DATE()) 
                AND YEAR(data_criacao) = YEAR(CURRENT_DATE())
                ORDER BY data_criacao DESC";
        
        $stmt = $conexao->prepare($sql);
        $stmt->bind_param("i", $id);
        break;
        
    case 'ano':
        // Filtro para o ano atual
        $sql = "SELECT 
                    id,
                    green,
                    red,
                    valor_green,
                    valor_red,
                    data_criacao
                FROM valor_mentores 
                WHERE id_mentores = ? 
                AND YEAR(data_criacao) = YEAR(CURRENT_DATE())
                ORDER BY data_criacao DESC";
        
        $stmt = $conexao->prepare($sql);
        $stmt->bind_param("i", $id);
        break;
        
    case 'dia':
    case 'hoje':
    default:
        // Filtro para o dia atual (comportamento original)
        $dataFormatada = $dataBahia->format('Y-m-d');
        
        $sql = "SELECT 
                    id,
                    green,
                    red,
                    valor_green,
                    valor_red,
                    data_criacao
                FROM valor_mentores 
                WHERE id_mentores = ? 
                AND DATE(data_criacao) = ?
                ORDER BY data_criacao DESC";
        
        $stmt = $conexao->prepare($sql);
        $stmt->bind_param("is", $id, $dataFormatada);
        break;
}

$stmt->execute();
$result = $stmt->get_result();
$entradas = [];

while ($row = $result->fetch_assoc()) {
    // Converte de UTC para fuso local
    $dataUTC = new DateTime($row['data_criacao'], new DateTimeZone('UTC'));
    $dataBahia = clone $dataUTC;
    $dataBahia->setTimezone(new DateTimeZone('America/Bahia'));
    
    // Formatos para frontend
    $row['horario_utc'] = $dataUTC->format(DateTime::ATOM);       // Ex: "2025-07-27T11:03:00Z"
    $row['horario_bahia'] = $dataBahia->format('Y-m-d H:i:s');    // Ex: "2025-07-27 08:03:00"
    
    $entradas[] = $row;
}

echo json_encode($entradas);
?>