

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Gestão Anual</title>
  <style>
    body {
      background-color: #eff1f1;
      font-family: Arial, sans-serif;
    }

    .auth-buttons {
      position: absolute;
      top: 10px;
      right: 20px;
    }

    .auth-buttons a {
      margin-left: 10px;
      text-decoration: none;
      background-color: #008cba;
      color: white;
      padding: 8px 12px;
      border-radius: 5px;
      font-weight: bold;
    }

    .auth-buttons a:hover {
      background-color: #006d98;
    }
  </style>
</head>
<body>
  <!-- Botões de autenticação (exibidos apenas para usuários não logados) -->
  <?php if (!isset($_SESSION['usuario_id'])): ?>
    <div class="auth-buttons">

    </div>
  <?php endif; ?>

  <div id="data-container"></div>

  <div id="menu-placeholder"></div>

  <script>
    // Carrega o menu externo
    fetch("menu.php")
      .then((response) => response.text())
      .then((data) => {
        document.getElementById("menu-placeholder").innerHTML = data;

        const menuButton = document.querySelector(".menu-button");
        const menu = document.getElementById("menu");

        if (menuButton && menu) {
          menuButton.addEventListener("click", () => {
            menu.style.display = menu.style.display === "block" ? "none" : "block";
          });

          document.addEventListener("click", (event) => {
            if (
              menu.style.display === "block" &&
              !menu.contains(event.target) &&
              !menuButton.contains(event.target)
            ) {
              menu.style.display = "none";
            }
          });
        }
      })
      .catch((error) => console.error("Erro ao carregar o menu:", error));
  </script>

  <script src="scripts.js"></script>
</body>
</html>
