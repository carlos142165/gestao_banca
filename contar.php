<?php
$str = "isssssiiiddsss";
echo "String: " . $str . "\n";
echo "Length: " . strlen($str) . "\n";

// Decompor
$chars = str_split($str);
echo "\nCaracteres:\n";
foreach ($chars as $i => $c) {
    echo ($i+1) . ": " . $c . "\n";
}

// Precisa 16 parâmetros
echo "\nPrecisa: 16 parâmetros\n";
echo "Tem: " . strlen($str) . " caracteres\n";

if (strlen($str) < 16) {
    echo "Faltam: " . (16 - strlen($str)) . " caracteres\n";
    echo "String corrigida: " . str_pad($str, 16, "s") . "\n";
}
?>
