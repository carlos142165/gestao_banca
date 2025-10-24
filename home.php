<?php
session_start();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            height: 100vh;
            overflow: hidden;
        }

        .header {
            background-color: #113647;
            height: 80px;
            width: 100%;
            position: fixed;
            top: 0;
            z-index: 1000;
        }

        .footer {
            background-color: #113647;
            height: 80px;
            width: 100%;
            position: fixed;
            bottom: 0;
            z-index: 1000;
        }

        .main-content {
            position: fixed;
            top: 80px;
            bottom: 80px;
            left: 0;
            right: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .container {
            width: 100%;
            max-width: 1320px;
            height: 100%;
            background-color: #f5f5f5;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow-y: auto;
        }

        /* ===== ÍCONE AO VIVO PISCANDO ===== */
        .ao-vivo-icon {
          display: inline-block !important;
          margin-left: 8px;
          vertical-align: middle;
        }

        .ao-vivo-icon i {
          font-size: 10px !important;
          color: #ef4444 !important;
          display: inline-block !important;
          animation: piscar-bola-viva 1.2s steps(2, start) infinite !important;
        }

        @keyframes piscar-bola-viva {
          0% {
            opacity: 1;
            text-shadow: 0 0 8px rgba(239, 68, 68, 0.8);
          }
          50% {
            opacity: 0.2;
            text-shadow: 0 0 2px rgba(239, 68, 68, 0.3);
          }
          100% {
            opacity: 1;
            text-shadow: 0 0 8px rgba(239, 68, 68, 0.8);
          }
        }

        /* ===== BOTÕES DE LOGIN/REGISTRE-SE ===== */
        .auth-buttons {
          display: flex;
          gap: 10px;
          align-items: center;
          margin-left: auto;
        }

        .auth-buttons a {
          display: flex;
          align-items: center;
          gap: 6px;
          padding: 8px 14px;
          text-decoration: none;
          border-radius: 6px;
          font-weight: 500;
          font-size: 13px;
          transition: all 0.3s ease;
          white-space: nowrap;
        }

        .btn-login {
          background-color: #2196F3;
          color: white;
          border: 2px solid #2196F3;
        }

        .btn-login:hover {
          background-color: #1976D2;
          border-color: #1976D2;
          transform: translateY(-2px);
          box-shadow: 0 4px 8px rgba(33, 150, 243, 0.3);
        }

        .btn-register {
          background-color: #4CAF50;
          color: white;
          border: 2px solid #4CAF50;
        }

        .btn-register:hover {
          background-color: #388E3C;
          border-color: #388E3C;
          transform: translateY(-2px);
          box-shadow: 0 4px 8px rgba(76, 175, 80, 0.3);
        }

        .auth-buttons a i {
          font-size: 14px;
        }

        /* ===== MODAL DE REGISTRO ===== */
        .modal-registro {
          display: none;
          position: fixed;
          z-index: 2000;
          left: 0;
          top: 0;
          width: 100%;
          height: 100%;
          background: rgba(0, 0, 0, 0.6);
          backdrop-filter: blur(4px);
          animation: fadeIn 0.3s ease;
        }

        .modal-registro.show {
          display: flex;
          justify-content: center;
          align-items: center;
        }

        @keyframes fadeIn {
          from {
            opacity: 0;
          }
          to {
            opacity: 1;
          }
        }

        .modal-content-registro {
          background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
          padding: 0;
          border-radius: 20px;
          width: 90%;
          max-width: 480px;
          max-height: 90vh;
          overflow-y: auto;
          color: white;
          animation: slideIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
          box-shadow: 0 20px 60px rgba(102, 126, 234, 0.4), 0 0 1px rgba(102, 126, 234, 0.5);
          position: relative;
          border: 1px solid rgba(255, 255, 255, 0.2);
          overflow: hidden;
        }

        .modal-content-registro::before {
          content: '';
          position: absolute;
          top: -50%;
          right: -50%;
          width: 200%;
          height: 200%;
          background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
          pointer-events: none;
        }

        @keyframes slideIn {
          from {
            transform: translateY(-50px) scale(0.95);
            opacity: 0;
          }
          to {
            transform: translateY(0) scale(1);
            opacity: 1;
          }
        }

        .modal-close-btn {
          position: absolute;
          top: 20px;
          right: 20px;
          background: rgba(255, 255, 255, 0.2);
          border: none;
          font-size: 28px;
          color: white;
          cursor: pointer;
          transition: all 0.3s ease;
          width: 40px;
          height: 40px;
          border-radius: 50%;
          display: flex;
          align-items: center;
          justify-content: center;
          z-index: 10;
          backdrop-filter: blur(10px);
        }

        .modal-close-btn:hover {
          background: rgba(255, 255, 255, 0.3);
          transform: rotate(90deg);
        }

        .modal-header {
          text-align: center;
          padding: 35px 30px 25px;
          border-bottom: 2px solid rgba(255, 255, 255, 0.2);
          position: relative;
          z-index: 1;
        }

        .modal-header h2 {
          margin: 0;
          font-size: 26px;
          color: white;
          font-weight: 700;
          letter-spacing: 0.5px;
          display: flex;
          align-items: center;
          justify-content: center;
          gap: 12px;
        }

        .modal-header h2 i {
          font-size: 28px;
          transition: all 0.3s ease;
        }

        .modal-header h2:hover i {
          transform: scale(1.1) rotate(5deg);
        }

        .modal-form-wrapper {
          padding: 30px;
          position: relative;
          z-index: 1;
        }

        .inputbox {
          position: relative;
          margin-bottom: 24px;
        }

        .inputUser {
          background: rgba(255, 255, 255, 0.1);
          border: 2px solid rgba(255, 255, 255, 0.2);
          border-radius: 12px;
          width: 100%;
          outline: none;
          color: white;
          font-size: 15px;
          letter-spacing: 0.5px;
          box-sizing: border-box;
          padding: 12px 16px;
          transition: all 0.3s ease;
          backdrop-filter: blur(10px);
        }

        .inputUser::placeholder {
          color: rgba(255, 255, 255, 0.6);
        }

        .inputUser:focus {
          background: rgba(255, 255, 255, 0.15);
          border-color: rgba(255, 255, 255, 0.4);
          box-shadow: 0 0 20px rgba(255, 255, 255, 0.1), inset 0 0 20px rgba(255, 255, 255, 0.05);
          transform: translateY(-2px);
        }

        .labelinput {
          position: absolute;
          top: 12px;
          left: 16px;
          pointer-events: none;
          transition: all 0.3s ease;
          font-size: 15px;
          color: rgba(255, 255, 255, 0.7);
          font-weight: 500;
        }

        .inputUser:focus ~ .labelinput,
        .inputUser:valid ~ .labelinput {
          top: -12px;
          left: 10px;
          font-size: 12px;
          color: rgba(255, 255, 255, 0.9);
          background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
          padding: 0 6px;
          font-weight: 600;
        }

        .toggle-password {
          position: absolute;
          top: 50%;
          right: 16px;
          transform: translateY(-50%);
          cursor: pointer;
          font-size: 18px;
          color: rgba(255, 255, 255, 0.7);
          user-select: none;
          transition: all 0.3s ease;
          display: flex;
          align-items: center;
          justify-content: center;
          width: 32px;
          height: 32px;
          border-radius: 8px;
          background: rgba(255, 255, 255, 0.05);
        }

        .toggle-password:hover {
          color: rgba(255, 255, 255, 0.95);
          background: rgba(255, 255, 255, 0.1);
          transform: translateY(-50%) scale(1.15);
          box-shadow: 0 4px 12px rgba(255, 255, 255, 0.15);
        }

        .toggle-password i {
          font-size: 18px;
          transition: all 0.3s ease;
        }

        .inputSenha {
          padding-right: 45px;
        }

        .caps-aviso {
          position: absolute;
          top: 100%;
          left: 16px;
          font-size: 12px;
          color: #ffd93d;
          margin-top: 6px;
          visibility: hidden;
          height: 16px;
          font-weight: 600;
          display: flex;
          align-items: center;
          gap: 4px;
        }

        .modal-form-group {
          margin-bottom: 24px;
        }

        .modal-form-group label {
          display: block;
          color: rgba(255, 255, 255, 0.9);
          margin-bottom: 12px;
          font-weight: 600;
          font-size: 14px;
          letter-spacing: 0.3px;
        }

        .radio-group {
          display: flex;
          gap: 20px;
          margin-top: 12px;
        }

        .radio-group label {
          display: flex;
          align-items: center;
          margin-bottom: 0;
          font-weight: 500;
          cursor: pointer;
          transition: all 0.3s ease;
        }

        .radio-group input[type="radio"] {
          appearance: none;
          width: 20px;
          height: 20px;
          border: 2px solid rgba(255, 255, 255, 0.4);
          border-radius: 50%;
          cursor: pointer;
          margin-right: 8px;
          transition: all 0.3s ease;
          background: rgba(255, 255, 255, 0.1);
        }

        .radio-group input[type="radio"]:hover {
          border-color: rgba(255, 255, 255, 0.6);
          background: rgba(255, 255, 255, 0.15);
        }

        .radio-group input[type="radio"]:checked {
          background: rgba(255, 255, 255, 0.9);
          border-color: white;
          box-shadow: inset 0 0 0 4px linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .radio-group label:hover {
          color: rgba(255, 255, 255, 1);
        }

        #submit-registro {
          background: linear-gradient(135deg, rgba(255, 255, 255, 0.25) 0%, rgba(255, 255, 255, 0.1) 100%);
          width: 100%;
          border: 2px solid rgba(255, 255, 255, 0.3);
          padding: 14px;
          border-radius: 12px;
          color: white;
          font-size: 16px;
          cursor: pointer;
          margin-top: 10px;
          transition: all 0.3s ease;
          font-weight: 700;
          letter-spacing: 0.5px;
          backdrop-filter: blur(10px);
          text-transform: uppercase;
          box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
        }

        #submit-registro:hover {
          background: linear-gradient(135deg, rgba(255, 255, 255, 0.35) 0%, rgba(255, 255, 255, 0.2) 100%);
          border-color: rgba(255, 255, 255, 0.5);
          transform: translateY(-2px);
          box-shadow: 0 12px 32px rgba(102, 126, 234, 0.3);
        }

        #submit-registro:active {
          transform: translateY(0);
          box-shadow: 0 4px 16px rgba(102, 126, 234, 0.2);
        }

        .error-message {
          color: #ffebee;
          font-size: 13px;
          margin-top: 10px;
          display: none;
          background: rgba(255, 59, 48, 0.2);
          padding: 10px 14px;
          border-radius: 8px;
          border-left: 3px solid #ff3b30;
          font-weight: 500;
          backdrop-filter: blur(10px);
        }

        .error-message::before {
          content: '⚠️ ';
          margin-right: 6px;
        }

        #nome-modal {
          text-transform: capitalize;
        }

        /* ===== MODAL DE LOGIN ===== */
        .modal-login {
          display: none;
          position: fixed;
          z-index: 2000;
          left: 0;
          top: 0;
          width: 100%;
          height: 100%;
          background: rgba(0, 0, 0, 0.6);
          backdrop-filter: blur(4px);
          animation: fadeIn 0.3s ease;
        }

        .modal-login.show {
          display: flex;
          justify-content: center;
          align-items: center;
        }

        .modal-content-login {
          background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
          padding: 0;
          border-radius: 20px;
          width: 90%;
          max-width: 450px;
          max-height: 90vh;
          overflow-y: auto;
          color: white;
          animation: slideIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
          box-shadow: 0 20px 60px rgba(102, 126, 234, 0.4), 0 0 1px rgba(102, 126, 234, 0.5);
          position: relative;
          border: 1px solid rgba(255, 255, 255, 0.2);
          overflow: hidden;
        }

        .modal-content-login::before {
          content: '';
          position: absolute;
          top: -50%;
          right: -50%;
          width: 200%;
          height: 200%;
          background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
          pointer-events: none;
        }

        .modal-close-btn {
          position: absolute;
          top: 20px;
          right: 20px;
          background: rgba(255, 255, 255, 0.2);
          border: none;
          font-size: 28px;
          color: white;
          cursor: pointer;
          transition: all 0.3s ease;
          width: 40px;
          height: 40px;
          border-radius: 50%;
          display: flex;
          align-items: center;
          justify-content: center;
          z-index: 10;
          backdrop-filter: blur(10px);
        }

        .modal-close-btn:hover {
          background: rgba(255, 255, 255, 0.3);
          transform: rotate(90deg);
        }

        .modal-header {
          text-align: center;
          padding: 35px 30px 25px;
          border-bottom: 2px solid rgba(255, 255, 255, 0.2);
          position: relative;
          z-index: 1;
        }

        .modal-header h2 {
          margin: 0;
          font-size: 26px;
          color: white;
          font-weight: 700;
          letter-spacing: 0.5px;
          display: flex;
          align-items: center;
          justify-content: center;
          gap: 12px;
        }

        .modal-header h2 i {
          font-size: 28px;
          transition: all 0.3s ease;
        }

        .modal-header h2:hover i {
          transform: scale(1.1) rotate(5deg);
        }

        .modal-form-wrapper {
          padding: 30px;
          position: relative;
          z-index: 1;
        }

        .inputbox {
          position: relative;
          margin-bottom: 24px;
        }

        .inputUser {
          background: rgba(255, 255, 255, 0.1);
          border: 2px solid rgba(255, 255, 255, 0.2);
          border-radius: 12px;
          width: 100%;
          outline: none;
          color: white;
          font-size: 15px;
          letter-spacing: 0.5px;
          box-sizing: border-box;
          padding: 12px 16px;
          transition: all 0.3s ease;
          backdrop-filter: blur(10px);
        }

        .inputUser::placeholder {
          color: rgba(255, 255, 255, 0.6);
        }

        .inputUser:focus {
          background: rgba(255, 255, 255, 0.15);
          border-color: rgba(255, 255, 255, 0.4);
          box-shadow: 0 0 20px rgba(255, 255, 255, 0.1), inset 0 0 20px rgba(255, 255, 255, 0.05);
          transform: translateY(-2px);
        }

        .labelinput {
          position: absolute;
          top: 12px;
          left: 16px;
          pointer-events: none;
          transition: all 0.3s ease;
          font-size: 15px;
          color: rgba(255, 255, 255, 0.7);
          font-weight: 500;
        }

        .inputUser:focus ~ .labelinput,
        .inputUser:valid ~ .labelinput {
          top: -12px;
          left: 10px;
          font-size: 12px;
          color: rgba(255, 255, 255, 0.9);
          background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
          padding: 0 6px;
          font-weight: 600;
        }

        .toggle-password {
          position: absolute;
          top: 50%;
          right: 16px;
          transform: translateY(-50%);
          cursor: pointer;
          font-size: 18px;
          color: rgba(255, 255, 255, 0.7);
          user-select: none;
          transition: all 0.3s ease;
          display: flex;
          align-items: center;
          justify-content: center;
          width: 32px;
          height: 32px;
          border-radius: 8px;
          background: rgba(255, 255, 255, 0.05);
        }

        .toggle-password:hover {
          color: rgba(255, 255, 255, 0.95);
          background: rgba(255, 255, 255, 0.1);
          transform: translateY(-50%) scale(1.15);
          box-shadow: 0 4px 12px rgba(255, 255, 255, 0.15);
        }

        .toggle-password i {
          font-size: 18px;
          transition: all 0.3s ease;
        }

        .inputSenha {
          padding-right: 45px;
        }

        .caps-aviso {
          position: absolute;
          top: 100%;
          left: 16px;
          font-size: 12px;
          color: #ffd93d;
          margin-top: 6px;
          visibility: hidden;
          height: 16px;
          font-weight: 600;
          display: flex;
          align-items: center;
          gap: 4px;
        }

        #submit-login,
        #submit-registro {
          background: linear-gradient(135deg, rgba(255, 255, 255, 0.25) 0%, rgba(255, 255, 255, 0.1) 100%);
          width: 100%;
          border: 2px solid rgba(255, 255, 255, 0.3);
          padding: 14px;
          border-radius: 12px;
          color: white;
          font-size: 16px;
          cursor: pointer;
          margin-top: 10px;
          transition: all 0.3s ease;
          font-weight: 700;
          letter-spacing: 0.5px;
          backdrop-filter: blur(10px);
          text-transform: uppercase;
          box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
        }

        #submit-login:hover,
        #submit-registro:hover {
          background: linear-gradient(135deg, rgba(255, 255, 255, 0.35) 0%, rgba(255, 255, 255, 0.2) 100%);
          border-color: rgba(255, 255, 255, 0.5);
          transform: translateY(-2px);
          box-shadow: 0 12px 32px rgba(102, 126, 234, 0.3);
        }

        #submit-login:active,
        #submit-registro:active {
          transform: translateY(0);
          box-shadow: 0 4px 16px rgba(102, 126, 234, 0.2);
        }

        .error-message {
          color: #ffebee;
          font-size: 13px;
          margin-top: 10px;
          display: none;
          background: rgba(255, 59, 48, 0.2);
          padding: 10px 14px;
          border-radius: 8px;
          border-left: 3px solid #ff3b30;
          font-weight: 500;
          backdrop-filter: blur(10px);
        }

        .error-message::before {
          content: '⚠️ ';
          margin-right: 6px;
        }

        .modal-footer-link {
          text-align: center;
          margin-top: 20px;
          color: rgba(255, 255, 255, 0.8);
          font-size: 13px;
          display: flex;
          flex-direction: column;
          gap: 10px;
          align-items: center;
        }

        .modal-footer-link > div {
          display: flex;
          gap: 20px;
          align-items: center;
          justify-content: center;
          flex-wrap: wrap;
        }

        .modal-footer-link a,
        .modal-footer-link button {
          color: rgba(255, 255, 255, 0.95);
          font-weight: 600;
          cursor: pointer;
          background: none;
          border: none;
          text-decoration: underline;
          transition: all 0.3s ease;
          padding: 0;
          font-size: 13px;
          white-space: nowrap;
        }

        .modal-footer-link a:hover,
        .modal-footer-link button:hover {
          color: white;
          transform: translateY(-1px);
        }

        @media (max-width: 768px) {
          .modal-content-registro,
          .modal-content-login {
            width: 95%;
            max-width: 100%;
          }

          .modal-form-wrapper {
            padding: 25px;
          }

          .modal-header {
            padding: 30px 25px 20px;
          }

          .radio-group {
            flex-direction: column;
            gap: 12px;
          }
        }
    </style>
    <link rel="stylesheet" href="css/menu-topo.css">
</head>
<body>
    <header class="header">
        <div class="menu-topo-container">
            <div id="top-bar"> 
                <div class="menu-container">
                    <!-- Botão hambúrguer para menu mobile -->
                    <button class="menu-button" onclick="toggleMenu()">☰</button>

                    <!-- Menu dropdown de navegação -->
                    <div id="menu" class="menu-content">
                        <a href="home.php">
                            <i class="fas fa-home menu-icon"></i><span>Home</span>
                        </a>
                        <a href="gestao-diaria.php">
                            <i class="fas fa-university menu-icon"></i><span>Gestão de Banca</span>
                        </a>
                        <a href="#" id="abrirGerenciaBanca">
                            <i class="fas fa-wallet menu-icon"></i><span>Gerenciar Banca</span>
                        </a>
                        <a href="bot_aovivo.php">
                            <i class="fas fa-robot menu-icon"></i><span>Bot ao Vivo</span><span class="ao-vivo-icon"><i class="fas fa-circle"></i></span>
                        </a>

                        <?php if (isset($_SESSION['usuario_id'])): ?>
                            <a href="conta.php" id="abrirMinhaContaModal">
                                <i class="fas fa-user-circle menu-icon"></i><span>Minha Conta</span>
                            </a>
                            <a href="logout.php">
                                <i class="fas fa-sign-out-alt menu-icon"></i><span>Sair</span>
                            </a>
                        <?php endif; ?>
                    </div>

                    <!-- Área do saldo da banca (canto direito) OU botões de login/registre-se -->
                    <div id="lista-mentores">
                        <!-- Mostrar quando LOGADO -->
                        <?php if (isset($_SESSION['usuario_id'])): ?>
                            <div class="valor-item-menu saldo-topo-ajustado">
                                <div class="valor-info-wrapper">
                                    <!-- Valor total da banca -->
                                    <div class="valor-label-linha">
                                        <i class="fa-solid fa-building-columns valor-icone-tema"></i>
                                        <span class="valor-label">Banca:</span>
                                        <span class="valor-bold-menu" id="valorTotalBancaLabel">R$ 0,00</span>
                                    </div>
                                    <!-- Lucro dos mentores -->
                                    <div class="valor-label-linha">
                                        <i class="fa-solid fa-arrow-trend-up valor-icone-tema" id="icone-lucro-dinamico"></i>
                                        <span class="valor-label">Lucro:</span>
                                        <span class="valor-bold-menu" id="lucro_valor_entrada">R$ 0,00</span>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- Mostrar quando NÃO LOGADO - Botões de Login/Registre-se -->
                            <div class="auth-buttons">
                                <button onclick="abrirModalLogin()" class="btn-login" style="display: flex; align-items: center; gap: 6px; padding: 8px 14px; text-decoration: none; border-radius: 6px; font-weight: 500; font-size: 13px; border: 2px solid #2196F3; background-color: #2196F3; color: white; cursor: pointer;">
                                    <i class="fas fa-sign-in-alt"></i> Login
                                </button>
                                <button onclick="abrirModalRegistro()" class="btn-register" style="display: flex; align-items: center; gap: 6px; padding: 8px 14px; text-decoration: none; border-radius: 6px; font-weight: 500; font-size: 13px; border: 2px solid #4CAF50; background-color: #4CAF50; color: white; cursor: pointer;">
                                    <i class="fas fa-user-plus"></i> Registre-se
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </header>
    
    <main class="main-content">
        <div class="container">
            <!-- CONTEÚDO VAZIO - A ADICIONAR -->
        </div>
    </main>
    
    <footer class="footer"></footer>

    <!-- MODAL DE REGISTRO -->
    <div id="modalRegistro" class="modal-registro">
        <div class="modal-content-registro">
            <button class="modal-close-btn" onclick="fecharModalRegistro()">&times;</button>
            
            <div class="modal-header">
                <h2><i class="fas fa-clipboard-user"></i> Criar Conta</h2>
            </div>

            <div class="modal-form-wrapper">
                <form id="formRegistro" method="POST" onsubmit="enviarFormularioRegistro(event)">
                    <!-- Nome -->
                    <div class="inputbox">
                        <input type="text" name="nome" id="nome-modal" class="inputUser" required>
                        <label for="nome-modal" class="labelinput">Nome Completo</label>
                    </div>

                    <!-- Email -->
                    <div class="inputbox">
                        <input type="email" name="email" id="email-modal" class="inputUser" required>
                        <label for="email-modal" class="labelinput">Endereço de Email</label>
                    </div>

                    <!-- Senha -->
                    <div class="inputbox">
                        <input type="password" name="senha" id="senha-modal" class="inputUser inputSenha" required>
                        <label for="senha-modal" class="labelinput">Senha</label>
                        <span class="toggle-password" onclick="togglePasswordVisibility('senha-modal')"><i class="fas fa-eye-slash"></i></span>
                        <div id="caps-lock-warning-modal" class="caps-aviso">Caps Lock ativado</div>
                    </div>

                    <!-- Telefone -->
                    <div class="inputbox">
                        <input type="tel" name="telefone" id="telefone-modal" class="inputUser" required>
                        <label for="telefone-modal" class="labelinput">Número de Telefone</label>
                    </div>

                    <!-- Sexo/Gênero -->
                    <div class="modal-form-group">
                        <label>Qual é o seu gênero?</label>
                        <div class="radio-group">
                            <label>
                                <input type="radio" name="genero" value="feminino" required>
                                Feminino
                            </label>
                            <label>
                                <input type="radio" name="genero" value="masculino" required>
                                Masculino
                            </label>
                            <label>
                                <input type="radio" name="genero" value="outros" required>
                                Outros
                            </label>
                        </div>
                    </div>

                    <div id="erro-modal" class="error-message"></div>

                    <input type="submit" id="submit-registro" value="Criar Conta">
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL DE LOGIN -->
    <div id="modalLogin" class="modal-login">
        <div class="modal-content-login">
            <button class="modal-close-btn" onclick="fecharModalLogin()">&times;</button>
            
            <div class="modal-header">
                <h2><i class="fas fa-sign-in-alt"></i> Acessar Conta</h2>
            </div>

            <div class="modal-form-wrapper">
                <form id="formLogin" method="POST" onsubmit="enviarFormularioLogin(event)">
                    <!-- Email -->
                    <div class="inputbox">
                        <input type="email" name="email" id="email-login" class="inputUser" required>
                        <label for="email-login" class="labelinput">Endereço de Email</label>
                    </div>

                    <!-- Senha -->
                    <div class="inputbox">
                        <input type="password" name="senha" id="senha-login" class="inputUser inputSenha" required>
                        <label for="senha-login" class="labelinput">Senha</label>
                        <span class="toggle-password" onclick="togglePasswordVisibility('senha-login')"><i class="fas fa-eye-slash"></i></span>
                        <div id="caps-lock-warning-login" class="caps-aviso">Caps Lock ativado</div>
                    </div>

                    <div id="erro-login" class="error-message"></div>

                    <input type="submit" id="submit-login" value="Acessar">
                </form>

                <div class="modal-footer-link">
                    <div>
                        <a href="recuperar_senha.php" style="color: rgba(255, 255, 255, 0.8); text-decoration: none;">
                            Esqueceu sua senha?
                        </a>
                        <span style="color: rgba(255, 255, 255, 0.6);">•</span>
                        <span>Não tem conta? <button onclick="irParaRegistro()">Registre-se</button></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Script para carregar Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- ✅ VERIFICAÇÃO DE AUTENTICAÇÃO PARA "GERENCIAR BANCA" -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const botaoGerencia = document.getElementById('abrirGerenciaBanca');
            const usuarioAutenticado = <?php echo json_encode(isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id'])); ?>;

            if (botaoGerencia && !usuarioAutenticado) {
                botaoGerencia.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    // Se não autenticado, navega para gestao-diaria.php para mostrar bloqueio
                    window.location.href = 'gestao-diaria.php';
                });
            } else if (botaoGerencia && usuarioAutenticado) {
                botaoGerencia.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    // Se autenticado, navega para gestao-diaria.php
                    window.location.href = 'gestao-diaria.php';
                });
            }
        });
    </script>

    <!-- Script do menu toggle -->
    <script>
    function toggleMenu() {
      var menu = document.getElementById("menu");
      if (menu) {
        // toggle inline display
        var isOpen = menu.style.display === "block" || menu.classList.contains('show');
        menu.style.display = isOpen ? "none" : "block";
        menu.classList.toggle('show');
      }
    }

    // Fechar menu ao clicar fora
    document.addEventListener('click', function(event) {
      var menu = document.getElementById("menu");
      var menuButton = document.querySelector(".menu-button");
      
      if (menu && menuButton) {
        // Se clicou no botão, já é tratado por toggleMenu
        if (menuButton.contains(event.target)) {
          return;
        }
        
        // Se não clicou no menu e não clicou no botão, fecha
        if (!menu.contains(event.target)) {
          menu.style.display = "none";
          menu.classList.remove('show');
        }
      }
    });

    // ===== FUNÇÕES DO MODAL DE REGISTRO =====
    function abrirModalRegistro() {
      document.getElementById("modalRegistro").classList.add("show");
    }

    function fecharModalRegistro() {
      document.getElementById("modalRegistro").classList.remove("show");
      document.getElementById("formRegistro").reset();
    }

    // ===== FUNÇÕES DO MODAL DE LOGIN =====
    function abrirModalLogin() {
      document.getElementById("modalLogin").classList.add("show");
    }

    function fecharModalLogin() {
      document.getElementById("modalLogin").classList.remove("show");
      document.getElementById("formLogin").reset();
    }

    function irParaRegistro() {
      fecharModalLogin();
      setTimeout(() => {
        abrirModalRegistro();
      }, 300);
    }

    // Fechar modal ao clicar fora
    document.getElementById("modalRegistro").addEventListener("click", function(event) {
      if (event.target === this) {
        fecharModalRegistro();
      }
    });

    document.getElementById("modalLogin").addEventListener("click", function(event) {
      if (event.target === this) {
        fecharModalLogin();
      }
    });

    // Fechar com tecla ESC
    document.addEventListener("keydown", function(event) {
      if (event.key === "Escape") {
        fecharModalRegistro();
        fecharModalLogin();
      }
    });

    // Toggle visibilidade da senha
    function togglePasswordVisibility(inputId) {
      const senha = document.getElementById(inputId);
      const icone = event.target.closest('.toggle-password');

      if (senha.type === "password") {
        senha.type = "text";
        icone.innerHTML = '<i class="fas fa-eye"></i>';
      } else {
        senha.type = "password";
        icone.innerHTML = '<i class="fas fa-eye-slash"></i>';
      }
    }

    // Detectar Caps Lock
    const passwordInput = document.getElementById("senha-modal");
    const capsLockWarning = document.getElementById("caps-lock-warning-modal");

    if (passwordInput) {
      passwordInput.addEventListener("keyup", function(event) {
        if (event.getModifierState("CapsLock")) {
          capsLockWarning.style.visibility = "visible";
        } else {
          capsLockWarning.style.visibility = "hidden";
        }
      });
    }

    // Formatar Telefone
    async function detectarCodigoPais() {
      try {
        const resposta = await fetch('https://ipapi.co/json/');
        const dados = await resposta.json();
        const codigoPais = dados.country_calling_code;
        const telefoneInput = document.getElementById('telefone-modal');

        if (telefoneInput && !telefoneInput.value.startsWith(codigoPais)) {
          telefoneInput.value = codigoPais + ' ';
        }
      } catch (erro) {
        console.warn('Não foi possível detectar o país:', erro);
      }
    }

    function aplicarMascaraTelefone() {
      const telefoneInput = document.getElementById('telefone-modal');

      if (telefoneInput) {
        telefoneInput.addEventListener('input', function(e) {
          let valor = e.target.value.replace(/\D/g, '');

          if (valor.startsWith('55')) {
            valor = valor.slice(2);
          }

          if (valor.length > 11) valor = valor.slice(0, 11);

          valor = valor.replace(/^(\d{2})(\d)/g, '($1) $2');
          valor = valor.replace(/(\d{5})(\d{1,4})$/, '$1-$2');

          e.target.value = '+55 ' + valor;
        });
      }
    }

    // Inicializar quando modal abrir
    const originalAbrirModal = window.abrirModalRegistro;
    window.abrirModalRegistro = function() {
      originalAbrirModal();
      detectarCodigoPais();
      aplicarMascaraTelefone();
    };

    // Enviar formulário via AJAX
    function enviarFormularioRegistro(event) {
      event.preventDefault();

      const formData = new FormData(document.getElementById("formRegistro"));
      const erroDiv = document.getElementById("erro-modal");

      fetch('formulario.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.text())
      .then(data => {
        if (data.includes("Cadastro efetuado com sucesso")) {
          alert("Cadastro realizado com sucesso! Redirecionando para login...");
          window.location.href = "login.php";
        } else if (data.includes("já está cadastrado")) {
          erroDiv.textContent = "Este e-mail já está cadastrado!";
          erroDiv.style.display = "block";
        } else {
          erroDiv.textContent = "Erro ao cadastrar. Tente novamente!";
          erroDiv.style.display = "block";
        }
      })
      .catch(error => {
        console.error('Erro:', error);
        erroDiv.textContent = "Erro de conexão. Tente novamente!";
        erroDiv.style.display = "block";
      });
    }

    // Enviar formulário de login via AJAX
    function enviarFormularioLogin(event) {
      event.preventDefault();

      const email = document.getElementById("email-login").value;
      const senha = document.getElementById("senha-login").value;
      const erroDiv = document.getElementById("erro-login");

      const formData = new FormData();
      formData.append('email', email);
      formData.append('senha', senha);
      formData.append('ajax', '1');

      fetch('login-user.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.text())
      .then(data => {
        console.log('Resposta do servidor:', data);
        
        // Se a resposta contiver sucesso, fecha o modal e recarrega
        if (data.includes("sucesso") || data.trim() === "sucesso") {
          erroDiv.style.display = "none";
          fecharModalLogin();
          // Aguarda um pouco e recarrega a página para refletir a sessão
          setTimeout(() => {
            location.reload();
          }, 500);
        } else if (data.includes("Senha Incorreta") || data.includes("incorreta")) {
          erroDiv.textContent = "E-mail ou senha incorretos!";
          erroDiv.style.display = "block";
        } else if (data.includes("E-mail Não Cadastrado") || data.includes("não cadastrado")) {
          erroDiv.textContent = "E-mail não encontrado!";
          erroDiv.style.display = "block";
        } else {
          erroDiv.textContent = "Erro ao fazer login. Tente novamente!";
          erroDiv.style.display = "block";
        }
      })
      .catch(error => {
        console.error('Erro:', error);
        erroDiv.textContent = "Erro de conexão. Tente novamente!";
        erroDiv.style.display = "block";
      });
    }

    // Detectar Caps Lock no login
    const passwordInputLogin = document.getElementById("senha-login");
    const capsLockWarningLogin = document.getElementById("caps-lock-warning-login");

    if (passwordInputLogin) {
      passwordInputLogin.addEventListener("keyup", function(event) {
        if (event.getModifierState("CapsLock")) {
          capsLockWarningLogin.style.visibility = "visible";
        } else {
          capsLockWarningLogin.style.visibility = "hidden";
        }
      });
    }

    // Inicializar detectar código ao carregar
    window.addEventListener('load', function() {
      detectarCodigoPais();
      
      // Se o parâmetro login_required está na URL, abrir modal de login
      const urlParams = new URLSearchParams(window.location.search);
      if (urlParams.has('login_required')) {
        abrirModalLogin();
        // Remover o parâmetro da URL para não aparecer novamente ao recarregar
        window.history.replaceState({}, document.title, window.location.pathname);
      }

      // ✅ Carregar dados de lucro e banca
      carregarDadosBancaELucro();
    });

    // ✅ FUNÇÃO PARA CARREGAR DADOS DINÂMICOS
    function carregarDadosBancaELucro() {
      // Só carregar se o usuário estiver autenticado
      const usuarioAutenticado = <?php echo json_encode(isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id'])); ?>;
      
      if (!usuarioAutenticado) return;

      fetch('dados_banca.php')
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            // Atualizar Banca
            const valorBancaLabel = document.getElementById('valorTotalBancaLabel');
            if (valorBancaLabel) {
              valorBancaLabel.textContent = data.banca_formatada;
            }

            // Atualizar Lucro
            const lucroValorEntrada = document.getElementById('lucro_valor_entrada');
            if (lucroValorEntrada) {
              lucroValorEntrada.textContent = data.lucro_total_formatado || 'R$ 0,00';
              
              // Atualizar classe de cor baseada no valor
              const lucroFloat = parseFloat(data.lucro_total || 0);
              lucroValorEntrada.classList.remove('saldo-positivo', 'saldo-negativo', 'saldo-neutro');
              
              if (lucroFloat > 0) {
                lucroValorEntrada.classList.add('saldo-positivo');
              } else if (lucroFloat < 0) {
                lucroValorEntrada.classList.add('saldo-negativo');
              } else {
                lucroValorEntrada.classList.add('saldo-neutro');
              }

              // Atualizar ícone dinamicamente
              atualizarIconeLucroDinamico(lucroFloat);
            }
          }
        })
        .catch(error => console.error('Erro ao carregar dados:', error));
    }

    // ✅ FUNÇÃO PARA ATUALIZAR ÍCONE DINAMICAMENTE
    function atualizarIconeLucroDinamico(lucro) {
      const iconeLucro = document.getElementById('icone-lucro-dinamico');
      if (!iconeLucro) return;

      // Remover classes anteriores
      iconeLucro.classList.remove('fa-arrow-trend-up', 'fa-arrow-trend-down', 'fa-minus');
      iconeLucro.style.transform = 'none';

      if (lucro > 0) {
        iconeLucro.classList.add('fa-arrow-trend-up');
        iconeLucro.style.color = '#9fe870';
      } else if (lucro < 0) {
        iconeLucro.classList.add('fa-arrow-trend-down');
        iconeLucro.style.color = '#e57373';
      } else {
        iconeLucro.classList.add('fa-minus');
        iconeLucro.style.color = '#cfd8dc';
      }
    }

    // ✅ ATUALIZAR A CADA 30 SEGUNDOS
    setInterval(carregarDadosBancaELucro, 30000);
    </script>
</body>
</html>
