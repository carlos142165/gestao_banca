<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Layout com 3 Containers</title>

<style>
        * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: Arial, sans-serif;
      background-color: #f0f0f0;
    }

    .container-wrapper {
      display: flex;
      justify-content: center;
      gap: 30px;
      padding: 25px 0;
      height: calc(100vh - 50px);
    }

    .container-box {
      width: 420px;
      height: 100%;
      background-color: #ffffff;
      border: 1px solid #ccc;
      border-radius: 8px;
      padding: 15px;
      overflow-y: auto;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    /* ===== ESTILOS DO BLOCO 1 ===== */
    .bloco-1 {
      width: 100%;
      height: 100%;
    }

    .container-resumos {
      height: 100%;
      display: flex;
      flex-direction: column;
    }

    .widget-meta-container {
      flex: 1;
      display: flex;
      flex-direction: column;
    }

    .widget-meta-row {
      flex: 1;
      display: flex;
      flex-direction: column;
    }

    .widget-meta-item {
      flex: 1;
      display: flex;
      flex-direction: column;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      border-radius: 15px;
      padding: 20px;
      color: white;
      margin-bottom: 20px;
    }

    .data-header-integrada {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      flex-wrap: wrap;
    }

    .data-texto-compacto {
      display: flex;
      align-items: center;
      gap: 8px;
      font-weight: bold;
      font-size: 16px;
    }

    .periodo-selecao-container {
      display: flex;
      gap: 10px;
    }

    .periodo-opcao {
      position: relative;
    }

    .periodo-label {
      display: flex;
      align-items: center;
      cursor: pointer;
      background: rgba(255, 255, 255, 0.2);
      padding: 8px 15px;
      border-radius: 20px;
      transition: all 0.3s ease;
    }

    .periodo-label:hover {
      background: rgba(255, 255, 255, 0.3);
    }

    .periodo-radio {
      display: none;
    }

    .periodo-radio:checked + .periodo-texto {
      background: rgba(255, 255, 255, 0.9);
      color: #667eea;
      font-weight: bold;
      padding: 5px 10px;
      border-radius: 15px;
    }

    .periodo-texto {
      font-size: 12px;
      font-weight: 600;
      transition: all 0.3s ease;
    }

    .data-separador-mini {
      width: 2px;
      height: 40px;
      background: rgba(255, 255, 255, 0.3);
      margin: 0 15px;
    }

    .status-periodo-mini {
      font-size: 14px;
      font-weight: 500;
    }

    .widget-conteudo-principal {
      flex: 1;
      display: flex;
      flex-direction: column;
    }

    .conteudo-left {
      flex: 1;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }

    .widget-meta-valor {
      display: flex;
      align-items: center;
      gap: 15px;
      margin-bottom: 15px;
    }

    .widget-meta-valor i {
      font-size: 24px;
      color: #ffd700;
    }

    .meta-valor-container {
      flex: 1;
    }

    .valor-texto {
      font-size: 28px;
      font-weight: bold;
    }

    .widget-meta-rotulo {
      font-size: 14px;
      margin-bottom: 15px;
      opacity: 0.9;
    }

    .widget-barra-container {
      position: relative;
      background: rgba(255, 255, 255, 0.2);
      height: 12px;
      border-radius: 6px;
      margin-bottom: 15px;
      overflow: hidden;
    }

    .widget-barra-progresso {
      height: 100%;
      background: linear-gradient(90deg, #4caf50, #8bc34a);
      border-radius: 6px;
      width: 0%;
      transition: width 0.5s ease;
    }

    .porcentagem-barra {
      position: absolute;
      right: 10px;
      top: 50%;
      transform: translateY(-50%);
      font-size: 11px;
      font-weight: bold;
    }

    .widget-info-progresso {
      margin-top: 10px;
    }

    .saldo-positivo {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 16px;
    }

    .saldo-info-rotulo {
      opacity: 0.9;
    }

    .saldo-info-valor {
      font-weight: bold;
    }

    .valor-ultrapassou {
      display: flex;
      align-items: center;
      gap: 10px;
      background: rgba(255, 215, 0, 0.2);
      padding: 10px;
      border-radius: 8px;
      margin-bottom: 15px;
    }

    .valor-ultrapassou i {
      color: #ffd700;
    }

    /* Campo Mentores */
    .campo_mentores {
      background: #f8f9fa;
      border-radius: 15px;
      padding: 20px;
      flex: 1;
    }

    .barra-superior {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      padding: 15px;
      background: white;
      border-radius: 10px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    .btn-add-usuario {
      background: #28a745;
      color: white;
      border: none;
      padding: 10px 15px;
      border-radius: 8px;
      cursor: pointer;
      font-size: 16px;
      transition: all 0.3s ease;
    }

    .btn-add-usuario:hover {
      background: #218838;
      transform: translateY(-2px);
    }

    .area-central {
      flex: 1;
      display: flex;
      justify-content: center;
    }

    .pontuacao {
      display: flex;
      align-items: center;
      gap: 15px;
      font-size: 24px;
      font-weight: bold;
    }

    .placar-green {
      color: #28a745;
    }

    .placar-red {
      color: #dc3545;
    }

    .separador {
      color: #6c757d;
    }

    .area-direita {
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    .valor-dinamico {
      display: flex;
      align-items: center;
      gap: 5px;
      font-size: 12px;
      font-weight: 600;
    }

    .valor-diaria {
      color: #17a2b8;
    }

    .valor-unidade {
      color: #6c757d;
    }

    .mentor-wrapper {
      display: flex;
      flex-direction: column;
      gap: 15px;
      max-height: 400px;
      overflow-y: auto;
    }

    .mentor-item {
      position: relative;
      display: flex;
      align-items: center;
      gap: 10px;
      background: white;
      border-radius: 12px;
      padding: 15px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      transition: all 0.3s ease;
    }

    .mentor-item:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 15px rgba(0,0,0,0.15);
    }

    .mentor-rank-externo {
      background: #6c757d;
      color: white;
      width: 30px;
      height: 30px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: bold;
      font-size: 12px;
    }

    .mentor-card {
      flex: 1;
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-left: 4px solid transparent;
      padding-left: 15px;
    }

    .card-positivo {
      border-left-color: #28a745;
    }

    .card-negativo {
      border-left-color: #dc3545;
    }

    .card-neutro {
      border-left-color: #6c757d;
    }

    .mentor-header {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .mentor-img {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      object-fit: cover;
    }

    .mentor-nome {
      font-size: 16px;
      font-weight: 600;
      color: #333;
    }

    .mentor-values-inline {
      display: flex;
      gap: 10px;
    }

    .value-box-green, .value-box-red, .value-box-saldo {
      text-align: center;
      padding: 5px 8px;
      border-radius: 6px;
      font-size: 12px;
      min-width: 50px;
    }

    .value-box-green {
      background: #d4edda;
      color: #155724;
    }

    .value-box-red {
      background: #f8d7da;
      color: #721c24;
    }

    .value-box-saldo {
      background: #e2e3e5;
      color: #383d41;
      font-weight: bold;
    }

    .mentor-menu-externo {
      position: relative;
    }

    .menu-toggle {
      cursor: pointer;
      padding: 5px 8px;
      color: #6c757d;
      font-weight: bold;
    }

    .menu-opcoes {
      display: none;
      position: absolute;
      right: 0;
      top: 100%;
      background: white;
      border-radius: 8px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.15);
      z-index: 10;
      min-width: 150px;
    }

    .menu-opcoes button {
      width: 100%;
      border: none;
      background: none;
      padding: 10px 15px;
      text-align: left;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 14px;
    }

    .menu-opcoes button:hover {
      background: #f8f9fa;
    }

    /* ===== ESTILOS DO BLOCO 2 ===== */
    .bloco-2 {
      width: 100%;
      height: 100%;
    }

    .resumo-mes {
      height: 100%;
      display: flex;
      flex-direction: column;
    }

    .bloco-meta-simples {
      background: linear-gradient(135deg, #ff7e5f 0%, #feb47b 100%);
      border-radius: 15px;
      padding: 20px;
      color: white;
      margin-bottom: 20px;
    }

    .titulo-bloco {
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 20px;
      margin-bottom: 20px;
    }

    .widget-conteudo-principal-2 {
      display: flex;
      flex-direction: column;
      gap: 20px;
    }

    .conteudo-left-2 {
      display: flex;
      flex-direction: column;
      gap: 15px;
    }

    .widget-meta-valor-2 {
      display: flex;
      align-items: center;
      gap: 15px;
    }

    .widget-meta-valor-2 i {
      font-size: 24px;
      color: #ffd700;
    }

    .meta-valor-container-2 {
      flex: 1;
    }

    .valor-texto-2 {
      font-size: 24px;
      font-weight: bold;
    }

    .widget-meta-rotulo-2 {
      font-size: 14px;
      opacity: 0.9;
    }

    .widget-barra-container-2 {
      position: relative;
      background: rgba(255, 255, 255, 0.2);
      height: 12px;
      border-radius: 6px;
      overflow: hidden;
    }

    .widget-barra-progresso-2 {
      height: 100%;
      background: linear-gradient(90deg, #4caf50, #8bc34a);
      border-radius: 6px;
      width: 0%;
      transition: width 0.5s ease;
    }

    .porcentagem-barra-2 {
      position: absolute;
      right: 10px;
      top: 50%;
      transform: translateY(-50%);
      font-size: 11px;
      font-weight: bold;
    }

    .widget-info-progresso-2 {
      display: flex;
      align-items: center;
    }

    .saldo-positivo-2 {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 16px;
    }

    .area-central-2 {
      display: flex;
      justify-content: center;
      padding: 15px;
      background: rgba(255, 255, 255, 0.1);
      border-radius: 10px;
    }

    .pontuacao-2 {
      display: flex;
      align-items: center;
      gap: 15px;
      font-size: 24px;
      font-weight: bold;
    }

    .placar-green-2 {
      color: #28a745;
    }

    .placar-red-2 {
      color: #dc3545;
    }

    .separador-2 {
      color: #fff;
    }

    .lista-dias {
      flex: 1;
      background: #f8f9fa;
      border-radius: 12px;
      padding: 15px;
      overflow-y: auto;
      max-height: 400px;
    }

    .linha-dia {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 12px 15px;
      margin-bottom: 8px;
      background: white;
      border-radius: 8px;
      box-shadow: 0 2px 5px rgba(0,0,0,0.05);
      transition: all 0.3s ease;
    }

    .linha-dia:hover {
      transform: translateX(5px);
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }

    .dia-hoje {
      border-left: 4px solid #007bff;
      background: #e3f2fd;
    }

    .dia-destaque {
      border-left: 4px solid #28a745;
      background: #d4edda;
    }

    .dia-destaque-negativo {
      border-left: 4px solid #dc3545;
      background: #f8d7da;
    }

    .data {
      display: flex;
      align-items: center;
      gap: 8px;
      font-weight: 600;
      color: #333;
      flex: 1;
    }

    .placar-dia {
      display: flex;
      align-items: center;
      gap: 8px;
      margin: 0 15px;
    }

    .placar {
      font-weight: bold;
      font-size: 14px;
    }

    .verde-bold {
      color: #28a745;
    }

    .vermelho-bold {
      color: #dc3545;
    }

    .valor {
      font-weight: bold;
      font-size: 14px;
      min-width: 80px;
      text-align: right;
    }

    .icone {
      margin-left: 10px;
      color: #28a745;
    }

    .texto-cinza {
      color: #6c757d !important;
    }

    /* Responsividade */
    @media (max-width: 1400px) {
      .container-wrapper {
        flex-wrap: wrap;
        justify-content: center;
      }

      .container-box {
        margin-bottom: 30px;
      }
    }

    @media (max-width: 900px) {
      .container-wrapper {
        flex-direction: column;
        align-items: center;
        gap: 20px;
      }

      .container-box {
        width: 90%;
        max-width: 500px;
      }
    }
</style>

  <div class="container-wrapper">
    <!-- CONTAINER 1 - BLOCO 1 -->
    <div class="container-box">
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
                        <span class="valor-texto" id="valor-texto-meta">R$ 150,00</span>
                      </div>
                    </div>
    
                    <!-- Exibição do valor que ultrapassou a meta -->
                    <div class="valor-ultrapassou" id="valor-ultrapassou" style="display: none;">
                      <i class="fa-solid fa-trophy"></i>
                      <span class="texto-ultrapassou">Lucro Extra: <span id="valor-extra">R$ 0,00</span></span>
                    </div>
    
                    <!-- RÓTULO QUE ESTAVA FALTANDO -->
                    <div class="widget-meta-rotulo" id="rotulo-meta">Meta do Dia</div>
    
                    <!-- Container da Barra de Progresso -->
                    <div class="widget-barra-container">
                      <div class="widget-barra-progresso" id="barra-progresso" style="width: 50%;"></div>
                      <div class="porcentagem-barra" id="porcentagem-barra">50%</div>
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
          
          <!-- Campo Mentores -->
          <div class="campo_mentores">
            <!-- Barra superior com botão à esquerda e placar centralizado -->
            <div class="barra-superior">
              <button class="btn-add-usuario" onclick="prepararFormularioNovoMentor()">
                <i class="fas fa-user-plus"></i>
              </button>
              
              <div class="area-central">
                <div class="pontuacao" id="pontuacao">
                  <span class="placar-green">3</span>
                  <span class="separador">×</span>
                  <span class="placar-red">1</span>
                </div>
              </div>

              <!-- NOVA ÁREA DIREITA -->
              <div class="area-direita">
                <div class="valor-dinamico valor-diaria">
                  <i class="fas fa-university"></i>
                  <span id="porcentagem-diaria">75%</span>
                </div>
                <div class="valor-dinamico valor-unidade">
                  <span class="rotulo-und">UND:</span>
                  <span id="valor-unidade">R$ 25,00</span>
                </div>
              </div>
            </div>

            <!-- Área dos mentores -->
            <div id="listaMentores" class="mentor-wrapper">
              <!-- Mentor 1 -->
              <div class="mentor-item">
                <div class="mentor-rank-externo">1º</div>
                <div class="mentor-card card-positivo">
                  <div class="mentor-header">
                    <img src="https://cdn-icons-png.flaticon.com/512/847/847969.png" alt="Mentor" class="mentor-img" />
                    <h3 class="mentor-nome">João Silva</h3>
                  </div>
                  <div class="mentor-right">
                    <div class="mentor-values-inline">
                      <div class="value-box-green green">
                        <p>Green</p>
                        <p>2</p>
                      </div>
                      <div class="value-box-red red">
                        <p>Red</p>
                        <p>0</p>
                      </div>
                      <div class="value-box-saldo saldo">
                        <p>Saldo</p>
                        <p>R$ 50,00</p>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="mentor-menu-externo">
                  <span class="menu-toggle" title="Mais opções">⋮</span>
                  <div class="menu-opcoes">
                    <button>
                      <i class="fas fa-trash"></i> Excluir Entrada
                    </button>
                    <button>
                      <i class="fas fa-user-edit"></i> Editar Mentor
                    </button>
                  </div>
                </div>
              </div>

              <!-- Mentor 2 -->
              <div class="mentor-item">
                <div class="mentor-rank-externo">2º</div>
                <div class="mentor-card card-positivo">
                  <div class="mentor-header">
                    <img src="https://cdn-icons-png.flaticon.com/512/847/847969.png" alt="Mentor" class="mentor-img" />
                    <h3 class="mentor-nome">Maria Santos</h3>
                  </div>
                  <div class="mentor-right">
                    <div class="mentor-values-inline">
                      <div class="value-box-green green">
                        <p>Green</p>
                        <p>1</p>
                      </div>
                      <div class="value-box-red red">
                        <p>Red</p>
                        <p>1</p>
                      </div>
                      <div class="value-box-saldo saldo">
                        <p>Saldo</p>
                        <p>R$ 25,00</p>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="mentor-menu-externo">
                  <span class="menu-toggle" title="Mais opções">⋮</span>
                  <div class="menu-opcoes">
                    <button>
                      <i class="fas fa-trash"></i> Excluir Entrada
                    </button>
                    <button>
                      <i class="fas fa-user-edit"></i> Editar Mentor
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- CONTAINER 2 - BLOCO 2 -->
    <div class="container-box">
      <div class="bloco bloco-2">
        <div class="resumo-mes">
          <!-- Cabeçalho fixo com metas mensais -->
          <div class="bloco-meta-simples fixo-topo">
            <!-- Título do mês atual -->
            <h2 class="titulo-bloco">
              <i class="fas fa-calendar-alt"></i> <span id="tituloMes">AGOSTO 2025</span>
            </h2>

            <!-- Conteúdo principal do widget -->
            <div class="widget-conteudo-principal-2">
              <div class="conteudo-left-2">
                <!-- Valor da Meta -->
                <div class="widget-meta-valor-2" id="meta-valor-2">
                  <i class="fa-solid-2 fa-coins-2"></i>
                  <div class="meta-valor-container-2">
                    <span class="valor-texto-2" id="valor-texto-meta-2">R$ 4.500,00</span>
                  </div>
                </div>
    
                <!-- Exibição do valor que ultrapassou a meta -->
                <div class="valor-ultrapassou-2" id="valor-ultrapassou-2" style="display: none;">
                  <i class="fa-solid-2 fa-trophy-2"></i>
                  <span class="texto-ultrapassou-2">Lucro Extra: <span id="valor-extra-2">R$ 0,00</span></span>
                </div>
    
                <!-- RÓTULO QUE ESTAVA FALTANDO -->
                <div class="widget-meta-rotulo-2" id="rotulo-meta-2">Meta do Mês</div>
    
                <!-- Container da Barra de Progresso -->
                <div class="widget-barra-container-2">
                  <div class="widget-barra-progresso-2" id="barra-progresso-2" style="width: 35%;"></div>
                  <div class="porcentagem-barra-2" id="porcentagem-barra-2">35%</div>
                </div>
    
                <!-- Info de progresso com saldo -->
                <div class="widget-info-progresso-2">
                  <span id="saldo-info-2" class="saldo-positivo-2">
                    <i class="fa-solid-2 fa-chart-line-2"></i>
                    <span class="saldo-info-rotulo-2">Lucro:</span>
                    <span class="saldo-info-valor-2">R$ 1.575,00</span>
                  </span>
                </div>
              </div>

              <div class="area-central-2">
                <div class="pontuacao-2" id="pontuacao-2">
                  <span class="placar-green-2">21</span>
                  <span class="separador-2">×</span>
                  <span class="placar-red-2">8</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Lista de dias do mês com resultados -->
          <div class="lista-dias">
            <!-- Dia 29/08 - Hoje -->
            <div class="linha-dia dia-hoje">
              <span class="data">
                <i class="fas fa-calendar-day"></i> 29/08/2025
              </span>
              <div class="placar-dia">
                <span class="placar verde-bold">3</span>
                <span class="placar separador">x</span>
                <span class="placar vermelho-bold">1</span>
              </div>
              <span class="valor verde-bold">R$ 75,00</span>
              <span class="icone">
                <i class="fas fa-check"></i>
              </span>
            </div>

            <!-- Dia 28/08 -->
            <div class="linha-dia dia-destaque">
              <span class="data">
                <i class="fas fa-calendar-day"></i> 28/08/2025
              </span>
              <div class="placar-dia">
                <span class="placar verde-bold">4</span>
                <span class="placar separador">x</span>
                <span class="placar vermelho-bold">2</span>
              </div>
              <span class="valor verde-bold">R$ 125,00</span>
              <span class="icone">
                <i class="fas fa-check"></i>
              </span>
            </div>

            <!-- Dia 27/08 -->
            <div class="linha-dia dia-destaque">
              <span class="data">
                <i class="fas fa-calendar-day"></i> 27/08/2025
              </span>
              <div class="placar-dia">
                <span class="placar verde-bold">2</span>
                <span class="placar separador">x</span>
                <span class="placar vermelho-bold">1</span>
              </div>
              <span class="valor verde-bold">R$ 50,00</span>
              <span class="icone">
                <i class="fas fa-check"></i>
              </span>
            </div>

            <!-- Dia 26/08 -->
            <div class="linha-dia dia-destaque-negativo">
              <span class="data">
                <i class="fas fa-calendar-day"></i> 26/08/2025
              </span>
              <div class="placar-dia">
                <span class="placar verde-bold">1</span>
                <span class="placar separador">x</span>
                <span class="placar vermelho-bold">3</span>
              </div>
              <span class="valor vermelho-bold">R$ -75,00</span>
              <span class="icone">
                <i class="fas fa-times"></i>
              </span>
            </div>

            <!-- Dia 25/08 -->
            <div class="linha-dia dia-destaque">
              <span class="data">
                <i class="fas fa-calendar-day"></i> 25/08/2025
              </span>
              <div class="placar-dia">
                <span class="placar verde-bold">5</span>
                <span class="placar separador">x</span>
                <span class="placar vermelho-bold">0</span>
              </div>
              <span class="valor verde-bold">R$ 200,00</span>
              <span class="icone">
                <i class="fas fa-check"></i>
              </span>
            </div>

            <!-- Dia 24/08 -->
            <div class="linha-dia dia-destaque">
              <span class="data">
                <i class="fas fa-calendar-day"></i> 24/08/2025
              </span>
              <div class="placar-dia">
                <span class="placar verde-bold">3</span>
                <span class="placar separador">x</span>
                <span class="placar vermelho-bold">1</span>
              </div>
              <span class="valor verde-bold">R$ 100,00</span>
              <span class="icone">
                <i class="fas fa-check"></i>
              </span>
            </div>

            <!-- Dia 23/08 -->
            <div class="linha-dia dia-destaque">
              <span class="data">
                <i class="fas fa-calendar-day"></i> 23/08/2025
              </span>
              <div class="placar-dia">
                <span class="placar verde-bold">2</span>
                <span class="placar separador">x</span>
                <span class="placar vermelho-bold">0</span>
              </div>
              <span class="valor verde-bold">R$ 75,00</span>
              <span class="icone">
                <i class="fas fa-check"></i>
              </span>
            </div>

            <!-- Dia 22/08 -->
            <div class="linha-dia dia-destaque">
              <span class="data">
                <i class="fas fa-calendar-day"></i> 22/08/2025
              </span>
              <div class="placar-dia">
                <span class="placar verde-bold">4</span>
                <span class="placar separador">x</span>
                <span class="placar vermelho-bold">1</span>
              </div>
              <span class="valor verde-bold">R$ 150,00</span>
              <span class="icone">
                <i class="fas fa-check"></i>
              </span>
            </div>

            <!-- Dia 21/08 -->
            <div class="linha-dia dia-destaque-negativo">
              <span class="data">
                <i class="fas fa-calendar-day"></i> 21/08/2025
              </span>
              <div class="placar-dia">
                <span class="placar verde-bold">0</span>
                <span class="placar separador">x</span>
                <span class="placar vermelho-bold">2</span>
              </div>
              <span class="valor vermelho-bold">R$ -100,00</span>
              <span class="icone">
                <i class="fas fa-times"></i>
              </span>
            </div>

            <!-- Dia 20/08 -->
            <div class="linha-dia dia-destaque">
              <span class="data">
                <i class="fas fa-calendar-day"></i> 20/08/2025
              </span>
              <div class="placar-dia">
                <span class="placar verde-bold">6</span>
                <span class="placar separador">x</span>
                <span class="placar vermelho-bold">1</span>
              </div>
              <span class="valor verde-bold">R$ 250,00</span>
              <span class="icone">
                <i class="fas fa-check"></i>
              </span>
            </div>

            <!-- Mais alguns dias de exemplo -->
            <div class="linha-dia dia-destaque">
              <span class="data">
                <i class="fas fa-calendar-day"></i> 19/08/2025
              </span>
              <div class="placar-dia">
                <span class="placar verde-bold">3</span>
                <span class="placar separador">x</span>
                <span class="placar vermelho-bold">0</span>
              </div>
              <span class="valor verde-bold">R$ 125,00</span>
              <span class="icone">
                <i class="fas fa-check"></i>
              </span>
            </div>

            <div class="linha-dia dia-destaque">
              <span class="data">
                <i class="fas fa-calendar-day"></i> 18/08/2025
              </span>
              <div class="placar-dia">
                <span class="placar verde-bold">2</span>
                <span class="placar separador">x</span>
                <span class="placar vermelho-bold">1</span>
              </div>
              <span class="valor verde-bold">R$ 75,00</span>
              <span class="icone">
                <i class="fas fa-check"></i>
              </span>
            </div>

            <div class="linha-dia dia-destaque">
              <span class="data">
                <i class="fas fa-calendar-day"></i> 17/08/2025
              </span>
              <div class="placar-dia">
                <span class="placar verde-bold">4</span>
                <span class="placar separador">x</span>
                <span class="placar vermelho-bold">2</span>
              </div>
              <span class="valor verde-bold">R$ 100,00</span>
              <span class="icone">
                <i class="fas fa-check"></i>
              </span>
            </div>

            <div class="linha-dia dia-destaque-negativo">
              <span class="data">
                <i class="fas fa-calendar-day"></i> 16/08/2025
              </span>
              <div class="placar-dia">
                <span class="placar verde-bold">1</span>
                <span class="placar separador">x</span>
                <span class="placar vermelho-bold">4</span>
              </div>
              <span class="valor vermelho-bold">R$ -125,00</span>
              <span class="icone">
                <i class="fas fa-times"></i>
              </span>
            </div>

            <div class="linha-dia dia-destaque">
              <span class="data">
                <i class="fas fa-calendar-day"></i> 15/08/2025
              </span>
              <div class="placar-dia">
                <span class="placar verde-bold">5</span>
                <span class="placar separador">x</span>
                <span class="placar vermelho-bold">1</span>
              </div>
              <span class="valor verde-bold">R$ 200,00</span>
              <span class="icone">
                <i class="fas fa-check"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- CONTAINER 3 - Vazio para adicionar mais conteúdo -->
    <div class="container-box">
      <h2>Container 3</h2>
      <p>Este terceiro container está disponível para adicionar mais funcionalidades ou relatórios.</p>
      <p>Você pode incluir aqui gráficos, estatísticas detalhadas, ou qualquer outro conteúdo relevante para o dashboard.</p>
    </div>
  </div>

  <script>
    // Atualizar data atual
    document.addEventListener('DOMContentLoaded', function() {
      const hoje = new Date();
      const dataFormatada = hoje.toLocaleDateString('pt-BR', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      });
      document.getElementById('data-atual').textContent = dataFormatada;

      // Configurar título do mês
      const meses = [
        "JANEIRO", "FEVEREIRO", "MARÇO", "ABRIL", "MAIO", "JUNHO",
        "JULHO", "AGOSTO", "SETEMBRO", "OUTUBRO", "NOVEMBRO", "DEZEMBRO"
      ];
      const mesAtual = meses[hoje.getMonth()];
      const anoAtual = hoje.getFullYear();
      document.getElementById("tituloMes").textContent = `${mesAtual} ${anoAtual}`;

      // Menu toggle functionality
      document.querySelectorAll('.menu-toggle').forEach(toggle => {
        toggle.addEventListener('click', function(e) {
          e.stopPropagation();
          const menu = this.nextElementSibling;
          
          // Fechar outros menus
          document.querySelectorAll('.menu-opcoes').forEach(otherMenu => {
            if (otherMenu !== menu) {
              otherMenu.style.display = 'none';
            }
          });
          
          // Toggle do menu atual
          menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
        });
      });

      // Fechar menus ao clicar fora
      document.addEventListener('click', function() {
        document.querySelectorAll('.menu-opcoes').forEach(menu => {
          menu.style.display = 'none';
        });
      });

      // Funcionalidade dos períodos
      document.querySelectorAll('input[name="periodo"]').forEach(radio => {
        radio.addEventListener('change', function() {
          const periodo = this.value;
          console.log('Período selecionado:', periodo);
          // Aqui você pode adicionar a lógica para alterar os dados conforme o período
        });
      });
    });

    // Funções placeholder para os botões
    function prepararFormularioNovoMentor() {
      alert('Função para adicionar novo mentor');
    }

    function editarAposta(id) {
      alert('Função para excluir entrada do mentor ID: ' + id);
    }

    function editarMentor(id) {
      alert('Função para editar mentor ID: ' + id);
    }
  </script>

</body>
</html>
