# 🚀 INSTRUÇÕES PARA UPLOAD NA HOSTINGER

## PROBLEMA ATUAL
- ❌ Webhook retorna 404 Not Found
- ❌ Arquivo não está sincronizado na Hostinger

## ARQUIVO A FAZER UPLOAD

**Arquivo:** `api/telegram-webhook.php`

**Caminho no servidor:** `/home/analisegb.com/public_html/gestao/gestao_banca/api/telegram-webhook.php`

## OPÇÃO 1: VIA CPANEL FILE MANAGER (Mais fácil)

1. Acesse: https://hostinger.com/cpanel
2. Login com suas credenciais
3. Clique em "File Manager"
4. Navegue até: `/home/analisegb.com/public_html/gestao/gestao_banca/api/`
5. Delete o arquivo antigo `telegram-webhook.php` (se existir)
6. Clique em "Upload" e selecione o arquivo novo do seu PC:
   - Local: `c:\xampp\htdocs\gestao\gestao_banca\api\telegram-webhook.php`
7. Aguarde o upload terminar
8. Teste a URL: https://analisegb.com/gestao/gestao_banca/api/telegram-webhook.php

## OPÇÃO 2: VIA FTP

Se você tem acesso FTP:

1. Conecte com credenciais FTP da Hostinger
2. Navegue até: `/public_html/gestao/gestao_banca/api/`
3. Delete `telegram-webhook.php` (se existir)
4. Upload o arquivo novo:
   - De: `c:\xampp\htdocs\gestao\gestao_banca\api\telegram-webhook.php`
   - Para: `/public_html/gestao/gestao_banca/api/telegram-webhook.php`

## OPÇÃO 3: VIA GIT

Se você está usando Git:

```bash
git add api/telegram-webhook.php
git commit -m "Fix: Corrigir bind_param para +0.5 GOL"
git push origin main
```

## VERIFICAR SE FUNCIONOU

Após fazer upload, teste:

1. Acesse: https://analisegb.com/gestao/gestao_banca/check-files.php
   - Deve mostrar "✅ ENCONTRADO" para `/api/telegram-webhook.php`

2. Acesse: https://analisegb.com/gestao/gestao_banca/check-production.php
   - Deve conectar ao banco de produção

3. Acesse: https://analisegb.com/gestao/gestao_banca/config-webhook.php
   - Se tudo OK, "last_error_message" deve desaparecer

## O QUE FOI CORRIGIDO

✅ **bind_param string:** De `"isssssiiiddsss"` (14 chars) para `"isssssiiiddsssss"` (16 chars)
✅ **Tipo de valor_over:** Agora é `d` (double) em vez de `i` (integer)
✅ **Type conversion:** `floatval()` antes do bind_param
✅ **Suporta +0.5, +1, +1.5, +2, etc**

## APÓS UPLOAD

Envie uma mensagem no Telegram com:
```
📊 🚨 OVER ( +0.5 ⚽️GOL  ) FT
Roma x Udinese
Gols over +0.5: 1.57
```

E verifique o log em:
https://analisegb.com/gestao/gestao_banca/logs/telegram-webhook.log

Deve aparecer:
```
✅ OVER detectado: valor extraído = 0.5
✅ bind_param executado com sucesso
✅ Insert executado com sucesso - ID: XXX
```
