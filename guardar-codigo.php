<?php
// ✅ ARQUIVO DADOS_BANCA.PHP - OTIMIZADO PARA PERÍODOS

require_once 'config.php';
require_once 'carregar_sessao.php';

$id_usuario = $_SESSION['usuario_id'] ?? null;
if (!$id_usuario) {
    echo json_encode(['success' => false, 'message' => 'Usuário não logado']);
    exit;
}

// Suas funções existentes (NÃO ALTERADAS)
function getSoma($conexao, $campo, $id_usuario) {
    $stmt = $conexao->prepare("SELECT SUM($campo) FROM controle WHERE id_usuario = ? AND $campo > 0");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $stmt->bind_result($total);
    $stmt->fetch();
    $stmt->close();
    return $total ?? 0;
}

function getUltimoCampo($conexao, $campo, $id_usuario) {
    $stmt = $conexao->prepare("
        SELECT $campo FROM controle
        WHERE id_usuario = ? AND $campo IS NOT NULL
        ORDER BY id DESC LIMIT 1
    ");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $stmt->bind_result($valor);
    $stmt->fetch();
    $stmt->close();
    return $valor;
}

// ✅ FUNÇÃO PARA CALCULAR LUCRO (NÃO ALTERADA)
function calcularLucro($conexao, $id_usuario) {
    $stmt = $conexao->prepare("
        SELECT 
            COALESCE(SUM(valor_green), 0),
            COALESCE(SUM(valor_red), 0)
        FROM valor_mentores
        WHERE id_usuario = ?
    ");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $stmt->bind_result($total_green, $total_red);
    $stmt->fetch();
    $stmt->close();
    
    return [
        'green' => $total_green,
        'red' => $total_red,
        'lucro' => $total_green - $total_red
    ];
}

// ✅ FUNÇÃO PARA CALCULAR META DIÁRIA (NÃO ALTERADA)
function calcularMetaDiaria($conexao, $id_usuario, $total_deposito, $total_saque) {
    try {
        // Meta baseada apenas na banca (sem lucro)
        $saldo_banca_para_meta = $total_deposito - $total_saque;
        
        // Buscar os últimos valores de diária e unidade
        $stmt = $conexao->prepare("
            SELECT diaria, unidade 
            FROM controle 
            WHERE id_usuario = ? AND diaria IS NOT NULL AND unidade IS NOT NULL 
            ORDER BY id DESC 
            LIMIT 1
        ");
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $stmt->bind_result($diaria, $unidade);
        $stmt->fetch();
        $stmt->close();
        
        // Valores padrão se não encontrar
        if ($diaria === null) $diaria = 2.00;
        if ($unidade === null) $unidade = 2;
        
        // CÁLCULO: (deposito - saque) * (diaria/100) * unidade
        $porcentagem_decimal = $diaria / 100;
        $meta_diaria = $saldo_banca_para_meta * $porcentagem_decimal * $unidade;
        
        return [
            'meta_diaria' => $meta_diaria,
            'diaria_usada' => $diaria,
            'unidade_usada' => $unidade,
            'saldo_banca_meta' => $saldo_banca_para_meta
        ];
        
    } catch (Exception $e) {
        error_log("Erro ao calcular meta diária: " . $e->getMessage());
        return [
            'meta_diaria' => 0,
            'diaria_usada' => 2,
            'unidade_usada' => 2,
            'saldo_banca_meta' => 0
        ];
    }
}

// ✅ FUNÇÃO PARA CALCULAR DADOS DA ÁREA DIREITA (NÃO ALTERADA)
function calcularAreaDireita($conexao, $id_usuario, $saldo_banca_total) {
    try {
        // Buscar última diária cadastrada
        $ultima_diaria = getUltimoCampo($conexao, 'diaria', $id_usuario);
        $diaria = $ultima_diaria ?? 2.00;
        
        // Calcular unidade de entrada: saldo_total * (diária / 100)
        $unidade_entrada = $saldo_banca_total * ($diaria / 100);
        
        return [
            'diaria_porcentagem' => $diaria,
            'saldo_banca_total' => $saldo_banca_total,
            'unidade_entrada' => $unidade_entrada,
            'diaria_formatada' => number_format($diaria, 0) . '%',
            'unidade_entrada_formatada' => 'R$ ' . number_format($unidade_entrada, 2, ',', '.')
        ];
        
    } catch (Exception $e) {
        error_log("Erro ao calcular área direita: " . $e->getMessage());
        return [
            'diaria_porcentagem' => 2,
            'saldo_banca_total' => 0,
            'unidade_entrada' => 0,
            'diaria_formatada' => '2%',
            'unidade_entrada_formatada' => 'R$ 0,00'
        ];
    }
}

// ✅ FUNÇÃO OTIMIZADA PARA CALCULAR DIAS RESTANTES
function calcularDiasRestantes() {
    $hoje = new DateTime();
    $agora = $hoje->format('Y-m-d H:i:s');
    
    // Dias restantes do mês (incluindo hoje)
    $diaAtual = (int)$hoje->format('d');
    $ultimoDiaMes = (int)$hoje->format('t');
    $diasRestantesMes = $ultimoDiaMes - $diaAtual + 1;
    
    // Dias restantes do ano (incluindo hoje)
    $fimAno = new DateTime($hoje->format('Y') . '-12-31 23:59:59');
    $diferenca = $hoje->diff($fimAno);
    $diasRestantesAno = $diferenca->days + 1;
    
    return [
        'mes' => $diasRestantesMes,
        'ano' => $diasRestantesAno,
        'info' => [
            'data_atual' => $hoje->format('Y-m-d'),
            'dia_atual' => $diaAtual,
            'ultimo_dia_mes' => $ultimoDiaMes,
            'mes_atual' => $hoje->format('m'),
            'ano_atual' => $hoje->format('Y'),
            'calculo_mes' => "Restam {$diasRestantesMes} de {$ultimoDiaMes} dias do mês",
            'calculo_ano' => "Restam {$diasRestantesAno} dias do ano " . $hoje->format('Y')
        ]
    ];
}

// ✅ FUNÇÃO PRINCIPAL PARA CALCULAR METAS POR PERÍODO
function calcularMetasPorPeriodo($meta_diaria) {
    $diasRestantes = calcularDiasRestantes();
    
    $meta_mensal = $meta_diaria * $diasRestantes['mes'];
    $meta_anual = $meta_diaria * $diasRestantes['ano'];
    
    return [
        // Metas calculadas
        'meta_diaria' => $meta_diaria,
        'meta_mensal' => $meta_mensal,
        'meta_anual' => $meta_anual,
        
        // Dias restantes
        'dias_restantes_mes' => $diasRestantes['mes'],
        'dias_restantes_ano' => $diasRestantes['ano'],
        
        // Formatações para exibição
        'meta_diaria_formatada' => 'R$ ' . number_format($meta_diaria, 2, ',', '.'),
        'meta_mensal_formatada' => 'R$ ' . number_format($meta_mensal, 2, ',', '.'),
        'meta_anual_formatada' => 'R$ ' . number_format($meta_anual, 2, ',', '.'),
        
        // Informações detalhadas
        'periodo_info' => [
            'data_hoje' => $diasRestantes['info']['data_atual'],
            'mes_atual' => $diasRestantes['info']['mes_atual'],
            'ano_atual' => $diasRestantes['info']['ano_atual'],
            'dia_atual' => $diasRestantes['info']['dia_atual'],
            'ultimo_dia_mes' => $diasRestantes['info']['ultimo_dia_mes'],
            'calculo_mes' => $diasRestantes['info']['calculo_mes'],
            'calculo_ano' => $diasRestantes['info']['calculo_ano'],
            
            // Fórmulas de cálculo
            'formula_diaria' => "Meta Diária: R$ " . number_format($meta_diaria, 2, ',', '.'),
            'formula_mensal' => "Meta Mensal: R$ " . number_format($meta_diaria, 2, ',', '.') . " × {$diasRestantes['mes']} dias = R$ " . number_format($meta_mensal, 2, ',', '.'),
            'formula_anual' => "Meta Anual: R$ " . number_format($meta_diaria, 2, ',', '.') . " × {$diasRestantes['ano']} dias = R$ " . number_format($meta_anual, 2, ',', '.')
        ]
    ];
}

// Processar requisições POST (cadastros) - NÃO ALTERADO
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    try {
        $acao = $input['acao'] ?? '';
        $valor = floatval($input['valor'] ?? 0);
        $diaria = floatval($input['diaria'] ?? 2);
        $unidade = intval($input['unidade'] ?? 2);
        $odds = floatval($input['odds'] ?? 1.5);
        
        $stmt = null;
        
        switch ($acao) {
            case 'deposito':
                $stmt = $conexao->prepare("INSERT INTO controle (id_usuario, deposito, diaria, unidade, odds, data_cadastro) VALUES (?, ?, ?, ?, ?, NOW())");
                $stmt->bind_param("idddi", $id_usuario, $valor, $diaria, $unidade, $odds);
                break;
                
            case 'saque':
                $stmt = $conexao->prepare("INSERT INTO controle (id_usuario, saque, diaria, unidade, odds, data_cadastro) VALUES (?, ?, ?, ?, ?, NOW())");
                $stmt->bind_param("idddi", $id_usuario, $valor, $diaria, $unidade, $odds);
                break;
                
            case 'alterar':
                $stmt = $conexao->prepare("INSERT INTO controle (id_usuario, diaria, unidade, odds, data_cadastro) VALUES (?, ?, ?, ?, NOW())");
                $stmt->bind_param("iddi", $id_usuario, $diaria, $unidade, $odds);
                break;
                
            case 'resetar':
                // Inserir registro de reset
                $stmt = $conexao->prepare("INSERT INTO controle (id_usuario, reset_banca, data_cadastro) VALUES (?, 1, NOW())");
                $stmt->bind_param("i", $id_usuario);
                $stmt->execute();
                
                // Limpar dados da tabela valor_mentores
                $stmt2 = $conexao->prepare("DELETE FROM valor_mentores WHERE id_usuario = ?");
                $stmt2->bind_param("i", $id_usuario);
                $stmt2->execute();
                $stmt2->close();
                break;
        }
        
        if ($stmt) {
            $result = $stmt->execute();
            $stmt->close();
            
            if (!$result) {
                throw new Exception("Erro ao executar operação");
            }
        }
        
        // ✅ CALCULAR VALORES APÓS OPERAÇÃO
        $total_deposito = getSoma($conexao, 'deposito', $id_usuario);
        $total_saque = getSoma($conexao, 'saque', $id_usuario);
        
        // Calcular lucro
        $dados_lucro = calcularLucro($conexao, $id_usuario);
        $lucro = $dados_lucro['lucro'];
        
        // Saldo total da banca
        $saldo_banca_total = $total_deposito - $total_saque + $lucro;
        
        // Calcular meta baseada apenas em (depósito - saque)
        $meta_resultado = calcularMetaDiaria($conexao, $id_usuario, $total_deposito, $total_saque);
        
        // Calcular dados para área direita
        $area_direita = calcularAreaDireita($conexao, $id_usuario, $saldo_banca_total);
        
        // ✅ CALCULAR METAS POR PERÍODO
        $metas_periodo = calcularMetasPorPeriodo($meta_resultado['meta_diaria']);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Operação realizada com sucesso',
            
            // Dados principais
            'banca' => $saldo_banca_total,
            'banca_formatada' => 'R$ ' . number_format($saldo_banca_total, 2, ',', '.'),
            'lucro' => $lucro,
            'lucro_formatado' => 'R$ ' . number_format($lucro, 2, ',', '.'),
            
            // Meta diária
            'meta_diaria' => $meta_resultado['meta_diaria'],
            'meta_diaria_formatada' => 'R$ ' . number_format($meta_resultado['meta_diaria'], 2, ',', '.'),
            'meta_diaria_brl' => 'R$ ' . number_format($meta_resultado['meta_diaria'], 2, ',', '.'),
            'diaria_atual' => $meta_resultado['diaria_usada'],
            'unidade_atual' => $meta_resultado['unidade_usada'],
            'saldo_base_meta' => $meta_resultado['saldo_banca_meta'],
            
            // Dados para área direita
            'diaria_formatada' => $area_direita['diaria_formatada'],
            'unidade_entrada_formatada' => $area_direita['unidade_entrada_formatada'],
            'diaria_raw' => $area_direita['diaria_porcentagem'],
            'saldo_banca_total' => $area_direita['saldo_banca_total'],
            'unidade_entrada_raw' => $area_direita['unidade_entrada'],
            
            // ✅ METAS POR PERÍODO (OTIMIZADO)
            'metas_periodo' => $metas_periodo,
            'meta_mensal' => $metas_periodo['meta_mensal'],
            'meta_mensal_formatada' => $metas_periodo['meta_mensal_formatada'],
            'meta_anual' => $metas_periodo['meta_anual'], 
            'meta_anual_formatada' => $metas_periodo['meta_anual_formatada'],
            'dias_restantes_mes' => $metas_periodo['dias_restantes_mes'],
            'dias_restantes_ano' => $metas_periodo['dias_restantes_ano'],
            'periodo_info' => $metas_periodo['periodo_info']
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ✅ PROCESSAR REQUISIÇÕES GET (CONSULTAS)
try {
    $total_deposito = getSoma($conexao, 'deposito', $id_usuario);
    $total_saque = getSoma($conexao, 'saque', $id_usuario);
    
    // Calcular lucro
    $dados_lucro = calcularLucro($conexao, $id_usuario);
    $total_green = $dados_lucro['green'];
    $total_red = $dados_lucro['red'];
    $lucro = $dados_lucro['lucro'];
    
    // Saldo total da banca
    $saldo_banca_total = $total_deposito - $total_saque + $lucro;
    
    // Buscar últimos valores de configuração
    $ultima_diaria = getUltimoCampo($conexao, 'diaria', $id_usuario);
    $ultima_unidade = getUltimoCampo($conexao, 'unidade', $id_usuario);
    $ultima_odds = getUltimoCampo($conexao, 'odds', $id_usuario);
    
    // Calcular meta baseada apenas em (depósito - saque)
    $meta_resultado = calcularMetaDiaria($conexao, $id_usuario, $total_deposito, $total_saque);
    
    // Calcular dados para área direita
    $area_direita = calcularAreaDireita($conexao, $id_usuario, $saldo_banca_total);
    
    // ✅ CALCULAR METAS POR PERÍODO
    $metas_periodo = calcularMetasPorPeriodo($meta_resultado['meta_diaria']);
    
    // ✅ RESPOSTA COMPLETA OTIMIZADA
    echo json_encode([
        'success' => true,
        
        // Dados principais da banca
        'banca' => $saldo_banca_total,
        'banca_formatada' => 'R$ ' . number_format($saldo_banca_total, 2, ',', '.'),
        'depositos_total' => $total_deposito,
        'depositos_formatado' => 'R$ ' . number_format($total_deposito, 2, ',', '.'),
        'saques_total' => $total_saque,
        'saques_formatado' => 'R$ ' . number_format($total_saque, 2, ',', '.'),
        'lucro' => $lucro,
        'lucro_formatado' => 'R$ ' . number_format($lucro, 2, ',', '.'),
        'green_total' => $total_green,
        'green_formatado' => 'R$ ' . number_format($total_green, 2, ',', '.'),
        'red_total' => $total_red,
        'red_formatado' => 'R$ ' . number_format($total_red, 2, ',', '.'),
        
        // Configurações atuais
        'diaria' => $ultima_diaria ?? 2,
        'unidade' => $ultima_unidade ?? 2,
        'odds' => $ultima_odds ?? 1.5,
        
        // Meta diária
        'meta_diaria' => $meta_resultado['meta_diaria'],
        'meta_diaria_formatada' => 'R$ ' . number_format($meta_resultado['meta_diaria'], 2, ',', '.'),
        'meta_diaria_brl' => 'R$ ' . number_format($meta_resultado['meta_diaria'], 2, ',', '.'),
        'diaria_usada' => $meta_resultado['diaria_usada'],
        'unidade_usada' => $meta_resultado['unidade_usada'],
        'saldo_base_meta' => $meta_resultado['saldo_banca_meta'],
        
        // Dados específicos para área direita
        'diaria_formatada' => $area_direita['diaria_formatada'],
        'unidade_entrada_formatada' => $area_direita['unidade_entrada_formatada'],
        'diaria_raw' => $area_direita['diaria_porcentagem'],
        'saldo_banca_total' => $area_direita['saldo_banca_total'],
        'unidade_entrada_raw' => $area_direita['unidade_entrada'],
        
        // ✅ METAS POR PERÍODO (ESTRUTURA OTIMIZADA)
        'metas_periodo' => $metas_periodo,
        'meta_mensal' => $metas_periodo['meta_mensal'],
        'meta_mensal_formatada' => $metas_periodo['meta_mensal_formatada'],
        'meta_anual' => $metas_periodo['meta_anual'], 
        'meta_anual_formatada' => $metas_periodo['meta_anual_formatada'],
        'dias_restantes_mes' => $metas_periodo['dias_restantes_mes'],
        'dias_restantes_ano' => $metas_periodo['dias_restantes_ano'],
        'periodo_info' => $metas_periodo['periodo_info'],
        
        // ✅ INFORMAÇÕES DETALHADAS PARA DEBUG
        'calculo_detalhado' => [
            'saldo_banca_total' => $saldo_banca_total,
            'saldo_base_meta' => $meta_resultado['saldo_banca_meta'],
            'depositos' => $total_deposito,
            'saques' => $total_saque,
            'lucro' => $lucro,
            'diaria_percentual' => $meta_resultado['diaria_usada'],
            'unidade_multiplicador' => $meta_resultado['unidade_usada'],
            'formula_meta_diaria' => "Base: R$ " . number_format($total_deposito, 2, ',', '.') . " - R$ " . number_format($total_saque, 2, ',', '.') . " = R$ " . number_format($meta_resultado['saldo_banca_meta'], 2, ',', '.') . " × {$meta_resultado['diaria_usada']}% × {$meta_resultado['unidade_usada']} = R$ " . number_format($meta_resultado['meta_diaria'], 2, ',', '.'),
            
            // Detalhes dos períodos
            'meta_diaria_base' => $meta_resultado['meta_diaria'],
            'meta_mensal_calculada' => $metas_periodo['meta_mensal'],
            'meta_anual_calculada' => $metas_periodo['meta_anual'],
            'dias_mes_atual' => $metas_periodo['dias_restantes_mes'],
            'dias_ano_atual' => $metas_periodo['dias_restantes_ano'],
            'formula_periodo_mensal' => $metas_periodo['periodo_info']['formula_mensal'],
            'formula_periodo_anual' => $metas_periodo['periodo_info']['formula_anual']
        ],
        
        // Dados específicos área direita para debug
        'area_direita_debug' => [
            'formula_unidade' => "Saldo Total: R$ " . number_format($saldo_banca_total, 2, ',', '.') . " × {$area_direita['diaria_porcentagem']}% = {$area_direita['unidade_entrada_formatada']}",
            'saldo_banca_total' => $saldo_banca_total,
            'depositos' => $total_deposito,
            'saques' => $total_saque,
            'lucro' => $lucro,
            'diaria_aplicada' => $area_direita['diaria_porcentagem'],
            'resultado_unidade' => $area_direita['unidade_entrada']
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Erro em dados_banca.php: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Erro interno do servidor: ' . $e->getMessage(),
        'meta_diaria' => 0,
        'meta_diaria_formatada' => 'R$ 0,00',
        'meta_diaria_brl' => 'R$ 0,00',
        'meta_mensal' => 0,
        'meta_mensal_formatada' => 'R$ 0,00',
        'meta_anual' => 0,
        'meta_anual_formatada' => 'R$ 0,00',
        'dias_restantes_mes' => 0,
        'dias_restantes_ano' => 0,
        'diaria_formatada' => '2%',
        'unidade_entrada_formatada' => 'R$ 0,00'
    ]);
}
?>


// php   ---------------------------------------------------------------









js -------------------------------------------------------------



const MetaDiariaManager = {
  // ✅ ATUALIZAR META DIÁRIA COM PERÍODOS
  async atualizarMetaDiaria() {
    try {
      console.log("🔄 Iniciando atualização da meta diária...");

      const response = await fetch("dados_banca.php", {
        method: "GET",
        headers: {
          "Cache-Control": "no-cache",
          "X-Requested-With": "XMLHttpRequest",
        },
      });

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }

      const data = await response.json();
      console.log("📊 Dados recebidos do PHP:", data);

      if (!data.success) {
        throw new Error(data.message || "Erro na resposta do servidor");
      }

      // ✅ APLICAR AJUSTE DE PERÍODO BASEADO NOS RADIO BUTTONS
      const dadosComPeriodo = this.aplicarAjustePeriodo(data);

      // Atualizar diferentes áreas da interface
      this.atualizarAreaDireita(dadosComPeriodo);
      this.atualizarModal(dadosComPeriodo);
      this.atualizarElementoMeta(dadosComPeriodo);

      console.log(
        "✅ Meta diária atualizada:",
        dadosComPeriodo.meta_display_formatada
      );
      return dadosComPeriodo;
    } catch (error) {
      console.error("❌ Erro ao atualizar meta diária:", error);
      if (typeof ToastManager !== "undefined") {
        ToastManager.mostrar("❌ Erro ao calcular meta diária", "erro");
      }
      this.mostrarErroMeta();
      return null;
    }
  },

  // ✅ FUNÇÃO PRINCIPAL PARA AJUSTAR PERÍODO
  aplicarAjustePeriodo(data) {
    try {
      // Verificar qual período está selecionado
      const radioSelecionado = document.querySelector(
        'input[name="periodo"]:checked'
      );

      if (!radioSelecionado) {
        console.log("ℹ️ Nenhum período selecionado, usando DIA como padrão");
        return this.prepararDadosPeriodo(data, "dia");
      }

      const periodo = radioSelecionado.value;
      console.log(`📅 Período detectado: ${periodo.toUpperCase()}`);

      return this.prepararDadosPeriodo(data, periodo);
    } catch (error) {
      console.error("❌ Erro ao aplicar ajuste de período:", error);
      return this.prepararDadosPeriodo(data, "dia"); // Fallback para DIA
    }
  },

  // ✅ PREPARAR DADOS BASEADO NO PERÍODO
  prepararDadosPeriodo(data, periodo) {
    let metaFinal, rotuloFinal, diasInfo;

    switch (periodo) {
      case "mes":
        metaFinal = parseFloat(data.meta_mensal) || 0;
        rotuloFinal = "META DO MÊS";
        diasInfo = `${data.dias_restantes_mes} dias restantes`;
        console.log(
          `📊 Período MÊS: Meta R$ ${metaFinal.toFixed(2)} (${
            data.dias_restantes_mes
          } dias)`
        );
        break;

      case "ano":
        metaFinal = parseFloat(data.meta_anual) || 0;
        rotuloFinal = "META DO ANO";
        diasInfo = `${data.dias_restantes_ano} dias restantes`;
        console.log(
          `📊 Período ANO: Meta R$ ${metaFinal.toFixed(2)} (${
            data.dias_restantes_ano
          } dias)`
        );
        break;

      case "dia":
      default:
        metaFinal = parseFloat(data.meta_diaria) || 0;
        rotuloFinal = "META DO DIA";
        diasInfo = "Meta para hoje";
        console.log(`📊 Período DIA: Meta R$ ${metaFinal.toFixed(2)}`);
        break;
    }

    // Formatar valores
    const metaFormatada =
      "R$ " +
      metaFinal.toLocaleString("pt-BR", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      });

    // Retornar dados modificados para o período
    return {
      ...data, // Manter todos os dados originais

      // Valores ajustados para o período
      meta_display: metaFinal,
      meta_display_formatada: metaFormatada,
      rotulo_periodo: rotuloFinal,
      periodo_ativo: periodo,
      dias_info: diasInfo,

      // Manter compatibilidade com código existente
      meta_diaria_brl: metaFormatada,
      meta_diaria_formatada: metaFormatada,

      // Log para debug
      debug_periodo: {
        periodo_selecionado: periodo,
        meta_original_dia: data.meta_diaria,
        meta_ajustada: metaFinal,
        rotulo: rotuloFinal,
        dias_restantes:
          periodo === "mes"
            ? data.dias_restantes_mes
            : periodo === "ano"
            ? data.dias_restantes_ano
            : 1,
      },
    };
  },

  // ✅ ATUALIZAR ÁREA DIREITA (campo_mentores)
  atualizarAreaDireita(data) {
    console.log("🔄 Atualizando área direita...");

    // Atualizar porcentagem diária
    const porcentagemElement = document.getElementById("porcentagem-diaria");
    if (porcentagemElement && data.diaria_formatada) {
      porcentagemElement.textContent = data.diaria_formatada;
      console.log("✅ Porcentagem diária atualizada:", data.diaria_formatada);
    }

    // Atualizar valor unidade
    const valorUnidadeElement = document.getElementById("valor-unidade");
    if (valorUnidadeElement && data.unidade_entrada_formatada) {
      valorUnidadeElement.textContent = data.unidade_entrada_formatada;
      console.log(
        "✅ Valor unidade atualizado:",
        data.unidade_entrada_formatada
      );
    }
  },

  // ✅ ATUALIZAR MODAL (modal-gerencia-banca)
  atualizarModal(data) {
    console.log("🔄 Atualizando modal...");

    // Atualizar valor da banca no modal
    const valorBancaLabel = document.getElementById("valorBancaLabel");
    if (valorBancaLabel && data.banca_formatada) {
      valorBancaLabel.textContent = data.banca_formatada;
      console.log("✅ Banca no modal atualizada:", data.banca_formatada);
    }

    // Atualizar valor do lucro no modal
    const valorLucroLabel = document.getElementById("valorLucroLabel");
    if (valorLucroLabel && data.lucro_formatado) {
      valorLucroLabel.textContent = data.lucro_formatado;
      console.log("✅ Lucro no modal atualizado:", data.lucro_formatado);
    }

    // ✅ APLICAR COR NO LUCRO BASEADO NO VALOR
    const lucroValor = parseFloat(data.lucro) || 0;
    const iconeLucro = document.getElementById("iconeLucro");
    const lucroLabel = document.getElementById("lucroLabel");

    if (iconeLucro && lucroLabel && valorLucroLabel) {
      if (lucroValor > 0) {
        iconeLucro.className = "fa-solid fa-money-bill-trend-up";
        lucroLabel.style.color = "#4CAF50"; // Verde
        valorLucroLabel.style.color = "#4CAF50";
      } else if (lucroValor < 0) {
        iconeLucro.className = "fa-solid fa-money-bill-trend-down";
        lucroLabel.style.color = "#f44336"; // Vermelho
        valorLucroLabel.style.color = "#f44336";
      } else {
        iconeLucro.className = "fa-solid fa-money-bill-trend-up";
        lucroLabel.style.color = "#666"; // Neutro
        valorLucroLabel.style.color = "#666";
      }
    }
  },

  // ✅ FUNÇÃO PRINCIPAL PARA ATUALIZAR ELEMENTO META
  atualizarElementoMeta(data) {
    console.log(
      "🎯 Atualizando elemento meta com dados do período:",
      data.debug_periodo
    );

    // Buscar elemento da meta
    const metaElement = this.buscarElementoMeta();
    const rotuloElement = this.buscarElementoRotulo();

    if (!metaElement) {
      console.warn("⚠️ Elemento da meta não encontrado!");
      return;
    }

    // Extrair valores necessários
    const saldoDia = parseFloat(data.lucro) || 0; // Lucro atual
    const metaCalculada = parseFloat(data.meta_display) || 0; // Meta do período selecionado
    const bancaTotal = parseFloat(data.banca) || 0; // Banca total

    console.log("📊 Valores para cálculo:", {
      saldoDia,
      metaCalculada,
      bancaTotal,
      periodo: data.periodo_ativo || "dia",
    });

    // Aplicar regras de negócio
    const resultado = this.calcularMetaFinal(
      saldoDia,
      metaCalculada,
      bancaTotal,
      data
    );

    // Atualizar interface
    this.atualizarInterfaceMeta(metaElement, rotuloElement, resultado);

    // Log final
    console.log("🎯 Meta atualizada:", {
      valorFinal: resultado.metaFinalFormatada,
      rotulo: resultado.rotulo,
      status: resultado.statusClass,
      periodo: data.periodo_ativo,
    });
  },

  // ✅ BUSCAR ELEMENTO DA META COM MÚLTIPLAS ESTRATÉGIAS
  buscarElementoMeta() {
    const possiveisElementos = [
      document.getElementById("meta-diaria-ajax"),
      document.getElementById("meta-valor"),
      document.querySelector(".meta-valor"),
      document.querySelector(".valor-meta"),
      document.querySelector("[data-meta]"),
    ];

    return possiveisElementos.find((el) => el !== null);
  },

  // ✅ BUSCAR ELEMENTO DO RÓTULO
  buscarElementoRotulo() {
    return (
      document.querySelector(".rotulo-meta") ||
      document.getElementById("rotulo-meta") ||
      document.querySelector("[data-rotulo]")
    );
  },

  // ✅ CALCULAR META FINAL BASEADA NAS REGRAS DE NEGÓCIO
  calcularMetaFinal(saldoDia, metaCalculada, bancaTotal, data) {
    let metaFinal,
      rotulo,
      statusClass,
      valorExtra = 0;

    // REGRA 1: Banca total <= 0 - Precisa depositar
    if (bancaTotal <= 0) {
      metaFinal = bancaTotal;
      rotulo = "DEPOSITE P/ COMEÇAR";
      statusClass = "sem-banca";
    }
    // REGRA 2: Meta foi batida (lucro >= meta)
    else if (saldoDia >= metaCalculada) {
      metaFinal = 0;
      rotulo = `${
        data.rotulo_periodo || "META"
      } BATIDA! <i class='fa-solid fa-trophy'></i>`;
      statusClass = "meta-batida";
      valorExtra = saldoDia - metaCalculada;
    }
    // REGRA 3: Lucro negativo
    else if (saldoDia < 0) {
      metaFinal = metaCalculada - saldoDia; // Meta + prejuízo
      rotulo = `RESTANDO P/ ${data.rotulo_periodo || "META"}`;
      statusClass = "negativo";
    }
    // REGRA 4: Lucro zero
    else if (saldoDia === 0) {
      metaFinal = metaCalculada;
      rotulo = data.rotulo_periodo || "META DO DIA";
      statusClass = "neutro";
    }
    // REGRA 5: Lucro positivo mas não bateu meta
    else {
      metaFinal = metaCalculada - saldoDia;
      rotulo = `RESTANDO P/ ${data.rotulo_periodo || "META"}`;
      statusClass = "lucro";
    }

    // Formatar valor final
    const metaFinalFormatada = metaFinal.toLocaleString("pt-BR", {
      style: "currency",
      currency: "BRL",
    });

    return {
      metaFinal,
      metaFinalFormatada,
      rotulo,
      statusClass,
      valorExtra,
    };
  },

  // ✅ ATUALIZAR INTERFACE DO ELEMENTO META
  atualizarInterfaceMeta(metaElement, rotuloElement, resultado) {
    // Remove texto de loading se existir
    const loadingText = metaElement.querySelector(".loading-text");
    if (loadingText) {
      loadingText.remove();
    }

    // Atualizar valor principal
    this.atualizarElementoComEstrategias(
      metaElement,
      resultado.metaFinalFormatada,
      resultado.statusClass
    );

    // Atualizar rótulo
    if (rotuloElement) {
      rotuloElement.innerHTML = resultado.rotulo;
      console.log("✅ Rótulo atualizado:", resultado.rotulo);
    }

    // Mostrar valor extra se meta foi batida
    if (resultado.valorExtra > 0) {
      this.mostrarValorExtra(resultado.valorExtra);
    } else {
      this.ocultarValorExtra();
    }

    // Aplicar animação
    this.aplicarAnimacao(metaElement);
  },

  // ✅ ATUALIZAR ELEMENTO COM MÚLTIPLAS ESTRATÉGIAS
  atualizarElementoComEstrategias(elemento, valor, statusClass) {
    // Estratégia 1: Tentar encontrar .valor-texto
    let valorTexto = elemento.querySelector(".valor-texto");

    if (valorTexto) {
      console.log("✅ Estratégia 1: Atualizando .valor-texto");
      valorTexto.textContent = valor;
    } else {
      // Estratégia 2: Verificar se tem ícone e criar estrutura
      const icone = elemento.querySelector("i.fa-solid, .fa-coins");

      if (icone) {
        console.log("✅ Estratégia 2: Criando estrutura com ícone");
        elemento.innerHTML = "";
        elemento.appendChild(icone);

        const span = document.createElement("span");
        span.className = "valor-texto";
        span.textContent = valor;
        elemento.appendChild(span);
      } else {
        // Estratégia 3: Atualizar textContent diretamente
        console.log("✅ Estratégia 3: Atualizando textContent");
        elemento.textContent = valor;
      }
    }

    // Aplicar classes CSS baseadas no status
    elemento.className = "valor-meta " + statusClass;

    console.log("✅ Elemento atualizado:", {
      conteudo: elemento.innerHTML || elemento.textContent,
      classes: elemento.className,
    });
  },

  // ✅ MOSTRAR VALOR EXTRA QUANDO META É BATIDA
  mostrarValorExtra(valorExtra) {
    const valorUltrapassouElement =
      document.getElementById("valor-ultrapassou");
    const valorExtraElement = document.getElementById("valor-extra");

    if (valorUltrapassouElement && valorExtraElement && valorExtra > 0) {
      const valorExtraFormatado = valorExtra.toLocaleString("pt-BR", {
        style: "currency",
        currency: "BRL",
      });

      valorExtraElement.textContent = valorExtraFormatado;
      valorUltrapassouElement.style.display = "flex";
      valorUltrapassouElement.classList.add("mostrar");
      console.log("✅ Valor extra mostrado:", valorExtraFormatado);
    }
  },

  // ✅ OCULTAR VALOR EXTRA
  ocultarValorExtra() {
    const valorUltrapassouElement =
      document.getElementById("valor-ultrapassou");
    const valorExtraElement = document.getElementById("valor-extra");

    if (valorUltrapassouElement && valorExtraElement) {
      valorExtraElement.textContent = "R$ 0,00";
      valorUltrapassouElement.style.display = "none";
      valorUltrapassouElement.classList.remove("mostrar");
    }
  },

  // ✅ APLICAR ANIMAÇÃO
  aplicarAnimacao(elemento) {
    elemento.classList.add("atualizado");
    setTimeout(() => {
      elemento.classList.remove("atualizado");
    }, 1500);
  },

  // ✅ MOSTRAR ERRO
  mostrarErroMeta() {
    const metaElement = this.buscarElementoMeta();
    if (metaElement) {
      metaElement.innerHTML = '<span style="color: #e74c3c;">R$ 0,00</span>';
      console.log("❌ Erro mostrado na meta");
    }
  },

  // ✅ INICIALIZAÇÃO
  async inicializar() {
    console.log("🚀 Inicializando MetaDiariaManager...");

    // Mostrar loading em todos os elementos possíveis
    const metaElement = this.buscarElementoMeta();
    if (metaElement) {
      metaElement.innerHTML = '<span class="loading-text">Calculando...</span>';
    }

    // ✅ CONFIGURAR LISTENERS PARA OS RADIO BUTTONS DE PERÍODO
    this.configurarListenersPeriodo();

    // Aguardar um pouco e atualizar
    setTimeout(() => {
      this.atualizarMetaDiaria();
    }, 500);
  },

  // ✅ CONFIGURAR LISTENERS PARA MUDANÇA DE PERÍODO
  configurarListenersPeriodo() {
    const radiosPeriodo = document.querySelectorAll('input[name="periodo"]');

    radiosPeriodo.forEach((radio) => {
      radio.addEventListener("change", (event) => {
        const periodoSelecionado = event.target.value;
        console.log(
          `📅 Período alterado para: ${periodoSelecionado.toUpperCase()}`
        );

        // Atualizar meta quando período mudar
        setTimeout(() => {
          this.atualizarMetaDiaria();
        }, 100);
      });
    });

    // Se não há radio buttons, criar um padrão DIA
    if (radiosPeriodo.length === 0) {
      console.log(
        "ℹ️ Nenhum radio button de período encontrado, usando DIA como padrão"
      );
    } else {
      console.log(
        `✅ ${radiosPeriodo.length} radio buttons de período configurados`
      );
    }
  },

  // ✅ OBSERVER PARA MUDANÇAS NO SALDO
  atualizarQuandoSaldoMudar() {
    const saldoDiaElement = document.querySelector(".valor-saldo");

    if (saldoDiaElement) {
      const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
          if (
            mutation.type === "childList" ||
            mutation.type === "characterData"
          ) {
            console.log("🔄 Saldo alterado, recalculando meta...");
            setTimeout(() => {
              this.atualizarMetaDiaria();
            }, 300);
          }
        });
      });

      observer.observe(saldoDiaElement, {
        childList: true,
        subtree: true,
        characterData: true,
      });

      console.log("👀 Observer configurado para saldo");
    }
  },
};

// ========================================
// WIDGET INTEGRADO COM PERÍODOS
// ========================================

const MetaProgressoWidget = {
  metaCalculada: 0,
  saldoDia: 0,
  metaFinal: 0,
  bancaTotal: 0,
  periodoAtivo: "dia",

  // ✅ INTEGRAR COM MetaDiariaManager
  integrarComMetaDiariaManager() {
    if (typeof MetaDiariaManager !== "undefined") {
      const originalFunc = MetaDiariaManager.atualizarElementoMeta;

      MetaDiariaManager.atualizarElementoMeta = (data) => {
        if (originalFunc) {
          originalFunc.call(MetaDiariaManager, data);
        }

        setTimeout(() => {
          this.atualizarWidget(data);
        }, 100);
      };

      console.log("🔗 Widget integrado com MetaDiariaManager");
    }
  },

  // ✅ ATUALIZAR WIDGET COM DADOS DO PERÍODO
  atualizarWidget(data) {
    try {
      console.log(
        "🔄 Atualizando widget com dados do período:",
        data.debug_periodo
      );

      // Usar dados ajustados para o período
      this.metaCalculada = parseFloat(data.meta_display) || 0;
      this.saldoDia = parseFloat(data.lucro) || 0;
      this.bancaTotal = parseFloat(data.banca) || 0;
      this.periodoAtivo = data.periodo_ativo || "dia";

      // Aplicar regras de negócio
      this.aplicarRegrasNegocio();

      // Atualizar interface
      this.atualizarInterface();

      console.log(
        "✅ Widget atualizado para período:",
        this.periodoAtivo.toUpperCase()
      );
    } catch (error) {
      console.error("❌ Erro no widget:", error);
    }
  },

  // ✅ APLICAR REGRAS DE NEGÓCIO
  aplicarRegrasNegocio() {
    // REGRA 1: Banca total <= 0
    if (this.bancaTotal <= 0) {
      this.metaFinal = this.bancaTotal;
      this.statusMeta = "sem-banca";
      this.rotulo = "DEPOSITE P/ COMEÇAR";
      this.textoSaldo = "Saldo";
      this.valorExtra = 0;
    }
    // REGRA 2: Meta batida (lucro >= meta)
    else if (this.saldoDia >= this.metaCalculada) {
      this.metaFinal = 0;
      this.statusMeta = "meta-batida";
      this.rotulo = `META BATIDA! <i class='fa-solid fa-trophy'></i>`;
      this.textoSaldo = "Lucro";
      this.valorExtra = this.saldoDia - this.metaCalculada;
    }
    // REGRA 3: Lucro negativo
    else if (this.saldoDia < 0) {
      this.metaFinal = this.metaCalculada - this.saldoDia;
      this.statusMeta = "negativo";
      this.rotulo = "RESTANDO P/ META";
      this.textoSaldo = "Negativo";
      this.valorExtra = 0;
    }
    // REGRA 4: Lucro zero
    else if (this.saldoDia === 0) {
      this.metaFinal = this.metaCalculada;
      this.statusMeta = "neutro";
      this.rotulo = `META ${this.periodoAtivo.toUpperCase()}`;
      this.textoSaldo = "Neutro";
      this.valorExtra = 0;
    }
    // REGRA 5: Lucro positivo mas meta não batida
    else {
      this.metaFinal = this.metaCalculada - this.saldoDia;
      this.statusMeta = "lucro";
      this.rotulo = "RESTANDO P/ META";
      this.textoSaldo = "Lucro";
      this.valorExtra = 0;
    }
  },

  // ✅ FORMATAR MOEDA
  formatarMoeda(valor) {
    return valor.toLocaleString("pt-BR", {
      style: "currency",
      currency: "BRL",
    });
  },

  // ✅ CALCULAR PROGRESSO
  calcularProgresso() {
    if (this.bancaTotal <= 0) {
      return 0;
    }

    if (this.statusMeta === "meta-batida") {
      return 100;
    }

    if (this.saldoDia < 0) {
      const progressoNegativo =
        Math.abs(this.saldoDia / this.metaCalculada) * 100;
      return -Math.min(progressoNegativo, 100);
    }

    if (this.metaCalculada === 0) return 0;
    return Math.max(
      0,
      Math.min(100, (this.saldoDia / this.metaCalculada) * 100)
    );
  },

  // ✅ ATUALIZAR INTERFACE COMPLETA
  atualizarInterface() {
    const metaValor = document.getElementById("meta-valor");
    const rotuloMeta = document.getElementById("rotulo-meta");
    const saldoInfo = document.getElementById("saldo-info");
    const barraProgresso = document.getElementById("barra-progresso");
    const valorUltrapassou = document.getElementById("valor-ultrapassou");
    const valorExtra = document.getElementById("valor-extra");

    if (!metaValor && !barraProgresso) {
      console.log("⚠️ Elementos do widget não encontrados");
      return;
    }

    // Atualizar valor principal
    if (metaValor) {
      this.atualizarValorPrincipal(metaValor);
    }

    const progresso = this.calcularProgresso();

    // Atualizar saldo com cores condicionais
    if (saldoInfo) {
      this.atualizarSaldoInfo(saldoInfo);
    }

    // Atualizar rótulo
    if (rotuloMeta) {
      rotuloMeta.innerHTML = this.rotulo;
    }

    // Controlar lucro extra
    if (valorUltrapassou && valorExtra) {
      this.controlarLucroExtra(valorUltrapassou, valorExtra);
    }

    // Atualizar barra de progresso
    if (barraProgresso) {
      this.atualizarBarra(barraProgresso, progresso);
      this.aplicarCores(metaValor, rotuloMeta, barraProgresso, progresso);
    }
  },

  // ✅ ATUALIZAR VALOR PRINCIPAL
  atualizarValorPrincipal(metaValor) {
    const valorTextoElement = metaValor.querySelector(".valor-texto");
    const loadingText = metaValor.querySelector(".loading-text");

    if (loadingText) {
      loadingText.remove();
    }

    const valorParaMostrar = this.formatarMoeda(this.metaFinal);

    if (valorTextoElement) {
      valorTextoElement.textContent = valorParaMostrar;
    } else {
      const icone = metaValor.querySelector(".fa-solid.fa-coins");
      if (icone) {
        metaValor.innerHTML = "";
        metaValor.appendChild(icone);
        const novoSpan = document.createElement("span");
        novoSpan.className = "valor-texto";
        novoSpan.textContent = valorParaMostrar;
        metaValor.appendChild(novoSpan);
      } else {
        metaValor.innerHTML = `
          <i class="fa-solid fa-coins"></i>
          <span class="valor-texto">${valorParaMostrar}</span>
        `;
      }
    }
  },

  // ✅ ATUALIZAR SALDO INFO
  atualizarSaldoInfo(saldoInfo) {
    let classCor = "saldo-zero";
    if (this.saldoDia > 0) {
      classCor = "saldo-positivo";
    } else if (this.saldoDia < 0) {
      classCor = "saldo-negativo";
    }

    saldoInfo.className = classCor;
    saldoInfo.innerHTML = `
      <i class="fa-solid fa-wallet"></i>
      ${this.textoSaldo}: ${this.formatarMoeda(this.saldoDia)}
    `;
  },

  // ✅ CONTROLAR LUCRO EXTRA
  controlarLucroExtra(valorUltrapassou, valorExtra) {
    if (this.valorExtra > 0 && this.statusMeta === "meta-batida") {
      valorExtra.textContent = this.formatarMoeda(this.valorExtra);
      valorUltrapassou.style.display = "flex";
      valorUltrapassou.classList.add("mostrar");
    } else {
      valorExtra.textContent = "R$ 0,00";
      valorUltrapassou.style.display = "none";
      valorUltrapassou.classList.remove("mostrar");
    }
  },

  // ✅ ATUALIZAR BARRA COM PORCENTAGEM
  atualizarBarra(barraProgresso, progresso) {
    const porcentagemTexto = document.getElementById("porcentagem-barra");

    let larguraBarra = Math.abs(progresso);
    if (this.bancaTotal <= 0) larguraBarra = 0;
    if (this.statusMeta === "meta-batida") larguraBarra = 100;

    barraProgresso.style.width = `${larguraBarra}%`;

    if (progresso < 0) {
      barraProgresso.classList.add("barra-negativa");
    } else {
      barraProgresso.classList.remove("barra-negativa");
    }

    // Porcentagem na ponta da barra
    if (porcentagemTexto) {
      porcentagemTexto.textContent = Math.round(progresso) + "%";

      if (larguraBarra <= 0) {
        porcentagemTexto.style.display = "none";
      } else if (larguraBarra < 15) {
        porcentagemTexto.style.display = "block";
        porcentagemTexto.style.left = `${larguraBarra + 3}%`;
        porcentagemTexto.style.color = this.obterCorBarra(progresso);
      } else {
        porcentagemTexto.style.display = "block";
        porcentagemTexto.style.left = `${larguraBarra - 10}%`;
        porcentagemTexto.style.color = "#fff";
      }
    }
  },

  // ✅ OBTER COR DA BARRA
  obterCorBarra(progresso) {
    if (progresso < 0) return "#e74c3c";
    if (this.statusMeta === "meta-batida") return "#2196f3";
    return "#4caf50";
  },

  // ✅ APLICAR CORES
  aplicarCores(metaValor, rotuloMeta, barraProgresso, progresso) {
    const larguraBarra =
      this.bancaTotal <= 0
        ? 0
        : this.statusMeta === "meta-batida"
        ? 100
        : Math.abs(progresso);

    let corBarra = "#9E9E9E";
    let corTexto = "#7f8c8d";

    switch (this.statusMeta) {
      case "sem-banca":
        corBarra = "#e67e22";
        corTexto = "#e67e22";
        break;
      case "meta-batida":
        corBarra = "#2196F3";
        corTexto = "#2196F3";
        break;
      case "negativo":
        corBarra = "#f44336";
        corTexto = "#e74c3c";
        break;
      case "neutro":
        corBarra = "#95a5a6";
        corTexto = "#7f8c8d";
        break;
      case "lucro":
        corBarra = "#4CAF50";
        corTexto = "#00a651";
        break;
    }

    if (metaValor) {
      const valorTexto = metaValor.querySelector(".valor-texto");
      if (valorTexto) {
        valorTexto.style.color = corTexto;
      }
    }

    barraProgresso.style.cssText = `
      width: ${larguraBarra}% !important;
      height: 100% !important;
      background-color: ${corBarra} !important;
      background: ${corBarra} !important;
      border-radius: 20px !important;
    `;
  },

  // ✅ INICIALIZAÇÃO
  inicializar() {
    console.log("🚀 Inicializando Widget com períodos...");

    this.integrarComMetaDiariaManager();

    setTimeout(() => {
      if (typeof MetaDiariaManager !== "undefined") {
        MetaDiariaManager.atualizarMetaDiaria();
      }
    }, 1500);

    console.log("✅ Widget integrado com sistema de períodos");
  },
};

// ========================================
// FUNÇÕES GLOBAIS E UTILITÁRIAS
// ========================================

// ✅ FUNÇÃO GLOBAL PARA ATUALIZAR META
window.atualizarMetaDiaria = () => {
  console.log("🔄 Função global: atualizarMetaDiaria chamada");
  return MetaDiariaManager.atualizarMetaDiaria();
};

// ✅ FUNÇÃO PARA FORÇAR ATUALIZAÇÃO
window.forcarAtualizacaoMeta = async () => {
  console.log("🔄 Forçando atualização completa da meta...");
  try {
    const data = await MetaDiariaManager.atualizarMetaDiaria();
    if (data && typeof MetaProgressoWidget !== "undefined") {
      MetaProgressoWidget.atualizarWidget(data);
    }
    console.log("✅ Atualização forçada concluída");
    return data;
  } catch (error) {
    console.error("❌ Erro na atualização forçada:", error);
    return null;
  }
};

// ✅ FUNÇÃO PARA ALTERAR PERÍODO PROGRAMATICAMENTE
window.alterarPeriodo = (periodo) => {
  const radio = document.querySelector(
    `input[name="periodo"][value="${periodo}"]`
  );
  if (radio) {
    radio.checked = true;
    radio.dispatchEvent(new Event("change"));
    console.log(`📅 Período alterado para: ${periodo.toUpperCase()}`);
    return true;
  } else {
    console.warn(`⚠️ Radio button para período "${periodo}" não encontrado`);
    return false;
  }
};

// ✅ FUNÇÃO DE DEBUG PARA PERÍODOS
window.debugPeriodos = () => {
  console.log("🔍 DEBUG PERÍODOS:");

  const radiosPeriodo = document.querySelectorAll('input[name="periodo"]');
  const radioSelecionado = document.querySelector(
    'input[name="periodo"]:checked'
  );

  console.log("Radio buttons encontrados:", radiosPeriodo.length);

  radiosPeriodo.forEach((radio, index) => {
    console.log(
      `  ${index + 1}. ${radio.value} - ${
        radio.checked ? "✅ SELECIONADO" : "❌"
      }`
    );
  });

  console.log(
    "Período ativo:",
    radioSelecionado ? radioSelecionado.value : "NENHUM"
  );

  return {
    total: radiosPeriodo.length,
    selecionado: radioSelecionado ? radioSelecionado.value : null,
    radios: Array.from(radiosPeriodo).map((r) => ({
      value: r.value,
      checked: r.checked,
    })),
  };
};

// ✅ FUNÇÃO PARA TESTAR TODOS OS PERÍODOS
window.testarPeriodos = async () => {
  console.log("🧪 Testando todos os períodos...");

  const periodos = ["dia", "mes", "ano"];
  const resultados = {};

  for (const periodo of periodos) {
    console.log(`\n📅 Testando período: ${periodo.toUpperCase()}`);

    // Alterar período
    const alterou = window.alterarPeriodo(periodo);
    if (!alterou) {
      console.warn(`⚠️ Não foi possível alterar para ${periodo}`);
      continue;
    }

    // Aguardar um pouco
    await new Promise((resolve) => setTimeout(resolve, 500));

    // Buscar dados
    try {
      const response = await fetch("dados_banca.php");
      const data = await response.json();

      if (data.success) {
        resultados[periodo] = {
          meta_diaria: data.meta_diaria,
          meta_mensal: data.meta_mensal,
          meta_anual: data.meta_anual,
          dias_mes: data.dias_restantes_mes,
          dias_ano: data.dias_restantes_ano,
        };

        console.log(`✅ ${periodo.toUpperCase()}:`, {
          metaCalculada:
            periodo === "dia"
              ? data.meta_diaria
              : periodo === "mes"
              ? data.meta_mensal
              : data.meta_anual,
          diasRestantes:
            periodo === "dia"
              ? 1
              : periodo === "mes"
              ? data.dias_restantes_mes
              : data.dias_restantes_ano,
        });
      }
    } catch (error) {
      console.error(`❌ Erro ao testar ${periodo}:`, error);
    }
  }

  console.log("\n📊 RESUMO DOS TESTES:");
  console.table(resultados);

  return resultados;
};

// ✅ FUNÇÃO PARA SIMULAR DADOS COM PERÍODOS
window.simularDadosPeriodos = (banca = 1000, meta = 20, lucro = 0) => {
  console.log("🧪 Simulando dados com cálculos de período...");

  // Simular cálculo de dias (usando data atual)
  const hoje = new Date();
  const ultimoDiaMes = new Date(
    hoje.getFullYear(),
    hoje.getMonth() + 1,
    0
  ).getDate();
  const diaAtual = hoje.getDate();
  const diasRestantesMes = ultimoDiaMes - diaAtual + 1;

  const fimAno = new Date(hoje.getFullYear(), 11, 31);
  const diasRestantesAno =
    Math.ceil((fimAno - hoje) / (1000 * 60 * 60 * 24)) + 1;

  const dadosSimulados = {
    success: true,
    banca: banca,
    meta_diaria: meta,
    meta_mensal: meta * diasRestantesMes,
    meta_anual: meta * diasRestantesAno,
    lucro: lucro,
    dias_restantes_mes: diasRestantesMes,
    dias_restantes_ano: diasRestantesAno,

    // Formatações
    meta_diaria_formatada: `R$ ${meta.toFixed(2).replace(".", ",")}`,
    meta_mensal_formatada: `R$ ${(meta * diasRestantesMes)
      .toFixed(2)
      .replace(".", ",")}`,
    meta_anual_formatada: `R$ ${(meta * diasRestantesAno)
      .toFixed(2)
      .replace(".", ",")}`,
    banca_formatada: `R$ ${banca.toFixed(2).replace(".", ",")}`,
    lucro_formatado: `R$ ${lucro.toFixed(2).replace(".", ",")}`,

    // Debug
    periodo_info: {
      data_hoje: hoje.toISOString().split("T")[0],
      calculo_mes: `Restam ${diasRestantesMes} de ${ultimoDiaMes} dias do mês`,
      calculo_ano: `Restam ${diasRestantesAno} dias do ano`,
      formula_mensal: `R$ ${meta.toFixed(2)} × ${diasRestantesMes} dias = R$ ${(
        meta * diasRestantesMes
      ).toFixed(2)}`,
      formula_anual: `R$ ${meta.toFixed(2)} × ${diasRestantesAno} dias = R$ ${(
        meta * diasRestantesAno
      ).toFixed(2)}`,
    },
  };

  console.log("📊 Dados simulados com períodos:", dadosSimulados);

  // Aplicar dados simulados
  const dadosComPeriodo =
    MetaDiariaManager.aplicarAjustePeriodo(dadosSimulados);
  MetaDiariaManager.atualizarElementoMeta(dadosComPeriodo);

  if (typeof MetaProgressoWidget !== "undefined") {
    MetaProgressoWidget.atualizarWidget(dadosComPeriodo);
  }

  return dadosSimulados;
};

// ========================================
// INICIALIZAÇÃO AUTOMÁTICA
// ========================================

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", () => {
    console.log("📄 DOM carregado, inicializando sistemas com períodos...");
    MetaDiariaManager.inicializar();
    MetaProgressoWidget.inicializar();
    MetaDiariaManager.atualizarQuandoSaldoMudar();
  });
} else {
  console.log("📄 DOM já carregado, inicializando sistemas com períodos...");
  MetaDiariaManager.inicializar();
  MetaProgressoWidget.inicializar();
  MetaDiariaManager.atualizarQuandoSaldoMudar();
}

// ========================================
// ATALHOS PARA DESENVOLVIMENTO
// ========================================

window.$ = {
  debug: () => debugPeriodos(),
  test: () => testarPeriodos(),
  force: () => forcarAtualizacaoMeta(),
  simulate: (banca, meta, lucro) => simularDadosPeriodos(banca, meta, lucro),
  periodo: (p) => alterarPeriodo(p),
  dia: () => alterarPeriodo("dia"),
  mes: () => alterarPeriodo("mes"),
  ano: () => alterarPeriodo("ano"),
};

// ========================================
// LOGS FINAIS
// ========================================

console.log("✅ Sistema de Meta Diária com PERÍODOS - CARREGADO!");
console.log("🔧 Funções disponíveis:");
console.log("  - atualizarMetaDiaria()");
console.log("  - forcarAtualizacaoMeta()");
console.log("  - alterarPeriodo('dia'|'mes'|'ano')");
console.log("  - debugPeriodos()");
console.log("  - testarPeriodos()");
console.log("  - simularDadosPeriodos(banca, meta, lucro)");
console.log("🎯 Atalhos rápidos:");
console.log("  - $.debug() - Debug períodos");
console.log("  - $.test() - Testar todos períodos");
console.log("  - $.force() - Forçar atualização");
console.log("  - $.dia() - Selecionar período DIA");
console.log("  - $.mes() - Selecionar período MÊS");
console.log("  - $.ano() - Selecionar período ANO");
console.log("  - $.simulate(1000, 20, 5) - Simular dados");
console.log("📱 Execute $.debug() para verificar períodos!");





js 2 -----------------------------

const MetaDiariaManager = {
  // ✅ CONTROLE SIMPLES
  atualizandoAtualmente: false,

  // ✅ ATUALIZAR META DIÁRIA - VERSÃO ESTÁVEL
  async atualizarMetaDiaria() {
    if (this.atualizandoAtualmente) return null;

    this.atualizandoAtualmente = true;

    try {
      const response = await fetch("dados_banca.php", {
        method: "GET",
        headers: {
          "Cache-Control": "no-cache",
          "X-Requested-With": "XMLHttpRequest",
        },
      });

      if (!response.ok) throw new Error(`HTTP ${response.status}`);

      const data = await response.json();
      if (!data.success) throw new Error(data.message);

      // Aplicar período e atualizar
      const dadosComPeriodo = this.aplicarAjustePeriodo(data);
      this.atualizarTodosElementos(dadosComPeriodo);

      return dadosComPeriodo;
    } catch (error) {
      console.error("❌ Erro:", error);
      this.mostrarErroMeta();
      return null;
    } finally {
      this.atualizandoAtualmente = false;
    }
  },

  // ✅ APLICAR AJUSTE DE PERÍODO - VERSÃO ESTÁVEL
  aplicarAjustePeriodo(data) {
    const radioSelecionado = document.querySelector(
      'input[name="periodo"]:checked'
    );
    const periodo = radioSelecionado?.value || "dia";

    let metaFinal, rotuloFinal;

    switch (periodo) {
      case "mes":
        metaFinal = parseFloat(data.meta_mensal) || 0;
        rotuloFinal = "META DO MÊS";
        break;
      case "ano":
        metaFinal = parseFloat(data.meta_anual) || 0;
        rotuloFinal = "META DO ANO";
        break;
      default:
        metaFinal = parseFloat(data.meta_diaria) || 0;
        rotuloFinal = "META DO DIA";
        break;
    }

    return {
      ...data,
      meta_display: metaFinal,
      meta_display_formatada:
        "R$ " +
        metaFinal.toLocaleString("pt-BR", {
          minimumFractionDigits: 2,
          maximumFractionDigits: 2,
        }),
      rotulo_periodo: rotuloFinal,
      periodo_ativo: periodo,
    };
  },

  // ✅ ATUALIZAR TODOS OS ELEMENTOS - VERSÃO ESTÁVEL
  atualizarTodosElementos(data) {
    // Calcular valores uma vez
    const saldoDia = parseFloat(data.lucro) || 0;
    const metaCalculada = parseFloat(data.meta_display) || 0;
    const bancaTotal = parseFloat(data.banca) || 0;
    const resultado = this.calcularMetaFinal(
      saldoDia,
      metaCalculada,
      bancaTotal,
      data
    );

    // Atualizar em sequência
    this.atualizarAreaDireita(data);
    this.atualizarModal(data);
    this.atualizarMetaElemento(resultado);
    this.atualizarRotulo(resultado.rotulo);
    this.atualizarValorExtra(resultado.valorExtra);
    this.atualizarBarraProgresso(resultado, data);
  },

  // ✅ ATUALIZAR ÁREA DIREITA - ESTÁVEL
  atualizarAreaDireita(data) {
    const porcentagemElement = document.getElementById("porcentagem-diaria");
    if (porcentagemElement && data.diaria_formatada) {
      porcentagemElement.textContent = data.diaria_formatada;
    }

    const valorUnidadeElement = document.getElementById("valor-unidade");
    if (valorUnidadeElement && data.unidade_entrada_formatada) {
      valorUnidadeElement.textContent = data.unidade_entrada_formatada;
    }
  },

  // ✅ ATUALIZAR MODAL - ESTÁVEL
  atualizarModal(data) {
    const valorBancaLabel = document.getElementById("valorBancaLabel");
    if (valorBancaLabel && data.banca_formatada) {
      valorBancaLabel.textContent = data.banca_formatada;
    }

    const valorLucroLabel = document.getElementById("valorLucroLabel");
    if (valorLucroLabel && data.lucro_formatado) {
      valorLucroLabel.textContent = data.lucro_formatado;
    }

    // ✅ APLICAR CORES DO LUCRO - USANDO CLASSES CSS
    const lucroValor = parseFloat(data.lucro) || 0;
    const iconeLucro = document.getElementById("iconeLucro");
    const lucroLabel = document.getElementById("lucroLabel");

    if (iconeLucro && lucroLabel && valorLucroLabel) {
      // Remover classes anteriores
      lucroLabel.className = lucroLabel.className.replace(
        /modal-lucro-\w+/g,
        ""
      );
      valorLucroLabel.className = valorLucroLabel.className.replace(
        /modal-lucro-\w+/g,
        ""
      );

      if (lucroValor > 0) {
        iconeLucro.className = "fa-solid fa-money-bill-trend-up";
        lucroLabel.classList.add("modal-lucro-positivo");
        valorLucroLabel.classList.add("modal-lucro-positivo");
      } else if (lucroValor < 0) {
        iconeLucro.className = "fa-solid fa-money-bill-trend-down";
        lucroLabel.classList.add("modal-lucro-negativo");
        valorLucroLabel.classList.add("modal-lucro-negativo");
      } else {
        iconeLucro.className = "fa-solid fa-money-bill-trend-up";
        lucroLabel.classList.add("modal-lucro-neutro");
        valorLucroLabel.classList.add("modal-lucro-neutro");
      }
    }
  },

  // ✅ CALCULAR META FINAL - LÓGICA CORRIGIDA PARA LUCRO EXTRA
  calcularMetaFinal(saldoDia, metaCalculada, bancaTotal, data) {
    let metaFinal,
      rotulo,
      statusClass,
      valorExtra = 0;

    // ✅ REGRA 1: Banca total <= 0 - Precisa depositar
    if (bancaTotal <= 0) {
      metaFinal = bancaTotal;
      rotulo = "DEPOSITE P/ COMEÇAR";
      statusClass = "sem-banca";
      valorExtra = 0; // ✅ SEM lucro extra
    }
    // ✅ REGRA 2: Meta foi batida E tem lucro extra (lucro > meta)
    else if (saldoDia > 0 && metaCalculada > 0 && saldoDia >= metaCalculada) {
      metaFinal = 0;
      rotulo = `${
        data.rotulo_periodo || "META"
      } BATIDA! <i class='fa-solid fa-trophy'></i>`;
      statusClass = "meta-batida";
      valorExtra = saldoDia - metaCalculada; // ✅ CALCULAR lucro extra real

      // ✅ VERIFICAÇÃO: Se não há lucro extra real, não mostrar
      if (valorExtra <= 0) {
        valorExtra = 0;
      }
    }
    // ✅ REGRA 3: Lucro negativo
    else if (saldoDia < 0) {
      metaFinal = metaCalculada - saldoDia; // Meta + prejuízo para recuperar
      rotulo = `RESTANDO P/ ${data.rotulo_periodo || "META"}`;
      statusClass = "negativo";
      valorExtra = 0; // ✅ SEM lucro extra
    }
    // ✅ REGRA 4: Lucro zero
    else if (saldoDia === 0) {
      metaFinal = metaCalculada;
      rotulo = data.rotulo_periodo || "META DO DIA";
      statusClass = "neutro";
      valorExtra = 0; // ✅ SEM lucro extra
    }
    // ✅ REGRA 5: Lucro positivo mas não bateu meta (saldo < meta)
    else {
      metaFinal = metaCalculada - saldoDia;
      rotulo = `RESTANDO P/ ${data.rotulo_periodo || "META"}`;
      statusClass = "lucro";
      valorExtra = 0; // ✅ SEM lucro extra - meta não foi batida
    }

    return {
      metaFinal,
      metaFinalFormatada: metaFinal.toLocaleString("pt-BR", {
        style: "currency",
        currency: "BRL",
      }),
      rotulo,
      statusClass,
      valorExtra,
    };
  },

  // ✅ ATUALIZAR META ELEMENTO - USANDO CLASSES CSS
  atualizarMetaElemento(resultado) {
    const metaValor =
      document.getElementById("meta-valor") ||
      document.querySelector(".widget-meta-valor");

    if (!metaValor) return;

    let valorTexto =
      metaValor.querySelector(".valor-texto") ||
      metaValor.querySelector("#valor-texto-meta");

    if (valorTexto) {
      valorTexto.textContent = resultado.metaFinalFormatada;
    } else {
      metaValor.innerHTML = `
        <i class="fa-solid fa-coins"></i>
        <span class="valor-texto" id="valor-texto-meta">${resultado.metaFinalFormatada}</span>
      `;
    }

    // ✅ APLICAR CLASSES CSS BASEADAS NO STATUS
    metaValor.className = metaValor.className.replace(
      /\bvalor-meta\s+\w+/g,
      ""
    );
    metaValor.classList.add("valor-meta", resultado.statusClass);
  },

  // ✅ ATUALIZAR RÓTULO - ESTÁVEL
  atualizarRotulo(rotulo) {
    const rotuloElement =
      document.getElementById("rotulo-meta") ||
      document.querySelector(".widget-meta-rotulo");

    if (rotuloElement) {
      rotuloElement.innerHTML = rotulo;
    }
  },

  // ✅ ATUALIZAR VALOR EXTRA - USANDO ESTRUTURA HTML LIMPA
  atualizarValorExtra(valorExtra) {
    const valorUltrapassouElement =
      document.getElementById("valor-ultrapassou");

    if (valorUltrapassouElement) {
      // ✅ VERIFICAÇÃO RIGOROSA: Só mostrar se realmente há lucro extra
      if (valorExtra > 0) {
        const valorFormatado = valorExtra.toLocaleString("pt-BR", {
          style: "currency",
          currency: "BRL",
        });

        // ✅ ESTRUTURA HTML LIMPA - SEM CSS INLINE
        valorUltrapassouElement.innerHTML = `
          <div class="valor-ultrapassou-container">
            <i class="fa-solid fa-trophy valor-ultrapassou-icone"></i>
            <span class="valor-ultrapassou-texto">Lucro Extra:</span>
            <span class="valor-ultrapassou-valor">${valorFormatado}</span>
          </div>
        `;

        valorUltrapassouElement.classList.remove("oculta");
        valorUltrapassouElement.classList.add("mostrar");
      } else {
        // ✅ OCULTAR USANDO CLASSES CSS
        valorUltrapassouElement.classList.remove("mostrar");
        valorUltrapassouElement.classList.add("oculta");

        // ✅ LIMPAR CONTEÚDO
        const valorExtraElement = document.getElementById("valor-extra");
        if (valorExtraElement) {
          valorExtraElement.textContent = "R$ 0,00";
        }
      }
    }
  },

  // ✅ ATUALIZAR BARRA PROGRESSO - USANDO APENAS CLASSES CSS
  atualizarBarraProgresso(resultado, data) {
    const barraProgresso = document.getElementById("barra-progresso");
    const saldoInfo = document.getElementById("saldo-info");
    const porcentagemBarra = document.getElementById("porcentagem-barra");

    if (!barraProgresso) return;

    const saldoDia = parseFloat(data.lucro) || 0;
    const metaCalculada = parseFloat(data.meta_display) || 0;
    const bancaTotal = parseFloat(data.banca) || 0;

    // Calcular progresso
    let progresso = 0;
    if (bancaTotal > 0 && metaCalculada > 0) {
      if (resultado.statusClass === "meta-batida") {
        progresso = 100;
      } else if (saldoDia < 0) {
        progresso = -Math.min(Math.abs(saldoDia / metaCalculada) * 100, 100);
      } else {
        progresso = Math.max(
          0,
          Math.min(100, (saldoDia / metaCalculada) * 100)
        );
      }
    }

    const larguraBarra = Math.abs(progresso);

    // ✅ SISTEMA DE CORES USANDO APENAS CLASSES CSS
    let temLucroExtra = false;

    // ✅ REMOVER TODAS AS CLASSES DE COR ANTERIORES
    barraProgresso.className = barraProgresso.className.replace(
      /\bbarra-\w+/g,
      ""
    );

    // ✅ VERIFICAR SE REALMENTE TEM LUCRO EXTRA
    if (
      resultado.valorExtra > 0 &&
      resultado.statusClass === "meta-batida" &&
      saldoDia > metaCalculada
    ) {
      temLucroExtra = true;
      barraProgresso.classList.add("barra-lucro-extra"); // Dourado
    } else {
      // Aplicar classe baseada no status
      barraProgresso.classList.add(`barra-${resultado.statusClass}`);
    }

    // ✅ ATUALIZAR APENAS A LARGURA VIA JAVASCRIPT - COR VIA CSS
    barraProgresso.style.width = `${larguraBarra}%`;
    // ✅ REMOVER qualquer backgroundColor inline que possa estar conflitando
    barraProgresso.style.backgroundColor = "";
    barraProgresso.style.background = "";

    // ✅ PORCENTAGEM NO FINAL DA BARRA - USANDO CLASSES CSS
    if (porcentagemBarra) {
      const porcentagemTexto = Math.round(progresso) + "%";
      porcentagemBarra.textContent = porcentagemTexto;

      if (larguraBarra <= 0) {
        porcentagemBarra.classList.add("oculta");
      } else {
        porcentagemBarra.classList.remove("oculta");
        // ✅ APENAS POSIÇÃO VIA JAVASCRIPT - ESTILO VIA CSS
        porcentagemBarra.style.left = `${larguraBarra}%`;
      }
    }

    // ✅ SALDO INFO - USANDO ESTRUTURA HTML LIMPA
    if (saldoInfo) {
      const saldoFormatado = saldoDia.toLocaleString("pt-BR", {
        style: "currency",
        currency: "BRL",
      });

      let textoSaldo = "Saldo";

      // ✅ VERIFICAÇÃO: Só mostrar "Lucro Extra" se realmente tem
      if (temLucroExtra && resultado.valorExtra > 0) {
        textoSaldo = "🏆 Lucro Extra";
      } else if (saldoDia > 0) {
        textoSaldo = "Lucro";
      } else if (saldoDia < 0) {
        textoSaldo = "Negativo";
      } else {
        textoSaldo = "Saldo";
      }

      // ✅ ESTRUTURA HTML LIMPA - SEM CSS INLINE
      saldoInfo.innerHTML = `
        <div class="saldo-info-container">
          <span class="saldo-info-rotulo">${textoSaldo}:</span>
          <span class="saldo-info-valor">${saldoFormatado}</span>
        </div>
      `;

      // ✅ APLICAR CLASSES CSS BASEADAS NO STATUS
      saldoInfo.className = temLucroExtra
        ? "saldo-extra"
        : saldoDia > 0
        ? "saldo-positivo"
        : saldoDia < 0
        ? "saldo-negativo"
        : "saldo-zero";
    }
  },

  // ✅ CONFIGURAR LISTENERS - ESTÁVEL
  configurarListenersPeriodo() {
    const radiosPeriodo = document.querySelectorAll('input[name="periodo"]');
    radiosPeriodo.forEach((radio) => {
      radio.addEventListener("change", () => {
        this.atualizarMetaDiaria();
      });
    });
  },

  // ✅ MOSTRAR ERRO - USANDO CLASSES CSS
  mostrarErroMeta() {
    const metaElement = document.getElementById("meta-valor");
    if (metaElement) {
      metaElement.innerHTML =
        '<i class="fa-solid fa-coins"></i><span class="valor-texto loading-text">R$ 0,00</span>';
    }
  },

  // ✅ APLICAR ANIMAÇÃO - USANDO CLASSES CSS
  aplicarAnimacao(elemento) {
    elemento.classList.add("atualizado");
    setTimeout(() => {
      elemento.classList.remove("atualizado");
    }, 1500);
  },

  // ✅ INICIALIZAÇÃO - ESTÁVEL
  inicializar() {
    // Loading simples
    const metaElement = document.getElementById("meta-valor");
    if (metaElement) {
      metaElement.innerHTML =
        '<i class="fa-solid fa-coins"></i><span class="valor-texto loading-text">Calculando...</span>';
    }

    // Configurar listeners
    this.configurarListenersPeriodo();

    // Primeira atualização
    this.atualizarMetaDiaria();
  },
};

// ========================================
// INTERCEPTAÇÃO AJAX - ESTÁVEL
// ========================================

function configurarInterceptadores() {
  const originalFetch = window.fetch;

  window.fetch = async function (...args) {
    const response = await originalFetch.apply(this, args);

    if (
      args[0] &&
      typeof args[0] === "string" &&
      args[0].includes("dados_banca.php") &&
      response.ok
    ) {
      setTimeout(() => {
        MetaDiariaManager.atualizarMetaDiaria();
      }, 50);
    }

    return response;
  };

  const originalXHR = window.XMLHttpRequest;
  function newXHR() {
    const xhr = new originalXHR();
    const originalSend = xhr.send;

    xhr.send = function (...args) {
      xhr.addEventListener("load", function () {
        if (
          xhr.responseURL &&
          xhr.responseURL.includes("dados_banca.php") &&
          xhr.status === 200
        ) {
          setTimeout(() => {
            MetaDiariaManager.atualizarMetaDiaria();
          }, 50);
        }
      });

      return originalSend.apply(this, args);
    };

    return xhr;
  }

  window.XMLHttpRequest = newXHR;
}

// ========================================
// FUNÇÕES GLOBAIS - ESTÁVEIS
// ========================================

window.atualizarMetaDiaria = () => {
  return MetaDiariaManager.atualizarMetaDiaria();
};

window.forcarAtualizacaoMeta = () => {
  MetaDiariaManager.atualizandoAtualmente = false;
  return MetaDiariaManager.atualizarMetaDiaria();
};

window.alterarPeriodo = (periodo) => {
  const radio = document.querySelector(
    `input[name="periodo"][value="${periodo}"]`
  );
  if (radio) {
    radio.checked = true;
    MetaDiariaManager.atualizarMetaDiaria();
    return true;
  }
  return false;
};

// ========================================
// ATALHOS SIMPLES E ESTÁVEIS
// ========================================

window.$ = {
  force: () => forcarAtualizacaoMeta(),
  dia: () => alterarPeriodo("dia"),
  mes: () => alterarPeriodo("mes"),
  ano: () => alterarPeriodo("ano"),

  test: () => {
    console.log("🧪 Teste básico:");
    alterarPeriodo("dia");
    console.log("✅ DIA");
    setTimeout(() => {
      alterarPeriodo("mes");
      console.log("✅ MÊS");
    }, 1000);
    setTimeout(() => {
      alterarPeriodo("ano");
      console.log("✅ ANO");
    }, 2000);
    setTimeout(() => {
      alterarPeriodo("dia");
      console.log("✅ Volta DIA");
    }, 3000);
    return "🎯 Teste iniciado";
  },

  info: () => {
    const metaElement = document.getElementById("meta-valor");
    const rotuloElement = document.getElementById("rotulo-meta");
    const barraElement = document.getElementById("barra-progresso");
    const extraElement = document.getElementById("valor-ultrapassou");

    const info = {
      meta: !!metaElement,
      rotulo: !!rotuloElement,
      barra: !!barraElement,
      extra: !!extraElement,
      metaContent: metaElement ? metaElement.textContent : "N/A",
      extraVisivel: extraElement
        ? !extraElement.classList.contains("oculta")
        : false,
      atualizando: MetaDiariaManager.atualizandoAtualmente,
    };

    console.log("📊 Info:", info);
    return "✅ Info verificada";
  },

  // ✅ TESTE ESPECÍFICO DAS CORES DA BARRA
  testCores: () => {
    console.log("🎨 Testando cores da barra de progresso...");

    const barra = document.getElementById("barra-progresso");
    if (!barra) {
      console.error("❌ Barra de progresso não encontrada!");
      return "❌ Erro: elemento não encontrado";
    }

    const coresTeste = [
      {
        classe: "barra-lucro",
        cor: "#4CAF50",
        desc: "Verde - Lucro Normal",
        progresso: 75,
      },
      {
        classe: "barra-meta-batida",
        cor: "#2196F3",
        desc: "Azul - Meta Batida",
        progresso: 100,
      },
      {
        classe: "barra-lucro-extra",
        cor: "#FFD700",
        desc: "Dourado - Lucro Extra",
        progresso: 100,
      },
      {
        classe: "barra-negativo",
        cor: "#f44336",
        desc: "Vermelho - Negativo",
        progresso: 25,
      },
      {
        classe: "barra-neutro",
        cor: "#95a5a6",
        desc: "Cinza - Neutro",
        progresso: 0,
      },
      {
        classe: "barra-sem-banca",
        cor: "#e67e22",
        desc: "Laranja - Sem Banca",
        progresso: 0,
      },
    ];

    coresTeste.forEach((teste, index) => {
      setTimeout(() => {
        console.log(`🎨 Aplicando: ${teste.desc}`);

        // Limpar classes anteriores
        barra.className = barra.className.replace(/\bbarra-\w+/g, "");

        // Limpar qualquer style inline
        barra.style.backgroundColor = "";
        barra.style.background = "";

        // Aplicar nova classe
        barra.classList.add(teste.classe);
        barra.style.width = `${teste.progresso}%`;

        // Verificar se a cor foi aplicada
        setTimeout(() => {
          const computedStyle = window.getComputedStyle(barra);
          const corAplicada = computedStyle.backgroundColor;

          console.log(`  ✅ Classe: ${teste.classe}`);
          console.log(`  🎯 Cor esperada: ${teste.cor}`);
          console.log(`  🎨 Cor aplicada: ${corAplicada}`);
          console.log(`  📏 Largura: ${teste.progresso}%`);

          // Verificar se as classes estão presentes
          console.log(`  📋 Classes na barra: ${barra.className}`);
        }, 100);
      }, index * 1500);
    });

    return "🎨 Teste de cores iniciado - 6 cores em 9s";
  },

  // ✅ FORÇAR LIMPEZA DE ESTILOS INLINE
  limparEstilos: () => {
    console.log("🧹 Limpando estilos inline da barra...");

    const barra = document.getElementById("barra-progresso");
    if (barra) {
      // Remover todos os estilos inline que podem conflitar
      barra.style.backgroundColor = "";
      barra.style.background = "";
      barra.removeAttribute("style");

      // Recriar o style apenas com largura
      barra.style.width = "50%";

      console.log("✅ Estilos inline removidos");
      console.log("📏 Largura resetada para 50%");
      console.log("📋 Classes atuais:", barra.className);

      return "✅ Limpeza concluída";
    } else {
      return "❌ Barra não encontrada";
    }
  },
};

// ========================================
// INICIALIZAÇÃO - ESTÁVEL
// ========================================

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", () => {
    MetaDiariaManager.inicializar();
    configurarInterceptadores();
  });
} else {
  MetaDiariaManager.inicializar();
  configurarInterceptadores();
}

console.log("✅ Sistema Meta Diária - SEM CSS INLINE!");
console.log("📱 Comandos:");
console.log("  $.force() - Forçar atualização");
console.log("  $.test() - Teste de períodos");
console.log("  $.info() - Ver status");
console.log("  $.testExtra() - Testar lucro extra");

css:

/* ========================================
   1. BARRA DE PROGRESSO + PORCENTAGEM
   ======================================== */

/* Porcentagem fixa no canto direito - APENAS NÚMERO */
.porcentagem-barra {
  position: absolute;
  top: 50%;
  left: 94%;
  transform: translate(-50%, -50%);
  font-size: clamp(8px, 1.8vw, 13px);
  font-weight: 700;
  color: #fff;
  text-shadow: 0 0 4px rgba(0, 0, 0, 0.6), 0 1px 2px rgba(0, 0, 0, 0.1);
  z-index: 10;
  border-radius: 50%;
  padding: 4px 8px;
  white-space: nowrap;
}
/* Campo da Porcentagem */
.widget-barra-container {
  position: relative;
  width: 300px;
  height: 26px;
  background-color: #f3f5f4;
  border-radius: 12px;
  overflow: hidden;
  border: 1px solid #bdc3c7; /* Borda externa de 1px */
  box-shadow: inset 0 0 6px 2px rgba(189, 195, 199, 0.4); /* Sombra interna suave */
}

/* COR DENTRO BARRA PROGRESSO */
#barra-progresso {
  transition: all 0.1s ease;
  border-radius: 18px;
  height: 17px; /* Mais grossa */
  display: flex;
  align-items: center; /* Alinha verticalmente */
  justify-content: center; /* Opcional: alinha horizontalmente */
  margin-top: 4.4px;
}

/* Barra dourada - lucro extra com gradiente premium */
.barra-lucro-extra,
#barra-progresso.barra-lucro-extra {
  background: linear-gradient(
    90deg,
    #f39821 0%,
    #f8911b 25%,
    #f8911b 50%,
    #f8911b 75%,
    #f39821 100%
  ) !important;
  background-size: 200% 100%;
  animation: goldWave 2s linear infinite;
}
/* Barra laranja - sem banca */
/* Sem banca - cinza */
/* Sem banca - cinza */
/* Sem banca - cinza */
.barra-sem-banca,
#barra-progresso.barra-sem-banca {
  background: linear-gradient(
    90deg,
    #95a5a6 0%,
    #8e9e9f 50%,
    #7f8c8d 100%
  ) !important;
}

/* Meta batida - azul */
.barra-meta-batida,
#barra-progresso.barra-meta-batida {
  background: linear-gradient(
    90deg,
    #2196f3 0%,
    #3793e0 50%,
    #1976d2 100%
  ) !important;
}

/* Negativo - vermelho */
.barra-negativo,
#barra-progresso.barra-negativo {
  background: linear-gradient(
    90deg,
    #f44336 0%,
    #e53935 50%,
    #d32f2f 100%
  ) !important;
}

/* Neutro - cinza */
.barra-neutro,
#barra-progresso.barra-neutro {
  background: linear-gradient(
    90deg,
    #95a5a6 0%,
    #8e9e9f 50%,
    #7f8c8d 100%
  ) !important;
}

/* Lucro - verde */
.barra-lucro,
#barra-progresso.barra-lucro {
  background: linear-gradient(
    90deg,
    #4caf50 0%,
    #43a047 50%,
    #388e3c 100%
  ) !important;
}

/* ========================================
   1. FIM BARRA DE PROGRESSO + PORCENTAGEM
   ======================================== */
/* */
/* */
/* */
/* */
/* */
/* */
/* */
/* */
/* */
/* */
/* */
/* */
/* */
/* */
/* */
/* ========================================
   3. STATUS META BATIDA
   ======================================== */
.widget-meta-rotulo {
  font-size: clamp(10px, 2.2vw, 14px); /* Fonte responsiva */
  color: #807f7f;
  margin-bottom: 15px;
  text-align: center;
  letter-spacing: 1px;
  font-weight: 600;
}
/* ========================================
   3. FIM STATUS META BATIDA
   ======================================== */
/* */
/* */
/* */
/* */
/* */
/* */
/* */
/* */
/* */
/* */
/* */
/* */
/* */
/* */
/* */
/* ========================================
   3. VALOR DA META 
   ======================================== */

/* Container principal do valor da meta - MAIOR */
@import url("https://fonts.googleapis.com/css2?family=Rajdhani:wght@600&display=swap");

.widget-meta-valor,
#meta-valor {
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 25px;
  font-weight: 900;
  font-family: "Rajdhani", sans-serif; /* Fonte estilizada para números */
  transition: all 0.3s ease;
}

.widget-meta-valor {
  font-size: clamp(18px, 5vw, 28px); /* Fonte responsiva */
  font-weight: 800;
  margin-bottom: 8px;
  text-align: center;
  color: #2c3e50;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: clamp(6px, 2vw, 12px); /* Gap responsivo */
  letter-spacing: -0.5px;
}

.widget-meta-valor .fa-solid.fa-coins {
  font-size: clamp(16px, 4vw, 26px); /* Fonte responsiva */
  color: #f39c12;
}

/* Ícone de moedas ao lado do valor */
.widget-meta-valor .fa-coins,
#meta-valor .fa-coins {
  margin-right: 0px;
  font-size: 28px;
  color: #f3b200; /* Dourado mais vibrante */
}

/* Texto do valor da meta */
.valor-texto {
  font-weight: 900;
  font-size: 24px;
  letter-spacing: -0.5px;
}

/* Laranja para sem banca */
.valor-meta.sem-banca .valor-texto {
  color: #e67e22;
}

/* Azul para meta batida */
.valor-meta.meta-batida .valor-texto {
  color: #2196f3;
}

/* Vermelho para negativo */
.valor-meta.negativo .valor-texto {
  color: #f53520;
}

/* Cinza para neutro */
.valor-meta.neutro .valor-texto {
  color: #7f8c8d;
}

/* Verde para lucro */
.valor-meta.lucro .valor-texto {
  color: #00a651;
}

/* Estado visível do valor extra */
.valor-ultrapassou {
  display: none;
  margin-top: -9px;
  margin-bottom: 10px;
  position: relative;
  z-index: 30;
}

/* Estado visível do valor extra */
.valor-ultrapassou.mostrar {
  display: flex !important;
  justify-content: center; /* Centraliza com a barra menor */
  animation: pulseGold 2s infinite;
}

/* Container com gradiente premium melhorado */

/* Efeito de brilho no fundo */

/* Ícone da taça dourada */
.valor-ultrapassou-icone {
  color: #f78f08;
  font-size: 24px;
  margin-right: 12px;

  animation: bounce 1.5s ease-in-out infinite;
}

/* Texto "Lucro Extra:" */
.valor-ultrapassou-texto {
  color: #0073df;
  font-weight: bold;
  font-size: 16px;
  margin-right: 8px;
}

/* Valor em dourado com sombra */
.valor-ultrapassou-valor {
  color: #f78f08;
  font-weight: 900;
  font-size: 20px;
}
/* ========================================
   3. FIM VALOR DA META 
   ======================================== */
/* */
/* */
/* */
/* */
/* */
/* */
/* */
/* */
/* */
/* */
/* */
/* */
/* */
/* */
/* */

/* ========================================
   8. SALDO + ROTULO
   ======================================== */

/* Container do saldo - centralizado com a barra menor */
#saldo-info {
  margin-top: 5px;
  margin-left: -170px;
}

.saldo-info-container {
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Rótulo do saldo */
.saldo-info-rotulo {
  font-weight: bold;
  margin-right: 1px;
  font-size: 13px;
}

/* Valor do saldo */
.saldo-info-valor {
  font-weight: bold;
  font-size: 13px;
}
#saldo-info i {
  margin-right: 0px;
  font-size: 1em;
}

.saldo-info-container {
  display: inline-flex;
  align-items: center;
  gap: 5px;
}

.saldo-positivo i {
  color: #4caf50;
}

.saldo-negativo i {
  color: #f44336;
}

.saldo-zero i {
  color: #95a5a6;
}

/* Verde para lucro positivo */

.saldo-positivo .saldo-info-valor {
  color: #00a651;
}

/* Vermelho para negativo */

.saldo-negativo .saldo-info-valor {
  color: #f44336;
}
/* */
/* */
/* ROTULO SALDO */
.saldo-zero .saldo-info-valor {
  color: #666;
}

/* Verde para lucro positivo */
.saldo-positivo .saldo-info-rotulo {
  color: #5d6360;
}

/* Vermelho para negativo */
.saldo-negativo .saldo-info-rotulo {
  color: #5d6360;
}

/* Cinza para neutro */
.saldo-zero .saldo-info-rotulo {
  color: #5d6360;
}

/* ========================================
   9. ANIMAÇÕES
   ======================================== */

/* Animação de onda dourada */
@keyframes goldWave {
  0% {
    background-position: 0% 50%;
  }
  100% {
    background-position: 200% 50%;
  }
}

/* Animação de brilho para o container */
@keyframes shimmer {
  0% {
    background-position: 0% 50%;
  }
  50% {
    background-position: 100% 50%;
  }
  100% {
    background-position: 0% 50%;
  }
}

/* Animação de shine */
@keyframes shine {
  0% {
    transform: translateX(-100%) translateY(-100%) rotate(45deg);
  }
  100% {
    transform: translateX(100%) translateY(100%) rotate(45deg);
  }
}

/* Animação de bounce para o ícone */
@keyframes bounce {
  0%,
  100% {
    transform: translateY(0);
  }
  50% {
    transform: translateY(-5px);
  }
}

/* Animação de pulse dourado */
@keyframes pulseGold {
  0% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.02);
  }
  100% {
    transform: scale(1);
  }
}

/* Animação de atualização */
@keyframes pulse-update {
  0% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.05);
  }
  100% {
    transform: scale(1);
  }
}

/* Classe para animação de atualização */
.atualizado {
  animation: pulse-update 1.5s ease-in-out;
}

/* ========================================
   10. MODAL - CORES DO LUCRO
   ======================================== */

.modal-lucro-positivo {
  color: #4caf50 !important;
}

.modal-lucro-negativo {
  color: #f44336 !important;
}

.modal-lucro-neutro {
  color: #666 !important;
}

/* ========================================
   11. RESPONSIVIDADE - MOBILE
   ======================================== */

@media (max-width: 768px) {
  /* Barra ajustada para mobile */
  .barra-container {
    width: 90%; /* Um pouco maior em mobile */
    height: 24px; /* Um pouco menor em mobile */
    border-radius: 12px;
  }

  #barra-progresso,
  .widget-barra-progresso {
    border-radius: 12px;
  }

  /* Porcentagem ajustada para mobile */
  .porcentagem-barra {
    font-size: 12px;
    right: 8px;
    font-weight: 800;
  }

  /* Valor da meta menor em mobile */
  .widget-meta-valor,
  #meta-valor {
    font-size: 20px;
  }

  .valor-texto {
    font-size: 20px;
  }

  .widget-meta-valor .fa-coins,
  #meta-valor .fa-coins {
    font-size: 18px;
  }

  /* Container do valor extra em mobile */
  .valor-ultrapassou-container {
    padding: 8px 12px;
  }

  .valor-ultrapassou-icone {
    font-size: 20px;
    margin-right: 8px;
  }

  .valor-ultrapassou-texto {
    font-size: 14px;
  }

  .valor-ultrapassou-valor {
    font-size: 16px;
  }

  .saldo-info-valor {
    font-size: 12px;
  }
}

/* ========================================
   12. ESTADOS DE LOADING
   ======================================== */

.loading-text {
  color: #999;
  font-style: italic;
}

/* ========================================
   13. CLASSES UTILITÁRIAS
   ======================================== */

.text-center {
  text-align: center;
}

.flex-center {
  display: flex;
  align-items: center;
  justify-content: center;
}

.flex-start {
  display: flex;
  align-items: center;
  justify-content: flex-start;
}

.font-bold {
  font-weight: bold;
}

.margin-top-small {
  margin-top: 8px;
}

.margin-top-medium {
  margin-top: 10px;
}
