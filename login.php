
<!DOCTYPE html>
<html lang="pt-br">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Tela de Login</title>

    <style>
      body {
        font-family: Arial, Helvetica, sans-serif;
        background-color: #113647;
      }

      .login {
        background-color: #1b4c60;
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        padding: 70px;
        border-radius: 15px;
        color: #eeeeee;
      }

      input {
        padding: 15px;
        border: none;
        outline: none;
        font-size: 15px;
        border-radius: 2px;
        margin-bottom: 12px;
      }

      button {
        background-color: #113647;
        border: none;
        padding: 15px;
        width: 100%;
        border-radius: 5px;
        color: #eeeeee;
        font-size: 15px;
        cursor: pointer;
      }

      button:hover {
        background-color: #15526f;
      }

      .links-container {
        margin-top: 20px;
        display: flex;
        justify-content: center;
        gap: 15px; /* Reduzido aqui! */
      }

      .link-custom {
        font-size: 13px;
        color: #eeeeee;
        text-decoration: underline;
        transition: color 0.3s ease;
      }

      .link-custom:hover {
        color: #ffd700;
      }

      #mensagemErro {
        position: fixed;
        top: 18%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 300px;
        padding: 20px;
        border-radius: 15px;
        opacity: 0;
        transition: opacity 0.3s ease;
        overflow: hidden;
        margin-bottom: 30px;
        display: inline-block;
        z-index: 1000; /* Garante que fique visível acima de outros elementos */
      }

      .caps-aviso {
        position: absolute;
        top: 100%;
        left: 0;
        font-size: 12px;
        color: rgb(137, 137, 29);
        margin-top: -30px;
        visibility: hidden;
        height: 16px; /* reserva espaço */
      }
    </style>
  </head>
  <body>
    <div
      id="mensagemErro"
      style="
        min-height: 50px;
        opacity: 0;
        transition: opacity 0.3s ease;
        overflow: hidden;
        margin-bottom: 20px;
      "
    >
      <div
        id="mensagemConteudo"
        style="
          display: none;
          background-color: #ffdddd;
          color: #a94442;
          padding: 10px 15px;
          border-radius: 5px;
          border: 1px solid #a94442;
          position: relative;
        "
      >
        ⚠️ Email ou senha inválidos.
        <button
          onclick="fecharMensagem()"
          style="
            background: none;
            border: none;
            color: #a94442;
            font-weight: bold;
            cursor: pointer;
          "
        >
          OK
        </button>
      </div>
    </div>

    <div class="login">
      <h1>Login</h1>

      <form action="login-user.php" method="POST">
        <input
          type="email"
          id="email"
          name="email"
          placeholder="Email"
          required
        />
        <br />

        <div style="position: relative">
          <input
            type="password"
            id="senha"
            name="senha"
            placeholder="Senha"
            required
          />
          <span
            id="toggleSenha"
            style="position: absolute; top: 12px; right: 10px; cursor: pointer"
            >👁️‍🗨️</span
          >
          <div id="caps-lock-warning" class="caps-aviso">
            ⚠️ Caps Lock está ativado!
          </div>
        </div>

        <button type="submit">Entrar</button>
      </form>





    

      <div class="links-container">
        <a href="formulario.php" class="link-custom">Registre-se já</a>
        <a href="recuperar_senha.php" class="link-custom">Recuperar senha</a>
      </div>
    </div>

    <script>
      const emailParam = new URLSearchParams(window.location.search).get(
        "email"
      );
      if (emailParam) {
        document.getElementById("email").value = emailParam;
      }

      window.onload = function () {
        const params = new URLSearchParams(window.location.search);
        const erro = params.get("erro");
        const tentouLogin = sessionStorage.getItem("tentouLogin");

        if (erro && tentouLogin === "true") {
          const erroBox = document.getElementById("mensagemErro");
          const conteudo = document.getElementById("mensagemConteudo");
          conteudo.style.display = "block";
          erroBox.style.opacity = "1";
          document.getElementById("email").focus();

          // Limpa o flag depois de exibir
          sessionStorage.removeItem("tentouLogin");
        }
      };

      function fecharMensagem() {
        const msgBox = document.getElementById("mensagemErro");
        const conteudo = document.getElementById("mensagemConteudo");
        msgBox.style.opacity = "0";
        conteudo.style.display = "none";
        document.getElementById("email").focus();
      }

      document
        .getElementById("toggleSenha")
        .addEventListener("click", function () {
          const senhaInput = document.getElementById("senha");
          const tipo = senhaInput.type === "password" ? "text" : "password";
          senhaInput.type = tipo;
          this.textContent = tipo === "text" ? "🙈" : "👁️‍🗨️";
        });
    </script>

    <script>
      document.querySelector("form").addEventListener("submit", function () {
        sessionStorage.setItem("tentouLogin", "true");
      });

      const passwordInput = document.getElementById("senha");
      const capsLockWarning = document.getElementById("caps-lock-warning");

      passwordInput.addEventListener("keyup", function (event) {
        if (event.getModifierState("CapsLock")) {
          capsLockWarning.style.visibility = "visible";
        } else {
          capsLockWarning.style.visibility = "hidden";
        }
      });
    </script>

      
  </body>

  


</html>
