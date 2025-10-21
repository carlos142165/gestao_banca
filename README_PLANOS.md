# 🎯 SISTEMA DE PLANOS COM MERCADO PAGO - GUIA DE IMPLEMENTAÇÃO

## 📋 Resumo

Este sistema implementa um modelo de assinatura com 4 planos (GRATUITO, PRATA, OURO, DIAMANTE) com integração completa ao Mercado Pago, suportando pagamentos com:
- 💳 Cartão de crédito/débito
- 🔲 PIX
- 💾 Cartões salvos para renovação automática

---

## 🔧 PASSO 1: CONFIGURAÇÃO DO BANCO DE DADOS

### 1.1 Executar o Script SQL

Execute o arquivo `db_schema_planos.sql` no seu phpMyAdmin:

```sql
-- No phpMyAdmin, importe o arquivo:
-- c:\xampp\htdocs\gestao\gestao_banca\db_schema_planos.sql
```

**Tabelas criadas:**
- `planos` - Lista de planos disponíveis
- `assinaturas` - Histórico de assinaturas dos usuários
- `transacoes_mercadopago` - Registro de todas as transações
- `cartoes_salvos` - Cartões salvos para pagamentos futuros

**Colunas adicionadas à tabela `usuarios`:**
- `id_plano` - ID do plano atual (padrão: 1 = GRATUITO)
- `status_assinatura` - Status (ativa/cancelada/expirada)
- `data_inicio_assinatura` - Quando a assinatura começou
- `data_fim_assinatura` - Quando a assinatura expira
- `tipo_ciclo` - Mensal ou anual
- `cartao_salvo` - Boolean se tem cartão salvo
- `token_cartao` - Token do Mercado Pago
- `ultimos_4_digitos` - Últimos 4 dígitos do cartão
- `bandeira_cartao` - Visa, Mastercard, etc
- `mercadopago_customer_id` - ID do cliente no MP
- `data_renovacao_automatica` - Data próxima renovação
- `renovacao_ativa` - Se renovação automática está ativa

---

## 🔐 PASSO 2: CONFIGURAR CREDENCIAIS MERCADO PAGO

### 2.1 Obter as chaves

1. Acesse https://www.mercadopago.com.br
2. Vá em **Configurações > Credenciais**
3. Copie seu **Access Token** e **Public Key**

### 2.2 Atualizar arquivo de configuração

Edite `config_mercadopago.php` (linha 9 e 10):

```php
define('MP_ACCESS_TOKEN', 'SEU_ACCESS_TOKEN_AQUI'); // Ex: APP_USR-12345...
define('MP_PUBLIC_KEY', 'SEU_PUBLIC_KEY_AQUI');     // Ex: APP_USR-12345...
```

### 2.3 Configurar URLs de retorno

No Mercado Pago:
1. Vá em **Integrações > Webhooks**
2. Adicione a URL: `http://seu-site.com/gestao_banca/webhook.php`
3. Selecione os eventos:
   - `payment.created`
   - `payment.updated`

---

## 📁 PASSO 3: ARQUIVOS CRIADOS

### Backend (PHP)
- `config_mercadopago.php` - Configurações e classe MercadoPagoManager
- `obter-planos.php` - Retorna lista de planos
- `obter-dados-usuario.php` - Dados da assinatura atual
- `obter-cartoes-salvos.php` - Cartões salvos do usuário
- `verificar-limite.php` - Verifica limites do plano
- `processar-pagamento.php` - Cria preferência de pagamento
- `webhook.php` - Processa confirmações (ATUALIZADO)

### Frontend (HTML/CSS/JS)
- `modal-planos-pagamento.html` - HTML do modal de planos + pagamento
- `js/plano-manager.js` - Gerenciador completo de planos

### Database
- `db_schema_planos.sql` - Script de criação de tabelas

---

## 🎨 PASSO 4: INTEGRAR NO TEMPLATE

### 4.1 Adicionar Modal ao HTML Principal

Edite o arquivo principal (ex: `gestao-diaria.php`) e adicione antes do `</body>`:

```html
<!-- Incluir o modal de planos -->
<?php include 'modal-planos-pagamento.html'; ?>

<!-- Scripts necessários -->
<script src="js/plano-manager.js"></script>
```

### 4.2 Inicializar o sistema

O `plano-manager.js` é inicializado automaticamente quando o DOM fica pronto.

---

## 🔄 PASSO 5: IMPLEMENTAR VALIDAÇÕES

### 5.1 Validar ao cadastrar Mentor

Edite `cadastrar-mentor-ajax.php` ou equivalente:

```javascript
// Antes de abrir o formulário de mentor:
const pode_prosseguir = await PlanoManager.verificarEExibirPlanos('mentor');
if (!pode_prosseguir) return; // Mostrou modal, cancela
```

### 5.2 Validar ao adicionar Entrada

Edite `ajax_deposito.php` ou equivalente:

```javascript
// Antes de permitir entrada:
const pode_prosseguir = await PlanoManager.verificarEExibirPlanos('entrada');
if (!pode_prosseguir) return; // Mostrou modal, cancela
```

---

## 💳 PASSO 6: FLUXO DE PAGAMENTO

### Fluxo Completo:

```
1. Usuário clica "Cadastrar Mentor" ou "Nova Entrada"
   ↓
2. Sistema verifica limite do plano
   ↓
3. Se atingiu limite → Abre Modal de Planos
   ↓
4. Usuário seleciona plano e período (MÊS/ANO)
   ↓
5. Abre Modal de Pagamento com 3 abas:
   - Cartão crédito/débito
   - PIX
   - Cartões salvos
   ↓
6. Sistema cria preferência no Mercado Pago
   ↓
7. Redireciona para MP para finalizar pagamento
   ↓
8. MP retorna com status
   ↓
9. Webhook valida e atualiza assinatura no BD
   ↓
10. Usuário volta com plano ativo
```

---

## 🎯 ENDPOINTS DA API

### GET `/obter-planos.php`
Retorna lista de planos ativos.

**Resposta:**
```json
{
  "success": true,
  "planos": [
    {
      "id": 1,
      "nome": "GRATUITO",
      "preco_mes": 0,
      "preco_ano": 0,
      "mentores_limite": 1,
      "entradas_diarias": 3,
      "icone": "fas fa-gift"
    }
  ]
}
```

### GET `/obter-dados-usuario.php`
Retorna dados da assinatura atual do usuário.

**Resposta:**
```json
{
  "success": true,
  "usuario": {
    "id": 1,
    "nome": "João Silva",
    "id_plano": 2,
    "nome_plano": "PRATA",
    "status_assinatura": "ativa",
    "data_fim_assinatura": "2025-11-20",
    "plano_ativo": true,
    "dias_restantes": 32
  }
}
```

### GET `/verificar-limite.php?acao=mentor`
Verifica se pode cadastrar.

**Respostas:**
```json
{
  "success": true,
  "pode_prosseguir": true
}
// OU
{
  "success": true,
  "pode_prosseguir": false,
  "mensagem": "Você atingiu o limite de mentores..."
}
```

### POST `/processar-pagamento.php`
Cria preferência no Mercado Pago.

**Payload:**
```json
{
  "id_plano": 2,
  "periodo": "mes",
  "modo_pagamento": "cartao",
  "titular": "João Silva",
  "numero_cartao": "4111111111111111",
  "validade": "12/25",
  "cvv": "123",
  "salvar_cartao": true
}
```

**Resposta:**
```json
{
  "success": true,
  "preference_url": "https://www.mercadopago.com/checkout/v1/...",
  "preference_id": "123456789"
}
```

---

## 🛡️ SEGURANÇA

### Recomendações:

1. **Use HTTPS em produção** - Obrigatório para PCI compliance
2. **Nunca armazene dados de cartão** - Use apenas tokens do MP
3. **Validar no backend** - Nunca confie apenas no frontend
4. **Rate limiting** - Implemente para endpoints de pagamento
5. **Logs** - Registre todas as transações em `/logs/webhook.log`
6. **Variáveis de ambiente** - Use `.env` em produção

```php
// Usar variáveis de ambiente:
define('MP_ACCESS_TOKEN', getenv('MP_ACCESS_TOKEN'));
define('MP_PUBLIC_KEY', getenv('MP_PUBLIC_KEY'));
```

---

## 🧪 TESTE

### Cartões de teste Mercado Pago:

**Aprovado:**
- Número: `4111 1111 1111 1111`
- Vencimento: `12/25`
- CVV: `123`

**Recusado:**
- Número: `5105 1051 0510 5100`
- Vencimento: `11/25`
- CVV: `456`

---

## 📊 MONITORAMENTO

### Verificar transações:

```php
// Em config_mercadopago.php, o arquivo de log é:
// /gestao_banca/logs/webhook.log

// Verificar assinaturas ativas:
SELECT u.nome, p.nome as plano, u.data_fim_assinatura 
FROM usuarios u 
JOIN planos p ON u.id_plano = p.id 
WHERE u.status_assinatura = 'ativa';

// Ver transações com problemas:
SELECT * FROM transacoes_mercadopago 
WHERE status_pagamento != 'aprovado' 
ORDER BY data_criacao DESC;
```

---

## 🆘 TROUBLESHOOTING

### Problema: Modal não abre
- Verifique se `plano-manager.js` está carregado
- Abra console (F12) e procure por erros
- Verifique se `fetch()` está funcionando

### Problema: Pagamento não confirma
- Veja o arquivo `/logs/webhook.log`
- Verifique Access Token no config_mercadopago.php
- Confirme que webhook está registrado no MP

### Problema: Limite não funciona
- Verifique se a tabela `mentores` existe
- Confirme que `id_plano` está preenchido em `usuarios`
- Teste endpoint `/verificar-limite.php`

---

## 📞 PRÓXIMAS FEATURES

- [ ] Renovação automática de cartão
- [ ] Painel administrativo de assinaturas
- [ ] Cancelamento de assinatura
- [ ] Notas fiscais automáticas
- [ ] Relatório de receita
- [ ] Cupons de desconto
- [ ] Upgrade/Downgrade de plano

---

## 📝 NOTAS

- O sistema usar sessionStorage para persistência de estado
- Plano GRATUITO nunca expira
- Renovação é automática se cartão está salvo
- Usuários recebem aviso 7 dias antes de expirar

---

**Status:** ✅ Implementação Completa
**Última atualização:** 2025-10-20
