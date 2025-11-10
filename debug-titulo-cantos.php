<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'config.php';

$conexao->set_charset("utf8mb4");

// Debug: Buscar uma mensagem de CANTOS para ver exatamente como está o título
$sql = "SELECT titulo, tipo_aposta 
        FROM bote 
        WHERE tipo_aposta = 'CANTOS' 
        LIMIT 1";

$resultado = $conexao->query($sql);

$dados = [];
if ($resultado && $row = $resultado->fetch_assoc()) {
    $titulo = $row['titulo'];
    
    // Análise detalhada
    $titulo_lower = strtolower($titulo);
    $tem_emoji_mountain = strpos($titulo, '⛳') !== false;
    $tem_canto_singular = strpos($titulo_lower, 'canto') !== false;
    $tem_cantos_plural = strpos($titulo_lower, 'cantos') !== false;
    $tem_escanteio = strpos($titulo_lower, 'escanteio') !== false;
    
    $dados = [
        'titulo_original' => $titulo,
        'titulo_lowercase' => $titulo_lower,
        'tem_emoji_mountain' => $tem_emoji_mountain,
        'tem_canto_singular' => $tem_canto_singular,
        'tem_cantos_plural' => $tem_cantos_plural,
        'tem_escanteio' => $tem_escanteio,
        'bytes' => unpack('H*', $titulo)[1], // Ver bytes do emoji
    ];
}

echo json_encode($dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

$conexao->close();
?>
