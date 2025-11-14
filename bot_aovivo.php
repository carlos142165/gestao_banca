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
    <link rel="stylesheet" href="css/menu-topo.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/telegram-mensagens.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/modal-historico-resultados.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/carousel-blocos.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;600&display=swap"
      rel="stylesheet"
    />
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

        /* ===== HEADER NOVO - LAYOUT COMPACTO ===== */
        .header-bloco {
            background: linear-gradient(135deg, #113647 0%, #0e2a35 100%);
            padding: 12px 16px;
            color: white;
            font-size: 13px;
            line-height: 1.6;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
            border-bottom: 2px solid #0a1f26;
            border-radius: 8px 8px 0 0;
        }

        /* Linha 1 - Data e UND */
        .linha-data {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 8px;
            font-weight: 500;
            font-size: 12px;
            letter-spacing: 0.3px;
            white-space: nowrap;
        }

        .und-input {
            width: 110px;
            padding: 8px 10px;
            border: 2px solid rgba(255, 255, 255, 0.6);
            border-radius: 6px;
            background: rgba(255, 255, 255, 0.12);
            color: white;
            font-weight: 600;
            font-size: 13px;
            font-family: "JetBrains Mono", monospace;
            transition: all 0.3s ease;
        }

        .und-input::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }

        .und-input:focus {
            outline: none;
            background: rgba(255, 255, 255, 0.2);
            border-color: white;
            box-shadow: 0 0 8px rgba(255, 255, 255, 0.3);
        }

        .linha-separadora {
            border-top: 1px solid rgba(255, 255, 255, 0.25);
            margin: 6px 0;
        }

        /* Container de Mensagens */
        .messages-area {
            padding: 20px;
            overflow-y: auto;
            flex: 1;
            background-color: #f0f4f8;
            border-radius: 0 0 8px 8px;
            width: 100%;
            box-sizing: border-box;
        }

        /* Seletor específico para telegram-messages-wrapper */
        body > main > div > div.bloco.bloco-1 > div > div.messages-area.telegram-messages-wrapper {
            width: 100% !important;
            padding: 20px !important;
            box-sizing: border-box !important;
        }

        /* Remove scrollbar em navegadores webkit */
        .messages-area::-webkit-scrollbar {
            display: none;
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
            padding: 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
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

        /* ===== RESPONSIVIDADE MOBILE ===== */
        @media screen and (max-width: 768px) {
            .main-content {
                align-items: stretch;
                padding: 10px 0;
            }

            .container {
                width: 100%;
                height: 100%;
                align-items: stretch;
                flex-direction: column;
                gap: 20px;
                padding: 10px 10px;
            }

            .bloco {
                width: 90%;
                max-width: 420px;
                height: 100%;
                margin: 0 auto;
                flex: 1 1 auto;
            }

            .header-bloco {
                padding: 10px 12px;
                font-size: 12px;
            }

            .linha-data {
                font-size: 11px;
                gap: 4px;
                margin-bottom: 6px;
            }

            .und-input {
                width: 100px;
                padding: 6px 8px;
                font-size: 12px;
            }

            .linha-separadora {
                margin: 4px 0;
            }

            .messages-area {
                padding: 15px;
                width: 100%;
                box-sizing: border-box;
                overflow-y: auto;
                flex: 1;
            }

            body > main > div > div.bloco.bloco-1 > div > div.messages-area.telegram-messages-wrapper {
                width: 100% !important;
                padding: 15px !important;
                box-sizing: border-box !important;
            }

            /* Grid das apostas mobile */
            .header-bloco > div[style*="grid-template-columns"] {
                grid-template-columns: 2fr 0.8fr 0.9fr !important;
                gap: 4px !important;
                font-size: 11px !important;
            }
        }

        @media screen and (max-width: 480px) {
            .main-content {
                align-items: stretch;
                padding: 8px 0;
            }

            .container {
                width: 100%;
                height: 100%;
                align-items: stretch;
                flex-direction: column;
                gap: 15px;
                padding: 8px 8px;
            }

            .bloco {
                width: 95%;
                max-width: 100%;
                height: 100%;
                margin: 0 auto;
                flex: 1 1 auto;
            }

            .header-bloco {
                padding: 8px 10px;
                font-size: 11px;
            }

            .linha-data {
                font-size: 10px;
                gap: 3px;
                margin-bottom: 4px;
            }

            .und-input {
                width: 90px;
                padding: 5px 6px;
                font-size: 11px;
            }

            .messages-area {
                padding: 12px;
                width: 100%;
                box-sizing: border-box;
                overflow-y: scroll;
                -ms-overflow-style: none;
                scrollbar-width: none;
                flex: 1;
            }

            body > main > div > div.bloco.bloco-1 > div > div.messages-area.telegram-messages-wrapper {
                width: 100% !important;
                padding: 12px !important;
                box-sizing: border-box !important;
                overflow-y: scroll !important;
                -ms-overflow-style: none !important;
                scrollbar-width: none !important;
            }

            .messages-area::-webkit-scrollbar {
                display: none;
            }

            /* Grid das apostas mobile pequeno */
            .header-bloco > div[style*="grid-template-columns"] {
                grid-template-columns: 1.8fr 0.7fr 0.8fr !important;
                gap: 3px !important;
                font-size: 10px !important;
            }
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

        /* ===== ESTILOS DA ENTRADA DE UND FORMATADA ===== */
        .und-input-formatado {
          transition: all 0.3s ease !important;
        }

        .und-input-formatado:focus {
          outline: none !important;
          border: 2px solid #fff !important;
          background: rgba(255,255,255,0.2) !important;
          box-shadow: 0 0 8px rgba(255,255,255,0.4) !important;
        }

        .und-input-formatado::placeholder {
          color: rgba(255,255,255,0.6) !important;
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
          from { opacity: 0; }
          to { opacity: 1; }
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
          from { transform: translateY(-50px); opacity: 0; }
          to { transform: translateY(0); opacity: 1; }
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
          justify-content: center;
          align-items: center;
        }

        .modal-login:not([style*="display: none"]) {
          display: flex !important;
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

        #submit-login {
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

        #submit-login:hover {
          background: linear-gradient(135deg, rgba(255, 255, 255, 0.35) 0%, rgba(255, 255, 255, 0.2) 100%);
          border-color: rgba(255, 255, 255, 0.5);
          transform: translateY(-2px);
          box-shadow: 0 12px 32px rgba(102, 126, 234, 0.3);
        }

        #submit-login:active {
          transform: translateY(0);
          box-shadow: 0 4px 16px rgba(102, 126, 234, 0.2);
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

        /* ✅ Estilos para o formulário de login modernizado */
        input::placeholder {
          color: rgba(255, 255, 255, 0.6) !important;
        }

        input:focus {
          background: rgba(255, 255, 255, 0.15) !important;
          border-color: rgba(255, 255, 255, 0.4) !important;
          box-shadow: 0 0 20px rgba(255, 255, 255, 0.1), inset 0 0 20px rgba(255, 255, 255, 0.05) !important;
          transform: translateY(-2px);
        }

        @media (max-width: 768px) {
          .modal-content-registro,
          .modal-content-login { width: 95%; max-width: 450px; }
          .modal-form-wrapper { padding: 20px; }
          .modal-header { padding: 25px 20px 20px; }
          .radio-group { flex-direction: column; gap: 10px; }
        }

        /* ✅ ESTILOS PARA SELECT NO MODAL */
        select {
            background: rgba(255, 255, 255, 0.1) !important;
            border: 2px solid rgba(255, 255, 255, 0.2) !important;
            border-radius: 12px !important;
            color: white !important;
            font-size: 15px !important;
            padding: 12px 16px !important;
            transition: all 0.3s ease !important;
            font-family: inherit !important;
        }

        select:focus {
            outline: none !important;
            background: rgba(255, 255, 255, 0.15) !important;
            border-color: rgba(255, 255, 255, 0.4) !important;
            box-shadow: 0 0 20px rgba(255, 255, 255, 0.1), inset 0 0 20px rgba(255, 255, 255, 0.05) !important;
            transform: translateY(-2px);
        }

        select option {
            background: #113647;
            color: white;
        }

        select option:checked {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
    </style>
</head>
<body <?php echo isset($_SESSION['usuario_id']) ? 'data-user-id="' . intval($_SESSION['usuario_id']) . '"' : ''; ?>>
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

                    <!-- ✅ BOTÕES PARA USUÁRIO ID 23 - ADD+ E RESULTADO -->
                    <?php if (isset($_SESSION['usuario_id']) && (intval($_SESSION['usuario_id']) === 23 || $_SESSION['usuario_id'] == 23)): ?>
                    <div id="botoes-admin-bot" style="display: flex; gap: 10px; align-items: center; margin-left: 20px;">
                        <button id="btnADD" onclick="abrirModalADD()" style="
                            background: linear-gradient(135deg, #4CAF50 0%, #388E3C 100%);
                            color: white;
                            border: none;
                            padding: 10px 16px;
                            border-radius: 6px;
                            cursor: pointer;
                            font-weight: 600;
                            font-size: 13px;
                            transition: all 0.3s ease;
                            display: flex;
                            align-items: center;
                            gap: 6px;
                            box-shadow: 0 4px 8px rgba(76, 175, 80, 0.3);
                        " onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 12px rgba(76, 175, 80, 0.4)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 8px rgba(76, 175, 80, 0.3)'">
                            <i class="fas fa-plus-circle"></i> ADD+
                        </button>
                        <button id="btnResultado" onclick="abrirModalResultado()" style="
                            background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                            color: white;
                            border: none;
                            padding: 10px 16px;
                            border-radius: 6px;
                            cursor: pointer;
                            font-weight: 600;
                            font-size: 13px;
                            transition: all 0.3s ease;
                            display: flex;
                            align-items: center;
                            gap: 6px;
                            box-shadow: 0 4px 8px rgba(33, 150, 243, 0.3);
                        " onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 12px rgba(33, 150, 243, 0.4)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 8px rgba(33, 150, 243, 0.3)'">
                            <i class="fas fa-flag-checkered"></i> Resultado
                        </button>
                    </div>
                    <?php endif; ?>

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
                                <a href="#" onclick="abrirModalLogin(); return false;" class="btn-login">
                                    <i class="fas fa-sign-in-alt"></i> Login
                                </a>
                                <a href="#" onclick="abrirModalRegistro(); return false;" class="btn-register">
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
                    <!-- HEADER AZUL - TUDO COMPACTO -->
                    <div class="header-bloco">
                        <!-- Linha 1: Data e UND -->
                        <div class="linha-data" style="justify-content: space-between">
                            <span>📅 <span id="resumo-dia-data">Carregando...</span></span>
                            <span
                                style="
                                    color: #4ade80;
                                    display: flex;
                                    align-items: center;
                                    gap: 8px;
                                    margin-left: auto;
                                "
                                >UND:<input
                                    type="text"
                                    id="resumo-valor-und-input"
                                    class="und-input"
                                    value="R$ 100,00"
                                    placeholder="R$ 0,00"
                                    style="
                                        color: #4ade80;
                                        border: 1px solid rgba(255, 255, 255, 0.4);
                                        border-radius: 4px;
                                        padding: 2px 4px;
                                        background: transparent;
                                        font-weight: bold;
                                        text-align: left;
                                        min-width: 30px;
                                        font-family: 'JetBrains Mono', monospace;
                                        font-size: 11px;
                                    "
                                /></span>
                        </div>

                        <!-- Separador 1 - Abaixo da data -->
                        <div class="linha-separadora"></div>

                        <!-- Linhas de Apostas - Com colunas alinhadas -->
                        <div
                            style="
                                margin-top: 6px;
                                font-family: 'JetBrains Mono', monospace;
                                font-size: 12px;
                                line-height: 1.6;
                                color: white;
                                letter-spacing: 0.5px;
                                font-weight: 500;
                                display: grid;
                                grid-template-columns: 2.2fr 0.9fr 1fr;
                                gap: 6px;
                                align-items: start;
                                grid-auto-flow: row;
                            "
                        >
                            <!-- Linha 1: +1 Gols asiaticos -->
                            <div>⚽ +1 Gols asiaticos</div>
                            <div style="text-align: center;">
                                <span style="color: #4ade80" id="placar-1-green">0</span>
                                <span style="color: #c0d0e0; font-size: 10px;"> X </span>
                                <span style="color: #eea7ad" id="placar-1-red">0</span>
                            </div>
                            <div style="text-align: left; color: #cfcdcd" id="valor-1-final">R$ 0,00</div>

                            <!-- Linha 2: +0.5 Gols ft -->
                            <div>⚽ +0.5 Gols ft</div>
                            <div style="text-align: center;">
                                <span style="color: #4ade80" id="placar-2-green">0</span>
                                <span style="color: #c0d0e0; font-size: 10px;"> X </span>
                                <span style="color: #eea7ad" id="placar-2-red">0</span>
                            </div>
                            <div style="text-align: left; color: #4ade80" id="valor-2-final">R$ 50,00</div>

                            <!-- Linha 3: +1 Cantos asiaticos -->
                            <div>🚩 +1 Cantos asiaticos</div>
                            <div style="text-align: center;">
                                <span style="color: #4ade80" id="placar-3-green">0</span>
                                <span style="color: #c0d0e0; font-size: 10px;"> X </span>
                                <span style="color: #eea7ad" id="placar-3-red">0</span>
                            </div>
                            <div style="text-align: left; color: #eea7ad" id="valor-3-final">R$ -25,00</div>
                        </div>

                        <!-- Separador 2 - Acima do Total -->
                        <div class="linha-separadora"></div>

                        <!-- Total - Com mesmo layout -->
                        <div
                            style="
                                font-family: 'JetBrains Mono', monospace;
                                font-size: 12px;
                                color: white;
                                letter-spacing: 0.5px;
                                font-weight: 600;
                                display: grid;
                                grid-template-columns: 2.2fr 0.9fr 1fr;
                                gap: 6px;
                                align-items: center;
                                margin-bottom: 12px;
                            "
                        >
                            <div>📊 Total:</div>
                            <div style="text-align: center;">
                                <span style="color: #4ade80" id="placar-total-green">0</span>
                                <span style="color: #c0d0e0; font-size: 10px;"> X </span>
                                <span style="color: #eea7ad" id="placar-total-red">0</span>
                            </div>
                            <div style="text-align: left; color: #4ade80" id="valor-total-final">R$ 25,00</div>
                        </div>
                    </div>

                    <!-- CONTAINER DE MENSAGENS -->
                    <div class="messages-area telegram-messages-wrapper" data-current-user-id="<?php echo intval($_SESSION['usuario_id'] ?? 0); ?>">
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
            <div class="modal-header">
                <h2 style="
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    gap: 12px;
                ">
                    <i class="fas fa-sign-in-alt"></i> Acesso Restrito
                </h2>
            </div>

            <div style="
                padding: 30px;
                position: relative;
                z-index: 1;
            ">
                <p style="
                    color: rgba(255, 255, 255, 0.9);
                    text-align: center;
                    margin-bottom: 25px;
                    font-size: 15px;
                    line-height: 1.6;
                ">
                    Esta área é restrita a usuários autenticados. Por favor, faça login para continuar.
                </p>

                <form id="formLogin" onsubmit="enviarFormularioLogin(event)">
                    <div style="position: relative; margin-bottom: 24px;">
                        <input type="email" name="email" id="email-login" placeholder="Email" required style="
                            background: rgba(255, 255, 255, 0.1);
                            border: 2px solid rgba(255, 255, 255, 0.2);
                            border-radius: 12px;
                            width: 100%;
                            outline: none;
                            color: white;
                            font-size: 15px;
                            padding: 12px 16px;
                            box-sizing: border-box;
                            transition: all 0.3s ease;
                        " />
                    </div>
                    <div style="position: relative; margin-bottom: 24px;">
                        <input type="password" name="senha" id="senha-login" placeholder="Senha" required style="
                            background: rgba(255, 255, 255, 0.1);
                            border: 2px solid rgba(255, 255, 255, 0.2);
                            border-radius: 12px;
                            width: 100%;
                            outline: none;
                            color: white;
                            font-size: 15px;
                            padding: 12px 16px;
                            box-sizing: border-box;
                            transition: all 0.3s ease;
                        " />
                    </div>
                    <div id="erro-login" style="
                        color: #ffebee;
                        font-size: 13px;
                        margin-bottom: 15px;
                        display: none;
                        background: rgba(255, 59, 48, 0.2);
                        padding: 10px 14px;
                        border-radius: 8px;
                        border-left: 3px solid #ff3b30;
                    "></div>
                    <button type="submit" style="
                        background: linear-gradient(135deg, rgba(255, 255, 255, 0.25) 0%, rgba(255, 255, 255, 0.1) 100%);
                        width: 100%;
                        border: 2px solid rgba(255, 255, 255, 0.3);
                        padding: 14px;
                        border-radius: 12px;
                        color: white;
                        font-size: 16px;
                        cursor: pointer;
                        margin-top: 10px;
                        font-weight: 700;
                        text-transform: uppercase;
                        transition: all 0.3s ease;
                    ">
                        Acessar
                    </button>
                </form>

                <div style="
                    text-align: center;
                    margin-top: 20px;
                    color: rgba(255, 255, 255, 0.8);
                    font-size: 13px;
                ">
                    <p style="margin-bottom: 15px;">
                        <a href="home.php" style="
                            color: rgba(255, 255, 255, 0.95);
                            font-weight: 600;
                            text-decoration: underline;
                            transition: all 0.3s ease;
                            cursor: pointer;
                        ">
                            ← Voltar para Home
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- ✅ MODAL PARA ADICIONAR APOSTA (ADD+) -->
    <div id="modalADD" class="modal-registro">
        <div class="modal-content-registro" style="max-width: 600px;">
            <button class="modal-close-btn" onclick="fecharModalADD()">&times;</button>
            
            <div class="modal-header">
                <h2><i class="fas fa-plus-circle"></i> Nova Aposta</h2>
            </div>

            <div class="modal-form-wrapper" style="max-height: 70vh; overflow-y: auto;">
                <form id="formADD" onsubmit="salvarAposta(event)">
                    <!-- Seletor de Modelo -->
                    <div class="modal-form-group">
                        <label for="selectModelo">Escolha o Modelo de Cadastro:</label>
                        <select id="selectModelo" onchange="preencherCamposModelo()" style="
                            width: 100%;
                            padding: 12px 16px;
                            background: rgba(255, 255, 255, 0.1);
                            border: 2px solid rgba(255, 255, 255, 0.2);
                            border-radius: 12px;
                            color: white;
                            font-size: 15px;
                            transition: all 0.3s ease;
                        ">
                            <option value="">-- Selecione um modelo --</option>
                            <option value="cantos">🚩 CANTOS</option>
                            <option value="plus1gol">⚽ +1 GOLS</option>
                            <option value="plus05gol">⚽ +0.5 GOL</option>
                        </select>
                    </div>

                    <!-- Separador visual -->
                    <div style="border-top: 1px solid rgba(255, 255, 255, 0.2); margin: 20px 0;"></div>

                    <!-- mensagem_completa -->
                    <div class="inputbox">
                        <input type="text" name="mensagem_completa" id="field-mensagem_completa" class="inputUser" placeholder="Mensagem Completa">
                        <label for="field-mensagem_completa" class="labelinput">Mensagem Completa</label>
                    </div>

                    <!-- titulo -->
                    <div class="inputbox">
                        <input type="text" name="titulo" id="field-titulo" class="inputUser" placeholder="Título">
                        <label for="field-titulo" class="labelinput">Título</label>
                    </div>

                    <!-- tipo_aposta -->
                    <div class="inputbox">
                        <input type="text" name="tipo_aposta" id="field-tipo_aposta" class="inputUser" placeholder="Tipo de Aposta">
                        <label for="field-tipo_aposta" class="labelinput">Tipo de Aposta</label>
                    </div>

                    <!-- Separador visual -->
                    <div style="border-top: 1px solid rgba(255, 255, 255, 0.2); margin: 20px 0;"></div>

                    <!-- time_1 e time_2 em linha -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div class="inputbox" style="position: relative;">
                            <input type="text" name="time_1" id="field-time_1" class="inputUser" placeholder="Time 1" oninput="filtrarTimes('field-time_1', 'dropdown-time_1')">
                            <label for="field-time_1" class="labelinput">Time 1</label>
                            <!-- Dropdown de autocomplete -->
                            <div id="dropdown-time_1" style="
                                position: absolute;
                                top: 100%;
                                left: 0;
                                right: 0;
                                background: rgba(30, 30, 60, 0.95);
                                border: 2px solid rgba(102, 126, 234, 0.4);
                                border-top: none;
                                border-radius: 0 0 10px 10px;
                                max-height: 200px;
                                overflow-y: auto;
                                display: none;
                                z-index: 1000;
                                backdrop-filter: blur(10px);
                            "></div>
                        </div>
                        <div class="inputbox" style="position: relative;">
                            <input type="text" name="time_2" id="field-time_2" class="inputUser" placeholder="Time 2" oninput="filtrarTimes('field-time_2', 'dropdown-time_2')">
                            <label for="field-time_2" class="labelinput">Time 2</label>
                            <!-- Dropdown de autocomplete -->
                            <div id="dropdown-time_2" style="
                                position: absolute;
                                top: 100%;
                                left: 0;
                                right: 0;
                                background: rgba(30, 30, 60, 0.95);
                                border: 2px solid rgba(102, 126, 234, 0.4);
                                border-top: none;
                                border-radius: 0 0 10px 10px;
                                max-height: 200px;
                                overflow-y: auto;
                                display: none;
                                z-index: 1000;
                                backdrop-filter: blur(10px);
                            "></div>
                        </div>
                    </div>

                    <!-- placar_1 e placar_2 em linha -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div class="inputbox">
                            <input type="number" name="placar_1" id="field-placar_1" class="inputUser" placeholder="Placar 1">
                            <label for="field-placar_1" class="labelinput">Placar 1</label>
                        </div>
                        <div class="inputbox">
                            <input type="number" name="placar_2" id="field-placar_2" class="inputUser" placeholder="Placar 2">
                            <label for="field-placar_2" class="labelinput">Placar 2</label>
                        </div>
                    </div>

                    <!-- escanteios_1 e escanteios_2 em linha -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div class="inputbox">
                            <input type="number" name="escanteios_1" id="field-escanteios_1" class="inputUser" placeholder="Escanteios 1">
                            <label for="field-escanteios_1" class="labelinput">Escanteios 1</label>
                        </div>
                        <div class="inputbox">
                            <input type="number" name="escanteios_2" id="field-escanteios_2" class="inputUser" placeholder="Escanteios 2">
                            <label for="field-escanteios_2" class="labelinput">Escanteios 2</label>
                        </div>
                    </div>

                    <!-- odds -->
                    <div class="inputbox">
                        <input type="number" step="0.01" name="odds" id="field-odds" class="inputUser" placeholder="Odds">
                        <label for="field-odds" class="labelinput">Odds</label>
                    </div>

                    <!-- valor_over -->
                    <div class="inputbox">
                        <input type="number" step="0.01" name="valor_over" id="field-valor_over" class="inputUser" placeholder="Valor Over">
                        <label for="field-valor_over" class="labelinput">Valor Over</label>
                    </div>

                    <!-- tipo_odds -->
                    <div class="inputbox">
                        <input type="text" name="tipo_odds" id="field-tipo_odds" class="inputUser" placeholder="Tipo Odds (Ex: Over/Under)">
                        <label for="field-tipo_odds" class="labelinput">Tipo Odds</label>
                    </div>

                    <!-- tempo_minuto -->
                    <div class="inputbox">
                        <input type="number" name="tempo_minuto" id="field-tempo_minuto" class="inputUser" placeholder="Tempo/Minuto">
                        <label for="field-tempo_minuto" class="labelinput">Tempo/Minuto</label>
                    </div>

                    <!-- Separador visual -->
                    <div style="border-top: 1px solid rgba(255, 255, 255, 0.2); margin: 20px 0;"></div>

                    <!-- odds_inicial_casa, odds_inicial_empate, odds_inicial_fora em linha -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px;">
                        <div class="inputbox">
                            <input type="number" step="0.01" name="odds_inicial_casa" id="field-odds_inicial_casa" class="inputUser" placeholder="Casa">
                            <label for="field-odds_inicial_casa" class="labelinput">Odds Casa</label>
                        </div>
                        <div class="inputbox">
                            <input type="number" step="0.01" name="odds_inicial_empate" id="field-odds_inicial_empate" class="inputUser" placeholder="Empate">
                            <label for="field-odds_inicial_empate" class="labelinput">Odds Empate</label>
                        </div>
                        <div class="inputbox">
                            <input type="number" step="0.01" name="odds_inicial_fora" id="field-odds_inicial_fora" class="inputUser" placeholder="Fora">
                            <label for="field-odds_inicial_fora" class="labelinput">Odds Fora</label>
                        </div>
                    </div>

                    <!-- Separador visual -->
                    <div style="border-top: 1px solid rgba(255, 255, 255, 0.2); margin: 20px 0;"></div>

                    <!-- ataques_perigosos_1 e ataques_perigosos_2 em linha -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div class="inputbox">
                            <input type="number" name="ataques_perigosos_1" id="field-ataques_perigosos_1" class="inputUser" placeholder="Ataques 1">
                            <label for="field-ataques_perigosos_1" class="labelinput">Ataques Perigosos 1</label>
                        </div>
                        <div class="inputbox">
                            <input type="number" name="ataques_perigosos_2" id="field-ataques_perigosos_2" class="inputUser" placeholder="Ataques 2">
                            <label for="field-ataques_perigosos_2" class="labelinput">Ataques Perigosos 2</label>
                        </div>
                    </div>

                    <!-- cartoes_amarelos_1 e cartoes_amarelos_2 em linha -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div class="inputbox">
                            <input type="number" name="cartoes_amarelos_1" id="field-cartoes_amarelos_1" class="inputUser" placeholder="Amarelos 1">
                            <label for="field-cartoes_amarelos_1" class="labelinput">Cartões Amarelos 1</label>
                        </div>
                        <div class="inputbox">
                            <input type="number" name="cartoes_amarelos_2" id="field-cartoes_amarelos_2" class="inputUser" placeholder="Amarelos 2">
                            <label for="field-cartoes_amarelos_2" class="labelinput">Cartões Amarelos 2</label>
                        </div>
                    </div>

                    <!-- cartoes_vermelhos_1 e cartoes_vermelhos_2 em linha -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div class="inputbox">
                            <input type="number" name="cartoes_vermelhos_1" id="field-cartoes_vermelhos_1" class="inputUser" placeholder="Vermelhos 1">
                            <label for="field-cartoes_vermelhos_1" class="labelinput">Cartões Vermelhos 1</label>
                        </div>
                        <div class="inputbox">
                            <input type="number" name="cartoes_vermelhos_2" id="field-cartoes_vermelhos_2" class="inputUser" placeholder="Vermelhos 2">
                            <label for="field-cartoes_vermelhos_2" class="labelinput">Cartões Vermelhos 2</label>
                        </div>
                    </div>

                    <!-- chutes_lado_1 e chutes_lado_2 em linha -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div class="inputbox">
                            <input type="number" name="chutes_lado_1" id="field-chutes_lado_1" class="inputUser" placeholder="Chutes Lado 1">
                            <label for="field-chutes_lado_1" class="labelinput">Chutes Lado 1</label>
                        </div>
                        <div class="inputbox">
                            <input type="number" name="chutes_lado_2" id="field-chutes_lado_2" class="inputUser" placeholder="Chutes Lado 2">
                            <label for="field-chutes_lado_2" class="labelinput">Chutes Lado 2</label>
                        </div>
                    </div>

                    <!-- chutes_alvo_1 e chutes_alvo_2 em linha -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div class="inputbox">
                            <input type="number" name="chutes_alvo_1" id="field-chutes_alvo_1" class="inputUser" placeholder="Chutes Alvo 1">
                            <label for="field-chutes_alvo_1" class="labelinput">Chutes Alvo 1</label>
                        </div>
                        <div class="inputbox">
                            <input type="number" name="chutes_alvo_2" id="field-chutes_alvo_2" class="inputUser" placeholder="Chutes Alvo 2">
                            <label for="field-chutes_alvo_2" class="labelinput">Chutes Alvo 2</label>
                        </div>
                    </div>

                    <!-- posse_bola_1 e posse_bola_2 em linha -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div class="inputbox">
                            <input type="number" name="posse_bola_1" id="field-posse_bola_1" class="inputUser" placeholder="Posse 1 (%)">
                            <label for="field-posse_bola_1" class="labelinput">Posse de Bola 1 (%)</label>
                        </div>
                        <div class="inputbox">
                            <input type="number" name="posse_bola_2" id="field-posse_bola_2" class="inputUser" placeholder="Posse 2 (%)">
                            <label for="field-posse_bola_2" class="labelinput">Posse de Bola 2 (%)</label>
                        </div>
                    </div>

                    <!-- Mensagem de erro -->
                    <div id="erro-aposta" class="error-message" style="margin-top: 15px;"></div>

                    <!-- Botão Salvar -->
                    <button type="submit" id="submit-salvar-aposta" style="
                        background: linear-gradient(135deg, rgba(255, 255, 255, 0.25) 0%, rgba(255, 255, 255, 0.1) 100%);
                        width: 100%;
                        border: 2px solid rgba(255, 255, 255, 0.3);
                        padding: 14px;
                        border-radius: 12px;
                        color: white;
                        font-size: 16px;
                        cursor: pointer;
                        margin-top: 20px;
                        transition: all 0.3s ease;
                        font-weight: 700;
                        letter-spacing: 0.5px;
                        backdrop-filter: blur(10px);
                        text-transform: uppercase;
                        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
                    " onmouseover="this.style.background='linear-gradient(135deg, rgba(255, 255, 255, 0.35) 0%, rgba(255, 255, 255, 0.2) 100%)'; this.style.borderColor='rgba(255, 255, 255, 0.5)'; this.style.boxShadow='0 12px 32px rgba(102, 126, 234, 0.3)'; this.style.transform='translateY(-2px)'" onmouseout="this.style.background='linear-gradient(135deg, rgba(255, 255, 255, 0.25) 0%, rgba(255, 255, 255, 0.1) 100%)'; this.style.borderColor='rgba(255, 255, 255, 0.3)'; this.style.boxShadow='0 8px 24px rgba(0, 0, 0, 0.15)'; this.style.transform='translateY(0)'">
                        💾 Salvar Aposta
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- ✅ MODAL DE RESULTADOS -->
    <div id="modalResultado" class="modal-registro" style="display: none;">
        <div class="modal-content-registro" style="max-width: 600px; padding: 40px;">
            <button class="modal-close-btn" onclick="fecharModalResultado()" style="font-size: 28px; top: 15px; right: 15px;">&times;</button>
            
            <h2 style="color: white; margin-bottom: 35px; text-align: center; font-size: 28px; font-weight: 700; letter-spacing: 1px;">
                <i class="fas fa-trophy" style="color: #FFD700; margin-right: 12px;"></i>Registrar Resultado
            </h2>
            
            <form id="formResultado" onsubmit="salvarResultado(event)">
                <!-- Seletor de Jogo -->
                <div class="inputbox" style="margin-bottom: 30px;">
                    <select id="selecionarJogo" name="aposta_id" class="inputUser" required onchange="atualizarDetalhesJogo()" style="padding: 14px 16px; font-size: 15px;">
                        <option value="">-- Selecionar Jogo --</option>
                    </select>
                </div>

                <!-- Campo Cinza para Exibir Resultado Selecionado -->
                <div style="
                    background: #e8e8e8;
                    border: 2px solid #ccc;
                    padding: 16px;
                    border-radius: 10px;
                    margin-bottom: 30px;
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    min-height: 50px;
                ">
                    <div>
                        <p style="color: #666; margin: 0 0 5px 0; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; font-weight: 600;">
                            📌 Resultado Selecionado
                        </p>
                        <p id="resultadoExibido" style="color: #333; margin: 0; font-size: 18px; font-weight: 700;">
                            Nenhum resultado selecionado
                        </p>
                    </div>
                    <div id="resultadoIcone" style="font-size: 32px; margin-left: 20px;">
                        ❓
                    </div>
                </div>

                <!-- Detalhes do Jogo Selecionado -->
                <div id="detalhesJogo" style="
                    background: linear-gradient(135deg, rgba(76, 175, 80, 0.15) 0%, rgba(102, 126, 234, 0.15) 100%);
                    border: 2px solid rgba(102, 126, 234, 0.3);
                    padding: 20px;
                    border-radius: 12px;
                    margin: 25px 0;
                    display: none;
                    backdrop-filter: blur(10px);
                ">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div>
                            <p style="color: #aaa; margin: 0 0 8px 0; font-size: 12px; text-transform: uppercase; letter-spacing: 1px;">
                                📋 Jogo
                            </p>
                            <p style="color: #fff; margin: 0; font-size: 16px; font-weight: 600;">
                                <span id="jogoTitulo">-</span>
                            </p>
                        </div>
                        <div>
                            <p style="color: #aaa; margin: 0 0 8px 0; font-size: 12px; text-transform: uppercase; letter-spacing: 1px;">
                                🎯 Tipo de Aposta
                            </p>
                            <p style="color: #fff; margin: 0; font-size: 16px; font-weight: 600;">
                                <span id="jogoTipo">-</span>
                            </p>
                        </div>
                    </div>
                    <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid rgba(255, 255, 255, 0.1);">
                        <p style="color: #aaa; margin: 0 0 8px 0; font-size: 12px; text-transform: uppercase; letter-spacing: 1px;">
                            ⏳ Resultado Atual
                        </p>
                        <p style="color: #fff; margin: 0; font-size: 18px; font-weight: 700;">
                            <span id="jogoResultadoAtual">Pendente</span>
                        </p>
                    </div>
                </div>

                <!-- Seletor de Resultado com cores vibrantes -->
                <div style="margin: 35px 0;">
                    <p style="color: #aaa; margin: 0 0 15px 0; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; font-weight: 600;">
                        🏆 Selecione o Resultado
                    </p>
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px;">
                        <button type="button" class="resultado-btn" data-resultado="GREEN" onclick="selecionarResultado('GREEN', this)" style="
                            padding: 20px 16px;
                            border: 3px solid #4CAF50;
                            background: rgba(76, 175, 80, 0.15);
                            color: #4CAF50;
                            border-radius: 10px;
                            cursor: pointer;
                            font-weight: 700;
                            font-size: 16px;
                            transition: all 0.3s ease;
                            box-shadow: 0 4px 15px rgba(76, 175, 80, 0.2);
                            text-transform: uppercase;
                            letter-spacing: 1px;
                        " onmouseover="this.style.background='rgba(76, 175, 80, 0.28)'; this.style.boxShadow='0 8px 25px rgba(76, 175, 80, 0.5)'; this.style.transform='translateY(-3px) scale(1.05)'; this.style.borderColor='#45a049';" onmouseout="if(this.getAttribute('data-selected') !== 'true') { this.style.background='rgba(76, 175, 80, 0.15)'; this.style.boxShadow='0 4px 15px rgba(76, 175, 80, 0.2)'; this.style.transform='translateY(0) scale(1)'; this.style.borderColor='#4CAF50'; }">
                            <i class="fas fa-check-circle" style="margin-right: 8px;"></i>GREEN
                        </button>
                        <button type="button" class="resultado-btn" data-resultado="RED" onclick="selecionarResultado('RED', this)" style="
                            padding: 20px 16px;
                            border: 3px solid #F44336;
                            background: rgba(244, 67, 54, 0.15);
                            color: #F44336;
                            border-radius: 10px;
                            cursor: pointer;
                            font-weight: 700;
                            font-size: 16px;
                            transition: all 0.3s ease;
                            box-shadow: 0 4px 15px rgba(244, 67, 54, 0.2);
                            text-transform: uppercase;
                            letter-spacing: 1px;
                        " onmouseover="this.style.background='rgba(244, 67, 54, 0.28)'; this.style.boxShadow='0 8px 25px rgba(244, 67, 54, 0.5)'; this.style.transform='translateY(-3px) scale(1.05)'; this.style.borderColor='#da190b';" onmouseout="if(this.getAttribute('data-selected') !== 'true') { this.style.background='rgba(244, 67, 54, 0.15)'; this.style.boxShadow='0 4px 15px rgba(244, 67, 54, 0.2)'; this.style.transform='translateY(0) scale(1)'; this.style.borderColor='#F44336'; }">
                            <i class="fas fa-times-circle" style="margin-right: 8px;"></i>RED
                        </button>
                        <button type="button" class="resultado-btn" data-resultado="REEMBOLSO" onclick="selecionarResultado('REEMBOLSO', this)" style="
                            padding: 20px 16px;
                            border: 3px solid #FFC107;
                            background: rgba(255, 193, 7, 0.15);
                            color: #FFC107;
                            border-radius: 10px;
                            cursor: pointer;
                            font-weight: 700;
                            font-size: 15px;
                            transition: all 0.3s ease;
                            box-shadow: 0 4px 15px rgba(255, 193, 7, 0.2);
                            text-transform: uppercase;
                            letter-spacing: 1px;
                        " onmouseover="this.style.background='rgba(255, 193, 7, 0.28)'; this.style.boxShadow='0 8px 25px rgba(255, 193, 7, 0.5)'; this.style.transform='translateY(-3px) scale(1.05)'; this.style.borderColor='#e0a800';" onmouseout="if(this.getAttribute('data-selected') !== 'true') { this.style.background='rgba(255, 193, 7, 0.15)'; this.style.boxShadow='0 4px 15px rgba(255, 193, 7, 0.2)'; this.style.transform='translateY(0) scale(1)'; this.style.borderColor='#FFC107'; }">
                            <i class="fas fa-redo-alt" style="margin-right: 8px;"></i>REEMB.
                        </button>
                    </div>
                </div>

                <input type="hidden" id="resultadoSelecionado" name="resultado" value="">

                <!-- Erro -->
                <div id="erro-resultado" class="error-message" style="margin: 20px 0; padding: 12px; background: rgba(244, 67, 54, 0.15); border: 1px solid rgba(244, 67, 54, 0.3); border-radius: 8px; color: #FF6B6B; display: none; font-size: 14px;"></div>

                <!-- Botão Salvar -->
                <button type="submit" id="submit-salvar-resultado" style="
                    background: linear-gradient(135deg, #FFD700 0%, #FFC107 100%);
                    width: 100%;
                    border: 2px solid #FFD700;
                    padding: 16px;
                    border-radius: 12px;
                    color: #333;
                    font-size: 16px;
                    cursor: pointer;
                    margin-top: 30px;
                    transition: all 0.3s ease;
                    font-weight: 700;
                    letter-spacing: 1px;
                    box-shadow: 0 8px 24px rgba(255, 215, 0, 0.35);
                    text-transform: uppercase;
                " onmouseover="this.style.background='linear-gradient(135deg, #FFF176 0%, #FFD54F 100%)'; this.style.boxShadow='0 12px 32px rgba(255, 215, 0, 0.5)'; this.style.transform='translateY(-2px)';" onmouseout="this.style.background='linear-gradient(135deg, #FFD700 0%, #FFC107 100%)'; this.style.boxShadow='0 8px 24px rgba(255, 215, 0, 0.35)'; this.style.transform='translateY(0)';">
                    <i class="fas fa-save" style="margin-right: 10px;"></i>Salvar Resultado
                </button>
            </form>
        </div>
    </div>

    <!-- ✅ VERIFICAÇÃO DE AUTENTICAÇÃO PARA "GERENCIAR BANCA" -->

    <script>
        // ✅ FUNÇÕES PARA MODAL ADD+
        function abrirModalADD() {
            console.log('🔓 Abrindo modal ADD...');
            const modal = document.getElementById("modalADD");
            console.log('Modal encontrado:', modal);
            if (modal) {
                modal.classList.add("show");
                console.log('✅ Classe show adicionada ao modal');
                
                const form = document.getElementById("formADD");
                if (form) form.reset();
                
                const select = document.getElementById("selectModelo");
                if (select) select.value = "";
            } else {
                console.error('❌ Modal #modalADD não encontrado!');
            }
        }

        function fecharModalADD() {
            const modal = document.getElementById("modalADD");
            if (modal) {
                modal.classList.remove("show");
                const form = document.getElementById("formADD");
                if (form) form.reset();
            }
        }

        // ✅ FUNÇÃO: Filtrar e buscar times com autocomplete
        function filtrarTimes(inputId, dropdownId) {
            const input = document.getElementById(inputId);
            const dropdown = document.getElementById(dropdownId);
            const query = input.value.trim();
            
            // Limpar dropdown se input vazio
            if (query.length < 1) {
                dropdown.style.display = 'none';
                dropdown.innerHTML = '';
                return;
            }
            
            console.log(`🔍 Buscando times com: "${query}"`);
            
            // Buscar times via API
            fetch(`api/buscar-times.php?q=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    console.log('✅ Resposta da API:', data);
                    
                    if (data.success && data.times.length > 0) {
                        // Preencher dropdown com resultados
                        dropdown.innerHTML = data.times.map((time, idx) => `
                            <div style="
                                padding: 12px 16px;
                                cursor: pointer;
                                border-bottom: 1px solid rgba(102, 126, 234, 0.2);
                                transition: all 0.2s ease;
                                color: #fff;
                                font-size: 14px;
                            " 
                            onmouseover="this.style.background='rgba(102, 126, 234, 0.3)'; this.style.paddingLeft='20px';"
                            onmouseout="this.style.background='transparent'; this.style.paddingLeft='16px';"
                            onclick="selecionarTime('${inputId}', '${dropdownId}', '${time.nome.replace(/'/g, "\\'")}')">
                                ⚽ ${time.nome}
                            </div>
                        `).join('');
                        
                        dropdown.style.display = 'block';
                    } else {
                        // Nenhum resultado
                        dropdown.innerHTML = `
                            <div style="padding: 12px 16px; color: #999; font-size: 13px;">
                                ❌ Nenhum time encontrado
                            </div>
                        `;
                        dropdown.style.display = 'block';
                    }
                })
                .catch(error => {
                    console.error('❌ Erro ao buscar times:', error);
                    dropdown.innerHTML = `
                        <div style="padding: 12px 16px; color: #f44336; font-size: 13px;">
                            ❌ Erro ao buscar times
                        </div>
                    `;
                    dropdown.style.display = 'block';
                });
        }

        // ✅ FUNÇÃO: Selecionar time do dropdown
        function selecionarTime(inputId, dropdownId, nomeTempo) {
            const input = document.getElementById(inputId);
            const dropdown = document.getElementById(dropdownId);
            
            // Preencher input e fechar dropdown
            input.value = nomeTempo;
            dropdown.style.display = 'none';
            dropdown.innerHTML = '';
            
            console.log(`✅ Time selecionado: ${nomeTempo}`);
        }

        // ✅ FUNÇÃO PARA PREENCHER CAMPOS BASEADO NO MODELO SELECIONADO
        function preencherCamposModelo() {
            const modelo = document.getElementById("selectModelo").value;
            
            // Limpar campos de dados (mas manter qualquer coisa que usuário já preencheu)
            // Apenas pré-preencher título, tipo_aposta e tipo_odds
            
            if (modelo === "cantos") {
                // Modelo: CANTOS
                document.getElementById("field-titulo").value = "OVER ( +1⛳ CANTOS )";
                document.getElementById("field-tipo_aposta").value = "CANTOS";
                document.getElementById("field-tipo_odds").value = "Over";
            } else if (modelo === "plus1gol") {
                // Modelo: +1 GOLS
                document.getElementById("field-titulo").value = "OVER ( +1 ⚽ GOL )";
                document.getElementById("field-tipo_aposta").value = "GOL";
                document.getElementById("field-tipo_odds").value = "Over";
            } else if (modelo === "plus05gol") {
                // Modelo: +0.5 GOL
                document.getElementById("field-titulo").value = "OVER ( +0.5 ⚽ GOL )";
                document.getElementById("field-tipo_aposta").value = "GOL";
                document.getElementById("field-tipo_odds").value = "Over";
            }
        }

        // ✅ FUNÇÃO PARA LIMPAR TODOS OS CAMPOS DO FORMULÁRIO
        function limparCamposFormulario() {
            const campos = [
                "field-mensagem_completa", "field-titulo", "field-tipo_aposta",
                "field-time_1", "field-time_2", "field-placar_1", "field-placar_2",
                "field-escanteios_1", "field-escanteios_2", "field-odds", "field-valor_over", 
                "field-tipo_odds", "field-tempo_minuto",
                "field-odds_inicial_casa", "field-odds_inicial_empate", "field-odds_inicial_fora",
                "field-ataques_perigosos_1", "field-ataques_perigosos_2",
                "field-cartoes_amarelos_1", "field-cartoes_amarelos_2",
                "field-cartoes_vermelhos_1", "field-cartoes_vermelhos_2",
                "field-chutes_lado_1", "field-chutes_lado_2",
                "field-chutes_alvo_1", "field-chutes_alvo_2",
                "field-posse_bola_1", "field-posse_bola_2"
            ];
            
            campos.forEach(id => {
                const elemento = document.getElementById(id);
                if (elemento) {
                    elemento.value = "";
                }
            });
        }

        // ✅ FUNÇÃO PARA SALVAR A APOSTA
        function salvarAposta(event) {
            event.preventDefault();
            
            const formADD = document.getElementById("formADD");
            const formData = new FormData(formADD);
            const erroDiv = document.getElementById("erro-aposta");
            
            // ✅ CONSTRUIR MENSAGEM_COMPLETA DINAMICAMENTE A PARTIR DOS CAMPOS
            const time_1 = document.getElementById("field-time_1").value;
            const time_2 = document.getElementById("field-time_2").value;
            const placar_1 = document.getElementById("field-placar_1").value;
            const placar_2 = document.getElementById("field-placar_2").value;
            const odds = document.getElementById("field-odds").value;
            const titulo = document.getElementById("field-titulo").value;
            const tipo_aposta = document.getElementById("field-tipo_aposta").value;
            const escanteios_1 = document.getElementById("field-escanteios_1").value;
            const escanteios_2 = document.getElementById("field-escanteios_2").value;
            
            // Construir mensagem formatada
            let mensagem_formatada = "Oportunidade! 🚨\n\n";
            mensagem_formatada += "📊 " + titulo + "\n\n";
            
            if (time_1 && time_2) {
                mensagem_formatada += time_1 + " (H) x " + time_2 + " (A)\n";
            }
            
            if (placar_1 && placar_2) {
                mensagem_formatada += "Placar: " + placar_1 + " - " + placar_2 + "\n";
            }
            
            if (tipo_aposta.toUpperCase().includes("CANTO")) {
                // Para CANTOS, incluir escanteios
                if (escanteios_1 && escanteios_2) {
                    mensagem_formatada += "⛳ Escanteios: " + escanteios_1 + " - " + escanteios_2 + "\n";
                }
                mensagem_formatada += "Escanteios over +1 : " + (odds || "1.85");
            } else {
                // Para GOLS
                mensagem_formatada += "Gols over : " + (odds || "1.75");
            }
            
            // Atualizar o campo mensagem_completa com a mensagem formatada
            formData.set('mensagem_completa', mensagem_formatada);
            
            console.log('📤 Enviando formulário com mensagem formatada...');
            console.log('📝 Mensagem:', mensagem_formatada);
            
            fetch('salvar-aposta.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('📡 Response status:', response.status);
                console.log('📡 Response headers:', response.headers);
                
                if (!response.ok) {
                    console.error('❌ HTTP Error:', response.status);
                }
                
                return response.text().then(text => {
                    console.log('📝 Response text:', text);
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('❌ ERRO DE PARSE JSON:', e);
                        console.error('Resposta do servidor:', text.substring(0, 500));
                        throw new Error('❌ Resposta inválida do servidor. Verifique o console para mais detalhes. Resposta: ' + text.substring(0, 200));
                    }
                });
            })
            .then(data => {
                console.log('✅ Dados recebidos:', data);
                if (data.success) {
                    erroDiv.style.display = "none";
                    alert('✅ Aposta salva com sucesso!');
                    fecharModalADD();
                    
                    // ✅ RECARREGAR MENSAGENS APÓS SALVAR
                    console.log('🔄 Recarregando mensagens...');
                    
                    // Se TelegramMessenger está disponível, recarregar mensagens
                    if (typeof TelegramMessenger !== 'undefined' && TelegramMessenger.loadMessages) {
                        console.log('📨 Chamando TelegramMessenger.loadMessages()');
                        TelegramMessenger.loadMessages();
                    } else {
                        // Fallback: recarregar página
                        console.log('📨 Recarregando página completa...');
                        setTimeout(() => {
                            location.reload();
                        }, 500);
                    }
                } else {
                    erroDiv.textContent = data.message || 'Erro ao salvar aposta. Tente novamente.';
                    erroDiv.style.display = "block";
                }
            })
            .catch(error => {
                console.error('❌ Erro capturado:', error);
                erroDiv.textContent = 'Erro: ' + error.message;
                erroDiv.style.display = "block";
            });
        }

        // ✅ FUNÇÃO PLACEHOLDER PARA MODAL DE RESULTADO
        function abrirModalResultado() {
            console.log('🏆 Abrindo modal de Resultados...');
            
            const modal = document.getElementById("modalResultado");
            if (!modal) {
                console.error('❌ Modal #modalResultado não encontrado!');
                return;
            }
            
            // Limpar seleção anterior
            document.getElementById("selecionarJogo").value = "";
            document.getElementById("resultadoSelecionado").value = "";
            document.getElementById("detalhesJogo").style.display = "none";
            document.getElementById("erro-resultado").textContent = "";
            document.getElementById("erro-resultado").style.display = "none";
            
            // Resetar campo cinza de resultado
            document.getElementById("resultadoExibido").textContent = "Nenhum resultado selecionado";
            document.getElementById("resultadoExibido").style.color = "#333";
            document.getElementById("resultadoIcone").textContent = "❓";
            
            // Remover seleção de botões de resultado - resetar para estado padrão
            document.querySelectorAll('.resultado-btn').forEach(btn => {
                btn.setAttribute('data-selected', 'false');
                const resultado = btn.getAttribute('data-resultado');
                if (resultado === 'GREEN') {
                    btn.style.background = 'rgba(76, 175, 80, 0.15)';
                    btn.style.borderColor = '#4CAF50';
                    btn.style.boxShadow = '0 4px 15px rgba(76, 175, 80, 0.2)';
                    btn.style.transform = 'translateY(0) scale(1)';
                } else if (resultado === 'RED') {
                    btn.style.background = 'rgba(244, 67, 54, 0.15)';
                    btn.style.borderColor = '#F44336';
                    btn.style.boxShadow = '0 4px 15px rgba(244, 67, 54, 0.2)';
                    btn.style.transform = 'translateY(0) scale(1)';
                } else {
                    btn.style.background = 'rgba(255, 193, 7, 0.15)';
                    btn.style.borderColor = '#FFC107';
                    btn.style.boxShadow = '0 4px 15px rgba(255, 193, 7, 0.2)';
                    btn.style.transform = 'translateY(0) scale(1)';
                }
                btn.style.borderWidth = '3px';
            });
            
            // Buscar jogos de hoje
            carregarJogosDoDia();
            
            // Abrir modal
            modal.classList.add("show");
            modal.style.display = "flex";
        }

        function fecharModalResultado() {
            const modal = document.getElementById("modalResultado");
            if (modal) {
                modal.classList.remove("show");
                modal.style.display = "none";
            }
        }

        // ✅ CARREGAR JOGOS DO DIA
        function carregarJogosDoDia() {
            console.log('📅 Carregando jogos de hoje...');
            
            fetch('api/carregar-mensagens-banco.php?action=get-messages')
                .then(response => response.json())
                .then(data => {
                    console.log('✅ Jogos carregados:', data.messages);
                    
                    const select = document.getElementById("selecionarJogo");
                    select.innerHTML = '<option value="">-- Selecionar Jogo --</option>';
                    
                    if (data.success && data.messages.length > 0) {
                        data.messages.forEach(msg => {
                            const option = document.createElement('option');
                            option.value = msg.update_id || msg.id;
                            option.textContent = (msg.title || msg.titulo || 'Jogo sem título') + 
                                              (msg.time_1 && msg.time_2 ? ` (${msg.time_1} x ${msg.time_2})` : '');
                            option.dataset.mensagem = JSON.stringify(msg);
                            select.appendChild(option);
                        });
                        console.log(`✅ ${data.messages.length} jogos carregados no dropdown`);
                    } else {
                        console.log('⚠️ Nenhum jogo encontrado para hoje');
                    }
                })
                .catch(error => {
                    console.error('❌ Erro ao carregar jogos:', error);
                    document.getElementById("erro-resultado").textContent = 'Erro ao carregar jogos: ' + error.message;
                    document.getElementById("erro-resultado").style.display = "block";
                });
        }

        // ✅ ATUALIZAR DETALHES DO JOGO SELECIONADO
        function atualizarDetalhesJogo() {
            const select = document.getElementById("selecionarJogo");
            const selectedOption = select.options[select.selectedIndex];
            
            if (!selectedOption.value) {
                document.getElementById("detalhesJogo").style.display = "none";
                return;
            }
            
            try {
                const msg = JSON.parse(selectedOption.dataset.mensagem);
                
                document.getElementById("jogoTitulo").textContent = msg.title || msg.titulo || '-';
                document.getElementById("jogoTipo").textContent = msg.type || msg.tipo_aposta || '-';
                
                // Mostrar resultado atual
                const resultadoAtual = msg.resultado && msg.resultado !== 'null' 
                    ? msg.resultado 
                    : 'Pendente ⏳';
                document.getElementById("jogoResultadoAtual").textContent = resultadoAtual;
                
                // Colorir resultado atual
                const spanResultado = document.getElementById("jogoResultadoAtual");
                if (msg.resultado === 'GREEN') {
                    spanResultado.style.color = '#4CAF50';
                } else if (msg.resultado === 'RED') {
                    spanResultado.style.color = '#F44336';
                } else if (msg.resultado === 'REEMBOLSO') {
                    spanResultado.style.color = '#FFC107';
                } else {
                    spanResultado.style.color = '#aaa';
                }
                
                document.getElementById("detalhesJogo").style.display = "block";
                console.log('✅ Detalhes do jogo atualizados');
            } catch (e) {
                console.error('❌ Erro ao parsear mensagem:', e);
            }
        }

        // ✅ SELECIONAR RESULTADO
        function selecionarResultado(resultado, button) {
            // Limpar seleção anterior de todos os botões
            document.querySelectorAll('.resultado-btn').forEach(btn => {
                btn.setAttribute('data-selected', 'false');
                const res = btn.getAttribute('data-resultado');
                
                if (res === 'GREEN') {
                    btn.style.background = 'rgba(76, 175, 80, 0.15)';
                    btn.style.borderColor = '#4CAF50';
                    btn.style.borderWidth = '3px';
                    btn.style.boxShadow = '0 4px 15px rgba(76, 175, 80, 0.2)';
                    btn.style.transform = 'translateY(0) scale(1)';
                } else if (res === 'RED') {
                    btn.style.background = 'rgba(244, 67, 54, 0.15)';
                    btn.style.borderColor = '#F44336';
                    btn.style.borderWidth = '3px';
                    btn.style.boxShadow = '0 4px 15px rgba(244, 67, 54, 0.2)';
                    btn.style.transform = 'translateY(0) scale(1)';
                } else {
                    btn.style.background = 'rgba(255, 193, 7, 0.15)';
                    btn.style.borderColor = '#FFC107';
                    btn.style.borderWidth = '3px';
                    btn.style.boxShadow = '0 4px 15px rgba(255, 193, 7, 0.2)';
                    btn.style.transform = 'translateY(0) scale(1)';
                }
            });
            
            // Destacar selecionado com efeito FORTE
            button.setAttribute('data-selected', 'true');
            if (resultado === 'GREEN') {
                button.style.background = 'rgba(76, 175, 80, 0.45)';
                button.style.borderColor = '#2e7d32';
                button.style.borderWidth = '4px';
                button.style.boxShadow = '0 0 25px rgba(76, 175, 80, 0.8), inset 0 0 15px rgba(76, 175, 80, 0.4), 0 8px 30px rgba(76, 175, 80, 0.5)';
                button.style.transform = 'translateY(-3px) scale(1.08)';
                
                document.getElementById("resultadoExibido").textContent = "✅ GREEN";
                document.getElementById("resultadoExibido").style.color = '#2e7d32';
                document.getElementById("resultadoIcone").textContent = "✅";
            } else if (resultado === 'RED') {
                button.style.background = 'rgba(244, 67, 54, 0.45)';
                button.style.borderColor = '#c62828';
                button.style.borderWidth = '4px';
                button.style.boxShadow = '0 0 25px rgba(244, 67, 54, 0.8), inset 0 0 15px rgba(244, 67, 54, 0.4), 0 8px 30px rgba(244, 67, 54, 0.5)';
                button.style.transform = 'translateY(-3px) scale(1.08)';
                
                document.getElementById("resultadoExibido").textContent = "❌ RED";
                document.getElementById("resultadoExibido").style.color = '#c62828';
                document.getElementById("resultadoIcone").textContent = "❌";
            } else {
                button.style.background = 'rgba(255, 193, 7, 0.45)';
                button.style.borderColor = '#f57f17';
                button.style.borderWidth = '4px';
                button.style.boxShadow = '0 0 25px rgba(255, 193, 7, 0.8), inset 0 0 15px rgba(255, 193, 7, 0.4), 0 8px 30px rgba(255, 193, 7, 0.5)';
                button.style.transform = 'translateY(-3px) scale(1.08)';
                
                document.getElementById("resultadoExibido").textContent = "🔄 REEMBOLSO";
                document.getElementById("resultadoExibido").style.color = '#f57f17';
                document.getElementById("resultadoIcone").textContent = "🔄";
            }
            
            document.getElementById("resultadoSelecionado").value = resultado;
            console.log('🎯 Resultado selecionado:', resultado);
        }

        // ✅ SALVAR RESULTADO
        function salvarResultado(event) {
            event.preventDefault();
            
            const apostaId = document.getElementById("selecionarJogo").value;
            const resultado = document.getElementById("resultadoSelecionado").value;
            const erroDiv = document.getElementById("erro-resultado");
            
            if (!apostaId) {
                erroDiv.textContent = '❌ Selecione um jogo';
                erroDiv.style.display = "block";
                return;
            }
            
            if (!resultado) {
                erroDiv.textContent = '❌ Selecione um resultado (GREEN, RED ou REEMBOLSO)';
                erroDiv.style.display = "block";
                return;
            }
            
            console.log('💾 Salvando resultado...');
            console.log('  Aposta ID:', apostaId);
            console.log('  Resultado:', resultado);
            
            const formData = new FormData();
            formData.append('aposta_id', apostaId);
            formData.append('resultado', resultado);
            
            fetch('salvar-resultado.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('📡 Response status:', response.status);
                return response.text().then(text => {
                    console.log('📝 Response text:', text);
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('❌ Erro ao fazer parse JSON:', e);
                        throw new Error('Resposta inválida do servidor');
                    }
                });
            })
            .then(data => {
                console.log('✅ Resposta recebida:', data);
                if (data.success) {
                    alert('✅ Resultado salvo com sucesso!');
                    fecharModalResultado();
                    
                    // Recarregar jogos no Bloco 1
                    if (typeof TelegramMessenger !== 'undefined' && TelegramMessenger.loadMessages) {
                        console.log('🔄 Recarregando mensagens...');
                        TelegramMessenger.loadMessages();
                    } else {
                        setTimeout(() => location.reload(), 500);
                    }
                } else {
                    erroDiv.textContent = data.message || 'Erro ao salvar resultado';
                    erroDiv.style.display = "block";
                }
            })
            .catch(error => {
                console.error('❌ Erro capturado:', error);
                erroDiv.textContent = 'Erro: ' + error.message;
                erroDiv.style.display = "block";
            });
        }

        // ✅ EVENT LISTENER: Fechar modal ao clicar fora
        document.getElementById("modalResultado").addEventListener("click", function(event) {
            if (event.target === this) {
                fecharModalResultado();
            }
        });

        // Fechar modal ao clicar fora
        document.getElementById("modalADD").addEventListener("click", function(event) {
            if (event.target === this) {
                fecharModalADD();
            }
        });

        // ✅ FECHAR DROPDOWNS DE AUTOCOMPLETE AO CLICAR FORA
        document.addEventListener("click", function(event) {
            const dropdowns = ['dropdown-time_1', 'dropdown-time_2'];
            dropdowns.forEach(dropdownId => {
                const dropdown = document.getElementById(dropdownId);
                const input = document.getElementById(dropdownId.replace('dropdown-', 'field-'));
                
                if (dropdown && input) {
                    // Se clicou fora do input e do dropdown, fechar
                    if (!event.target.closest(`#${input.id}`) && !event.target.closest(`#${dropdownId}`)) {
                        dropdown.style.display = 'none';
                    }
                }
            });
        });

        // Fechar com tecla ESC
        document.addEventListener("keydown", function(event) {
            if (event.key === "Escape") {
                fecharModalADD();
                fecharModalResultado();
            }
        });
    </script>

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
      document.getElementById("modalLogin").style.display = "flex";
    }

    function fecharModalLogin() {
      const usuarioAutenticado = <?php echo json_encode(isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id'])); ?>;
      
      // Se não está autenticado, redireciona para home ao fechar o modal
      if (!usuarioAutenticado) {
        console.log('❌ Usuário não autenticado - redirecionando para home.php');
        window.location.href = 'home.php';
        return;
      }
      
      // Se está autenticado, apenas fecha o modal normalmente
      document.getElementById("modalLogin").style.display = "none";
      document.getElementById("formLogin").reset();
    }

    function irParaRegistro() {
      fecharModalLogin();
      setTimeout(() => {
        abrirModalRegistro();
      }, 300);
    }

    // Fechar modal ao clicar fora (para o modal de login com display:flex)
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
        capsLockWarning.style.visibility = event.getModifierState("CapsLock") ? "visible" : "hidden";
      });
    }

    // Formatar Telefone
    async function detectarCodigoPais() {
      try {
        // Implementar lógica de detecção se necessário
      } catch (erro) {
        console.warn('Erro ao detectar código do país:', erro);
      }
    }

    function aplicarMascaraTelefone() {
      const telefoneInput = document.getElementById('telefone-modal');

      if (telefoneInput) {
        telefoneInput.addEventListener("input", function (e) {
          let valor = e.target.value.replace(/\D/g, '');
          
          if (valor.length > 11) {
            valor = valor.substring(0, 11);
          }
          
          if (valor.length >= 6) {
            valor = valor.substring(0, 5) + '-' + valor.substring(5);
          }
          
          e.target.value = valor;
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
        if (data.includes('sucesso') || data.includes('Bem-vindo')) {
          window.location.href = 'home.php';
        } else {
          erroDiv.textContent = data || 'Erro ao criar conta. Tente novamente.';
          erroDiv.style.display = "block";
        }
      })
      .catch(error => {
        console.error('Erro:', error);
        erroDiv.textContent = 'Erro ao conectar com o servidor.';
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
        console.log('📡 Resposta do servidor:', data);
        
        if (data.includes("sucesso") || data.trim() === "sucesso") {
          erroDiv.style.display = "none";
          console.log('✅ Login bem-sucedido - recarregando página');
          
          // Fechar o modal
          document.getElementById("modalLogin").style.display = "none";
          
          // Recarregar com force-refresh para ignorar cache
          setTimeout(() => {
            location.reload(true);
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
        console.error('❌ Erro na requisição:', error);
        erroDiv.textContent = "Erro de conexão. Tente novamente!";
        erroDiv.style.display = "block";
      });
    }

    // Inicializar detectar código ao carregar
    window.addEventListener('DOMContentLoaded', function() {
      detectarCodigoPais();
      // Sempre verificar autenticação quando a página carrega (sem cache de sessionStorage)
      verificarAutenticacao();
    });

    // ✅ Se não autenticado, mostrar bloqueio (modal de login)
    function verificarAutenticacao() {
      const usuario_autenticado = <?php echo json_encode($usuario_autenticado); ?>;
      console.log('🔍 Verificando autenticação PHP:', usuario_autenticado);
      
      if (!usuario_autenticado) {
        console.log('❌ Usuário não autenticado - mostrando modal de login');
        // Aguardar um pouco para garantir que o DOM está pronto
        setTimeout(function() {
          abrirModalLogin();
        }, 300);
      } else {
        console.log('✅ Usuário autenticado - modal não será aberto');
        // Fechar o modal se ele estiver aberto
        document.getElementById("modalLogin").style.display = "none";
      }
    }
    </script>

    <!-- MODAL DE LOGIN PARA USUÁRIOS NÃO AUTENTICADOS -->
    <?php if (false): ?>
    <!-- Modal desativado - página pública -->
    <?php endif; ?>

    <!-- ✅ VERIFICAÇÃO DE AUTENTICAÇÃO PARA "GERENCIAR BANCA" E ABRIR MODAL DE LOGIN AUTOMATICAMENTE -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const botaoGerencia = document.getElementById('abrirGerenciaBanca');
            const usuarioAutenticado = <?php echo json_encode($usuario_autenticado); ?>;

            // ✅ SE NÃO ESTÁ AUTENTICADO, ABRE O MODAL DE LOGIN AUTOMATICAMENTE
            if (!usuarioAutenticado) {
                // Aguardar um pouco para garantir que o DOM está pronto
                setTimeout(function() {
                    abrirModalLogin();
                    console.log('✅ Modal de login aberto automaticamente para usuário não autenticado');
                }, 500);
            }

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
    <script>
      // ===== NOVO: Formatação de moeda no UND com dynamic width =====
      const undInputs = document.querySelectorAll("#resumo-valor-und-input");

      undInputs.forEach((input) => {
        // Função para ajustar largura do input
        function adjustWidth() {
          // Cria um elemento temporário para medir o texto
          const temp = document.createElement("span");
          temp.style.visibility = "hidden";
          temp.style.position = "absolute";
          temp.style.fontFamily = "JetBrains Mono, monospace";
          temp.style.fontSize = "11px";
          temp.style.fontWeight = "bold";
          temp.style.padding = "2px 4px";
          temp.textContent = input.value;
          document.body.appendChild(temp);

          const width = temp.offsetWidth;
          document.body.removeChild(temp);

          // Começa com 130px, mas aumenta se o conteúdo for maior
          input.style.width = Math.max(130, width + 4) + "px";
        }

        // Evento ao digitar - formatar a moeda
        input.addEventListener("input", function (e) {
          let valor = e.target.value.replace(/\D/g, "");
          if (valor.length === 0) {
            e.target.value = "R$ 0,00";
            adjustWidth();
            return;
          }
          valor = (parseInt(valor) / 100).toLocaleString("pt-BR", {
            style: "currency",
            currency: "BRL",
          });
          e.target.value = valor;
          adjustWidth();
        });

        // Evento ao clicar/focar - SELECIONA TUDO mas MANTÉM a formatação
        input.addEventListener("focus", function (e) {
          e.target.select();
        });

        // Evento ao sair do campo - garante formatação correta
        input.addEventListener("blur", function (e) {
          if (e.target.value === "") {
            e.target.value = "R$ 0,00";
          } else {
            let valor = e.target.value.replace(/\D/g, "");
            if (valor.length > 0) {
              valor = (parseInt(valor) / 100).toLocaleString("pt-BR", {
                style: "currency",
                currency: "BRL",
              });
              e.target.value = valor;
            } else {
              e.target.value = "R$ 0,00";
            }
          }
          adjustWidth();
        });

        // Ajustar largura inicial
        adjustWidth();
      });
    </script>
        <!-- ✅ SCRIPT PARA ATUALIZAR PLACAR DO DIA (GREEN E RED) -->
    <script>
      // ✅ FUNÇÃO PARA ATUALIZAR PLACAR COM GREEN E RED DO DIA
      function atualizarPlacarDia() {
        // Obter valor do UND (input)
        const undInput = document.getElementById('resumo-valor-und-input');
        const valorUndText = undInput ? undInput.value.replace(/[^\d,]/g, '').replace(',', '.') : '100';
        const valorUnd = parseFloat(valorUndText) || 100;
        
        // Função auxiliar para formatar moeda
        function formatarMoeda(valor) {
          return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
          }).format(valor);
        }
        
        fetch('obter-placar-dia.php')
          .then(response => response.json())
          .then(data => {
            if (data.success && data.apostas) {
              console.log('✅ Dados das apostas recebidos:', data.apostas);
              console.log('💰 Valor UND: ' + formatarMoeda(valorUnd));
              
              // ========== ATUALIZAR APOSTA 1: +1⚽GOL ==========
              const aposta1 = data.apostas.aposta_1;
              if (aposta1) {
                document.getElementById('placar-1-green').textContent = aposta1.green;
                document.getElementById('placar-1-red').textContent = aposta1.red;
                
                // Calcular valor final
                const lucro1 = (aposta1.lucro_coef_green + aposta1.lucro_coef_red) * valorUnd;
                const valor1Elem = document.getElementById('valor-1-final');
                if (valor1Elem) {
                  valor1Elem.textContent = formatarMoeda(lucro1);
                  valor1Elem.style.color = lucro1 >= 0 ? '#4ade80' : '#eea7ad';
                }
                
                console.log(`📊 Aposta 1 (+1⚽GOL): ${aposta1.green} Green, ${aposta1.red} Red | Lucro: ${formatarMoeda(lucro1)}`);
              }
              
              // ========== ATUALIZAR APOSTA 2: +0.5⚽GOL ==========
              const aposta2 = data.apostas.aposta_2;
              if (aposta2) {
                document.getElementById('placar-2-green').textContent = aposta2.green;
                document.getElementById('placar-2-red').textContent = aposta2.red;
                
                // Calcular valor final
                const lucro2 = (aposta2.lucro_coef_green + aposta2.lucro_coef_red) * valorUnd;
                const valor2Elem = document.getElementById('valor-2-final');
                if (valor2Elem) {
                  valor2Elem.textContent = formatarMoeda(lucro2);
                  valor2Elem.style.color = lucro2 >= 0 ? '#4ade80' : '#eea7ad';
                }
                
                console.log(`📊 Aposta 2 (+0.5⚽GOL): ${aposta2.green} Green, ${aposta2.red} Red | Lucro: ${formatarMoeda(lucro2)}`);
              }
              
              // ========== ATUALIZAR APOSTA 3: +1⛳️CANTOS ==========
              const aposta3 = data.apostas.aposta_3;
              if (aposta3) {
                document.getElementById('placar-3-green').textContent = aposta3.green;
                document.getElementById('placar-3-red').textContent = aposta3.red;
                
                // Calcular valor final
                const lucro3 = (aposta3.lucro_coef_green + aposta3.lucro_coef_red) * valorUnd;
                const valor3Elem = document.getElementById('valor-3-final');
                if (valor3Elem) {
                  valor3Elem.textContent = formatarMoeda(lucro3);
                  valor3Elem.style.color = lucro3 >= 0 ? '#4ade80' : '#eea7ad';
                }
                
                console.log(`📊 Aposta 3 (+1⛳️CANTOS): ${aposta3.green} Green, ${aposta3.red} Red | Lucro: ${formatarMoeda(lucro3)}`);
              }
              
              // ========== ATUALIZAR TOTAL ==========
              if (data.total) {
                document.getElementById('placar-total-green').textContent = data.total.green;
                document.getElementById('placar-total-red').textContent = data.total.red;
                
                // Calcular valor final total
                const lucroTotal = (data.total.lucro_coef_green + data.total.lucro_coef_red) * valorUnd;
                const valorTotalElem = document.getElementById('valor-total-final');
                if (valorTotalElem) {
                  valorTotalElem.textContent = formatarMoeda(lucroTotal);
                  valorTotalElem.style.color = lucroTotal >= 0 ? '#4ade80' : '#eea7ad';
                }
                
                console.log(`📊 Total do dia: ${data.total.green} Green, ${data.total.red} Red | Lucro Total: ${formatarMoeda(lucroTotal)}`);
              }
            } else {
              console.warn('⚠️ Dados inválidos ou sem sucesso:', data);
            }
          })
          .catch(error => console.error('❌ Erro ao carregar placar:', error));
      }
      
      // Carregar ao abrir página
      document.addEventListener('DOMContentLoaded', function() {
        setTimeout(function() {
          atualizarPlacarDia();
        }, 500);
      });
      
      // Atualizar a cada 10 segundos
      setInterval(atualizarPlacarDia, 10000);
      
      // ✅ ADICIONAR LISTENER PARA MUDANÇAS NO INPUT UND
      const undInput = document.getElementById('resumo-valor-und-input');
      if (undInput) {
        undInput.addEventListener('change', function() {
          console.log('💰 Valor UND alterado, recalculando placares...');
          setTimeout(atualizarPlacarDia, 100);
        });
        
        undInput.addEventListener('blur', function() {
          console.log('💰 Input UND perdeu foco, recalculando placares...');
          setTimeout(atualizarPlacarDia, 100);
        });
      }
    </script>

    <script src="js/rotulo-lucro-dinamico.js?v=<?php echo time(); ?>" defer></script>

    <!-- ✅ SCRIPT PARA SALVAR MENSAGENS NO BANCO DE DADOS -->
    <script src="js/telegram-salvar-bote.js?v=<?php echo time(); ?>" defer></script>

    <!-- ✅ SCRIPT PARA CARREGAR MENSAGENS DO TELEGRAM -->
    <script src="js/telegram-mensagens.js?v=<?php echo time(); ?>" defer></script>

    <!-- ✅ SCRIPT PARA CARREGAR DADOS DINÂMICOS - CLONE DE HOME.PHP -->
    <script>
    // ✅ ADICIONAR LISTENERS PARA DATA E UND NA INICIALIZAÇÃO
    document.addEventListener('DOMContentLoaded', function() {
      // Atualizar Data
      function atualizarData() {
        const hoje = new Date();
        const diasSemana = ['Domingo', 'Segunda-Feira', 'Terça-Feira', 'Quarta-Feira', 'Quinta-Feira', 'Sexta-Feira', 'Sábado'];
        const dia = String(hoje.getDate()).padStart(2, '0');
        const mes = String(hoje.getMonth() + 1).padStart(2, '0');
        const diaSemana = diasSemana[hoje.getDay()];
        
        const textoDia = `${diaSemana} - ${dia}/${mes}`;
        const resumoDiaData = document.getElementById('resumo-dia-data');
        if (resumoDiaData) {
          resumoDiaData.textContent = textoDia;
        }
      }
      
      atualizarData();
      
      // Atualizar UND a cada 30 segundos
      setInterval(atualizarData, 30000);
    });

    /*
    // ✅ SCRIPT ANTIGO - AGORA SUBSTITUÍDO PELO NOVO SISTEMA
    // ✅ FUNÇÃO PARA FORMATAR MOEDA NA ENTRADA
    function formatarMoedaAoDigitar(input) {
      let valor = input.value.replace(/\D/g, '');
      
      if (valor.length === 0) {
        input.value = 'R$ 0,00';
        return;
      }
      
      // Converter para número com 2 casas decimais
      valor = (parseInt(valor) / 100).toLocaleString('pt-BR', {
        style: 'currency',
        currency: 'BRL'
      });
      
      input.value = valor;
    }

    // ✅ ADICIONAR LISTENER NA ENTRADA DE UND
    document.addEventListener('DOMContentLoaded', function() {
      const undInput = document.getElementById('resumo-valor-und-input');
      if (undInput) {
        // Formatar ao digitar
        undInput.addEventListener('input', function(e) {
          formatarMoedaAoDigitar(e.target);
        });
        
        // Remover máscara ao focar
        undInput.addEventListener('focus', function(e) {
          const valorLimpo = e.target.value.replace(/\D/g, '');
          e.target.value = valorLimpo;
        });
        
        // Recolocar máscara ao sair
        undInput.addEventListener('blur', function(e) {
          if (e.target.value === '') {
            e.target.value = 'R$ 0,00';
          } else {
            formatarMoedaAoDigitar(e.target);
          }
        });
      }
    });

    // ✅ FUNÇÃO PARA ATUALIZAR RESUMO COM DATA E UND
    function atualizarResumoDiaEUnd() {
      // Atualizar data
      const hoje = new Date();
      const diasSemana = ['Domingo', 'Segunda-Feira', 'Terça-Feira', 'Quarta-Feira', 'Quinta-Feira', 'Sexta-Feira', 'Sábado'];
      const dia = String(hoje.getDate()).padStart(2, '0');
      const mes = String(hoje.getMonth() + 1).padStart(2, '0');
      const ano = hoje.getFullYear();
      const diaSemana = diasSemana[hoje.getDay()];
      
      const textoDia = `${diaSemana} - ${dia}/${mes}`;
      const resumoDiaData = document.getElementById('resumo-dia-data');
      if (resumoDiaData) {
        resumoDiaData.textContent = textoDia;
        console.log('📅 Data atualizada:', textoDia);
      }
      
      // Obter valor da unidade
      const undInput = document.getElementById('resumo-valor-und-input');
      if (!undInput) {
        console.error('❌ Elemento #resumo-valor-und-input não encontrado');
        return;
      }
      
      // ✅ NÃO SOBRESCREVER se o usuário já digitou um valor diferente
      const valorAtual = undInput.value.replace(/\D/g, '');
      const valorPadrao = 'R$ 0,00'.replace(/\D/g, '');
      
      // Se o usuário já mudou o valor, não sobrescrever
      if (valorAtual !== valorPadrao && valorAtual !== '') {
        console.log('✅ Valor já foi editado pelo usuário, não sobrescrevendo:', undInput.value);
        return;
      }
      
      // Tentar obter valor da unidade do localStorage
      let valorUnd = localStorage.getItem('valor-unidade');
      
      if (valorUnd) {
        // Se tiver no localStorage, usar
        undInput.value = valorUnd;
        console.log('💾 UND do localStorage:', valorUnd);
      } else {
        // Se não tiver, fazer fetch para obter-und.php
        console.log('🔄 Buscando UND do servidor...');
        fetch('obter-und.php')
          .then(response => {
            if (!response.ok) {
              throw new Error('Erro na resposta: ' + response.status);
            }
            return response.json();
          })
          .then(data => {
            console.log('📡 Resposta recebida:', data);
            
            if (data.success && data.valor_formatado) {
              undInput.value = data.valor_formatado;
              console.log('✅ UND atualizado:', data.valor_formatado);
              
              // Salvar no localStorage
              localStorage.setItem('valor-unidade', data.valor_formatado);
            } else {
              undInput.value = data.valor_formatado || 'R$ 0,00';
              console.warn('⚠️ Dados não carregados, usando padrão:', data.valor_formatado);
            }
          })
          .catch(error => {
            console.error('❌ Erro ao obter UND:', error);
            undInput.value = 'R$ 0,00';
          });
      }
    }
    */

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
    setInterval(function() {
      carregarDadosBancaELucro();
    }, 30000);
    </script>

    <!-- ✅ SCRIPT PARA MODAL DE HISTÓRICO DE RESULTADOS -->
    <script src="js/modal-historico-resultados.js?v=<?php echo time(); ?>" defer></script>

    <!-- ✅ SCRIPT CAROUSEL RESPONSIVO PARA BLOCOS -->
    <script src="js/carousel-blocos.js" defer></script>
</body>
</html>