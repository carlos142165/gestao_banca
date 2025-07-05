
<?php
session_start();
include_once('config.php');

$saldo_banca = 0;

if (isset($_SESSION['usuario_id'])) {
    $id_usuario = $_SESSION['usuario_id'];

    // Soma depósitos
    $soma_depositos = 0;
    $stmt = mysqli_prepare($conexao, "SELECT SUM(deposito) FROM controle WHERE id_usuario = ?");
    mysqli_stmt_bind_param($stmt, "i", $id_usuario);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $soma_depositos);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    // Soma saques
    $soma_saque = 0;
    $stmt = mysqli_prepare($conexao, "SELECT SUM(saque) FROM controle WHERE id_usuario = ?");
    mysqli_stmt_bind_param($stmt, "i", $id_usuario);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $soma_saque);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    // Calcula saldo
    $saldo_banca = $soma_depositos - $soma_saque;
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
        padding-left: -3px;
        width: 180px;
      }



  .menu-content {
    text-align: left;
  }

  .menu-content a {
    display: flex;
    align-items: center;
    padding: 10px 15px;
    text-decoration: none;
    color: #333;
  }

  .menu-icon {
    margin-right: 7px;
    flex-shrink: 0;
    min-width: 20px;
    text-align: center; /* garante que fiquem colados na esquerda */
  }

  




      #menu a {
        color: #004080;
        padding: 12px 0px 12px 10px; /* Reduzi o padding esquerdo */
        display: flex;
        align-items: center;
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
      margin-left: -20px;
      }

      /* Ajuste especial para telas menores que 600px */
      @media (max-width: 600px) {
     .logo-img {
      width: 50vw;
      max-width: 70px;
      min-width: 70px;
      flex-direction: row;
      margin-left: -20px;
      
      
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





/* codigo responsavel pelo saldo da banca e icone */
.valor-item-menu {
  display: flex;
  align-items: center; /* Centraliza verticalmente */
  justify-content: flex-start;
  padding: 6px 8px;
  gap: 5px;
  border-radius: 8px;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
  
  
}

.valor-item-menu:hover {
  transform: translateY(-2px);
  
}

.valor-icone-menu {
  font-size: 18px;
  color: rgb(184, 153, 238);
  min-width: 22px;
  display: flex;
  align-items: center; /* Garante centralização do ícone dentro do espaço */
  justify-content: center;
  
}

.valor-texto {
  display: flex;
  flex-direction: column;
}

.valor-bold-menu {
  font-weight: bold;
  font-size: 13px; /* Tamanho menor */
  color: rgb(235, 235, 235);
  margin-bottom: 1px;
}

.valor-desc-menu {
  font-size: 11px; /* Tamanho menor */
  color: rgb(219, 218, 218);
  margin: 0;
}


.saldo-topo-ajustado {
  position: absolute;
  z-index: 1001;
}

/* Para telas grandes (computadores) */
@media (min-width: 768px) {
  .saldo-topo-ajustado {
    top: 5px;
    right: 15px;
  }
}

/* Para telas pequenas (celulares) */
@media (max-width: 767px) {
  .saldo-topo-ajustado {
    top: 5px;
    left: 270px;
  }
}
/* aqui finaliza o codigo responsavel pelo saldo da banca e icone */




</style>


       <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

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
    <a href="home.php"><i class="fas fa-home menu-icon"></i><span>Home</span></a>
    <a href="gestao-diaria.php"><i class="fas fa-calendar-day menu-icon"></i><span>Gestão do Dia</span></a>
    <a href="gestao-mensal.php"><i class="fas fa-calendar-alt menu-icon"></i><span>Gestão Mensal</span></a>
    <a href="gestao-anual.php"><i class="fas fa-calendar menu-icon"></i><span>Gestão Anual</span></a>
    <a href="estatisticas.php"><i class="fas fa-chart-bar menu-icon"></i><span>Estatísticas</span></a>
    <a href="painel-controle.php"><i class="fas fa-cogs menu-icon"></i><span>Painel de Controle</span></a>
    <?php if (isset($_SESSION['usuario_id'])): ?>
      <a href="logout.php"><i class="fas fa-sign-out-alt menu-icon"></i><span>Sair</span></a>
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
     <div class="valor-item-menu saldo-topo-ajustado">
         <i class="valor-icone-menu fa fa-piggy-bank"></i>
         <div>
         <span class="valor-bold-menu">R$ <?= number_format($saldo_banca, 2, ',', '.') ?></span><br>
        <span class="valor-desc-menu">Banca</span>
      </div>
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
