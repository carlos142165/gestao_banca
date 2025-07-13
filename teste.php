<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Teste de Mentor</title>
  <style>
    .mentor-card {
      border: 1px solid #ccc;
      padding: 10px;
      margin: 10px;
      cursor: pointer;
    }

    .formulario-mentor {
      display: none;
      padding: 20px;
      border: 1px solid #333;
      background-color: #f9f9f9;
      width: 300px;
      position: fixed;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
    }

    .mentor-foto-preview {
      width: 60px;
      height: 60px;
      border-radius: 50%;
      object-fit: cover;
    }

    .mentor-nome-preview {
      font-size: 18px;
      font-weight: bold;
    }
  </style>
</head>
<body>

  <div class="mentor-wrapper">
    <div class="mentor-card" data-nome="Carlos Silva" data-foto="https://via.placeholder.com/60" data-id="1">
      <p>Mentor: Carlos Silva</p>
    </div>
    <div class="mentor-card" data-nome="Fernanda Souza" data-foto="https://via.placeholder.com/60" data-id="2">
      <p>Mentor: Fernanda Souza</p>
    </div>
  </div>

  <div class="formulario-mentor">
    <button onclick="fecharFormulario()">❌</button>
    <img src="" class="mentor-foto-preview" />
    <h3 class="mentor-nome-preview">Nome do Mentor</h3>
    <form id="form-mentor">
      <input type="hidden" class="mentor-id-hidden" name="id_mentor" />
      <input type="submit" value="Enviar" />
    </form>
  </div>

  <script>
    const formulario = document.querySelector(".formulario-mentor");
    const nomePreview = formulario.querySelector(".mentor-nome-preview");
    const fotoPreview = formulario.querySelector(".mentor-foto-preview");
    const idHidden = formulario.querySelector(".mentor-id-hidden");

    function fecharFormulario() {
      formulario.style.display = "none";
    }

    document.querySelectorAll(".mentor-card").forEach(card => {
      card.addEventListener("click", () => {
        nomePreview.textContent = card.getAttribute("data-nome");
        fotoPreview.src = card.getAttribute("data-foto");
        idHidden.value = card.getAttribute("data-id");
        formulario.style.display = "block";
      });
    });
  </script>

</body>
</html>
