<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sem Saldo</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background-color: #fffbe6;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      margin: 0;
    }
    .card {
      background: white;
      padding: 40px;
      border-radius: 14px;
      box-shadow: 0 0 20px rgba(0,0,0,0.1);
      text-align: center;
      max-width: 400px;
    }
    .card i {
      font-size: 36px;
      color: #e74c3c;
      margin-bottom: 20px;
    }
    .card h2 {
      margin-bottom: 16px;
      font-size: 22px;
      color: #333;
    }
    .card p {
      font-size: 17px;
      color: #666;
      margin-bottom: 24px;
    }
    .card button {
      padding: 12px 24px;
      background: #3498db;
      color: white;
      border: none;
      border-radius: 8px;
      font-size: 16px;
      cursor: pointer;
    }
    .card button:hover {
      background: #2980b9;
    }
  </style>
</head>
<body>
  <div class="card">
    <i class="fa-solid fa-circle-exclamation"></i>
    <h2>Saldo insuficiente</h2>
    <p>Você precisa ter saldo na banca para acessar esta página.</p>
    <button onclick="window.location.href='painel-controle.php'">Voltar ao Painel</button>
  </div>
</body>
</html>
