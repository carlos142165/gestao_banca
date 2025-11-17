# 📤 ARQUIVOS PARA UPLOAD - HOSTINGER

## ✅ STATUS: TODOS OS ARQUIVOS PRONTOS PARA PRODUÇÃO

### 🔴 CRÍTICOS (Obrigatório Upload)

#### 1. **config.php** ⭐
- **Mudança**: Adicionada função `obterConexao()` com reconexão automática
- **Impacto**: **CRÍTICO** - Sem isso, webhook continuará desconectando
- **Linha de Mudança**: Adicionadas linhas 82-130
- **Alteração Principal**: 
  ```php
  function obterConexao() {
      global $conexao;
      if ($conexao && $conexao->ping()) {
          return $conexao;
      }
      // Reconecta automaticamente...
  }
  ```

#### 2. **api/telegram-webhook.php**
- **Mudança**: Adicionada verificação `$conexao = obterConexao();` no início
- **Impacto**: Garante que webhook sempre tem conexão ativa
- **Localização**: Linhas 27, 176, 322

### 🟢 SUPORTE (Recomendado Upload)

#### 3. **obter-und.php** (NOVO)
- **Função**: Retorna o valor da UND (Unidade) do usuário via AJAX
- **Necessário para**: bot_aovivo.php exibir valores corretamente
- **Tipo**: Novo arquivo essencial

#### 4. **webhook-health-check.php**
- **Função**: Monitor automático do webhook (para cron job)
- **Frequência**: Executar a cada 5 minutos
- **Impacto**: Detecta e corrige problemas automaticamente

#### 5. **webhook-status.php**
- **Função**: Dashboard em tempo real do status
- **URL**: https://analisegb.com/gestao/gestao_banca/webhook-status.php
- **Uso**: Monitoramento visual

#### 6. **webhook-test.php**
- **Função**: Script de teste rápido
- **Uso**: Validação durante setup

#### 7. **teste-obter-conexao.php** (NOVO)
- **Função**: Testa se reconexão automática funciona
- **URL**: https://analisegb.com/gestao/gestao_banca/teste-obter-conexao.php

### 📋 DOCUMENTAÇÃO

- **WEBHOOK_FIX_SUMMARY.md** - Este documento resumido

---

## 🚀 INSTRUÇÕES DE UPLOAD

### Via Git (Recomendado)
```bash
cd c:\xampp\htdocs\gestao\gestao_banca
git push origin main
```

### Via FTP (Hostinger cPanel)
1. Fazer login em: https://www.hostinger.com.br/cpanel
2. Acessar: File Manager ou FTP
3. Fazer upload dos arquivos:
   - ✅ `config.php`
   - ✅ `api/telegram-webhook.php`
   - ✅ `obter-und.php`
   - ✅ `webhook-health-check.php`
   - ✅ `webhook-status.php`
   - ✅ `webhook-test.php`
   - ✅ `teste-obter-conexao.php`

---

## ⚙️ CONFIGURAÇÃO CRON JOB (IMPORTANTE)

**Localização**: cPanel > Cron Jobs

**Comando a adicionar**:
```
*/5 * * * * curl -s https://analisegb.com/gestao/gestao_banca/webhook-health-check.php
```

**Frequência**: A cada 5 minutos
**Função**: Verifica se webhook está vivo e reconecta automaticamente se necessário

---

## ✅ CHECKLIST PRÉ-UPLOAD

- [ ] Fazer backup do `config.php` atual no servidor
- [ ] Upload de `config.php` (CRÍTICO)
- [ ] Upload de `api/telegram-webhook.php` (CRÍTICO)
- [ ] Upload de `obter-und.php` (IMPORTANTE)
- [ ] Upload dos scripts de suporte
- [ ] Configurar cron job
- [ ] Testar webhook: https://analisegb.com/gestao/gestao_banca/webhook-status.php
- [ ] Testar reconexão: https://analisegb.com/gestao/gestao_banca/teste-obter-conexao.php
- [ ] Verificar logs: https://analisegb.com/gestao/gestao_banca/logs/telegram-webhook.log

---

## 🔍 VERIFICAÇÃO PÓS-UPLOAD

### 1. Dashboard do Webhook
```
URL: https://analisegb.com/gestao/gestao_banca/webhook-status.php
Esperado: Todos os status em GREEN ✅
```

### 2. Teste da Reconexão
```
URL: https://analisegb.com/gestao/gestao_banca/teste-obter-conexao.php
Esperado: Todos os testes PASS ✅
```

### 3. Log do Webhook
```
Arquivo: logs/telegram-webhook.log
Esperado: "✅ Insert executado com sucesso" ou "✅ Resultado processado"
```

---

## 📞 SUPORTE

Se houver erros após upload:

1. **Erro 500 em webhook**:
   - Verificar permissões de arquivo (755 para .php)
   - Verificar se `config.php` foi uploadado
   - Verificar logs em cPanel > Error Logs

2. **Conexão recusada**:
   - Verificar credenciais em `config.php`
   - Ping ao banco: `mysql -h 127.0.0.1 -u u857325944_formu -p u857325944_formu`

3. **UND não carrega em bot_aovivo.php**:
   - Verificar se `obter-und.php` foi uploadado
   - Testar URL: https://analisegb.com/gestao/gestao_banca/obter-und.php
   - Verificar console do navegador para erros AJAX

---

## 📊 RESUMO TÉCNICO

| Arquivo | Tipo | Crítico | Mudança |
|---------|------|---------|---------|
| config.php | Modificado | ⭐⭐⭐ | +49 linhas (obterConexao) |
| telegram-webhook.php | Modificado | ⭐⭐⭐ | +3 chamadas de obterConexao() |
| obter-und.php | Novo | ⭐⭐ | 67 linhas |
| webhook-health-check.php | Novo | ⭐ | Monitoramento |
| webhook-status.php | Novo | ⭐ | Dashboard |
| webhook-test.php | Novo | ⭐ | Teste |
| teste-obter-conexao.php | Novo | ⭐ | Validação |

---

## ✨ RESULTADO ESPERADO

Após upload e configuração:
- ✅ Webhook funcionando 24/7 sem desconexões
- ✅ Mensagens chegando no Telegram E sendo salvas no banco
- ✅ Reconexão automática em caso de timeout
- ✅ Monitoramento contínuo via cron job
- ✅ Dashboard de status em tempo real

**Status: 🟢 PRONTO PARA PRODUÇÃO**
