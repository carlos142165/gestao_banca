<?php
header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('America/Sao_Paulo');
require_once 'config.php';

// Simular dados do banco
$teste_dados = [
    ['resultado' => 'GREEN', 'titulo' => 'OVER (+0.5 ⚽GOL FT)'],
    ['resultado' => 'RED', 'titulo' => 'OVER (+0.5 ⚽GOL FT)'],
    ['resultado' => 'REEMBOLSO', 'titulo' => 'OVER (+0.5 ⚽GOL FT)'],
    ['resultado' => 'GREEN', 'titulo' => 'OVER (+0.5 ⚽GOL FT)'],
    ['resultado' => 'REEMBOLSO', 'titulo' => 'OVER (+0.5 ⚽GOL FT)'],
];

$filtrarSemReembolso = true;

$resultado_filtrado = [];
foreach ($teste_dados as $row) {
    if ($filtrarSemReembolso && strtoupper($row['resultado']) === 'REEMBOLSO') {
        error_log("🚫 IGNORANDO REEMBOLSO");
        continue;
    }
    $resultado_filtrado[] = $row;
}

error_log("📊 TESTE: Antes do filtro: " . count($teste_dados) . " itens");
error_log("📊 TESTE: Depois do filtro: " . count($resultado_filtrado) . " itens");

echo json_encode([
    'success' => true,
    'antes_filtro' => $teste_dados,
    'depois_filtro' => $resultado_filtrado,
    'quantidade_antes' => count($teste_dados),
    'quantidade_depois' => count($resultado_filtrado)
]);
?>
