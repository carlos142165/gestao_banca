
















<!DOCTYPE html>
<html lang="pt-br">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Gestão</title>

  <style>
    body, html {
      height: 100%;
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(135deg, #0f2e3a, #295a6f, #4e8b9e);
      margin: 0;
      padding: 0;
      color: #f5f5f5;
    }

   



</style>



<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">


</head>





<body>



<?php if (isset($_SESSION['mensagem'])): ?>
  <div class="mensagem-status" id="mensagemStatus">
    <i class="fa-solid fa-triangle-exclamation"></i>
    <span><?= $_SESSION['mensagem'] ?></span>
    <button class="btn-fechar" onclick="document.getElementById('mensagemStatus').style.display='none'">OK</button>
  </div>
  <?php unset($_SESSION['mensagem']); ?>
<?php endif; ?>























<script>
 function toggleMenu() {
  var menu = document.getElementById("menu");
  menu.style.display = menu.style.display === "block" ? "none" : "block";
 }
</script>








</body>
</html>


