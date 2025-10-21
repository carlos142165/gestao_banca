<?php
/**
 * CONFIGURAÇÃO MERCADO PAGO
 * ========================================
 * Este arquivo contém as configurações e funções auxiliares para integração com Mercado Pago
 */

// ✅ CREDENCIAIS MERCADO PAGO (Configure com suas chaves)
define('MP_ACCESS_TOKEN', 'APP_USR-3237573864728549-102019-04e2fd4b60492785833312c31e0dffd8-1565964651'); // Substitua com seu token
define('MP_PUBLIC_KEY', 'APP_USR-ca9ca659-4278-49a6-a7cc-bed2041ac437');     // Substitua com sua chave pública

// ✅ URLS DE CALLBACK
$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
define('MP_SUCCESS_URL', $base_url . '/gestao_banca/webhook.php?status=success');
define('MP_FAILURE_URL', $base_url . '/gestao_banca/webhook.php?status=failure');
define('MP_PENDING_URL', $base_url . '/gestao_banca/webhook.php?status=pending');
define('MP_NOTIFICATION_URL', $base_url . '/gestao_banca/webhook.php');

// ✅ AMBIENTE (development ou production)
define('MP_ENVIRONMENT', 'development'); // Mude para 'production' em produção

// ✅ CONFIGURAÇÕES ADICIONAIS
define('MP_TIMEOUT', 30);
define('MP_MAX_RETRY', 3);

/**
 * CLASSE PARA GERENCIAR MERCADO PAGO
 * ========================================
 */
class MercadoPagoManager {
    
    /**
     * Criar preferência de pagamento
     * @param int $id_usuario
     * @param int $id_plano
     * @param string $tipo_ciclo ('mensal' ou 'anual')
     * @param string $modo_pagamento ('cartao', 'pix', etc)
     * @return array
     */
    public static function criarPreferencia($id_usuario, $id_plano, $tipo_ciclo, $modo_pagamento = null) {
        global $conexao;
        
        // Buscar dados do plano e usuário
        $stmt = $conexao->prepare("
            SELECT p.*, u.nome, u.email 
            FROM planos p
            JOIN usuarios u ON u.id = ?
            WHERE p.id = ?
        ");
        $stmt->bind_param("ii", $id_usuario, $id_plano);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return ['success' => false, 'message' => 'Plano ou usuário não encontrado'];
        }
        
        $data = $result->fetch_assoc();
        $stmt->close();
        
        // Determinar valor baseado no ciclo
        $preco = ($tipo_ciclo === 'anual') ? $data['preco_ano'] : $data['preco_mes'];
        $descricao = "Plano {$data['nome']} - " . ucfirst($tipo_ciclo);
        
        // Montar dados da preferência
        $preference_data = [
            "items" => [
                [
                    "title" => $descricao,
                    "description" => "Acesso ao plano {$data['nome']} por " . ($tipo_ciclo === 'anual' ? '12 meses' : '1 mês'),
                    "quantity" => 1,
                    "unit_price" => floatval($preco),
                    "currency_id" => "BRL"
                ]
            ],
            "payer" => [
                "name" => $data['nome'],
                "email" => $data['email']
            ],
            "payment_methods" => [
                "excluded_payment_types" => [],
                "excluded_payment_methods" => [],
                "installments" => 1
            ],
            "back_urls" => [
                "success" => MP_SUCCESS_URL . "&usuario=" . urlencode($id_usuario),
                "failure" => MP_FAILURE_URL,
                "pending" => MP_PENDING_URL
            ],
            "auto_return" => "approved",
            "notification_url" => MP_NOTIFICATION_URL,
            "external_reference" => "user_{$id_usuario}_plan_{$id_plano}_{$tipo_ciclo}",
            "expires" => true,
            "expiration_date_from" => date('Y-m-d\TH:i:s\Z'),
            "expiration_date_to" => date('Y-m-d\TH:i:s\Z', strtotime('+1 hour')),
            "metadata" => [
                "id_usuario" => $id_usuario,
                "id_plano" => $id_plano,
                "tipo_ciclo" => $tipo_ciclo,
                "modo_pagamento" => $modo_pagamento
            ]
        ];
        
        // Se for pagamento com PIX, adicionar método
        if ($modo_pagamento === 'pix') {
            $preference_data["payment_methods"]["excluded_payment_methods"] = [
                ["id" => "account_money"],
                ["id" => "bolbradesco"]
            ];
        }
        
        // Se for cartão, adicionar método
        if ($modo_pagamento === 'cartao') {
            $preference_data["payment_methods"]["excluded_payment_methods"] = [
                ["id" => "bank_transfer"],
                ["id" => "ticket"],
                ["id" => "pix"]
            ];
        }
        
        // Enviar para Mercado Pago
        return self::enviarRequisicao('preferences', $preference_data, 'POST');
    }
    
    /**
     * Enviar requisição para API do Mercado Pago
     * @param string $endpoint
     * @param array $data
     * @param string $method
     * @return array
     */
    public static function enviarRequisicao($endpoint, $data = [], $method = 'GET') {
        $url = "https://api.mercadopago.com/checkout/preferences";
        
        if ($endpoint !== 'preferences') {
            $url = "https://api.mercadopago.com/" . $endpoint;
        }
        
        $headers = [
            "Authorization: Bearer " . MP_ACCESS_TOKEN,
            "Content-Type: application/json",
            "X-Idempotency-Key: " . uniqid()
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, MP_TIMEOUT);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        if ($curl_error) {
            return [
                'success' => false,
                'message' => 'Erro na requisição: ' . $curl_error,
                'http_code' => $http_code
            ];
        }
        
        $response_data = json_decode($response, true);
        
        if ($http_code >= 200 && $http_code < 300) {
            return [
                'success' => true,
                'data' => $response_data,
                'http_code' => $http_code
            ];
        } else {
            return [
                'success' => false,
                'message' => $response_data['message'] ?? 'Erro na API',
                'data' => $response_data,
                'http_code' => $http_code
            ];
        }
    }
    
    /**
     * Obter informações de pagamento
     * @param string $payment_id
     * @return array
     */
    public static function obterPagamento($payment_id) {
        return self::enviarRequisicao("payments/{$payment_id}", [], 'GET');
    }
    
    /**
     * Salvar cartão do usuário
     * @param int $id_usuario
     * @param string $token_cartao
     * @param array $dados_cartao
     * @return bool
     */
    public static function salvarCartao($id_usuario, $token_cartao, $dados_cartao) {
        global $conexao;
        
        $ultimos_digitos = substr($dados_cartao['numero'], -4);
        $bandeira = $dados_cartao['bandeira'] ?? 'unknown';
        $titular = $dados_cartao['titular'] ?? '';
        $mes = intval($dados_cartao['mes'] ?? 0);
        $ano = intval($dados_cartao['ano'] ?? 0);
        
        $stmt = $conexao->prepare("
            INSERT INTO cartoes_salvos 
            (id_usuario, token_mercadopago, ultimos_digitos, bandeira, titulr_cartao, mes_expiracao, ano_expiracao)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            token_mercadopago = VALUES(token_mercadopago),
            ultimos_digitos = VALUES(ultimos_digitos),
            bandeira = VALUES(bandeira),
            mes_expiracao = VALUES(mes_expiracao),
            ano_expiracao = VALUES(ano_expiracao)
        ");
        
        $result = $stmt->bind_param(
            "issssii",
            $id_usuario,
            $token_cartao,
            $ultimos_digitos,
            $bandeira,
            $titular,
            $mes,
            $ano
        );
        
        if ($result === false) {
            return false;
        }
        
        $exec = $stmt->execute();
        $stmt->close();
        
        return $exec;
    }
    
    /**
     * Criar assinatura (registro no banco)
     * @param int $id_usuario
     * @param int $id_plano
     * @param string $tipo_ciclo
     * @param string $modo_pagamento
     * @param float $valor_pago
     * @param string $id_pagamento_mp
     * @return int|false ID da assinatura ou false
     */
    public static function criarAssinatura($id_usuario, $id_plano, $tipo_ciclo, $modo_pagamento, $valor_pago, $id_pagamento_mp) {
        global $conexao;
        
        // Calcular data de expiração
        $data_inicio = new DateTime();
        $data_fim = clone $data_inicio;
        
        if ($tipo_ciclo === 'anual') {
            $data_fim->add(new DateInterval('P1Y'));
        } else {
            $data_fim->add(new DateInterval('P1M'));
        }
        
        $stmt = $conexao->prepare("
            INSERT INTO assinaturas 
            (id_usuario, id_plano, data_inicio, data_fim, status, tipo_ciclo, valor_pago, id_pago_mercadopago, modo_pagamento)
            VALUES (?, ?, ?, ?, 'ativa', ?, ?, ?, ?)
        ");
        
        $stmt->bind_param(
            "iissssds",
            $id_usuario,
            $id_plano,
            $data_inicio->format('Y-m-d H:i:s'),
            $data_fim->format('Y-m-d H:i:s'),
            $tipo_ciclo,
            $valor_pago,
            $id_pagamento_mp,
            $modo_pagamento
        );
        
        if ($stmt->execute()) {
            $id_assinatura = $conexao->insert_id;
            $stmt->close();
            
            // Atualizar a tabela usuarios
            self::atualizarUsuarioAssinatura($id_usuario, $id_plano, $data_fim->format('Y-m-d H:i:s'), $tipo_ciclo);
            
            return $id_assinatura;
        }
        
        return false;
    }
    
    /**
     * Atualizar dados da assinatura do usuário
     * @param int $id_usuario
     * @param int $id_plano
     * @param string $data_fim
     * @param string $tipo_ciclo
     * @return bool
     */
    public static function atualizarUsuarioAssinatura($id_usuario, $id_plano, $data_fim, $tipo_ciclo) {
        global $conexao;
        
        $stmt = $conexao->prepare("
            UPDATE usuarios
            SET 
                id_plano = ?,
                status_assinatura = 'ativa',
                data_inicio_assinatura = NOW(),
                data_fim_assinatura = ?,
                tipo_ciclo = ?,
                renovacao_ativa = TRUE
            WHERE id = ?
        ");
        
        $stmt->bind_param("issi", $id_plano, $data_fim, $tipo_ciclo, $id_usuario);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
    
    /**
     * Verificar se plano expirou
     * @param int $id_usuario
     * @return bool
     */
    public static function planoExpirou($id_usuario) {
        global $conexao;
        
        $stmt = $conexao->prepare("
            SELECT data_fim_assinatura FROM usuarios WHERE id = ?
        ");
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return true;
        }
        
        $data = $result->fetch_assoc();
        $stmt->close();
        
        if (is_null($data['data_fim_assinatura'])) {
            return false; // Plano gratuito
        }
        
        return strtotime($data['data_fim_assinatura']) < time();
    }
    
    /**
     * Obter plano atual do usuário
     * @param int $id_usuario
     * @return array|null
     */
    public static function obterPlanoAtual($id_usuario) {
        global $conexao;
        
        $stmt = $conexao->prepare("
            SELECT p.* FROM planos p
            JOIN usuarios u ON u.id_plano = p.id
            WHERE u.id = ?
        ");
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            // Retornar plano gratuito padrão
            return self::obterPlanoGratuito();
        }
        
        $plano = $result->fetch_assoc();
        $stmt->close();
        
        return $plano;
    }
    
    /**
     * Obter plano gratuito
     * @return array|null
     */
    public static function obterPlanoGratuito() {
        global $conexao;
        
        $stmt = $conexao->prepare("SELECT * FROM planos WHERE nome = 'GRATUITO' LIMIT 1");
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return null;
        }
        
        $plano = $result->fetch_assoc();
        $stmt->close();
        
        return $plano;
    }
    
    /**
     * Verificar limite de mentores
     * @param int $id_usuario
     * @param int $id_plano
     * @return bool
     */
    public static function verificarLimiteMentores($id_usuario, $id_plano = null) {
        global $conexao;
        
        if (!$id_plano) {
            $stmt = $conexao->prepare("SELECT id_plano FROM usuarios WHERE id = ?");
            $stmt->bind_param("i", $id_usuario);
            $stmt->execute();
            $result = $stmt->get_result();
            $usuario = $result->fetch_assoc();
            $stmt->close();
            $id_plano = $usuario['id_plano'] ?? 1;
        }
        
        // Obter limite do plano
        $stmt = $conexao->prepare("SELECT mentores_limite FROM planos WHERE id = ?");
        $stmt->bind_param("i", $id_plano);
        $stmt->execute();
        $result = $stmt->get_result();
        $plano = $result->fetch_assoc();
        $stmt->close();
        
        $limite = intval($plano['mentores_limite'] ?? 1);
        
        // Contar mentores do usuário
        $stmt = $conexao->prepare("SELECT COUNT(*) as total FROM mentores WHERE id_usuario = ?");
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $result = $stmt->get_result();
        $contagem = $result->fetch_assoc();
        $stmt->close();
        
        return $contagem['total'] < $limite;
    }
    
    /**
     * Verificar limite de entradas
     * @param int $id_usuario
     * @param int $id_plano
     * @return bool
     */
    public static function verificarLimiteEntradas($id_usuario, $id_plano = null) {
        global $conexao;
        
        if (!$id_plano) {
            $stmt = $conexao->prepare("SELECT id_plano FROM usuarios WHERE id = ?");
            $stmt->bind_param("i", $id_usuario);
            $stmt->execute();
            $result = $stmt->get_result();
            $usuario = $result->fetch_assoc();
            $stmt->close();
            $id_plano = $usuario['id_plano'] ?? 1;
        }
        
        // Obter limite do plano
        $stmt = $conexao->prepare("SELECT entradas_diarias FROM planos WHERE id = ?");
        $stmt->bind_param("i", $id_plano);
        $stmt->execute();
        $result = $stmt->get_result();
        $plano = $result->fetch_assoc();
        $stmt->close();
        
        $limite = intval($plano['entradas_diarias'] ?? 3);
        
        // Se limite for 999 (ilimitado), retornar true
        if ($limite >= 999) {
            return true;
        }
        
        // Contar entradas de hoje
        $stmt = $conexao->prepare("
            SELECT COUNT(*) as total FROM valor_mentores 
            WHERE id_usuario = ? AND DATE(data_criacao) = CURDATE()
        ");
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $result = $stmt->get_result();
        $contagem = $result->fetch_assoc();
        $stmt->close();
        
        return $contagem['total'] < $limite;
    }
}

?>
