<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
function atualizarDadosBanca() {
  $.getJSON('dados_banca.php', function(resposta) {
    if (resposta.success) {
      let saldoFormatado = parseFloat(resposta.banca).toLocaleString('pt-BR', {
        style: 'currency',
        currency: 'BRL'
      });
      $('#saldo-banca').text(saldoFormatado);
    } else {
      $('#saldo-banca').text('Erro ao carregar');
    }
  });
}

// Atualiza a cada 10 segundos
setInterval(atualizarDadosBanca, 10000);
atualizarDadosBanca(); // Atualiza na primeira carga
</script>