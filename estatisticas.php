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
    </style>
</head>
<body>
    <header class="header"></header>
    
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

                            <!-- Área dos mentores -->
                            <div id="listaMentores" class="mentor-wrapper">
                                <!-- Aqui será inserido o conteúdo PHP dos mentores -->
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

                    <!-- Lista de dias do mês -->
                    <div class="lista-dias">
                        <!-- Aqui será inserido o conteúdo PHP da lista de dias -->
                        <div id="dados-mes-info" style="display: none;">
                        </div>
                    </div>
                    </div>
                </div>
                
            </div>
            
            <!-- BLOCO 3 -->
            <div class="bloco bloco-3">
                <h3>Bloco 3</h3>
                <p>Este terceiro e último bloco completa a estrutura horizontal. Todos os blocos trabalham em conjunto para formar um layout coeso e funcional.</p>
                <p>O design responsivo garante que a estrutura funcione perfeitamente em zoom de 90%, 100%, 125% e outras configurações, sempre mantendo todos os elementos visíveis na tela.</p>
                <p>A altura fixa garante que mesmo com pouco conteúdo, o bloco ocupe todo o espaço vertical disponível.</p>
                <p>Conteúdo adicional pode ser adicionado e será scrollável dentro do bloco, mantendo a estrutura geral intacta.</p>
            </div>
            
        </div>
    </main>
    
    <footer class="footer"></footer>
</body>
</html>