# ⚡ GUIA RÁPIDO - AÇÃO IMEDIATA

## 🎯 O QUE FOI RESOLVIDO

✅ Webhook desconectava a cada 8 horas
✅ Mensagens chegavam no Telegram mas não eram salvas
✅ Reconexão automática implementada
✅ Timeouts aumentados para 7 dias

## 🚀 PRÓXIMAS 24 HORAS

### Hoje - Upload para Produção

```bash
# Opção 1: Via Git (RECOMENDADO)
git push origin main

# Opção 2: Via FTP no cPanel
- Login: Hostinger cPanel
- Upload: config.php + api/telegram-webhook.php + obter-und.php
```

### Hoje - Configurar Cron Job

```
cPanel > Cron Jobs > Adicionar novo
Comando: */5 * * * * curl -s https://analisegb.com/gestao/gestao_banca/webhook-health-check.php
Clique: Add New Cron Job
```

### Hoje - Testar

```
1. Webhook Status: https://analisegb.com/gestao/gestao_banca/webhook-status.php
   → Esperado: Tudo GREEN ✅

2. Teste de Reconexão: https://analisegb.com/gestao/gestao_banca/teste-obter-conexao.php
   → Esperado: Todos PASS ✅

3. Enviar mensagem no Telegram
   → Esperado: Aparece no Bot ao Vivo e no banco de dados ✅
```

## 📋 CHECKLIST DE UPLOAD

### OBRIGATÓRIO (Sem isso continua desconectando)

- [ ] Upload: `config.php`
- [ ] Upload: `api/telegram-webhook.php`

### ALTAMENTE RECOMENDADO (Necessário para funcionar corretamente)

- [ ] Upload: `obter-und.php`
- [ ] Upload: `webhook-health-check.php`
- [ ] Upload: `webhook-status.php`
- [ ] Upload: `webhook-test.php`
- [ ] Upload: `teste-obter-conexao.php`

### CONFIGURAÇÃO

- [ ] Cron job configurado (a cada 5 min)

---

## 🔧 EM CASO DE ERRO

### Erro 500 no Webhook

```
1. Verificar permissões: 755 para .php
2. Verificar: cPanel > Error Logs
3. Re-upload de config.php
```

### UND não carrega em bot_aovivo.php

```
1. Verificar: obter-und.php foi uploadado?
2. Testar: https://analisegb.com/gestao/gestao_banca/obter-und.php
3. Verificar: Console do navegador (F12 > Network)
```

### Webhook continua desconectando

```
1. Conferir: config.php foi uploadado?
2. Testar: https://analisegb.com/gestao/gestao_banca/webhook-test.php
3. Verificar logs: logs/telegram-webhook.log
```

---

## 📊 RESULTADOS ESPERADOS

### Após 24 horas

- ✅ Mensagens chegando no Telegram
- ✅ Mensagens sendo salvas no banco
- ✅ Sem erros de conexão

### Após 7 dias

- ✅ Webhook ainda funcionando (comprova timeout funcionou)
- ✅ Dashboard mostrando status GREEN
- ✅ Cron job executando regularmente

---

## 📞 DOCUMENTAÇÃO COMPLETA

Para entender melhor, leia:

- `SOLUCAO_WEBHOOK_COMPLETA.md` - Explicação técnica completa
- `UPLOAD_HOSTINGER_CHECKLIST.md` - Lista detalhada de upload
- `WEBHOOK_FIX_SUMMARY.md` - Resumo da solução

---

## ⏰ TEMPO ESTIMADO

- Upload: 5 minutos
- Cron Job: 2 minutos
- Teste: 5 minutos
- **Total: 12 minutos**

---

## ✅ VALIDAÇÃO RÁPIDA

Após upload, execute:

```bash
# Terminal
curl https://analisegb.com/gestao/gestao_banca/teste-obter-conexao.php

# Esperado:
# ✅ Primeira chamada funcionou
# ✅ Conexão respondendo ao ping
# ✅ Segunda chamada funcionou
# ✅ Query simples funcionou
# ✅ Teste de tabela controle funcionou
# ✅ TODOS OS TESTES CONCLUÍDOS
```

---

## 🎯 SUCESSO =

Quando você ver:

```
[2025-11-12 10:00:00] Webhook acionado
✅ Insert executado com sucesso - ID: XXX
✅ Oportunidade salva com sucesso
```

**Significa: Tudo está funcionando perfeitamente! 🎉**

---

_Última atualização: 2025-11-12_
_Status: Pronto para Deploy_
