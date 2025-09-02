<?php
// Endpoint que retorna o HTML das linhas do resumo do mês
require_once __DIR__ . '/..//config.php';
require_once __DIR__ . '/..//carregar_sessao.php';
require_once __DIR__ . '/..//funcoes.php';

// Definir timezone (opcional, mantém comportamento igual ao original)
date_default_timezone_set('America/Bahia');

$hoje = date('Y-m-d');
$mes = date('m');
$ano = date('Y');

try {
  $sql = "
    SELECT 
      DATE(data_criacao) AS data,
      SUM(CASE WHEN green = 1 THEN valor_green ELSE 0 END) AS total_valor_green,
      SUM(CASE WHEN red = 1 THEN valor_red ELSE 0 END) AS total_valor_red,
      COUNT(CASE WHEN green = 1 THEN 1 END) AS total_green,
      COUNT(CASE WHEN red = 1 THEN 1 END) AS total_red
    FROM valor_mentores
    WHERE id_usuario = ? AND MONTH(data_criacao) = ? AND YEAR(data_criacao) = ?
    GROUP BY DATE(data_criacao)
  ";

  $stmt = $conexao->prepare($sql);
  $stmt->bind_param('iii', $_SESSION['usuario_id'], $mes, $ano);
  $stmt->execute();
  $result = $stmt->get_result();

  $dados_por_dia = [];
  while ($row = $result->fetch_assoc()) {
    $dados_por_dia[$row['data']] = $row;
  }
} catch (Exception $e) {
  // Em caso de erro, devolve mensagem simples
  echo '<div class="gd-linha-dia gd-dia-hoje"><span class="data">Erro ao carregar dados</span></div>';
  exit();
}

// Monta o array de dias com valor (mesma lógica do original)
$dias_com_valores = [];
foreach ($dados_por_dia as $data => $dados) {
  if ((int)$dados['total_green'] > 0 || (int)$dados['total_red'] > 0) {
    $dias_com_valores[$data] = $dados;
  }
}

if (!isset($dias_com_valores[$hoje])) {
  $dias_com_valores[$hoje] = [
    'total_valor_green' => 0,
    'total_valor_red' => 0,
    'total_green' => 0,
    'total_red' => 0
  ];
}

ksort($dias_com_valores);

foreach ($dias_com_valores as $data_mysql => $dados) {
  list($ano_data, $mes_data, $dia_data) = explode('-', $data_mysql);
  $data_exibicao = $dia_data . '/' . $mes_data . '/' . $ano_data;

  $saldo_dia = floatval($dados['total_valor_green']) - floatval($dados['total_valor_red']);
  $saldo_formatado = number_format($saldo_dia, 2, ',', '.');

  $cor_valor = ($saldo_dia == 0) ? 'texto-cinza' : ($saldo_dia > 0 ? 'verde-bold' : 'vermelho-bold');
  $classe_texto = ($saldo_dia == 0) ? 'texto-cinza' : '';
  $placar_cinza = ((int)$dados['total_green'] === 0 && (int)$dados['total_red'] === 0) ? 'texto-cinza' : '';

  $classe_dia = ($data_mysql === $hoje)
    ? 'gd-dia-hoje ' . ($saldo_dia >= 0 ? 'gd-borda-verde' : 'gd-borda-vermelha')
    : 'dia-normal';

  if ($data_mysql < $hoje && $saldo_dia > 0) {
    $classe_destaque = 'gd-dia-destaque';
  } elseif ($data_mysql < $hoje && $saldo_dia < 0) {
    $classe_destaque = 'gd-dia-destaque-negativo';
  } else {
    $classe_destaque = '';
  }

  $classe_nao_usada = ($data_mysql > $hoje) ? 'dia-nao-usada' : '';
  $classe_sem_valor = ($data_mysql < $hoje && (int)$dados['total_green'] === 0 && (int)$dados['total_red'] === 0) ? 'gd-dia-sem-valor' : '';

  echo "<div class=\"gd-linha-dia {$classe_dia} {$classe_destaque} {$classe_nao_usada} {$classe_sem_valor}\" data-date=\"{$data_mysql}\">";
  echo "<span class=\"data {$classe_texto}\"> {$data_exibicao}</span>";
  echo "<div class=\"placar-dia\">";
  echo "<span class=\"placar verde-bold {$placar_cinza}\">" . (int)$dados['total_green'] . "</span>";
  echo "<span class=\"placar separador {$placar_cinza}\">x</span>";
  echo "<span class=\"placar vermelho-bold {$placar_cinza}\">" . (int)$dados['total_red'] . "</span>";
  echo "</div>";
  echo "<span class=\"valor {$cor_valor}\">R$ {$saldo_formatado}</span>";
  echo "<span class=\"icone {$classe_texto}\"><i class=\"fas fa-check\"></i></span>";
  echo "</div>";
}

  if (empty($dias_com_valores)) {
  echo '<div class="gd-linha-dia gd-dia-hoje"><span class="data">Nenhuma operação registrada este mês</span></div>';
}

exit();
?>
