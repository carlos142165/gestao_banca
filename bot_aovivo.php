<?php
session_start();

// 🔐 Verificação de sessão
if (!isset($_SESSION['usuario_id']) || empty($_SESSION['usuario_id'])) {
  // Não redireciona mais, apenas marca que não está autenticado
  $usuario_autenticado = false;
} else {
  $usuario_autenticado = true;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bot ao Vivo</title>
    <!-- ✅ Carregar CSS ANTES dos estilos inline -->
    <link rel="stylesheet" href="css/menu-topo.css">
    <link rel="stylesheet" href="css/telegram-mensagens.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .telegram-container {
            display: flex;
            flex-direction: column;
            height: 100%;
            gap: 0;
        }
        
        .telegram-messages-wrapper {
            flex: 1;
        }
    </style>
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
            padding: 20px 20px 20px 20px;
            display: flex;
            justify-content: center;
            align-items: stretch;
        }

        .container {
            width: 100%;
            max-width: 1320px;
            display: flex;
            justify-content: center;
            align-items: stretch;
            gap: 30px;
            height: 100%;
        }

        .bloco {
            width: 420px;
            height: 100%;
            background-color: #f5f5f5;
            border: 2px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow-y: auto;
            flex-shrink: 0;
        }

        /* Classes específicas dos blocos do seu código */
        .bloco-1 {
            /* Estilos específicos do bloco 1 se necessário */
        }

        .bloco-2 {
            /* Estilos específicos do bloco 2 se necessário */
        }

        .bloco-3 {
            /* Mantido como bloco genérico */
        }

        /* Suas classes existentes do documento */
        .container-resumos {
            /* Estilos para container de resumos */
        }

        .widget-meta-container {
            /* Estilos para widget meta */
        }

        .widget-meta-row {
            /* Estilos para row do widget */
        }

        .widget-meta-item {
            /* Estilos para item do widget */
        }

        .data-header-integrada {
            /* Estilos para header de data */
        }

        .data-texto-compacto {
            /* Estilos para texto compacto */
        }

        .data-principal-integrada {
            /* Estilos para data principal */
        }

        .periodo-selecao-container {
            /* Estilos para container de seleção de período */
        }

        .periodo-opcao {
            /* Estilos para opção de período */
        }

        .periodo-label {
            /* Estilos para label de período */
        }

        .periodo-radio {
            /* Estilos para radio de período */
        }

        .periodo-texto {
            /* Estilos para texto de período */
        }

        .espaco-equilibrio {
            /* Estilos para espaço de equilíbrio */
        }

        .data-separador-mini {
            /* Estilos para separador mini */
        }

        .status-periodo-mini {
            /* Estilos para status período mini */
        }

        .widget-conteudo-principal {
            /* Estilos para conteúdo principal do widget */
        }

        .conteudo-left {
            /* Estilos para conteúdo esquerdo */
        }

        .widget-meta-valor {
            /* Estilos para valor da meta */
        }

        .meta-valor-container {
            /* Estilos para container do valor da meta */
        }

        .valor-texto {
            /* Estilos para texto do valor */
        }

        .valor-ultrapassou {
            /* Estilos para valor que ultrapassou */
        }

        .texto-ultrapassou {
            /* Estilos para texto ultrapassou */
        }

        .widget-meta-rotulo {
            /* Estilos para rótulo da meta */
        }

        .widget-barra-container {
            /* Estilos para container da barra */
        }

        .widget-barra-progresso {
            /* Estilos para barra de progresso */
        }

        .porcentagem-barra {
            /* Estilos para porcentagem da barra */
        }

        .widget-info-progresso {
            /* Estilos para info de progresso */
        }

        .saldo-positivo {
            /* Estilos para saldo positivo */
        }

        .saldo-info-rotulo {
            /* Estilos para rótulo do saldo */
        }

        .saldo-info-valor {
            /* Estilos para valor do saldo */
        }

        .campo_mentores {
            /* Estilos para campo de mentores */
        }

        .barra-superior {
            /* Estilos para barra superior */
        }

        .btn-add-usuario {
            /* Estilos para botão adicionar usuário */
        }

        .area-central {
            /* Estilos para área central */
        }

        .pontuacao {
            /* Estilos para pontuação */
        }

        .placar-green {
            /* Estilos para placar green */
        }

        .separador {
            /* Estilos para separador */
        }

        .placar-red {
            /* Estilos para placar red */
        }

        .area-direita {
            /* Estilos para área direita */
        }

        .valor-dinamico {
            /* Estilos para valor dinâmico */
        }

        .valor-diaria {
            /* Estilos para valor diário */
        }

        .valor-unidade {
            /* Estilos para valor unidade */
        }

        .rotulo-und {
            /* Estilos para rótulo unidade */
        }

        .mentor-wrapper {
            /* Estilos para wrapper de mentores */
        }

        .mentor-item {
            /* Estilos para item de mentor */
        }

        .mentor-rank-externo {
            /* Estilos para rank externo do mentor */
        }

        .mentor-card {
            /* Estilos para card do mentor */
        }

        .card-neutro {
            /* Estilos para card neutro */
        }

        .card-positivo {
            /* Estilos para card positivo */
        }

        .card-negativo {
            /* Estilos para card negativo */
        }

        .mentor-header {
            /* Estilos para header do mentor */
        }

        .mentor-img {
            /* Estilos para imagem do mentor */
        }

        .mentor-nome {
            /* Estilos para nome do mentor */
        }

        .mentor-right {
            /* Estilos para lado direito do mentor */
        }

        .mentor-values-inline {
            /* Estilos para valores inline do mentor */
        }

        .value-box-green {
            /* Estilos para box verde */
        }

        .value-box-red {
            /* Estilos para box vermelho */
        }

        .value-box-saldo {
            /* Estilos para box saldo */
        }

        .green {
            /* Estilos para green */
        }

        .red {
            /* Estilos para red */
        }

        .saldo {
            /* Estilos para saldo */
        }

        .mentor-menu-externo {
            /* Estilos para menu externo do mentor */
        }

        .menu-toggle {
            /* Estilos para toggle do menu */
        }

        .menu-opcoes {
            /* Estilos para opções do menu */
        }

        .resumo-mes {
            /* Estilos para resumo do mês */
        }

        .bloco-meta-simples {
            /* Estilos para bloco meta simples */
        }

        .fixo-topo {
            /* Estilos para fixo no topo */
        }

        .campo-armazena-data-placar {
            /* Estilos para campo que armazena data e placar */
        }

        .titulo-bloco {
            /* Estilos para título do bloco */
        }

        .area-central-2 {
            /* Estilos para área central 2 */
        }

        .pontuacao-2 {
            /* Estilos para pontuação 2 */
        }

        .placar-green-2 {
            /* Estilos para placar green 2 */
        }

        .separador-2 {
            /* Estilos para separador 2 */
        }

        .placar-red-2 {
            /* Estilos para placar red 2 */
        }

        .widget-conteudo-principal-2 {
            /* Estilos para conteúdo principal 2 */
        }

        .conteudo-left-2 {
            /* Estilos para conteúdo esquerdo 2 */
        }

        .widget-meta-valor-2 {
            /* Estilos para valor meta 2 */
        }

        .fa-solid-2 {
            /* Estilos para ícone solid 2 */
        }

        .fa-coins-2 {
            /* Estilos para ícone coins 2 */
        }

        .meta-valor-container-2 {
            /* Estilos para container valor meta 2 */
        }

        .valor-texto-2 {
            /* Estilos para texto valor 2 */
        }

        .valor-ultrapassou-2 {
            /* Estilos para valor ultrapassou 2 */
        }

        .fa-trophy-2 {
            /* Estilos para ícone troféu 2 */
        }

        .texto-ultrapassou-2 {
            /* Estilos para texto ultrapassou 2 */
        }

        .widget-meta-rotulo-2 {
            /* Estilos para rótulo meta 2 */
        }

        .widget-barra-container-2 {
            /* Estilos para container barra 2 */
        }

        .widget-barra-progresso-2 {
            /* Estilos para barra progresso 2 */
        }

        .porcentagem-barra-2 {
            /* Estilos para porcentagem barra 2 */
        }

        .widget-info-progresso-2 {
            /* Estilos para info progresso 2 */
        }

        .saldo-positivo-2 {
            /* Estilos para saldo positivo 2 */
        }

        .fa-chart-line-2 {
            /* Estilos para ícone chart line 2 */
        }

        .saldo-info-rotulo-2 {
            /* Estilos para rótulo saldo 2 */
        }

        .saldo-info-valor-2 {
            /* Estilos para valor saldo 2 */
        }

        .lista-dias {
            /* Estilos para lista de dias */
        }

        .gd-linha-dia {
            /* Estilos para linha do dia */
        }

        .valor-positivo {
            /* Estilos para valor positivo */
        }

        .valor-negativo {
            /* Estilos para valor negativo */
        }

        .valor-zero {
            /* Estilos para valor zero */
        }

        .gd-dia-hoje {
            /* Estilos para dia hoje */
        }

        .gd-borda-verde {
            /* Estilos para borda verde */
        }

        .gd-borda-vermelha {
            /* Estilos para borda vermelha */
        }

        .dia-normal {
            /* Estilos para dia normal */
        }

        .gd-dia-destaque {
            /* Estilos para dia destaque */
        }

        .gd-dia-destaque-negativo {
            /* Estilos para dia destaque negativo */
        }

        .gd-dia-sem-valor {
            /* Estilos para dia sem valor */
        }

        .dia-futuro {
            /* Estilos para dia futuro */
        }

        .trofeu-icone {
            /* Estilos para ícone troféu */
        }

        .data {
            /* Estilos para data */
        }

        .texto-cinza {
            /* Estilos para texto cinza */
        }

        .placar-dia {
            /* Estilos para placar do dia */
        }

        .placar {
            /* Estilos para placar */
        }

        .verde-bold {
            /* Estilos para verde bold */
        }

        .vermelho-bold {
            /* Estilos para vermelho bold */
        }

        .valor {
            /* Estilos para valor */
        }

        .icone {
            /* Estilos para ícone */
        }

        .bloco h3 {
            color: #113647;
            margin-bottom: 15px;
            text-align: center;
        }

        .bloco p {
            color: #333;
            line-height: 1.6;
            text-align: justify;
            margin-bottom: 15px;
        }

        /* Responsividade para diferentes resoluções */
        @media screen and (max-width: 1400px) {
            .container {
                max-width: 100%;
                justify-content: center;
            }
        }

        @media screen and (max-width: 1320px) {
            .bloco {
                width: calc(33.333% - 20px);
                min-width: 280px;
                height: 100%;
            }
        }

        @media screen and (max-width: 1024px) {
            .main-content {
                flex-direction: column;
                overflow-y: auto;
                align-items: center;
            }
            
            .container {
                flex-direction: column;
                align-items: center;
                height: auto;
                min-height: 100%;
            }
            
            .bloco {
                width: 100%;
                max-width: 420px;
                height: calc(33.33vh - 40px);
                min-height: 200px;
                margin-bottom: 20px;
            }
            
            .bloco:last-child {
                margin-bottom: 0;
            }
        }

        @media screen and (max-width: 768px) {
            .main-content {
                padding: 15px;
            }
            
            .bloco {
                padding: 15px;
                height: calc(33.33vh - 35px);
                min-height: 180px;
            }
        }

        @media screen and (max-width: 480px) {
            .header, .footer {
                height: 70px;
            }
            
            .main-content {
                top: 70px;
                bottom: 70px;
                padding: 10px;
            }
            
            .bloco {
                padding: 12px;
                height: calc(33.33vh - 30px);
                min-height: 150px;
            }
        }

        /* Suporte para diferentes níveis de zoom */
        @media screen and (min-resolution: 120dpi) {
            .container {
                padding: 0 20px;
            }
        }

        @media screen and (min-resolution: 144dpi) {
            .container {
                padding: 0 25px;
            }
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
    </style>
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

                        <?php if (isset($_SESSION['usuario_id']) && (intval($_SESSION['usuario_id']) === 23 || $_SESSION['usuario_id'] == 23)): ?>
                            <a href="administrativa.php" style="background-color: #e7defdff !important;">
                                <i class="fas fa-chart-line menu-icon"></i><span>Área Administrativa</span>
                            </a>
                        <?php endif; ?>

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
                                        <span class="valor-label" id="rotulo-lucro-dinamico">CARREGANDO..:</span>
                                        <span class="valor-bold-menu" id="lucro_valor_entrada">R$ 0,00</span>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- Mostrar quando NÃO LOGADO - Botões de Login/Registre-se -->
                            <div class="auth-buttons">
                                <a href="login.php" class="btn-login">
                                    <i class="fas fa-sign-in-alt"></i> Login
                                </a>
                                <a href="login-user.php" class="btn-register">
                                    <i class="fas fa-user-plus"></i> Registre-se
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </header>
    
    <main class="main-content">
        <div class="container">
           
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
            
            <!-- ==================================================================================================================== -->
            <!-- ========================== BLOCO 1 - BLOCO 1 - BLOCO 1 - BLOCO 1 - BLOCO 1 ======================== -->
            <!-- ==================================================================================================================== -->
            <!-- BLOCO 1 -->
            <div class="bloco bloco-1">
                <div class="telegram-container">
                    <div class="telegram-header">
                        <i class="fab fa-telegram"></i>
                        <h3>Mensagens Telegram</h3>
                        <div class="telegram-status">
                            <span class="telegram-status-dot"></span>
                            <span>Ao vivo</span>
                        </div>
                    </div>
                    <div class="telegram-messages-wrapper">
                        <div class="telegram-loading">
                            <div class="telegram-loading-spinner"></div>
                            <p>Carregando mensagens...</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- ==================================================================================================================== -->
            <!-- ========================== BLOCO 1 - BLOCO 1 - BLOCO 1 - BLOCO 1 - BLOCO 1 ======================== -->
            <!-- ==================================================================================================================== -->
             <!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
            
            <!-- ==================================================================================================================== -->
            <!-- ========================== BLOCO 2 - BLOCO 2 - BLOCO 2 - BLOCO 2 - BLOCO 2 ======================== -->
            <!-- ==================================================================================================================== -->
            
            <!-- BLOCO 2 -->
            <div class="bloco bloco-2">

                
            </div>
            
            <!-- ==================================================================================================================== -->
            <!-- ========================== BLOCO 2 - BLOCO 2 - BLOCO 2 - BLOCO 2 - BLOCO 2 ======================== -->
            <!-- ==================================================================================================================== -->
            <!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
            <!-- ==================================================================================================================== -->
            <!-- ========================== BLOCO 3 - BLOCO 3 - BLOCO 3 - BLOCO 3 - BLOCO 3 ======================== -->
            <!-- ==================================================================================================================== -->
            
            <!-- BLOCO 3 -->
            <div class="bloco bloco-3">

            </div>
            
            <!-- ==================================================================================================================== -->
            <!-- ========================== BLOCO 3 - BLOCO 3 - BLOCO 3 - BLOCO 3 - BLOCO 3 ======================== -->
            <!-- ==================================================================================================================== -->
             <!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
<!-- -->
            
        </div>
    </main>
    
    <footer class="footer"></footer>

    <!-- ✅ VERIFICAÇÃO DE AUTENTICAÇÃO PARA "GERENCIAR BANCA" -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const botaoGerencia = document.getElementById('abrirGerenciaBanca');
            const usuarioAutenticado = <?php echo json_encode($usuario_autenticado); ?>;

            if (botaoGerencia && !usuarioAutenticado) {
                botaoGerencia.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    // Se não autenticado, mostra o modal de login
                    const modal = document.getElementById('modalLoginBlockade');
                    if (modal) {
                        modal.style.display = 'flex';
                    }
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
    </script>

    <!-- MODAL DE LOGIN PARA USUÁRIOS NÃO AUTENTICADOS -->
    <?php if (false): ?>
    <!-- Modal desativado - página pública -->
    <?php endif; ?>

    <!-- ✅ VERIFICAÇÃO DE AUTENTICAÇÃO PARA "GERENCIAR BANCA" -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const botaoGerencia = document.getElementById('abrirGerenciaBanca');
            const usuarioAutenticado = <?php echo json_encode($usuario_autenticado); ?>;

            if (botaoGerencia && !usuarioAutenticado) {
                botaoGerencia.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    // Se não autenticado, mostra o modal de login
                    const modal = document.getElementById('modalLoginBlockade');
                    if (modal) {
                        modal.style.display = 'flex';
                    }
                });
            }
        });
    </script>

    <!-- ✅ Script para atualizar rótulo dinâmico do lucro - FORA DO ENDIF PARA FUNCIONAR COM USUÁRIOS AUTENTICADOS -->
    <script src="js/rotulo-lucro-dinamico.js" defer></script>

    <!-- ✅ SCRIPT PARA CARREGAR MENSAGENS DO TELEGRAM -->
    <script src="js/telegram-mensagens.js" defer></script>

    <!-- ✅ SCRIPT PARA CARREGAR DADOS DINÂMICOS - CLONE DE HOME.PHP -->
    <script>
    // ✅ FUNÇÃO PARA CARREGAR DADOS DINÂMICOS
    function carregarDadosBancaELucro() {
      // Só carregar se o usuário estiver autenticado
      const usuarioAutenticado = <?php echo json_encode(isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id'])); ?>;
      
      if (!usuarioAutenticado) return;

      fetch('dados_banca.php')
        .then(response => response.json())
        .then(data => {
          console.log('✅ Dados recebidos:', data);
          
          if (data.success) {
            // ========== ATUALIZAR BANCA ==========
            const valorBancaLabel = document.getElementById('valorTotalBancaLabel');
            if (valorBancaLabel) {
              valorBancaLabel.textContent = data.banca_formatada || 'R$ 0,00';
              console.log('💰 Banca atualizada:', data.banca_formatada);
            }

            // ========== ATUALIZAR LUCRO E ÍCONE ==========
            const lucroValorEntrada = document.getElementById('lucro_valor_entrada');
            const iconeLucro = document.getElementById('icone-lucro-dinamico');
            
            if (lucroValorEntrada && iconeLucro) {
              // Obter valor formatado e bruto
              const lucroFormatado = data.lucro_total_formatado || 'R$ 0,00';
              const lucroBruto = parseFloat(data.lucro_total_historico || 0);
              
              // Atualizar texto
              lucroValorEntrada.textContent = lucroFormatado;
              console.log('� Lucro atualizado:', lucroFormatado);
              console.log('📊 Lucro bruto para cálculo:', lucroBruto);
              
              // ========== REMOVER CLASSES ANTIGAS ==========
              lucroValorEntrada.classList.remove('saldo-positivo', 'saldo-negativo', 'saldo-neutro');
              iconeLucro.classList.remove('fa-arrow-trend-up', 'fa-arrow-trend-down', 'fa-minus');
              
              // ========== APLICAR ESTILO BASEADO NO VALOR ==========
              if (lucroBruto > 0) {
                // POSITIVO
                lucroValorEntrada.classList.add('saldo-positivo');
                iconeLucro.classList.add('fa-arrow-trend-up');
                iconeLucro.style.color = '#9fe870';
                console.log('✅ Lucro POSITIVO - Verde (#9fe870)');
                
                // Animação sutil
                iconeLucro.style.transform = 'translateY(-2px)';
                setTimeout(() => { iconeLucro.style.transform = 'translateY(0)'; }, 300);
                
              } else if (lucroBruto < 0) {
                // NEGATIVO
                lucroValorEntrada.classList.add('saldo-negativo');
                iconeLucro.classList.add('fa-arrow-trend-down');
                iconeLucro.style.color = '#e57373';
                console.log('✅ Lucro NEGATIVO - Vermelho (#e57373)');
                
                // Animação sutil
                iconeLucro.style.transform = 'translateY(2px)';
                setTimeout(() => { iconeLucro.style.transform = 'translateY(0)'; }, 300);
                
              } else {
                // NEUTRO (ZERO)
                lucroValorEntrada.classList.add('saldo-neutro');
                iconeLucro.classList.add('fa-minus');
                iconeLucro.style.color = '#cfd8dc';
                console.log('✅ Lucro NEUTRO - Cinza (#cfd8dc)');
                iconeLucro.style.transform = 'translateY(0)';
              }
              
              // ========== ATUALIZAR RÓTULO DINÂMICO ==========
              // Chamar o sistema de rótulo dinâmico se disponível
              if (typeof RotuloLucroDinamico !== 'undefined' && RotuloLucroDinamico.atualizarRotuloDinamico) {
                RotuloLucroDinamico.atualizarRotuloDinamico(lucroBruto);
                console.log('🏷️ Rótulo dinâmico atualizado');
              }
            } else {
              console.error('❌ Elementos não encontrados!');
            }
          }
        })
        .catch(error => console.error('❌ Erro ao carregar dados:', error));
    }

    // ✅ Carregar ao abrir página
    window.addEventListener('load', function() {
      // ✅ AGUARDAR 1 SEGUNDO para garantir que o CSS foi carregado
      setTimeout(function() {
        console.log('📡 Iniciando carregamento de dados...');
        carregarDadosBancaELucro();
      }, 1000);
    });

    // ✅ ATUALIZAR A CADA 30 SEGUNDOS
    setInterval(carregarDadosBancaELucro, 30000);
    </script>
</body>
</html>