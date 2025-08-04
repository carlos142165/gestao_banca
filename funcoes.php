<?php
function calcularSaldoBanca(): float {
  $depositos      = isset($_SESSION['depositos']) ? (float) $_SESSION['depositos'] : 0.0;
  $saques         = isset($_SESSION['saques_totais']) ? (float) $_SESSION['saques_totais'] : 0.0;
  $saldo_mentores = isset($_SESSION['saldo_mentores']) ? (float) $_SESSION['saldo_mentores'] : 0.0;

  return $depositos - $saques + $saldo_mentores;
}


?>


