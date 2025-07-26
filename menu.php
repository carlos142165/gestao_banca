<?php
session_start();
include_once('config.php');

// Inicialização
$saldo_banca = 0;
$saldo_mentores = 0;
$saques_banca = 0;
$saques_mentores = 0;
$saques_reais = 0;
$classe_saldo = '';

if (isset($_SESSION['usuario_id'])) {
    $id_usuario = $_SESSION['usuario_id'];

    // Depósitos
    $stmt = mysqli_prepare($conexao, "SELECT COALESCE(SUM(deposito), 0) FROM controle WHERE id_usuario = ?");
    mysqli_stmt_bind_param($stmt, "i", $id_usuario);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $soma_depositos);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    // Saques - banca
    $stmt = mysqli_prepare($conexao, "
        SELECT COALESCE(SUM(saque), 0)
        FROM controle
        WHERE id_usuario = ? AND (origem IS NULL OR origem = 'banca')
    ");
    mysqli_stmt_bind_param($stmt, "i", $id_usuario);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $saques_banca);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    // Saques - mentores
    $stmt = mysqli_prepare($conexao, "
        SELECT COALESCE(SUM(saque), 0)
        FROM controle
        WHERE id_usuario = ? AND origem = 'mentor'
    ");
    mysqli_stmt_bind_param($stmt, "i", $id_usuario);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $saques_mentores);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    // Saques totais
    $saques_reais = $saques_banca + $saques_mentores;

    // Green e Red
    $stmt = mysqli_prepare($conexao, "SELECT COALESCE(SUM(valor_green), 0) FROM valor_mentores WHERE id_usuario = ?");
    mysqli_stmt_bind_param($stmt, "i", $id_usuario);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $valor_green);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare($conexao, "SELECT COALESCE(SUM(valor_red), 0) FROM valor_mentores WHERE id_usuario = ?");
    mysqli_stmt_bind_param($stmt, "i", $id_usuario);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $valor_red);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    // Cálculo final
    $saldo_mentores = $valor_green - $valor_red;
    $saldo_banca = ($soma_depositos - $saques_banca) + $saldo_mentores;

    // Cor da classe do saldo
    if ($saldo_mentores < 0) {
        $classe_saldo = 'saldo-negativo';
    } elseif ($saldo_mentores == 0.00) {
        $classe_saldo = 'saldo-neutro';
    } else {
        $classe_saldo = 'saldo-positivo';
    }
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
  max-width: 100%;
  white-space: nowrap;
  
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
    right: 5px;
    left: auto; /* remove o posicionamento fixo que pode causar problemas */
  }
}
/* aqui finaliza o codigo responsavel pelo saldo da banca e icone */

.valor-item-menu {
    background-color: #113647;
    border-radius: 8px;
    padding: 10px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    font-family: 'Segoe UI', 'Roboto', sans-serif;
    margin-top: -8px;
}

.valor-info-wrapper {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.valor-label-linha {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 1px 0;
    border-bottom: 1px solid #1f4d5f;
}

.valor-label {
    font-weight: 500;
    font-size: 11px;
    color: #cfd8dc;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.valor-bold-menu {
    font-size: 13px;
    font-weight: bold;
    color: #a9b5bbff; /* azul petróleo como sugestão */
}


.valor-valor-saque {
    font-size: 13px;
    font-weight: bold;
    color: #90a4ae;
}

.valor-total-mentores {
    font-size: 13px;
    font-weight: bold;
    color: #80b3ff;
}

.valor-icone-tema {
    font-size: 12px;
    color: #90a4ae;
    transition: transform 0.3s ease, color 0.3s ease;
}

.valor-label-linha:hover .valor-icone-tema {
    transform: scale(1.1);
    color: #ffffff;
}

@media (max-width: 480px) {
  .valor-item-menu {
    padding: 8px;
    border-radius: 6px;
    margin-top: 0px;
  }

  .valor-label-linha {
    gap: 4px;
    padding: 0;
  }

  .valor-label {
    font-size: 10px;
    letter-spacing: 0.2px;
  }

  .valor-bold-menu,
  .valor-valor-saque,
  .valor-total-mentores {
    font-size: 12px;
    line-height: 1.1;
  }

  .valor-icone-tema {
    font-size: 11px;
  }
}

.saldo-positivo {
    color: #9fe870; /* verde cana */
}

.saldo-negativo {
    color: #e57373; /* vermelho suave */
}

.saldo-neutro {
    color: #cfd8dc; /* cinza claro */
}




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
    <a href="gestao-diaria.php"><i class="fas fa-university menu-icon"></i><span>Gestão de Banca</span></a>
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
  <div class="valor-info-wrapper">
    <div class="valor-label-linha">
      <i class="fa-solid fa-building-columns valor-icone-tema"></i>
      <span class="valor-label">Banca:</span>
      <span class="valor-bold-menu">R$ <?= number_format($saldo_banca, 2, ',', '.') ?></span>
    </div>
    <div class="valor-label-linha">
      <i class="fa-solid fa-arrow-up-from-bracket valor-icone-tema"></i>
      <span class="valor-label">Saque:</span>
      <span class="valor-valor-saque">R$ <?= number_format($saques_reais, 2, ',', '.') ?></span>
    </div>
    <div class="valor-label-linha">
      <i class="fa-solid fa-chart-line valor-icone-tema"></i>
      <span class="valor-label">Saldo:</span>
      <span class="valor-total-mentores <?= $classe_saldo ?>">R$ <?= number_format($saldo_mentores, 2, ',', '.') ?></span>
    </div>
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
