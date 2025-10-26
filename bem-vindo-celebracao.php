<?php
/**
 * 🎉 BEM-VINDO AO SISTEMA GLOBAL DE CELEBRAÇÃO 🎉
 * 
 * Este arquivo é apenas uma introdução visual.
 * Você será redirecionado para as ferramentas em breve.
 */

session_start();

// Se não estiver logado, redireciona para login
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$usuario_nome = $_SESSION['usuario_nome'] ?? 'Usuário';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🎉 Sistema de Celebração Implementado!</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .welcome-container {
            background: white;
            border-radius: 20px;
            padding: 60px 40px;
            max-width: 600px;
            width: 100%;
            box-shadow: 0 30px 90px rgba(0, 0, 0, 0.3);
            text-align: center;
            animation: slideInUp 0.6s ease-out;
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
        
        .emoji-grande {
            font-size: 80px;
            margin-bottom: 20px;
            animation: bounce 2s infinite;
        }
        
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }
        
        h1 {
            color: #667eea;
            font-size: 32px;
            margin-bottom: 15px;
            font-weight: 700;
        }
        
        .subtitulo {
            color: #666;
            font-size: 16px;
            margin-bottom: 40px;
            line-height: 1.6;
        }
        
        .features {
            background: #f8f9ff;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            text-align: left;
        }
        
        .feature-item {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
            color: #555;
        }
        
        .feature-item:last-child {
            margin-bottom: 0;
        }
        
        .feature-icon {
            color: #667eea;
            font-size: 20px;
            min-width: 25px;
        }
        
        .botoes-acao {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .btn {
            padding: 15px 20px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 50px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: #f0f0f0;
            color: #667eea;
            border: 2px solid #667eea;
        }
        
        .btn-secondary:hover {
            background: #667eea;
            color: white;
        }
        
        .btn-full {
            grid-column: 1 / -1;
        }
        
        .info-box {
            background: #e8f5e9;
            border-left: 4px solid #4caf50;
            padding: 15px;
            border-radius: 8px;
            color: #2e7d32;
            font-size: 13px;
            margin-bottom: 20px;
        }
        
        .rodape {
            color: #999;
            font-size: 12px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .confete {
            position: fixed;
            pointer-events: none;
            font-size: 20px;
            animation: queda 3s ease-in forwards;
        }
        
        @keyframes queda {
            to {
                transform: translateY(100vh) rotate(360deg);
                opacity: 0;
            }
        }
        
        .saudacao {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-size: 14px;
            display: inline-block;
        }
        
        @media (max-width: 600px) {
            .welcome-container {
                padding: 40px 25px;
            }
            
            h1 {
                font-size: 24px;
            }
            
            .emoji-grande {
                font-size: 60px;
            }
            
            .botoes-acao {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="welcome-container">
        <div class="emoji-grande">🎉</div>
        
        <div class="saudacao">
            👋 Olá, <?php echo htmlspecialchars($usuario_nome); ?>!
        </div>
        
        <h1>Sistema de Celebração Implementado!</h1>
        
        <p class="subtitulo">
            O sistema global de celebração de plano agora está ativo em todo o site.
            Quando você muda de plano, uma linda animação aparece para comemorar! 🎊
        </p>
        
        <div class="features">
            <div class="feature-item">
                <div class="feature-icon"><i class="fas fa-check"></i></div>
                <div><strong>Funciona em todas as páginas</strong> - home, dashboard, admin</div>
            </div>
            <div class="feature-item">
                <div class="feature-icon"><i class="fas fa-check"></i></div>
                <div><strong>Detecção automática</strong> - verifica a cada 3 segundos</div>
            </div>
            <div class="feature-item">
                <div class="feature-icon"><i class="fas fa-check"></i></div>
                <div><strong>Sincroniza entre abas</strong> - múltiplas abas compartilham celebração</div>
            </div>
            <div class="feature-item">
                <div class="feature-icon"><i class="fas fa-check"></i></div>
                <div><strong>Animação com confete</strong> - visual impressionante</div>
            </div>
            <div class="feature-item">
                <div class="feature-icon"><i class="fas fa-check"></i></div>
                <div><strong>Sem celebração repetida</strong> - apenas 1x por sessão</div>
            </div>
        </div>
        
        <div class="info-box">
            <strong>💡 Dica:</strong> O sistema usa localStorage para rastrear o plano anterior.
            Se você mudar de plano e acessar qualquer página, a celebração aparecerá automaticamente!
        </div>
        
        <div class="botoes-acao">
            <a href="verificacao-celebracao.php" class="btn btn-secondary" target="_blank">
                <i class="fas fa-stethoscope"></i> Verificar Sistema
            </a>
            <a href="teste-celebracao-global.php" class="btn btn-secondary" target="_blank">
                <i class="fas fa-flask"></i> Testar
            </a>
            <a href="home.php" class="btn btn-primary btn-full">
                <i class="fas fa-home"></i> Ir para Home
            </a>
        </div>
        
        <div class="rodape">
            <p>Sistema de Celebração Global v1.0 • 2024</p>
            <p style="margin-top: 8px;">
                <a href="CELEBRACAO-GLOBAL-README.md" style="color: #667eea; text-decoration: none;">
                    📖 Ver Documentação
                </a>
            </p>
        </div>
    </div>
    
    <script>
        // Criar confete ao carregar
        function criarConfete() {
            const confetes = ['🎉', '🎊', '✨', '🎈', '⭐', '💫', '🌟'];
            
            for (let i = 0; i < 20; i++) {
                const confete = document.createElement('div');
                confete.className = 'confete';
                confete.textContent = confetes[Math.floor(Math.random() * confetes.length)];
                confete.style.left = Math.random() * 100 + '%';
                confete.style.top = '-20px';
                confete.style.animationDelay = Math.random() * 0.5 + 's';
                document.body.appendChild(confete);
                
                setTimeout(() => confete.remove(), 3500);
            }
        }
        
        // Criar confete ao carregar
        window.addEventListener('load', criarConfete);
        
        // Log no console
        console.log('%c🎉 SISTEMA DE CELEBRAÇÃO GLOBAL INICIALIZADO', 'color: #667eea; font-size: 16px; font-weight: bold;');
        console.log('%cVocê pode testar clicando em "Testar" acima ou virando para outro plano', 'color: #666; font-size: 12px;');
        console.log('%cAbra o DevTools (F12) → Storage → LocalStorage para ver os dados', 'color: #666; font-size: 12px;');
    </script>
</body>
</html>
