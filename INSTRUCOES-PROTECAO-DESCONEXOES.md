# 🔧 PROTEÇÃO CONTRA DESCONEXÕES - Guia de Implementação

## ✅ O Problema

O webhook estava desconectando do banco de dados após algumas horas de operação, causando falha no recebimento de mensagens do Telegram.

**Causa raiz:** 
- Timeouts padrão do MySQL (28800 segundos = 8 horas)
- Falta de reconexão automática robusta
- Sem verificação periódica de saúde da conexão

---

## ✅ Soluções Implementadas

### **1. Melhorias em `config.php`**

#### Antes:
```php
function obterConexao() {
    global $conexao;
    if ($conexao && $conexao->ping()) {
        return $conexao;
    }
    // Criar nova conexão...
}
```

#### Depois (ROBUSTO):
```php
function obterConexao() {
    global $conexao;
    
    // ✅ Verificar se conexão existe
    if (!$conexao) {
        return criarNovaConexao();
    }
    
    // ✅ Verificar com PING (mais confiável)
    if ($conexao->ping()) {
        return $conexao;
    }
    
    // ✅ Se ping falhou, reconectar
    return criarNovaConexao();
}

function criarNovaConexao() {
    // ✅ Criar conexão
    // ✅ SET TIMEOUT = 604800s (7 dias)
    // ✅ SET net_read_timeout = 604800
    // ✅ SET net_write_timeout = 604800
    // ✅ SET autocommit = 1
}
```

**Melhorias:**
- ✅ Verificação de NULL antes de ping()
- ✅ Timeouts aumentados para TODOS os parâmetros (7 dias)
- ✅ Função separada para criar conexão (reutilizável)
- ✅ Tratamento de exceções

---

### **2. Proteção no Webhook (`api/telegram-webhook.php`)**

#### No início do webhook:
```php
// ✅ GARANTIR QUE CONEXÃO ESTÁ ATIVA - COM MÚLTIPLAS TENTATIVAS
$conexao = obterConexao();
$tentativas = 0;
$maxTentativas = 3;

while (!$conexao && $tentativas < $maxTentativas) {
    sleep(1); // Aguardar 1 segundo
    $conexao = criarNovaConexao();
    $tentativas++;
}

if (!$conexao) {
    // Falhar apenas após 3 tentativas
    http_response_code(500);
    exit;
}
```

**Resultado:**
- ✅ Até 3 tentativas automáticas de reconexão
- ✅ Aguarda 1 segundo entre tentativas
- ✅ Garante conexão antes de processar mensagem

#### Em `salvarNosBancoDados()`:
```php
// ✅ MÚLTIPLAS TENTATIVAS
$conexao = obterConexao();
$tentativas = 0;

while (!$conexao && $tentativas < 3) {
    file_put_contents($logFile, "⚠️ Tentativa " . ($tentativas + 1) . "/3...");
    sleep(1);
    $conexao = criarNovaConexao();
    $tentativas++;
}

// ✅ PING ANTES DE EXECUTAR
if (!$conexao->ping()) {
    $conexao = criarNovaConexao();
}
```

**Resultado:**
- ✅ Tenta reconectar se cair
- ✅ Verifica ping antes de cada operação
- ✅ Logs detalhados de cada tentativa

---

### **3. Health Check Periódico (`webhook-health-check-v2.php`)**

Script que verifica a saúde do webhook a cada 5 minutos:

```bash
# Adicione no cron (cPanel > Cron Jobs):
*/5 * * * * curl -s https://analisegb.com/gestao/gestao_banca/webhook-health-check-v2.php
```

**O que faz:**
1. ✅ Verifica se conexão está ativa (ping)
2. ✅ Executa query simples (SELECT 1)
3. ✅ Conta mensagens da última hora
4. ✅ Retorna status JSON
5. ✅ Reconnecta automaticamente se falhou

---

## 📊 Timeout Configuration (7 dias)

```sql
-- Valores agora setados:
SET SESSION wait_timeout = 604800;              -- 7 dias
SET SESSION interactive_timeout = 604800;       -- 7 dias
SET SESSION net_read_timeout = 604800;          -- 7 dias
SET SESSION net_write_timeout = 604800;         -- 7 dias
SET SESSION autocommit = 1;                     -- Auto-commit ativo
```

**Antes:** 28800s (8 horas) ❌
**Depois:** 604800s (7 dias) ✅

---

## 🚀 Implementação em Produção

### **PASSO 1: Upload dos Arquivos**

Envie para Hostinger:
- ✅ `config.php` (com novas funções)
- ✅ `api/telegram-webhook.php` (com proteção)
- ✅ `webhook-health-check-v2.php` (NEW)

### **PASSO 2: Configurar Cron Job**

No cPanel Hostinger:
1. Vá para **Cron Jobs**
2. Adicione nova tarefa:
   ```
   */5 * * * * curl -s https://analisegb.com/gestao/gestao_banca/webhook-health-check-v2.php
   ```
3. Salve

**O que acontece:**
- A cada 5 minutos, o health check verifica a conexão
- Se desconectou, reconecta automaticamente
- Se houver erro, você receberá no log do cron

### **PASSO 3: Monitorar os Logs**

Verifique logs de reconexão:
```
cat /home/sua_conta/public_html/gestao/gestao_banca/logs/webhook.log
cat /home/sua_conta/public_html/gestao/gestao_banca/logs/webhook-health-check.log
```

---

## 📋 Checklist Final

- [ ] Upload de `config.php` para produção
- [ ] Upload de `api/telegram-webhook.php` para produção
- [ ] Upload de `webhook-health-check-v2.php` para produção
- [ ] Criar pasta `/logs` no servidor
- [ ] Configurar cron job para executar a cada 5 minutos
- [ ] Testar: enviar mensagem do Telegram
- [ ] Aguardar 8+ horas e verificar se continua recebendo
- [ ] Conferir logs em: `/logs/webhook.log`

---

## 🧪 Teste Local

Antes de enviar para produção:

```bash
# 1. Executar health check local
http://localhost/gestao/gestao_banca/webhook-health-check-v2.php

# 2. Deve retornar:
{
    "status": "ok",
    "mensagem": "Webhook está saudável",
    "conexao": "ativa",
    "mensagens_ultima_hora": 5
}

# 3. Enviar mensagem de teste do Telegram
# 4. Verificar se foi salva no banco
# 5. Aguardar 12+ horas e enviar outra mensagem
```

---

## ⚡ Resumo das Proteções

| Proteção | O que faz | Onde |
|----------|-----------|------|
| **Ping Check** | Verifica se conexão responde | `obterConexao()` |
| **Retry Loop** | Tenta até 3 vezes | `salvarNosBancoDados()` |
| **Timeouts** | 604800s (7 dias) | `criarNovaConexao()` |
| **Health Check** | Verifica a cada 5 min | cron job |
| **Error Logging** | Registra todas as tentativas | `/logs/webhook.log` |

---

## 🔍 Debugging

Se ainda tiver problemas:

1. **Ver último erro:**
   ```
   tail -f /logs/webhook.log
   ```

2. **Verificar status do MySQL:**
   ```
   curl http://localhost/check-db.php
   ```

3. **Forçar reconexão:**
   ```
   curl http://localhost/gestao/gestao_banca/webhook-health-check-v2.php
   ```

4. **Verificar timeouts no servidor:**
   ```sql
   SHOW VARIABLES LIKE '%timeout%';
   ```

---

## 📞 Suporte

Se o webhook continuar desconectando:
1. Verifique os logs (`/logs/webhook.log`)
2. Procure por mensagens de erro
3. Se vir "Connection lost", o MySQL está encerrando a conexão
4. Solicite ao Hostinger para aumentar os timeouts globais do servidor

Arquivos modificados:
- ✅ `config.php` - Função `obterConexao()` + `criarNovaConexao()`
- ✅ `api/telegram-webhook.php` - Proteção de reconexão
- ✅ `webhook-health-check-v2.php` - NEW - Health check periódico
