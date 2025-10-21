# ✅ CHECKLIST DE IMPLEMENTAÇÃO - SISTEMA DE PLANOS

## 📊 RESUMO DO QUE FOI CRIADO

- [x] Schema do banco de dados com 5 novas tabelas
- [x] Configuração completa do Mercado Pago
- [x] Modal visual de planos com toggle MÊS/ANO
- [x] Modal de pagamento com 3 abas (Cartão, PIX, Salvos)
- [x] APIs PHP para gerenciar planos
- [x] Sistema completo de JavaScript para front-end
- [x] Webhook para processar confirmações
- [x] Sistema de validação de limites
- [x] Documentação e exemplos

---

## 🚀 PRÓXIMOS PASSOS

### 1️⃣ CONFIGURAÇÃO DO BANCO DE DADOS

```
✅ Execute: db_schema_planos.sql no phpMyAdmin
   - Vai criar: planos, assinaturas, transacoes_mercadopago, cartoes_salvos
   - Vai adicionar colunas em usuarios
```

### 2️⃣ CONFIGURAR MERCADO PAGO

```
✅ Edite: config_mercadopago.php
   - Adicione seu Access Token (linha 9)
   - Adicione seu Public Key (linha 10)
   - Configure a URL do webhook no painel MP
```

### 3️⃣ INTEGRAR NO HTML

```html
✅ No seu template principal (ex: gestao-diaria.php), adicione antes do </body>:

<?php include 'modal-planos-pagamento.html'; ?>
<script src="js/plano-manager.js"></script>
```

### 4️⃣ ADICIONAR VALIDAÇÕES

```javascript
✅ Antes de permitir cadastro de mentor:
const pode = await PlanoManager.verificarEExibirPlanos('mentor');
if (!pode) return;

✅ Antes de permitir nova entrada:
const pode = await PlanoManager.verificarEExibirPlanos('entrada');
if (!pode) return;
```

### 5️⃣ TESTAR TUDO

```
✅ Acesse: http://localhost/gestao_banca/teste-planos.php
   - Teste cada endpoint
   - Teste a interface
   - Teste as validações
   - Teste com cartões de teste do MP
```

---

## 📁 ARQUIVOS CRIADOS

### 📂 Backend (PHP)
```
✅ config_mercadopago.php          - Configurações e classe MercadoPagoManager
✅ obter-planos.php                - GET: lista de planos
✅ obter-dados-usuario.php         - GET: dados da assinatura
✅ obter-cartoes-salvos.php        - GET: cartões salvos
✅ verificar-limite.php            - GET: verificar limites
✅ processar-pagamento.php         - POST: criar preferência Mercado Pago
✅ webhook.php                     - POST: processar confirmações (ATUALIZADO)
```

### 📂 Frontend (HTML/CSS/JS)
```
✅ modal-planos-pagamento.html     - HTML + CSS do modal completo
✅ js/plano-manager.js             - JavaScript: lógica de planos
✅ exemplo-integracao.html         - Exemplo de integração
✅ teste-planos.php                - Página de testes
```

### 📂 Database
```
✅ db_schema_planos.sql            - Schema SQL completo
```

### 📂 Documentação
```
✅ README_PLANOS.md                - Documentação completa
✅ IMPLEMENTACAO_CHECKLIST.md      - Este arquivo
```

---

## 🎯 ARQUITETURA DO SISTEMA

```
┌─────────────────────────────────────────────────────┐
│                   USUÁRIO                           │
└────────────────┬────────────────────────────────────┘
                 │
        ┌────────▼──────────┐
        │ APLICAÇÃO WEB     │
        │ (gestao-diaria.php│
        └────────┬──────────┘
                 │
    ┌────────────┼────────────┐
    │            │            │
    ▼            ▼            ▼
┌────────┐ ┌───────────┐ ┌──────────┐
│Planos  │ │Pagamento  │ │Validação │
│Modal   │ │Modal      │ │Limites   │
└───┬────┘ └─────┬─────┘ └────┬─────┘
    │            │            │
    └────────────┼────────────┘
                 │
        ┌────────▼──────────────┐
        │ JAVASCRIPT            │
        │ plano-manager.js      │
        └────────┬──────────────┘
                 │
    ┌────────────┴────────────────┐
    │                             │
    ▼                             ▼
┌─────────────────────┐  ┌───────────────────┐
│ PHP APIs:           │  │ Mercado Pago:     │
│ - obter-planos      │  │ - Criar Preference│
│ - verificar-limite  │  │ - Processar Pagto │
│ - processar-pagto   │  │ - Retornar Status │
│ - webhook           │  └───────────────────┘
└────────┬────────────┘
         │
    ┌────▼─────────────────┐
    │ BANCO DE DADOS:       │
    │ - usuarios            │
    │ - planos              │
    │ - assinaturas         │
    │ - transacoes_mp       │
    │ - cartoes_salvos      │
    └───────────────────────┘
```

---

## 💳 FLUXO DE PAGAMENTO

```
1. Usuário clica em "Cadastrar Mentor"
   ↓
2. Sistema verifica limit do plano via verificar-limite.php
   ↓
3. Se atingiu limite:
   - Abre modal-planos-pagamento.html
   - Mostra 4 planos com preços de MÊS/ANO
   ↓
4. Usuário clica em "Contratar Agora" (ex: PRATA)
   ↓
5. Abre modal de pagamento com 3 abas:
   - Cartão (crédito/débito)
   - PIX
   - Cartões salvos
   ↓
6. Usuário preenche dados ou escolhe cartão salvo
   ↓
7. Clica "Confirmar Pagamento"
   ↓
8. JavaScript envia POST para processar-pagamento.php
   ↓
9. PHP usa MercadoPagoManager para criar preferência
   ↓
10. Redireciona para Mercado Pago (checkout)
    ↓
11. Usuário completa pagamento no MP
    ↓
12. MP confirma pagamento e chama webhook.php
    ↓
13. Webhook:
    - Atualiza status em transacoes_mercadopago
    - Cria registro em assinaturas
    - Atualiza usuarios com novo plano
    - Salva cartão se marcado
    ↓
14. Usuário volta ao site com plano ativo
    ↓
15. Agora pode cadastrar mais mentores/entradas
```

---

## 🔑 VARIÁVEIS DE AMBIENTE IMPORTANTES

```php
// Editar: config_mercadopago.php

define('MP_ACCESS_TOKEN', 'SEU_ACCESS_TOKEN_AQUI');
// Onde pega: https://www.mercadopago.com.br > Configurações > Credenciais

define('MP_PUBLIC_KEY', 'SEU_PUBLIC_KEY_AQUI');
// Onde pega: Mesmo lugar acima

define('MP_ENVIRONMENT', 'development');
// development = teste | production = produção

define('MP_SUCCESS_URL', base_url . '/webhook.php?status=success');
define('MP_FAILURE_URL', base_url . '/webhook.php?status=failure');
define('MP_PENDING_URL', base_url . '/webhook.php?status=pending');
```

---

## 🧪 TESTES RECOMENDADOS

### Teste 1: Endpoints
```bash
1. Abra: http://localhost/gestao_banca/teste-planos.php
2. Clique em "Testar /obter-planos.php"
3. Deve retornar 4 planos
```

### Teste 2: Modal
```bash
1. Clique em "Abrir Modal de Planos"
2. Deve aparecer com 4 planos
3. Teste o toggle MÊS/ANO
4. Preços devem mudar
```

### Teste 3: Pagamento com Cartão de Teste
```
Número: 4111 1111 1111 1111
Data: 12/25
CVV: 123
```

### Teste 4: Validação de Limites
```
1. Como usuário no plano GRATUITO:
   - Tente cadastrar 2º mentor
   - Deve mostrar modal de planos
   
2. Como usuário no plano PRATA:
   - Tente cadastrar 6º mentor
   - Deve mostrar modal de upgrade
```

---

## 🛡️ SEGURANÇA

### ✅ Implementado
- [x] Validação no backend
- [x] CSRF protection (use token em formulários)
- [x] Hash de senhas
- [x] Tokens do Mercado Pago (nunca dados brutos)
- [x] SQL Prepared Statements
- [x] Log de transações

### ⚠️ Implementar em Produção
- [ ] HTTPS obrigatório
- [ ] Rate limiting nos endpoints
- [ ] Validação de referência da transação
- [ ] Criptografia de dados sensíveis
- [ ] Rotação de chaves de API
- [ ] Monitoramento de fraudes

---

## 📊 QUERIES ÚTEIS PARA DEBUG

```sql
-- Ver todos os planos
SELECT * FROM planos;

-- Ver assinaturas ativas
SELECT u.nome, p.nome as plano, u.data_fim_assinatura, u.status_assinatura
FROM usuarios u
LEFT JOIN planos p ON u.id_plano = p.id
WHERE u.status_assinatura = 'ativa'
ORDER BY u.data_fim_assinatura DESC;

-- Ver transações com problemas
SELECT * FROM transacoes_mercadopago
WHERE status_pagamento != 'aprovado'
ORDER BY data_criacao DESC
LIMIT 10;

-- Ver cartões salvos
SELECT * FROM cartoes_salvos
ORDER BY data_criacao DESC;

-- Ver assinaturas que vão expirar
SELECT u.nome, p.nome, u.data_fim_assinatura, 
       DATEDIFF(u.data_fim_assinatura, CURDATE()) as dias_restantes
FROM usuarios u
LEFT JOIN planos p ON u.id_plano = p.id
WHERE u.data_fim_assinatura BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
ORDER BY u.data_fim_assinatura;
```

---

## 🆘 TROUBLESHOOTING

### Problema: "MercadoPagoManager não encontrado"
```
✅ Solução: Adicione no topo do arquivo:
require_once 'config_mercadopago.php';
```

### Problema: "Modal não abre"
```
✅ Solução:
1. Verifique se plano-manager.js está carregado (F12 > Console)
2. Verifique se modal-planos-pagamento.html está incluído
3. Teste: PlanoManager.abrirModalPlanos() no console
```

### Problema: "Pagamento não confirma"
```
✅ Solução:
1. Verifique config_mercadopago.php (tokens corretos?)
2. Veja /logs/webhook.log
3. Verifique se webhook está registrado no MP
4. Use cartão de teste: 4111 1111 1111 1111
```

### Problema: "Limite não funciona"
```
✅ Solução:
1. Verifique se usuario.id_plano está preenchido
2. Teste: http://localhost/gestao_banca/verificar-limite.php
3. Verifique se tabelas de planos estão preenchidas
```

---

## 📞 PRÓXIMAS FEATURES

- [ ] Renovação automática (cron job)
- [ ] Painel administrativo de assinaturas
- [ ] Cancelamento de plano
- [ ] Notas fiscais
- [ ] Relatórios de receita
- [ ] Cupons de desconto
- [ ] Upgrade/Downgrade entre planos
- [ ] Trial gratuito
- [ ] Notificações por email
- [ ] Dashboard de métricas

---

## 📝 RESUMO FINAL

**Status:** ✅ **PRONTO PARA USAR**

**O que fazer agora:**

1. Execute `db_schema_planos.sql` no phpMyAdmin
2. Configure credenciais em `config_mercadopago.php`
3. Inclua os modais em seu template HTML
4. Adicione validações nas funções de cadastro
5. Teste em http://localhost/gestao_banca/teste-planos.php
6. Coloque em produção com HTTPS

**Tempo estimado:** 30-60 minutos

**Suporte:** Veja README_PLANOS.md para documentação completa

---

**Versão:** 1.0
**Data:** 2025-10-20
**Atualização:** 2025-10-20
