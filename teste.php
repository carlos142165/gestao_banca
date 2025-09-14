<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estrutura Responsiva</title>
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

        /* Estilos para o topo menu */
        .menu-topo-container {
            background-color: #113647;
            height: 80px;
            width: 100%;
            position: fixed;
            top: 0;
            z-index: 1000;
        }

        #top-bar {
            height: 100%;
            display: flex;
            align-items: center;
        }

        .menu-container {
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }

        .menu-button {
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
        }

        .menu-content {
            display: flex;
            gap: 20px;
        }

        .menu-content a {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 4px;
            transition: background-color 0.3s;
        }

        .menu-content a:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .menu-icon {
            font-size: 16px;
        }

        #lista-mentores {
            color: white;
        }

        .valor-item-menu {
            display: flex;
            align-items: center;
        }

        .valor-info-wrapper {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .valor-label-linha {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }

        .valor-icone-tema {
            font-size: 16px;
        }

        .valor-label {
            color: #ccc;
        }

        .valor-bold-menu {
            color: white;
            font-weight: bold;
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

        /* Classes específicas dos blocos */
        .bloco-1 {
            /* Estilos específicos do bloco 1 */
        }

        .bloco-2 {
            /* Estilos específicos do bloco 2 */
        }

        .bloco-3 {
            /* Mantido como bloco genérico */
        }

        /* Classes do seu código existente */
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

        .erro-mentores {
            /* Estilos para erro de mentores */
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
                width: calc(50% - 15px);
                min-width: 350px;
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
                height: calc(50vh - 40px);
                min-height: 300px;
                margin-bottom: 20px;
            }
            
            .bloco:last-child {
                margin-bottom: 0;
            }

            .menu-content {
                display: none;
            }

            .menu-content.show {
                display: flex;
                flex-direction: column;
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background-color: #113647;
                padding: 20px;
                border-top: 1px solid rgba(255, 255, 255, 0.1);
            }
        }

        @media screen and (max-width: 768px) {
            .main-content {
                padding: 15px;
            }
            
            .bloco {
                padding: 15px;
                height: calc(50vh - 35px);
                min-height: 250px;
            }

            .menu-container {
                padding: 0 15px;
            }

            .valor-info-wrapper {
                font-size: 12px;
            }
        }

        @media screen and (max-width: 480px) {
            .menu-topo-container {
                height: 70px;
            }

            .footer {
                height: 70px;
            }
            
            .main-content {
                top: 70px;
                bottom: 70px;
                padding: 10px;
            }
            
            .bloco {
                padding: 12px;
                height: calc(50vh - 30px);
                min-height: 200px;
            }

            .menu-container {
                padding: 0 10px;
            }

            .valor-label-linha {
                font-size: 11px;
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
    </style>
</head>
<body>
    <!-- TOPO MENU -->
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
            <a href="estatisticas.php">
              <i class="fas fa-chart-bar menu-icon"></i><span>Estatísticas</span>
            </a>
            <a href="painel-controle.php">
              <i class="fas fa-cogs menu-icon"></i><span>Painel de Controle</span>
            </a>
            <?php if (isset($_SESSION['usuario_id'])): ?>
              <a href="logout.php">
                <i class="fas fa-sign-out-alt menu-icon"></i><span>Sair</span>
              </a>
            <?php endif; ?>
          </div>

          <!-- Área do saldo da banca (canto direito) -->
          <div id="lista-mentores">
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
                  <i class="fa-solid fa-money-bill-trend-up valor-icone-tema"></i>
                  <span class="valor-label" id="lucro_entradas_rotulo">Lucro:</span>
                  <span class="valor-bold-menu" id="lucro_valor_entrada">R$ 0,00</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <main class="main-content">
        <div class="container">
            
            <!-- BLOCO 1 -->
            <div class="bloco bloco-1">
                <div class="container-resumos">
                    <!-- Widget Meta com seu código PHP integrado -->
                    <div class="widget-meta-container">
                        <div class="widget-meta-row">
                            <div class="widget-meta-item" id="widget-meta">
                                
                                <!-- Header com data e placar integrados -->
                              <div class="data-header-integrada" id="data-header">
                                 <div class="data-texto-compacto">
                                 <i class="fa-solid fa-calendar-days"></i>
                                 <span class="data-principal-integrada" id="data-atual"></span>
                              </div>
                                    
                                    <!-- Caixas de seleção de período -->
                                    <div class="periodo-selecao-container">
                                        <div class="periodo-opcao">
                                            <label class="periodo-label">
                                                <input type="radio" name="periodo" value="dia" class="periodo-radio" checked>
                                                <span class="periodo-texto">DIA</span>
                                            </label>
                                        </div>
                                        <div class="periodo-opcao">
                                            <label class="periodo-label">
                                                <input type="radio" name="periodo" value="mes" class="periodo-radio">
                                                <span class="periodo-texto">MÊS</span>
                                            </label>
                                        </div>
                                        <div class="periodo-opcao">
                                            <label class="periodo-label">
                                                <input type="radio" name="periodo" value="ano" class="periodo-radio">
                                                <span class="periodo-texto">ANO</span>
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <!-- Espaço para equilíbrio -->
                                    <div class="espaco-equilibrio"></div>
                                    
                                    <div class="data-separador-mini"></div>
                                    
                                    <div class="status-periodo-mini" id="status-periodo">
                                        <!-- Status período será preenchido via JS -->
                                    </div>
                                </div>

                        <!-- Conteúdo principal do widget -->
                        <div class="widget-conteudo-principal">
                          <div class="conteudo-left">
                             <!-- Valor da Meta -->
                        <div class="widget-meta-valor" id="meta-valor">
                            <i class="fa-solid fa-coins"></i>
                            <div class="meta-valor-container">
                                <span class="valor-texto" id="valor-texto-meta">carregando..</span>
                            </div>
                        </div>
                            
                             <!-- Exibição do valor que ultrapassou a meta -->
                             <div class="valor-ultrapassou" id="valor-ultrapassou" style="display: none;">
                                <i class="fa-solid fa-trophy"></i>
                                <span class="texto-ultrapassou">Lucro Extra: <span id="valor-extra">R$ 0,00</span></span>
                             </div>
                            
                             <!-- RÓTULO -->
                             <div class="widget-meta-rotulo" id="rotulo-meta">Meta do Dia</div>
                            
                             <!-- Container da Barra de Progresso -->
                             <div class="widget-barra-container">
                                <div class="widget-barra-progresso" id="barra-progresso"></div>
                                <div class="porcentagem-barra" id="porcentagem-barra">0%</div>
                             </div>
                            
                             <!-- Info de progresso com saldo -->
                              <div class="widget-info-progresso">
                              <span id="saldo-info" class="saldo-positivo">
                             <i class="fa-solid fa-chart-line"></i>
                             <span class="saldo-info-rotulo">Lucro:</span>
                             <span class="saldo-info-valor">R$ 75,00</span>
                             </span>
                            </div>
                            </div>
                        </div>
                            </div>
                        </div>
                    </div>
                </div>
                        
                        <!-- Campo Mentores -->
                        <div class="campo_mentores">
                            <!-- Barra superior -->
                            <div class="barra-superior">
                                <button class="btn-add-usuario" onclick="prepararFormularioNovoMentor()">
                                    <i class="fas fa-user-plus"></i>
                                </button>
                                
                                <div class="area-central">
                                    <div class="pontuacao" id="pontuacao">
                                        <span class="placar-green">0</span>
                                        <span class="separador">x</span>
                                        <span class="placar-red">0</span>
                                    </div>
                                </div>

                                <div class="area-direita">
                                    <div class="valor-dinamico valor-diaria">
                                        <i class="fas fa-university"></i>
                                        <span id="porcentagem-diaria">Carregando...</span>
                                    </div>
                                    <div class="valor-dinamico valor-unidade">
                                        <span class="rotulo-und">UND:</span>
                                        <span id="valor-unidade">Carregando...</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Área dos mentores - SEU CÓDIGO PHP ORIGINAL -->
                            <div id="listaMentores" class="mentor-wrapper">
                                <?php
                                try {
                                  // Consulta para buscar mentores e seus valores
                                  $sql = "
                                    SELECT m.id, m.nome, m.foto,
                                           COALESCE(SUM(v.green), 0) AS total_green,
                                           COALESCE(SUM(v.red), 0) AS total_red,
                                           COALESCE(SUM(v.valor_green), 0) AS total_valor_green,
                                           COALESCE(SUM(v.valor_red), 0) AS total_valor_red
                                    FROM mentores m
                                    LEFT JOIN valor_mentores v ON m.id = v.id_mentores
                                    WHERE m.id_usuario = ?
                                    GROUP BY m.id, m.nome, m.foto
                                    ORDER BY (COALESCE(SUM(v.valor_green), 0) - COALESCE(SUM(v.valor_red), 0)) DESC
                                  ";

                                  $stmt = $conexao->prepare($sql);
                                  $stmt->bind_param("i", $id_usuario_logado);
                                  $stmt->execute();
                                  $result = $stmt->get_result();

                                  $lista_mentores = [];
                                  $total_geral_saldo = 0;

                                  while ($mentor = $result->fetch_assoc()) {
                                    $total_subtraido = floatval($mentor['total_valor_green']) - floatval($mentor['total_valor_red']);
                                    $mentor['saldo'] = $total_subtraido;
                                    $lista_mentores[] = $mentor;
                                    $total_geral_saldo += $total_subtraido;
                                  }

                                  foreach ($lista_mentores as $posicao => $mentor) {
                                    $rank = $posicao + 1;
                                    $saldo_formatado = number_format($mentor['saldo'], 2, ',', '.');
                                    $nome_seguro = htmlspecialchars($mentor['nome']);
                                    
                                    // Verificação da foto do mentor
                                    $foto_original = $mentor['foto'];
                                    if (empty($foto_original) || $foto_original === 'avatar-padrao.png') {
                                      $foto_path = 'https://cdn-icons-png.flaticon.com/512/847/847969.png';
                                    } else {
                                      $foto_path = 'uploads/' . htmlspecialchars($foto_original);
                                      if (!file_exists($foto_path)) {
                                        $foto_path = 'https://cdn-icons-png.flaticon.com/512/847/847969.png';
                                      }
                                    }

                                    // Determina a cor da borda baseada no saldo
                                    if ($mentor['saldo'] == 0) {
                                      $classe_borda = 'card-neutro';
                                    } elseif ($mentor['saldo'] > 0) {
                                      $classe_borda = 'card-positivo';
                                    } else {
                                      $classe_borda = 'card-negativo';
                                    }

                                    echo "
                                    <div class='mentor-item'>
                                      <div class='mentor-rank-externo'>{$rank}º</div>

                                      <div class='mentor-card {$classe_borda}' 
                                           data-nome='{$nome_seguro}'
                                           data-foto='{$foto_path}'
                                           data-id='{$mentor['id']}'>
                                        <div class='mentor-header'>
                                          <img src='{$foto_path}' alt='Foto de {$nome_seguro}' class='mentor-img' 
                                               onerror=\"this.src='https://cdn-icons-png.flaticon.com/512/847/847969.png'\" />
                                          <h3 class='mentor-nome'>{$nome_seguro}</h3>
                                        </div>
                                        <div class='mentor-right'>
                                          <div class='mentor-values-inline'>
                                            <div class='value-box-green green'>
                                              <p>Green</p>
                                              <p>{$mentor['total_green']}</p>
                                            </div>
                                            <div class='value-box-red red'>
                                              <p>Red</p>
                                              <p>{$mentor['total_red']}</p>
                                            </div>
                                            <div class='value-box-saldo saldo'>
                                              <p>Saldo</p>
                                              <p>R$ {$saldo_formatado}</p>
                                            </div>
                                          </div>
                                        </div>
                                      </div>

                                      <div class='mentor-menu-externo'>
                                        <span class='menu-toggle' title='Mais opções'>⋮</span>
                                        <div class='menu-opcoes'>
                                          <button onclick='editarAposta({$mentor["id"]})'>
                                            <i class='fas fa-trash'></i> Excluir Entrada
                                          </button>
                                          <button onclick='editarMentor({$mentor["id"]})'>
                                            <i class='fas fa-user-edit'></i> Editar Mentor
                                          </button>
                                        </div>
                                      </div>
                                    </div>
                                    ";
                                  }

                                  // Elementos auxiliares para cálculos JavaScript
                                  echo "
                                  <div id='total-green-dia' data-green='" . array_sum(array_column($lista_mentores, 'total_green')) . "' style='display:none;'></div>
                                  <div id='total-red-dia' data-red='" . array_sum(array_column($lista_mentores, 'total_red')) . "' style='display:none;'></div>
                                  <div id='saldo-dia' data-total='" . number_format($total_geral_saldo, 2, ',', '.') . "' style='display:none;'></div>
                                  ";
                                  
                                } catch (Exception $e) {
                                  echo "<div class='erro-mentores'>Erro ao carregar mentores!</div>";
                                  error_log("Erro ao carregar mentores: " . $e->getMessage());
                                }
                                ?>
                            </div>
                        </div>
            </div>
            
            <!-- BLOCO 2 -->
            <div class="bloco bloco-2">
                <div class="resumo-mes">
                    <!-- Cabeçalho fixo com metas mensais -->
                    <div class="bloco-meta-simples fixo-topo">
                      <div class="campo-armazena-data-placar">
                       <!-- Título do mês atual -->
                       <h2 class="titulo-bloco">
                        <i class="fas fa-calendar-alt"></i> <span id="tituloMes"></span>
                       </h2>

                       <script>
                        const meses = [
                          "JANEIRO", "FEVEREIRO", "MARÇO", "ABRIL", "MAIO", "JUNHO",
                          "JULHO", "AGOSTO", "SETEMBRO", "OUTUBRO", "NOVEMBRO", "DEZEMBRO"
                        ];
                        const hoje = new Date();
                        const mesAtual = meses[hoje.getMonth()];
                        const anoAtual = hoje.getFullYear();
                        document.getElementById("tituloMes").textContent = `${mesAtual} ${anoAtual}`;
                       </script>

                        <div class="area-central-2">
                            <div class="pontuacao-2" id="pontuacao-2">
                                <span class="placar-green-2">0</span>
                                <span class="separador-2">×</span>
                                <span class="placar-red-2">0</span>
                            </div>
                        </div>          
                       </div>

                <!-- Conteúdo principal do widget 2 -->
                <div class="widget-conteudo-principal-2">
                  <div class="conteudo-left-2">
                     <!-- Valor da Meta 2 -->
                 <div class="widget-meta-valor-2" id="meta-valor-2">
                    <i class="fa-solid-2 fa-coins-2"></i>
                    <div class="meta-valor-container-2">
                        <span class="valor-texto-2" id="valor-texto-meta-2">carregando..</span>
                    </div>
                 </div>
                    
                     <!-- Exibição do valor que ultrapassou a meta 2 -->
                     <div class="valor-ultrapassou-2" id="valor-ultrapassou-2" style="display: none;">
                        <i class="fa-solid-2 fa-trophy-2"></i>
                        <span class="texto-ultrapassou-2">Lucro Extra: <span id="valor-extra-2">R$ 0,00</span></span>
                     </div>
                    
                     <!-- RÓTULO 2 -->
                     <div class="widget-meta-rotulo-2" id="rotulo-meta-2">Meta do Dia</div>
                    
                     <!-- Container da Barra de Progresso 2 -->
                     <div class="widget-barra-container-2">
                        <div class="widget-barra-progresso-2" id="barra-progresso-2"></div>
                        <div class="porcentagem-barra-2" id="porcentagem-barra-2">0%</div>
                     </div>
                    
                     <!-- Info de progresso com saldo 2 -->
                      <div class="widget-info-progresso-2">
                      <span id="saldo-info-2" class="saldo-positivo-2">
                     <i class="fa-solid-2 fa-chart-line-2"></i>
                     <span class="saldo-info-rotulo-2">Lucro:</span>
                     <span class="saldo-info-valor-2">carregando..</span>
                     </span>
                    </div>
                    </div>
                </div>

                    <!-- Lista de dias do mês com resultados -->
                    <div class="lista-dias">
                        <?php
                        // Obter configurações de meta
                        $meta_diaria = isset($_SESSION['meta_diaria']) ? floatval($_SESSION['meta_diaria']) : 0;
                        $meta_mensal = isset($_SESSION['meta_mensal']) ? floatval($_SESSION['meta_mensal']) : 0;
                        $meta_anual = isset($_SESSION['meta_anual']) ? floatval($_SESSION['meta_anual']) : 0;

                        // Determinar qual meta usar baseado no período atual (se disponível)
                        $periodo_atual = $_SESSION['periodo_filtro'] ?? 'dia';
                        $meta_atual = ($periodo_atual === 'mes') ? $meta_mensal : 
                                      (($periodo_atual === 'ano') ? $meta_anual : $meta_diaria);

                        // Obter data atual
                        $hoje = date('Y-m-d');

                        // Obter primeiro e último dia do mês atual
                        $mes_atual = date('m');
                        $ano_atual = date('Y');
                        $total_dias_mes = date('t');

                        // Loop através de TODOS os dias do mês
                        for ($dia = 1; $dia <= $total_dias_mes; $dia++) {
                            $dia_formatado = str_pad($dia, 2, '0', STR_PAD_LEFT);
                            $data_mysql = $ano_atual . '-' . $mes_atual . '-' . $dia_formatado;
                            $data_exibicao = $dia_formatado . '/' . $mes_atual . '/' . $ano_atual;
                            
                            // Buscar dados do dia (se existirem)
                            $dados_dia = isset($dados_por_dia[$data_mysql]) ? $dados_por_dia[$data_mysql] : [
                                'total_valor_green' => 0,
                                'total_valor_red' => 0,
                                'total_green' => 0,
                                'total_red' => 0
                            ];
                            
                            // Calcular saldo do dia
                            $saldo_dia = floatval($dados_dia['total_valor_green']) - floatval($dados_dia['total_valor_red']);
                            $saldo_formatado = number_format($saldo_dia, 2, ',', '.');
                            
                            // Verificar meta batida SEMPRE baseada na meta DIÁRIA, não no período atual
                            $meta_batida = false;
                            
                            // SEMPRE usar a meta diária para verificar se foi batida, independente do período selecionado
                            if ($meta_diaria > 0 && $saldo_dia >= $meta_diaria) {
                                $meta_batida = true;
                            }
                            
                            // Para dias passados com saldo positivo, considerar meta batida
                            if (!$meta_batida && $data_mysql < $hoje && $saldo_dia > 0) {
                                // Se não há meta diária configurada, mas tem saldo positivo em dia passado
                                if ($meta_diaria <= 0) {
                                    $meta_batida = true;
                                }
                                // Ou se o saldo é significativamente positivo (backup)
                                elseif ($saldo_dia >= ($meta_diaria * 0.8)) {
                                    // Considera 80% da meta como "praticamente batida" para days passados
                                    $meta_batida = true;
                                }
                            }
                            
                            // Determinar classe de cor baseada no saldo
                            $classe_valor_cor = '';
                            if ($saldo_dia > 0) {
                                $classe_valor_cor = 'valor-positivo';
                            } elseif ($saldo_dia < 0) {
                                $classe_valor_cor = 'valor-negativo';
                            } else {
                                $classe_valor_cor = 'valor-zero';
                            }
                            
                            // Determinar cores e classes dos elementos internos
                            $cor_valor = ($saldo_dia == 0) ? 'texto-cinza' : ($saldo_dia > 0 ? 'verde-bold' : 'vermelho-bold');
                            $classe_texto = ($saldo_dia == 0) ? 'texto-cinza' : '';
                            $placar_cinza = ((int)$dados_dia['total_green'] === 0 && (int)$dados_dia['total_red'] === 0) ? 'texto-cinza' : '';
                            
                            // Classes do dia
                            $classes_dia = [];
                            
                            if ($data_mysql === $hoje) {
                                $classes_dia[] = 'gd-dia-hoje';
                                $classes_dia[] = ($saldo_dia >= 0) ? 'gd-borda-verde' : 'gd-borda-vermelha';
                            } else {
                                $classes_dia[] = 'dia-normal';
                            }
                            
                            // Destaque para dias passados
                            if ($data_mysql < $hoje) {
                                if ($saldo_dia > 0) {
                                    $classes_dia[] = 'gd-dia-destaque';
                                } elseif ($saldo_dia < 0) {
                                    $classes_dia[] = 'gd-dia-destaque-negativo';
                                }
                                
                                // Classe para dias sem valor
                                if ((int)$dados_dia['total_green'] === 0 && (int)$dados_dia['total_red'] === 0) {
                                    $classes_dia[] = 'gd-dia-sem-valor';
                                }
                            }
                            
                            // Dias futuros
                            if ($data_mysql > $hoje) {
                                $classes_dia[] = 'dia-futuro';
                            }
                            
                            // DEFINIR ÍCONE: Sempre baseado na meta DIÁRIA batida, não no período
                            $icone_classe = $meta_batida ? 'fa-trophy trofeu-icone' : 'fa-check';
                            
                            // Montar string de classes (incluindo a classe de cor)
                            $classe_dia_string = 'gd-linha-dia ' . $classe_valor_cor . ' ' . implode(' ', $classes_dia);
                            $data_meta_attr = $meta_batida ? 'true' : 'false';
                            
                            // ADICIONAR ATRIBUTOS EXTRAS para o JavaScript identificar facilmente
                            $data_saldo_attr = $saldo_dia;
                            $data_meta_diaria_attr = $meta_diaria;
                            
                            // HTML com classes CSS aplicadas e atributos extras
                            echo '
                            <div class="'.$classe_dia_string.'" 
                                 data-date="'.$data_mysql.'" 
                                 data-meta-batida="'.$data_meta_attr.'"
                                 data-saldo="'.$data_saldo_attr.'"
                                 data-meta-diaria="'.$data_meta_diaria_attr.'"
                                 data-periodo-atual="'.$periodo_atual.'">
                                <span class="data '.$classe_texto.'">'.$data_exibicao.'</span>

                                <div class="placar-dia">
                                    <span class="placar verde-bold '.$placar_cinza.'">'.(int)$dados_dia['total_green'].'</span>
                                    <span class="placar separador '.$placar_cinza.'">×</span>
                                    <span class="placar vermelho-bold '.$placar_cinza.'">'.(int)$dados_dia['total_red'].'</span>
                                </div>

                                <span class="valor '.$cor_valor.'">R$ '.$saldo_formatado.'</span>

                                <span class="icone '.$classe_texto.'">
                                    <i class="fa-solid '.$icone_classe.'"></i>
                                </span>
                            </div>';
                        }
                        ?>

                        <!-- Elemento oculto para informações de estado (necessário para o JavaScript) -->
                        <div id="dados-mes-info" style="display: none;" 
                             data-mes="<?php echo $mes_atual; ?>" 
                             data-ano="<?php echo $ano_atual; ?>" 
                             data-meta-diaria="<?php echo $meta_diaria; ?>"
                             data-meta-mensal="<?php echo $meta_mensal; ?>"
                             data-meta-anual="<?php echo $meta_anual; ?>"
                             data-periodo-atual="<?php echo $periodo_atual; ?>"
                             data-hoje="<?php echo $hoje; ?>">
                        </div>

                        <!-- Script inline para reforçar a lógica de troféus -->
                        <script>
                        // Garantir que as informações de meta batida sejam preservadas
                        document.addEventListener('DOMContentLoaded', function() {
                            console.log('📊 Verificando consistência de troféus após carregamento PHP...');
                            
                            // Verificar todas as linhas e marcar no cache do MonitorContinuo se existir
                            const linhas = document.querySelectorAll('.gd-linha-dia');
                            linhas.forEach(linha => {
                                const dataLinha = linha.getAttribute('data-date');
                                const metaBatida = linha.getAttribute('data-meta-batida') === 'true';
                                const saldo = parseFloat(linha.getAttribute('data-saldo')) || 0;
                                
                                if (dataLinha && metaBatida) {
                                    console.log(`✅ PHP marcou ${dataLinha} como meta batida (saldo: R$ ${saldo.toFixed(2)})`);
                                    
                                    // Se MonitorContinuo já existe, adicionar ao cache
                                    if (window.MonitorContinuo && window.MonitorContinuo.marcarMetaBatida) {
                                        setTimeout(() => {
                                            window.MonitorContinuo.marcarMetaBatida(dataLinha);
                                        }, 100);
                                    }
                                }
                            });
                            
                            console.log(`📊 Verificação concluída - ${linhas.length} linhas processadas`);
                        });
                        </script>
                    </div>
                    </div>
                </div>
            </div>
            
        </div>
    </main>
    
    <footer class="footer"></footer>

    <script>
        // Função para toggle do menu mobile
        function toggleMenu() {
            const menu = document.getElementById('menu');
            menu.classList.toggle('show');
        }

        // Funções placeholder para seu código PHP
        function prepararFormularioNovoMentor() {
            console.log('Preparar formulário novo mentor');
        }

        function editarAposta(id) {
            console.log('Editar aposta:', id);
        }

        function editarMentor(id) {
            console.log('Editar mentor:', id);
        }
    </script>
</body>
</html>