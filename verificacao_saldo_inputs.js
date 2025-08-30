
// ✅ Verificação de saldo ao digitar valores nos inputs

function verificarSaldoInput(inputElement) {
  const valorDigitado = inputElement.value;
  const mensagem = inputElement.parentElement.querySelector('.mensagem-status-input');

  fetch('verificar_deposito_atualizado.php')
    .then(response => response.json())
    .then(data => {
      const saldoDisponivel = parseFloat(data.saldo);
      const valor = parseFloat(valorDigitado.replace(/[^\d,]/g, '').replace(',', '.')) || 0;

      if (valor > saldoDisponivel) {
        mensagem.textContent = 'Saldo Insuficiente!';
        mensagem.classList.add('negativo');
        mensagem.classList.remove('positivo', 'neutro');
      } else {
        mensagem.textContent = '';
        mensagem.classList.remove('negativo');
      }
    })
    .catch(error => {
      console.error('Erro ao verificar saldo:', error);
    });
}

// ✅ Aplicar verificação nos inputs relevantes

document.addEventListener('DOMContentLoaded', function () {
  const inputTotal = document.getElementById('input-total');
  const inputRed = document.getElementById('input-red');

  if (inputTotal) {
    inputTotal.addEventListener('input', function () {
      verificarSaldoInput(inputTotal);
    });
  }

  if (inputRed) {
    inputRed.addEventListener('input', function () {
      verificarSaldoInput(inputRed);
    });
  }
});
