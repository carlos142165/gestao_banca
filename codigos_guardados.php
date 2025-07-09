









<!-- CODIGO RESPONSAVEL PELO FORMULARIO QUE BUSCA USUARIO E CADASTRA VALORES REFERENTE  -->

<div class="user">

  <!-- Linha de adicionar e buscar usuário -->
<div class="row">
  <label for="buscar">Buscar Usuário</label>
  <select id="buscar">
    <option value="">Buscar Usuário</option>
  </select>
</div>



  <!-- Linha de checkboxes com input de valor -->
  <div class="row checkbox-row">
    <label>
      <input type="checkbox" id="green" />Green</label>

    <label>
      <input type="checkbox" class="red" id="red" />Red</label>

    <input type="number" id="valor" placeholder="Valor" />
  </div>

  <!-- Botão de envio -->
  <div class="row">
    <button class="btn-submit">Enviar</button>
  </div>

</div>

<!-- FIM DO CODIGO RESPONSAVEL PELO FORMULARIO QUE BUSCA USUARIO E CADASTRA VALORES REFERENTE  -->






<!-- CODIGO PARA PEGAR DA PAGINA BUSCAR_MENTORES.PHP E ABRIR NO MENU DE SELEÇÃO  -->
<script>
document.addEventListener("DOMContentLoaded", function() {
  fetch("buscar_mentores.php")
    .then(response => response.json())
    .then(data => {
      const select = document.getElementById("buscar");
      select.innerHTML = '<option value="">Buscar Usuário</option>';

      data.forEach(nome => {
        const option = document.createElement("option");
        option.value = nome;
        option.textContent = nome;
        select.appendChild(option);
      });
    })
    .catch(error => console.error("Erro ao carregar mentores:", error));
});
</script>
<!-- FIM DO CODIGO PARA PEGAR DA PAGINA BUSCAR_MENTORES.PHP E ABRIR NO MENU DE SELEÇÃO  -->