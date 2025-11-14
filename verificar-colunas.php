<?php
require 'config.php';

$result = $conexao->query('DESCRIBE bote');
echo "Colunas da tabela 'bote':\n";
echo "============================\n";
while($row = $result->fetch_assoc()) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}
?>
