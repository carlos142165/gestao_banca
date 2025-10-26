<?php
session_start();

// 🔐 Verificação de sessão
if (!isset($_SESSION['usuario_id']) || empty($_SESSION['usuario_id'])) {
  // Redireciona para home se não está autenticado
  header('Location: home.php');
  exit();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minha Conta</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Poppins", sans-serif;
            background-color: #f5f5f5;
            min-height: 100vh;
            height: 100vh;
            padding: 10px;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .container-conta {
            width: 100%;
            max-width: 500px;
            background: white;
            border-radius: 24px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            max-height: 90vh;
            min-height: 100%;
            animation: slideIn 0.4s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-60px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .header-conta {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
        }

        .header-conta h1 {
            font-size: 18px;
            margin-bottom: 2px;
            font-weight: 600;
        }

        .header-conta p {
            font-size: 12px;
            opacity: 0.9;
            margin: 0;
        }

        .btn-voltar {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
        }

        .btn-voltar:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .body-conta {
            padding: 15px 15px;
            overflow-y: auto;
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        /* Scroll suave e elegante */
        .body-conta::-webkit-scrollbar {
            width: 6px;
        }

        .body-conta::-webkit-scrollbar-track {
            background: transparent;
        }

        .body-conta::-webkit-scrollbar-thumb {
            background: #ddd;
            border-radius: 3px;
        }

        .body-conta::-webkit-scrollbar-thumb:hover {
            background: #999;
        }

        /* Distribuir conteúdo */
        .conteudo-principal {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 12px;
            justify-content: space-between;
        }

        .botao-excluir-conta-container {
            flex-shrink: 0;
            margin-top: auto;
            padding-top: 10px;
            border-top: 2px solid #f0f2f5;
        }

        /* Seção Plano */
        .secao-plano {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
            padding: 12px 12px;
            border-radius: 12px;
            border-left: 4px solid #667eea;
            flex-shrink: 0;
        }

        .secao-plano-titulo {
            font-size: 12px;
            font-weight: 600;
            color: #667eea;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }

        .secao-plano-conteudo {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .secao-plano-valor {
            font-size: 16px;
            font-weight: 700;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .badge-plano {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            background-color: rgba(255, 255, 255, 0.7);
            border: 1px solid;
        }

        .badge-plano i {
            font-size: 12px;
        }

        .badge-plano-gratuito {
            border-color: #95a5a6;
            color: #95a5a6;
        }

        .badge-plano-prata {
            border-color: #c0392b;
            color: #c0392b;
        }

        .badge-plano-ouro {
            border-color: #f39c12;
            color: #f39c12;
        }

        .badge-plano-diamante {
            border-color: #2980b9;
            color: #2980b9;
        }

        .plano-data-expiracao {
            font-size: 10px;
            color: #7f8c8d;
            margin-top: 4px;
            display: block;
        }

        .btn-alterar-plano {
            background: #667eea;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 11px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-alterar-plano:hover {
            background: #764ba2;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        /* Seção Campos */
        .secao-campo {
            display: flex;
            flex-direction: column;
            gap: 8px;
            flex: 1;
        }

        .secao-campo-label {
            font-size: 11px;
            font-weight: 700;
            color: #667eea;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .secao-campo-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 10px;
            background: #f8f9fa;
            border-radius: 10px;
            border: 1px solid #e8eef4;
            transition: all 0.3s ease;
            flex: 1;
        }

        .secao-campo-item:hover {
            background: #fff;
            border-color: #667eea;
        }

        .secao-campo-icone {
            font-size: 16px;
            color: #667eea;
            min-width: 16px;
        }

        .secao-campo-info {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 1px;
        }

        .secao-campo-rotulo {
            font-size: 10px;
            font-weight: 600;
            color: #94a3b8;
            text-transform: uppercase;
        }

        .secao-campo-valor {
            font-size: 13px;
            color: #2c3e50;
            word-break: break-word;
        }

        .secao-campo-botao {
            background: transparent;
            border: none;
            color: #667eea;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
            padding: 2px 6px;
            border-radius: 4px;
        }

        .secao-campo-botao:hover {
            background: rgba(102, 126, 234, 0.1);
            transform: scale(1.1);
        }

        /* Seção Senha */
        .secao-senha {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
            padding: 12px 12px;
            border-radius: 12px;
            border-left: 4px solid #667eea;
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .secao-senha-titulo {
            font-size: 12px;
            font-weight: 600;
            color: #667eea;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0;
        }

        .secao-senha-item {
            display: flex;
            flex-direction: column;
            gap: 4px;
            padding: 8px;
            background: #fff;
            border-radius: 8px;
            border: 1px solid #e8eef4;
            margin-bottom: 0;
            flex: 1;
            justify-content: space-between;
        }

        .secao-senha-item label {
            display: block;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 4px;
            font-size: 12px;
        }

        .input-senha {
            width: 100%;
            padding: 6px 8px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 12px;
            box-sizing: border-box;
            transition: all 0.3s ease;
        }

        .input-senha:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.1);
        }

        .btn-atualizar-senha {
            background: #667eea;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            margin-top: 10px;
            flex-shrink: 0;
        }

        .btn-atualizar-senha:hover {
            background: #764ba2;
            transform: translateY(-2px);
        }

        .btn-atualizar-senha:active {
            transform: translateY(0);
        }

        /* Botão Excluir Conta */
        .btn-excluir-conta {
            width: 100%;
            padding: 8px 10px;
            background: #e74c3c;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .btn-excluir-conta:hover {
            background: #c0392b;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(231, 76, 60, 0.3);
        }

        .btn-excluir-conta:active {
            transform: translateY(0);
        }

        /* Modal Editar Campo */
        .modal-editar-campo {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 10000;
            backdrop-filter: blur(5px);
        }

        .modal-editar-campo.ativo {
            display: flex;
        }

        .modal-editar-campo-conteudo {
            background: white;
            border-radius: 24px;
            width: 90%;
            max-width: 450px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            animation: slideIn 0.4s ease-out;
        }

        .modal-editar-campo-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            text-align: center;
            position: relative;
        }

        .modal-editar-campo-header h3 {
            font-size: 20px;
            margin: 0;
            font-weight: 600;
        }

        .btn-fechar-editar-campo {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .btn-fechar-editar-campo:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: rotate(90deg);
        }

        .modal-editar-campo-body {
            padding: 25px;
        }

        .formulario-editar-campo {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .campo-editar-input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 14px;
            box-sizing: border-box;
            transition: all 0.3s ease;
        }

        .campo-editar-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }

        .botoes-editar-campo {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }

        .btn-cancelar-editar {
            flex: 1;
            padding: 10px;
            background: #e0e0e0;
            color: #333;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-cancelar-editar:hover {
            background: #d0d0d0;
        }

        .btn-salvar-editar {
            flex: 1;
            padding: 10px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .btn-salvar-editar:hover {
            background: #764ba2;
        }

        /* Modal Confirmação de Exclusão */
        #modal-confirmar-exclusao-conta {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 10000;
        }

        #modal-confirmar-exclusao-conta.ativo {
            display: flex;
        }

        .modal-confirmar-exclusao-conteudo {
            background: white;
            border-radius: 24px;
            width: 90%;
            max-width: 450px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.4);
            animation: slideIn 0.4s ease-out;
            z-index: 10001;
        }

        .modal-confirmar-exclusao-header {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
            padding: 30px 25px;
            text-align: center;
        }

        .modal-confirmar-exclusao-header h3 {
            margin: 0;
            font-size: 22px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .modal-confirmar-exclusao-body {
            padding: 25px;
        }

        .modal-confirmar-exclusao-texto {
            font-size: 14px;
            color: #2c3e50;
            line-height: 1.6;
            margin-bottom: 15px;
        }

        .modal-confirmar-exclusao-aviso {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 12px;
            border-radius: 8px;
            font-size: 13px;
            color: #856404;
            margin-bottom: 20px;
        }

        .modal-confirmar-exclusao-input {
            width: 100%;
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            box-sizing: border-box;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }

        .modal-confirmar-exclusao-input:focus {
            outline: none;
            border-color: #e74c3c;
        }

        .botoes-confirmar-exclusao {
            display: flex;
            gap: 10px;
        }

        .btn-cancelar-exclusao,
        .btn-confirmar-exclusao {
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            font-size: 12px;
        }

        .btn-cancelar-exclusao {
            background: #e0e0e0;
            color: #333;
        }

        .btn-cancelar-exclusao:hover {
            background: #d0d0d0;
        }

        .btn-confirmar-exclusao {
            background: #e74c3c;
            color: white;
        }

        .btn-confirmar-exclusao:hover:not(:disabled) {
            background: #c0392b;
            transform: translateY(-2px);
        }

        .btn-confirmar-exclusao:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Responsivo */
        @media (max-width: 768px) {
            .container-conta {
                width: 95%;
                max-height: 95vh;
            }

            .body-conta {
                padding: 15px;
                gap: 12px;
            }

            .header-conta {
                padding: 15px;
            }
        }

        @media (max-width: 480px) {
            .container-conta {
                width: 98%;
                border-radius: 16px;
            }

            .body-conta {
                padding: 12px;
                gap: 12px;
            }

            .header-conta {
                padding: 12px;
                flex-direction: column;
                gap: 8px;
                text-align: center;
            }

            .header-conta h1 {
                font-size: 16px;
            }

            .btn-voltar {
                width: 100%;
                justify-content: center;
            }
        }

        /* ===== MODAL DE PLANOS ===== */
        .modal-planos {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000 !important;
        }

        .modal-planos-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(4px);
            z-index: 1;
        }

        .modal-planos-container {
            position: relative;
            background: #fff;
            border-radius: 16px;
            width: 95%;
            max-width: 1400px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideInDown 0.3s ease-out;
            z-index: 2;
        }

        .modal-planos-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 30px;
            border-bottom: 2px solid #ecf0f1;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 16px 16px 0 0;
            color: white;
        }

        .modal-planos-header h2 {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
        }

        .modal-planos-close {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            padding: 8px 12px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .modal-planos-close:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.1);
        }

        /* ===== TOGGLE MÊS/ANO ===== */
        .modal-planos-toggle {
            padding: 30px;
            text-align: center;
            background: #f8f9fa;
            border-bottom: 1px solid #ecf0f1;
        }

        .toggle-group {
            display: inline-flex;
            background: #e8e8e8;
            border-radius: 50px;
            padding: 4px;
            gap: 4px;
        }

        .toggle-btn {
            padding: 12px 30px;
            border: none;
            background: transparent;
            cursor: pointer;
            border-radius: 50px;
            font-weight: 600;
            font-size: 14px;
            color: #666;
            transition: all 0.3s ease;
            position: relative;
        }

        .toggle-btn.active {
            background: #fff;
            color: #667eea;
            box-shadow: 0 2px 10px rgba(102, 126, 234, 0.2);
        }

        .economize {
            display: inline-block;
            background: #f39c12;
            color: white;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 10px;
            margin-left: 6px;
            font-weight: 700;
        }

        /* ===== GRID DE PLANOS ===== */
        .modal-planos-content {
            padding: 40px 30px;
        }

        .planos-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 25px;
        }

        @media (max-width: 1200px) {
            .planos-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 900px) {
            .planos-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 600px) {
            .planos-grid {
                grid-template-columns: 1fr;
            }
        }

        .plano-card {
            background: #fff;
            border: 2px solid #ecf0f1;
            border-radius: 12px;
            padding: 25px;
            text-align: center;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .plano-card::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--cor-plano, #95a5a6);
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.3s ease;
        }

        .plano-card:hover {
            border-color: var(--cor-plano, #95a5a6);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transform: translateY(-5px);
        }

        .plano-card:hover::before {
            transform: scaleX(1);
        }

        .plano-card.popular {
            border: 2px solid #f39c12;
            transform: scale(1.05);
            box-shadow: 0 15px 40px rgba(243, 156, 18, 0.15);
        }

        .plano-card.popular::after {
            content: "POPULAR";
            position: absolute;
            top: -8px;
            right: 15px;
            background: #f39c12;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
        }

        .plano-icone {
            font-size: 40px;
            margin-bottom: 15px;
            color: var(--cor-plano, #95a5a6);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .plano-icone i {
            font-size: 40px;
            color: var(--cor-plano, #95a5a6);
        }

        .plano-nome {
            font-size: 20px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .plano-preco {
            font-size: 32px;
            font-weight: 700;
            color: var(--cor-plano, #95a5a6);
            margin: 15px 0;
        }

        .plano-ciclo {
            font-size: 12px;
            color: #7f8c8d;
            margin-bottom: 20px;
            text-transform: uppercase;
        }

        .plano-features {
            text-align: left;
            margin: 20px 0;
            padding: 20px 0;
            border-top: 1px solid #ecf0f1;
            border-bottom: 1px solid #ecf0f1;
        }

        .plano-feature {
            display: flex;
            align-items: center;
            margin: 10px 0;
            font-size: 13px;
            color: #555;
        }

        .plano-feature i {
            color: var(--cor-plano, #95a5a6);
            margin-right: 10px;
            font-size: 14px;
        }

        .btn-contratar {
            width: 100%;
            padding: 12px;
            background: linear-gradient(
                135deg,
                var(--cor-plano, #95a5a6),
                var(--cor-plano-dark, #7f8c8d)
            );
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            font-size: 14px;
            margin-top: 15px;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-contratar:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }

        .btn-contratar:active {
            transform: translateY(0);
        }

        .btn-contratar:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .modal-planos-footer {
            text-align: center;
            padding: 20px 30px;
            background: #f8f9fa;
            border-top: 1px solid #ecf0f1;
            color: #7f8c8d;
            font-size: 13px;
            border-radius: 0 0 16px 16px;
        }

        .footer-text i {
            margin-right: 8px;
            color: #27ae60;
        }

        /* ===== MODAL DE PAGAMENTO ===== */
        .modal-pagamento {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10001 !important;
        }

        .modal-pagamento-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(4px);
            z-index: 1;
        }

        .modal-pagamento-container {
            position: relative;
            background: #fff;
            border-radius: 16px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideInUp 0.3s ease-out;
            z-index: 2;
        }

        .modal-pagamento-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 30px;
            border-bottom: 2px solid #ecf0f1;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 16px 16px 0 0;
            color: white;
        }

        .btn-voltar-modal {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            cursor: pointer;
            padding: 8px 12px;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-size: 14px;
            font-weight: 600;
        }

        .btn-voltar-modal:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .info-plano-selecionado {
            flex: 1;
            text-align: center;
        }

        .info-plano-selecionado h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 700;
        }

        .info-plano-selecionado p {
            margin: 5px 0 0 0;
            font-size: 14px;
            opacity: 0.9;
        }

        .modal-pagamento-close {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            padding: 8px 12px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .modal-pagamento-close:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        /* ===== TABS ===== */
        .pagamento-tabs {
            display: flex;
            padding: 0;
            border-bottom: 2px solid #ecf0f1;
            background: #f8f9fa;
        }

        .tab-btn {
            flex: 1;
            padding: 15px;
            background: transparent;
            border: none;
            color: #7f8c8d;
            cursor: pointer;
            font-weight: 600;
            font-size: 13px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border-bottom: 3px solid transparent;
        }

        .tab-btn:hover {
            color: #667eea;
            background: rgba(102, 126, 234, 0.05);
        }

        .tab-btn.active {
            color: #667eea;
            border-bottom-color: #667eea;
        }

        /* ===== CONTEÚDO DAS ABAS ===== */
        .pagamento-content {
            padding: 30px;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        /* ===== FORMULÁRIO DE PAGAMENTO ===== */
        .form-pagamento {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 8px;
            font-size: 13px;
        }

        .input-pagamento {
            padding: 12px;
            border: 2px solid #ecf0f1;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto;
        }

        .input-pagamento:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .input-pagamento::placeholder {
            color: #bdc3c7;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 10px 0;
        }

        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: #667eea;
        }

        .checkbox-group label {
            cursor: pointer;
            font-size: 13px;
            color: #555;
            margin: 0;
        }

        /* ===== CONTAINER PIX ===== */
        .pix-container {
            text-align: center;
        }

        .pix-info {
            background: #e8f5e9;
            border-left: 4px solid #27ae60;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            text-align: left;
        }

        .pix-info i {
            color: #27ae60;
            margin-right: 10px;
        }

        .pix-info p {
            margin: 0;
            font-size: 13px;
            color: #2c5f2d;
        }

        .pix-opcoes {
            display: flex;
            gap: 15px;
            flex-direction: column;
            margin: 25px 0;
        }

        .btn-pix-dinamico {
            padding: 15px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s ease;
        }

        .btn-pix-dinamico:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }

        .pix-nota {
            font-size: 12px;
            color: #7f8c8d;
            background: #f8f9fa;
            padding: 12px;
            border-radius: 8px;
            margin-top: 20px;
        }

        /* ===== CARTÕES SALVOS ===== */
        .cartoes-salvos-lista {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .cartao-salvo-item {
            padding: 15px;
            border: 2px solid #ecf0f1;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .cartao-salvo-item:hover {
            border-color: #667eea;
            background: rgba(102, 126, 234, 0.05);
        }

        .cartao-salvo-item input[type="radio"] {
            accent-color: #667eea;
            margin-right: 10px;
        }

        .cartao-info {
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .cartao-icone {
            font-size: 28px;
            color: #667eea;
        }

        .btn-adicionar-cartao {
            width: 100%;
            padding: 12px;
            background: #ecf0f1;
            border: 2px dashed #667eea;
            border-radius: 8px;
            color: #667eea;
            cursor: pointer;
            font-weight: 600;
            margin-top: 15px;
            transition: all 0.3s ease;
        }

        .btn-adicionar-cartao:hover {
            background: rgba(102, 126, 234, 0.1);
        }

        /* ===== BOTÃO PAGAR ===== */
        .btn-pagar {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 700;
            cursor: pointer;
            font-size: 15px;
            margin-top: 20px;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-pagar:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }

        .btn-pagar:active {
            transform: translateY(0);
        }

        .btn-pagar:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        /* ===== ANIMAÇÕES ===== */
        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        /* ===== RESPONSIVO MODAIS ===== */
        @media (max-width: 768px) {
            .modal-planos-container {
                width: 95%;
                max-height: 95vh;
            }

            .planos-grid {
                grid-template-columns: 1fr;
            }

            .plano-card.popular {
                transform: scale(1);
            }

            .modal-pagamento-container {
                width: 95%;
                max-width: 100%;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .pagamento-tabs {
                flex-wrap: wrap;
            }

            .tab-btn {
                font-size: 12px;
                padding: 12px;
            }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="css/celebracao-plano.css">
</head>
<body>
    <div class="container-conta">
        <div class="header-conta">
            <div>
                <h1>Minha Conta</h1>
                <p id="email-usuario-header">email@example.com</p>
                <p id="id-usuario-header" style="font-size: 11px; opacity: 0.8; margin-top: 4px;">ID: -</p>
            </div>
            <a href="home.php" class="btn-voltar">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>

        <div class="body-conta">
            <div class="conteudo-principal">
                <!-- Seção Plano -->
                <div class="secao-plano">
                <div class="secao-plano-titulo">Tipo de Plano</div>
                <div class="secao-plano-conteudo">
                    <div class="secao-plano-valor" id="valor-plano">
                        <span class="badge-plano badge-plano-gratuito">
                            <i class="fas fa-gift"></i>
                            <span>Gratuito</span>
                        </span>
                    </div>
                    <button class="btn-alterar-plano" id="btn-alterar-plano">
                        <i class="fas fa-exchange-alt"></i> Alterar
                    </button>
                </div>
            </div>

            <!-- Seção Dados Pessoais -->
            <div class="secao-campo">
                <div class="secao-campo-label">Dados Pessoais</div>
                
                <!-- Nome -->
                <div class="secao-campo-item">
                    <i class="fas fa-user secao-campo-icone"></i>
                    <div class="secao-campo-info">
                        <div class="secao-campo-rotulo">Nome</div>
                        <div class="secao-campo-valor" id="valor-nome">Carregando...</div>
                    </div>
                    <button class="secao-campo-botao" id="btn-editar-nome" title="Editar nome">
                        <i class="fas fa-edit"></i>
                    </button>
                </div>

                <!-- Email -->
                <div class="secao-campo-item">
                    <i class="fas fa-envelope secao-campo-icone"></i>
                    <div class="secao-campo-info">
                        <div class="secao-campo-rotulo">Email</div>
                        <div class="secao-campo-valor" id="valor-email">Carregando...</div>
                    </div>
                    <button class="secao-campo-botao" id="btn-editar-email" title="Editar email">
                        <i class="fas fa-edit"></i>
                    </button>
                </div>

                <!-- Telefone -->
                <div class="secao-campo-item">
                    <i class="fas fa-phone secao-campo-icone"></i>
                    <div class="secao-campo-info">
                        <div class="secao-campo-rotulo">Telefone</div>
                        <div class="secao-campo-valor" id="valor-telefone">Não informado</div>
                    </div>
                    <button class="secao-campo-botao" id="btn-editar-telefone" title="Editar telefone">
                        <i class="fas fa-edit"></i>
                    </button>
                </div>
            </div>

            <!-- Seção Segurança -->
            <div class="secao-senha">
                <div class="secao-senha-titulo">Alterar Senha</div>
                
                <div class="secao-senha-item">
                    <label for="input-senha-atual">Senha Atual</label>
                    <input type="password" id="input-senha-atual" class="input-senha" placeholder="Senha atual" />
                </div>

                <div class="secao-senha-item">
                    <label for="input-senha-nova">Nova Senha</label>
                    <input type="password" id="input-senha-nova" class="input-senha" placeholder="Mínimo 6 caracteres" />
                </div>

                <div class="secao-senha-item">
                    <label for="input-senha-confirma">Confirmar Senha</label>
                    <input type="password" id="input-senha-confirma" class="input-senha" placeholder="Confirme a nova senha" />
                </div>

                <button class="btn-atualizar-senha" id="btn-atualizar-senha">
                    <i class="fas fa-key"></i> Atualizar Senha
                </button>
            </div>
            </div>

            <!-- Seção Excluir -->
            <div class="botao-excluir-conta-container">
                <button class="btn-excluir-conta">
                    <i class="fas fa-trash"></i> Excluir Minha Conta
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Editar Campo -->
    <div id="modal-editar-campo" class="modal-editar-campo">
        <div class="modal-editar-campo-conteudo">
            <div class="modal-editar-campo-header">
                <h3>Editar Campo</h3>
                <button class="btn-fechar-editar-campo">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="modal-editar-campo-body">
                <div class="formulario-editar-campo">
                    <input type="text" id="input-editar-campo" class="campo-editar-input" placeholder="Digite o novo valor">
                    
                    <div class="botoes-editar-campo">
                        <button class="btn-cancelar-editar" id="btn-cancelar-editar-campo">
                            Cancelar
                        </button>
                        <button class="btn-salvar-editar" id="btn-salvar-editar-campo">
                            <i class="fas fa-save"></i> Salvar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Confirmação de Exclusão de Conta -->
    <div id="modal-confirmar-exclusao-conta">
        <div class="modal-confirmar-exclusao-conteudo">
            <div class="modal-confirmar-exclusao-header">
                <h3>
                    <i class="fas fa-exclamation-triangle"></i>
                    Excluir Conta
                </h3>
            </div>

            <div class="modal-confirmar-exclusao-body">
                <div class="modal-confirmar-exclusao-texto">
                    <strong>Atenção!</strong> Esta ação é <strong>irreversível</strong>. Todos os seus dados serão deletados permanentemente.
                </div>

                <div class="modal-confirmar-exclusao-aviso">
                    <strong>⚠️ Aviso:</strong> Após confirmar, você não poderá recuperar nenhuma informação.
                </div>

                <div class="modal-confirmar-exclusao-texto">
                    Para confirmar, digite <strong>SIM</strong> no campo abaixo:
                </div>

                <input type="text" id="input-confirmacao-exclusao" class="modal-confirmar-exclusao-input" placeholder="Digite SIM para confirmar" />

                <div class="botoes-confirmar-exclusao">
                    <button class="btn-cancelar-exclusao" id="btn-cancelar-confirmacao-exclusao">
                        Cancelar
                    </button>
                    <button class="btn-confirmar-exclusao" id="btn-confirmar-exclusao-conta" disabled>
                        <i class="fas fa-trash"></i> Confirmar Exclusão
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Planos -->
    <div id="modal-planos" class="modal-planos" style="display: none">
        <div class="modal-planos-overlay"></div>

        <div class="modal-planos-container">
            <!-- Cabeçalho -->
            <div class="modal-planos-header">
                <h2>Escolha seu Plano</h2>
                <button class="modal-planos-close" onclick="fecharModalPlanos()">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Toggle MÊS/ANO -->
            <div class="modal-planos-toggle">
                <div class="toggle-group">
                    <button class="toggle-btn active" data-periodo="mes" onclick="alternarPeriodo('mes')">
                        MÊS
                    </button>
                    <button class="toggle-btn" data-periodo="ano" onclick="alternarPeriodo('ano')">
                        ANO
                        <span class="economize">ECONOMIZE</span>
                    </button>
                </div>
            </div>

            <!-- Container de Planos -->
            <div class="modal-planos-content">
                <div class="planos-grid" id="planosGrid">
                    <!-- Planos serão carregados dinamicamente -->
                </div>
            </div>

            <!-- Rodapé -->
            <div class="modal-planos-footer">
                <p class="footer-text">
                    <i class="fas fa-lock"></i>
                    Pagamento seguro com Mercado Pago
                </p>
            </div>
        </div>
    </div>

    <!-- Modal de Pagamento -->
    <div id="modal-pagamento" class="modal-pagamento" style="display: none">
        <div class="modal-pagamento-overlay"></div>

        <div class="modal-pagamento-container">
            <!-- Cabeçalho com Info do Plano -->
            <div class="modal-pagamento-header">
                <button class="btn-voltar" onclick="voltarParaPlanos()">
                    <i class="fas fa-arrow-left"></i> Voltar
                </button>
                <div class="info-plano-selecionado">
                    <h3 id="nomePlanoSelecionado"></h3>
                    <p id="valorPlanoSelecionado"></p>
                </div>
                <button class="modal-pagamento-close" onclick="fecharModalPagamento()">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Tabs de Pagamento -->
            <div class="pagamento-tabs">
                <button class="tab-btn active" data-tab="cartao" onclick="mudarAba('cartao')">
                    <i class="fas fa-credit-card"></i> Cartão
                </button>
                <button class="tab-btn" data-tab="pix" onclick="mudarAba('pix')">
                    <i class="fas fa-qrcode"></i> PIX
                </button>
                <button class="tab-btn" data-tab="salvo" onclick="mudarAba('salvo')">
                    <i class="fas fa-save"></i> Cartões Salvos
                </button>
            </div>

            <!-- Conteúdo das Abas -->
            <div class="pagamento-content">
                <!-- ABA: CARTÃO -->
                <div class="tab-content active" id="tab-cartao">
                    <form id="formCartao" class="form-pagamento">
                        <div class="form-group">
                            <label>Titular do Cartão</label>
                            <input type="text" id="titular" class="input-pagamento" placeholder="Nome conforme no cartão" required />
                        </div>

                        <div class="form-group">
                            <label>Número do Cartão</label>
                            <input type="text" id="numeroCartao" class="input-pagamento" placeholder="0000 0000 0000 0000" maxlength="19" required />
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Validade (MM/AA)</label>
                                <input type="text" id="dataValidade" class="input-pagamento" placeholder="MM/AA" maxlength="5" required />
                            </div>

                            <div class="form-group">
                                <label>CVV</label>
                                <input type="text" id="cvv" class="input-pagamento" placeholder="***" maxlength="4" required />
                            </div>
                        </div>

                        <div class="checkbox-group">
                            <input type="checkbox" id="salvarCartao" />
                            <label for="salvarCartao">Salvar este cartão para futuras compras</label>
                        </div>

                        <button type="button" class="btn-pagar" onclick="processarPagamentoCartao()">
                            <i class="fas fa-lock"></i> Confirmar Pagamento
                        </button>
                    </form>
                </div>

                <!-- ABA: PIX -->
                <div class="tab-content" id="tab-pix">
                    <div class="pix-container">
                        <div class="pix-info">
                            <i class="fas fa-info-circle"></i>
                            <p>Ao clicar em "Pagar com PIX", você será redirecionado para confirmar o pagamento no Mercado Pago.</p>
                        </div>

                        <div class="pix-opcoes">
                            <button type="button" class="btn-pix-dinamico" onclick="processarPagamentoPIX('dinamico')">
                                <i class="fas fa-qrcode"></i>
                                <span>PIX QR Code</span>
                            </button>

                            <button type="button" class="btn-pix-dinamico" onclick="processarPagamentoPIX('copia')">
                                <i class="fas fa-copy"></i>
                                <span>Copiar e Colar</span>
                            </button>
                        </div>

                        <p class="pix-nota">
                            <strong>Nota:</strong> O PIX é instantâneo. Após confirmar no Mercado Pago, seu plano será ativado imediatamente.
                        </p>
                    </div>
                </div>

                <!-- ABA: CARTÕES SALVOS -->
                <div class="tab-content" id="tab-salvo">
                    <div id="cartoesSalvos" class="cartoes-salvos-lista">
                        <!-- Cartões salvos serão carregados aqui -->
                    </div>

                    <button type="button" class="btn-adicionar-cartao" onclick="mudarAba('cartao')">
                        <i class="fas fa-plus"></i> Usar novo cartão
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Script gerenciador de conta -->
    <script src="js/gerenciador-conta.js" defer></script>
    <!-- Script de celebração de plano -->
    <script src="js/celebracao-plano.js" defer></script>
</body>
</html>
