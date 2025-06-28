
<?php
session_start();
include_once('config.php');

$soma_depositos = 0;

if (isset($_SESSION['usuario_id'])) {
    $id_usuario = $_SESSION['usuario_id'];

    $stmt = mysqli_prepare($conexao, "SELECT SUM(deposito) FROM controle WHERE id_usuario = ?");
    mysqli_stmt_bind_param($stmt, "i", $id_usuario);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $soma_depositos);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
}
?>








<!DOCTYPE html>
<html lang="pt">
  <head>

    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Gestão</title>

    <style>
      /*  AQUI ESTAO OS CODIGOS DOS MENU */
      #menu-placeholder {
        background-color: #113647;
        color: #eff1f1;
        padding: 15px 20px;
        font-family: Arial, sans-serif;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        font-size: 14px;
        height: 60px;
        
        
        
      }

      .menu-button {
        background: none;
        color: #eff1f1;
        border: none;
        font-size: 25px;
        cursor: pointer;
        padding: 5px;
        line-height: 1;
        
      }

      #menu {
        display: none;
        position: absolute;
        top: 60px;
        left: 20px;
        background-color: #eff1f1;
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        z-index: 1000;
      }

      #menu a {
        color: #004080;
        padding: 12px 20px;
        display: block;
        text-decoration: none;
      }

      #menu a:hover {
        background-color: #daf3f5;
      }
      /*  AQUI ESTAO OS CODIGOS DOS MENU */

      /*  AQUI ESTAO OS CODIGOS DOS BOTOES REGISTRE-SE E ENTRAR*/
      #top-bar {
        display: flex;
        justify-content: flex-start;
        align-items: center;
        background-color: #113647;
        padding: 15px 5px;
        color: white;
        font-family: Arial, sans-serif;
      }

      .app-title {
        font-size: 20px;
        font-weight: bold;
      }

      .top-buttons {
        display: flex;
        gap: 15px;
        margin-right: auto;
        font-size: 18px;
      }

      .top-link {
        color:rgb(233, 198, 45);
        text-decoration: none;
        font-weight: 500;
        border: 1px solid white;
        padding: 6px 12px;
        border-radius: 4px;
        transition: background 0.3s;
      }

      .top-link-entrar {
        color:rgb(235, 235, 235);
        text-decoration: none;
        font-weight: 500;
        border: 1px solid white;
        padding: 6px 12px;
        border-radius: 4px;
        transition: background 0.3s;
      }

      .top-link:hover {
        background-color: white;
        color: #113647;
      }

      .top-link-entrar:hover {
        background-color: white;
        color: #113647;
      }

      @media (max-width: 600px) {
        .top-link {
          font-size: 14px;
          padding: 5px 8px;
        }

        .top-link-entrar {
          font-size: 14px;
          padding: 5px 8px;
        }

        .top-buttons {
          gap: 10px;
        }

        .app-title {
          font-size: 16px;
        }
      }

      .logo-img {
      width: 30vw;
      max-width: 100px;
      min-width: 80px;
      height: auto;
      object-fit: contain;
      transition: width 0.3s ease;
      margin-left: 60px;
      }

      /* Ajuste especial para telas menores que 600px */
      @media (max-width: 600px) {
     .logo-img {
      width: 50vw;
      max-width: 70px;
      min-width: 70px;
      flex-direction: row;
      margin-left: 50px;
      
     }

     .top-buttons {
     flex-direction: row;
     align-items: center;
     gap: 8px;
     }
    }

     .usuario-saldo {
      margin-left: 15px;
      font-weight: bold;
      display: flex;
      align-items: center;
      color: white; /* ou a cor que combine com seu topo */
      font-family: Arial, sans-serif;
     }

     .usuario-saldo img {
     width: 20px;
     height: 20px;
     margin-right: 6px;
     }

      /*  AQUI FINALIZA OS CODIGOS DOS BOTOES REGISTRE-SE E ENTRAR*/
    </style>




  </head>

<!DOCTYPE html>
<html lang="pt">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Gestão</title>
    <style>
      /* ... (seus estilos permanecem os mesmos) ... */
    </style>
  </head>



  
  <body>

    <div class="menu-container">
      <button class="menu-button" onclick="toggleMenu()">☰</button>
      <div id="menu" class="menu-content">
        <a href="home.php">Home</a>
        <a href="gestao-diaria.php">Gestão do Dia</a>
        <a href="gestao-mensal.php">Gestão Mensal</a>
        <a href="gestao-anual.php">Gestão Anual</a>
        <a href="estatisticas.php">Estatísticas</a>
        <a href="painel-controle.php">Painel de Controle</a>
        <?php if (isset($_SESSION['usuario_id'])): ?>
         <a href="logout.php">Sair</a>
         <?php endif; ?>
        </div>
    </div>

    <div id="top-logo">
       <div class="logo-wrapper">
       <img src="img/logo.png" alt="Logomarca" class="logo-img" />
       </div>
    </div>

    <div id="top-bar">
      <?php if (!isset($_SESSION['usuario_id'])): ?>
        <div class="top-buttons">
          <a href="formulario.php" class="top-link">Registre-se</a>
          <a href="login.php" class="top-link-entrar">Entrar</a>
        </div>
      <?php endif; ?>
    </div>

   <?php if (isset($_SESSION['usuario_id'])): ?>
     <div class="usuario-saldo">
     <img src="img/" alt="Usuário">
     Banca: R$ <?php echo number_format($soma_depositos, 2, ',', '.'); ?>
  </div>
    <?php endif; ?>


    <script>
      function toggleMenu() {
        var menu = document.getElementById("menu");
        menu.style.display = menu.style.display === "block" ? "none" : "block";
      }
    </script>
  </body>
  
</html>
