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
    <title>Alterar Plano</title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container-planos {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header-planos {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }

        .header-planos > div {
            color: white;
        }

        .header-planos h1 {
            font-size: 32px;
            margin-bottom: 5px;
            font-weight: 700;
        }

        .header-planos p {
            font-size: 14px;
            opacity: 0.95;
            font-weight: 500;
        }

        .btn-voltar-planos {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }

        .btn-voltar-planos:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }

        .conteudo-planos {
            background: white;
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        /* Estilos do modal inline */
        .modal-planos {
            position: static !important;
            display: block !important;
            z-index: auto !important;
            background: transparent !important;
            width: 100% !important;
            height: auto !important;
            align-items: auto !important;
            justify-content: auto !important;
        }

        .modal-planos-overlay {
            display: none !important;
        }

        .modal-planos-container {
            position: static !important;
            background: transparent !important;
            box-shadow: none !important;
            width: 100% !important;
            max-width: 100% !important;
            border-radius: 0 !important;
            max-height: none !important;
            overflow: visible !important;
            animation: none !important;
        }

        .modal-planos-header {
            background: transparent !important;
            border-bottom: none !important;
            padding: 0 0 30px 0 !important;
            margin-bottom: 30px !important;
            display: none !important;
        }

        .modal-planos-header h2 {
            font-size: 24px !important;
            margin-bottom: 10px !important;
        }

        .modal-planos-close {
            display: none !important;
        }

        .modal-planos-content {
            padding: 0 !important;
        }

        .modal-planos-footer {
            background: transparent !important;
            border-top: 1px solid #ecf0f1 !important;
            padding: 30px 0 0 0 !important;
            margin-top: 30px !important;
            text-align: center !important;
            color: #7f8c8d !important;
            font-size: 13px !important;
            border-radius: 0 !important;
        }

        .footer-text i {
            margin-right: 8px !important;
            color: #27ae60 !important;
        }

        /* Grid responsivo para planos */
        .planos-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 25px;
            margin: 0 !important;
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

            .header-planos {
                flex-direction: column;
                text-align: center;
            }

            .btn-voltar-planos {
                width: 100%;
                justify-content: center;
            }

            .conteudo-planos {
                padding: 20px;
            }
        }

        /* Estilos de toggle */
        .modal-planos-toggle {
            padding: 30px 0 !important;
            text-align: center !important;
            background: transparent !important;
            border-bottom: 1px solid #ecf0f1 !important;
            margin-bottom: 30px !important;
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
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container-planos">
        <div class="header-planos">
            <div>
                <h1>Alterar Plano</h1>
                <p>Escolha o melhor plano para você</p>
            </div>
            <a href="conta.php" class="btn-voltar-planos">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>

        <div class="conteudo-planos">
            <!-- Modal de Planos (será exibido inline) -->
            <?php include 'modal-planos-pagamento.html'; ?>
        </div>
    </div>

    <!-- Scripts necessários -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"></script>
    <script src="js/plano-manager.js" defer></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Mostrar o modal de planos e abrir automaticamente
            const modal = document.getElementById('modal-planos');
            if (modal) {
                // Remove estilo display: none se estiver
                modal.style.display = '';
                
                // Carregar planos se a função existir
                if (typeof PlanoManager !== 'undefined' && PlanoManager.carregarPlanos) {
                    PlanoManager.carregarPlanos();
                }
            }
        });
    </script>
</body>
</html>
