<?php
// carregar-mentores.php - VERSÃO COM MENTOR OCULTO PARA EVITAR ERROS

require_once 'config.php';
require_once 'carregar_sessao.php';
require_once 'funcoes.php';

// Verifica login
$id_usuario = $_SESSION['usuario_id'] ?? null;
if (!$id_usuario) {
  echo "<div class='mentor-card card-neutro'>Usuário não autenticado.</div>";
  exit;
}

// ✅ DETECTAR PERÍODO DE MÚLTIPLAS FONTES
function detectarPeriodo() {
    // 1. Verificar POST/GET direto
    $periodo_direto = $_POST['periodo'] ?? $_GET['periodo'] ?? null;
    if ($periodo_direto && in_array($periodo_direto, ['dia', 'mes', 'ano'])) {
        return $periodo_direto;
    }
    
    // 2. Verificar header do JavaScript (para sincronização)
    $periodo_header = $_SERVER['HTTP_X_PERIODO_FILTRO'] ?? null;
    if ($periodo_header && in_array($periodo_header, ['dia', 'mes', 'ano'])) {
        return $periodo_header;
    }
    
    // 3. Verificar na sessão (persistência)
    $periodo_sessao = $_SESSION['periodo_filtro_ativo'] ?? null;
    if ($periodo_sessao && in_array($periodo_sessao, ['dia', 'mes', 'ano'])) {
        return $periodo_sessao;
    }
    
    // 4. Padrão
    return 'dia';
}

// ✅ SALVAR PERÍODO NA SESSÃO PARA PERSISTÊNCIA
function salvarPeriodoSessao($periodo) {
    $_SESSION['periodo_filtro_ativo'] = $periodo;
}

// ✅ PEGA O PERÍODO DETECTADO
$periodo = detectarPeriodo();
salvarPeriodoSessao($periodo);

// ✅ LOG PARA DEBUG
error_log("DEBUG carregar-mentores.php: Período detectado = $periodo");

// Dados da sessão (mantidos iguais)
$ultima_diaria       = $_SESSION['porcentagem_entrada'] ?? 0;
$saldo_mentores      = $_SESSION['saldo_mentores'] ?? 0;
$depositos           = $_SESSION['depositos'] ?? 0;
$saques_totais       = $_SESSION['saques_totais'] ?? 0;
$resultado_entrada   = $_SESSION['resultado_entrada'] ?? 0;
$meia_unidade        = $_SESSION['meta_meia_unidade'] ?? 0;

$saldo_banca = calcularSaldoBanca();

$diaria_formatada    = (intval($ultima_diaria) == $ultima_diaria)
  ? intval($ultima_diaria) . '%'
  : number_format($ultima_diaria, 2, ',', '.') . '%';

$resultado_formatado = number_format($resultado_entrada, 2, ',', '.');
$meta_formatado      = number_format($meia_unidade, 2, ',', '.');

// ✅ PREPARA CONDIÇÃO DE DATA BASEADA NO PERÍODO - MELHORADA
$condicaoData = '';
date_default_timezone_set('America/Bahia');

switch ($periodo) {
    case 'dia':
        $condicaoData = "AND DATE(vm.data_criacao) = CURDATE()";
        error_log("DEBUG: Aplicando filtro DIA - " . date('Y-m-d'));
        break;
        
    case 'mes':
        $condicaoData = "AND MONTH(vm.data_criacao) = MONTH(CURDATE()) 
                        AND YEAR(vm.data_criacao) = YEAR(CURDATE())";
        error_log("DEBUG: Aplicando filtro MÊS - " . date('Y-m'));
        break;
        
    case 'ano':
        $condicaoData = "AND YEAR(vm.data_criacao) = YEAR(CURDATE())";
        error_log("DEBUG: Aplicando filtro ANO - " . date('Y'));
        break;
        
    default:
        $condicaoData = '';
        error_log("DEBUG: Sem filtro de data aplicado");
        break;
}

// ✅ QUERY DE MENTORES (mantida igual)
$sql_mentores = "SELECT id, nome, foto FROM mentores WHERE id_usuario = ?";
$stmt = $conexao->prepare($sql_mentores);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$result = $stmt->get_result();

$lista_mentores = [];
$total_geral_saldo = 0;
$total_geral_green = 0;
$total_geral_red = 0;
$tem_mentores_reais = false;

// ✅ PROCESSAMENTO DOS MENTORES REAIS
while ($mentor = $result->fetch_assoc()) {
  $tem_mentores_reais = true;
  $id_mentor   = $mentor['id'];
  $nome_mentor = htmlspecialchars($mentor['nome'] ?? 'Mentor', ENT_QUOTES, 'UTF-8');
  $foto_mentor = htmlspecialchars($mentor['foto'], ENT_QUOTES, 'UTF-8');

  // Query com filtro de período aplicado
  $sql_valores = "
    SELECT 
      COALESCE(SUM(CASE WHEN green = 1 THEN 1 ELSE 0 END), 0) AS total_green,
      COALESCE(SUM(CASE WHEN red = 1 THEN 1 ELSE 0 END), 0) AS total_red,
      COALESCE(SUM(CASE WHEN green = 1 THEN valor_green ELSE 0 END), 0) AS total_valor_green,
      COALESCE(SUM(CASE WHEN red = 1 THEN valor_red ELSE 0 END), 0) AS total_valor_red,
      COUNT(*) as total_registros
    FROM valor_mentores vm
    WHERE vm.id_mentores = ? 
    {$condicaoData}
  ";
  
  $stmt_val = $conexao->prepare($sql_valores);
  $stmt_val->bind_param("i", $id_mentor);
  $stmt_val->execute();
  $valores = $stmt_val->get_result()->fetch_assoc();
  $stmt_val->close();

  error_log("DEBUG Mentor $nome_mentor (ID: $id_mentor): Green={$valores['total_green']}, Red={$valores['total_red']}, Registros={$valores['total_registros']}");

  $saldo = $valores['total_valor_green'] - $valores['total_valor_red'];

  $mentor['valores'] = $valores;
  $mentor['saldo']   = $saldo;
  $mentor['nome']    = $nome_mentor;
  $mentor['foto']    = $foto_mentor;

  $lista_mentores[]     = $mentor;
  $total_geral_saldo   += $saldo;
  $total_geral_green   += $valores['total_green'];
  $total_geral_red     += $valores['total_red'];
}

// ✅ NOVO: ADICIONAR MENTOR OCULTO SE NÃO HOUVER MENTORES REAIS
if (!$tem_mentores_reais) {
    $mentor_oculto = [
        'id' => 0,
        'nome' => 'Sistema',
        'foto' => '',
        'valores' => [
            'total_green' => 0,
            'total_red' => 0,
            'total_valor_green' => 0,
            'total_valor_red' => 0,
            'total_registros' => 0
        ],
        'saldo' => 0,
        'oculto' => true // Marca como oculto
    ];
    
    $lista_mentores[] = $mentor_oculto;
    error_log("DEBUG: Mentor oculto adicionado para evitar erros de cálculo");
}

// ✅ LOG DO RESULTADO GERAL
error_log("DEBUG Total Geral: Saldo=R$ $total_geral_saldo, Green=$total_geral_green, Red=$total_geral_red, Período=$periodo, Mentores Reais=" . ($tem_mentores_reais ? 'Sim' : 'Não'));

// Ordena por saldo (mantido igual)
usort($lista_mentores, fn($a, $b) => $b['saldo'] <=> $a['saldo']);

// ✅ CABEÇALHO COM INFORMAÇÕES DO PERÍODO (OPCIONAL - PARA DEBUG)
if (isset($_GET['debug']) && $_GET['debug'] === '1') {
    echo "<!-- DEBUG: Período=$periodo, Condição=$condicaoData, Mentores=" . count($lista_mentores) . ", Reais=$tem_mentores_reais -->\n";
}

// ✅ NOVO: SE NÃO HÁ MENTORES REAIS, MOSTRAR BOTÃO PARA CADASTRAR
if (!$tem_mentores_reais) {
    echo "
    <div class='mentor-item sem-mentores'>
        <div class='container-primeiro-mentor'>
            <div class='icone-mentor-vazio'>
                <i class='fas fa-user-plus'></i>
            </div>
            <h3 class='titulo-sem-mentores'>Nenhum Mentor Cadastrado</h3>
            <p class='descricao-sem-mentores'>Para começar a usar o sistema, você precisa cadastrar seu primeiro mentor.</p>
            <button class='btn-primeiro-mentor' onclick='prepararFormularioNovoMentor()'>
                <i class='fas fa-user-plus'></i>
                Cadastre Seu Primeiro Mentor
            </button>
        </div>
    </div>
    ";
} else {
    // ✅ EXIBE OS MENTORES REAIS COM VALORES FILTRADOS
    $rank = 1;
    foreach ($lista_mentores as $posicao => $mentor) {
        // Pula mentor oculto na exibição
        if (isset($mentor['oculto']) && $mentor['oculto']) {
            continue;
        }
        
        $valores          = $mentor['valores'];
        $saldo_formatado  = number_format($mentor['saldo'], 2, ',', '.');

        $classe_borda = $mentor['saldo'] == 0
            ? 'card-neutro'
            : ($mentor['saldo'] > 0 ? 'card-positivo' : 'card-negativo');

        // Verificação da foto
        $foto_path = 'uploads/' . $mentor['foto'];
        if (!file_exists($foto_path) || empty($mentor['foto'])) {
            $foto_path = 'https://cdn-icons-png.flaticon.com/512/847/847969.png';
        }

        echo "
        <div class='mentor-item'>
          <div class='mentor-rank-externo'>{$rank}º</div>

          <div class='mentor-card {$classe_borda}' 
               data-nome='{$mentor['nome']}'
               data-foto='{$foto_path}'
               data-id='{$mentor['id']}'>
            <div class='mentor-header'>
              <img src='{$foto_path}' alt='Foto de {$mentor['nome']}' class='mentor-img' 
                   onerror=\"this.src='https://cdn-icons-png.flaticon.com/512/847/847969.png'\" />
              <h3 class='mentor-nome'>{$mentor['nome']}</h3>
            </div>
            <div class='mentor-right'>
              <div class='mentor-values-inline'>
                <div class='value-box-green green'><p>Green</p><p>{$valores['total_green']}</p></div>
                <div class='value-box-red red'><p>Red</p><p>{$valores['total_red']}</p></div>
                <div class='value-box-saldo saldo'><p>Saldo</p><p>R$ {$saldo_formatado}</p></div>
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
        
        $rank++; // Incrementa rank apenas para mentores reais
    }
}

// ✅ ELEMENTOS COM DADOS DO PERÍODO SELECIONADO + INFORMAÇÕES EXTRAS
echo "<div id='total-green-dia' data-green='{$total_geral_green}' style='display:none;'></div>";
echo "<div id='total-red-dia' data-red='{$total_geral_red}' style='display:none;'></div>";
echo "<div id='saldo-dia' data-total='" . number_format($total_geral_saldo, 2, ',', '.') . "' style='display:none;'></div>";
echo "<div id='meta-meia-unidade' data-meta='R$ {$meta_formatado}' style='display:none;'></div>";
echo "<div id='resultado-unidade' data-resultado='R$ {$resultado_formatado}' style='display:none;'></div>";
echo "<div id='porcentagem-entrada' data-porcentagem='{$diaria_formatada}' style='display:none;'></div>";
echo "<div id='menu-saldo-banca' data-banca='R$ " . number_format($saldo_banca, 2, ',', '.') . "' style='display:none;'></div>";
echo "<div id='menu-saques' data-saques='R$ " . number_format($saques_totais, 2, ',', '.') . "' style='display:none;'></div>";

// ✅ DADOS DO PERÍODO PARA SINCRONIZAÇÃO
echo "<div id='periodo-atual' data-periodo='{$periodo}' style='display:none;'></div>";
echo "<div id='periodo-timestamp' data-timestamp='" . time() . "' style='display:none;'></div>";

// ✅ DADOS DETALHADOS PARA O SISTEMA INTEGRADO
$classe_saldo = $total_geral_saldo > 0 ? 'saldo-positivo' : ($total_geral_saldo < 0 ? 'saldo-negativo' : 'saldo-neutro');
echo "<div id='menu-saldo-mentores' data-saldo='R$ " . number_format($total_geral_saldo, 2, ',', '.') . "' data-classe='{$classe_saldo}' style='display:none;'></div>";

// ✅ INFORMAÇÕES PARA DEBUG E SINCRONIZAÇÃO
echo "<div id='filtro-info' data-periodo='{$periodo}' data-total-mentores='" . count($lista_mentores) . "' data-condicao-sql='" . htmlspecialchars($condicaoData) . "' style='display:none;'></div>";

// ✅ DADOS PARA INTEGRAÇÃO COM dados_banca.php
echo "<div id='lucro-filtrado' data-green='{$total_geral_green}' data-red='{$total_geral_red}' data-lucro='" . number_format($total_geral_saldo, 2, ',', '.') . "' data-periodo='{$periodo}' style='display:none;'></div>";

// ✅ NOVO: INDICADOR DE ESTADO PARA JAVASCRIPT
echo "<div id='estado-mentores' data-tem-mentores='" . ($tem_mentores_reais ? 'true' : 'false') . "' data-total-reais='" . ($tem_mentores_reais ? count($lista_mentores) - 1 : 0) . "' style='display:none;'></div>";

// ✅ DADOS PARA JAVASCRIPT - SINCRONIZAÇÃO
echo "<script>
  // ✅ Atualizar período no sistema JavaScript se existir
  if (typeof MetaDiariaManager !== 'undefined') {
    MetaDiariaManager.periodoAtual = '{$periodo}';
  }
  
  if (typeof SistemaFiltroPeriodoIntegrado !== 'undefined') {
    SistemaFiltroPeriodoIntegrado.periodoAtual = '{$periodo}';
  }
  
  // ✅ Sincronizar radio buttons
  const radioCorreto = document.querySelector('input[name=\"periodo\"][value=\"{$periodo}\"]');
  if (radioCorreto && !radioCorreto.checked) {
    radioCorreto.checked = true;
  }
  
  // ✅ NOVO: Verificar estado dos mentores
  const temMentores = " . ($tem_mentores_reais ? 'true' : 'false') . ";
  if (typeof window.estadoMentores !== 'undefined') {
    window.estadoMentores.temMentores = temMentores;
    window.estadoMentores.totalReais = " . ($tem_mentores_reais ? count($lista_mentores) - 1 : 0) . ";
  }
  
  // ✅ LOG para debug
  console.log('📊 Mentores carregados:', {
    periodo: '{$periodo}',
    totalMentores: " . count($lista_mentores) . ",
    mentoresReais: " . ($tem_mentores_reais ? count($lista_mentores) - 1 : 0) . ",
    temMentores: temMentores,
    totalGreen: {$total_geral_green},
    totalRed: {$total_geral_red},
    saldoTotal: '{$total_geral_saldo}',
    timestamp: '" . date('H:i:s') . "'
  });
</script>";

// ✅ LOG FINAL
error_log("DEBUG carregar-mentores.php finalizado: Período=$periodo, Mentores=" . count($lista_mentores) . ", Mentores Reais=" . ($tem_mentores_reais ? 'Sim' : 'Não') . ", Saldo Total=R$ $total_geral_saldo");

?>