# 🔧 CORREÇÃO: Erro "Mensagem não encontrada" ao Deletar

## 🎯 Problema Encontrado

**Erro:** "Erro: Mensagem não encontrada"  
**Causa:** Conflito entre qual coluna usar como ID para deletar:
- Frontend enviava: `telegram_message_id` como `id`
- Backend procurava: coluna `id` (chave primária)
- Resultado: Mensagem não encontrada, mesmo existindo no banco

## ✅ Soluções Implementadas

### 1️⃣ **Arquivo `api/deletar-mensagem.php` (CONSOLIDADO)**

Versão consolidada e melhorada com:
- ✅ Tenta deletar por ID primário PRIMEIRO
- ✅ Se falhar, tenta por `telegram_message_id`
- ✅ Log detalhado de cada tentativa
- ✅ Debug para production
- ✅ Tratamento robusto de erros

**Funcionalidade:**
```php
// Passo 1: Tenta por ID primário
DELETE FROM bote WHERE id = ?

// Passo 2: Se não encontrou, tenta por telegram_message_id
DELETE FROM bote WHERE telegram_message_id = ?
```

### 2️⃣ **Arquivo `api/carregar-mensagens-banco.php` (MODIFICADO)**

Corrigido para retornar ID primário (importante para delete):

**Antes:**
```php
'id' => intval($row['telegram_message_id'] ?: $row['id']),
```

**Depois:**
```php
'id' => intval($row['id']),  // ✅ USA ID PRIMÁRIO
'telegram_message_id' => intval($row['telegram_message_id'] ?: 0),
```

Mudança feita em 3 funções:
- `getMessagesFromDatabase()` ✅
- `pollNewMessages()` ✅
- `getMessagesByDate()` ✅

### 3️⃣ **Arquivo `js/telegram-mensagens.js` (MODIFICADO)**

Atualizado para usar API consolidada:

```javascript
fetch("api/deletar-mensagem.php", {
  method: "POST",
  headers: { "Content-Type": "application/json" },
  body: JSON.stringify({ message_id: messageId }),
})
```

## 📋 Por Que Isso Funciona Agora?

```
ANTES (ERRO):
Frontend: msg.id = 5 (era telegram_message_id)
Backend: DELETE FROM bote WHERE id = 5
Banco: Procura na coluna "id" (chave primária)
Resultado: ❌ Não encontra, porque o ID primário é 123, não 5

DEPOIS (FUNCIONA):
Frontend: msg.id = 123 (agora é ID primário)
Backend: Tenta 2 formas:
  1️⃣ DELETE FROM bote WHERE id = 123 ✅
  2️⃣ DELETE FROM bote WHERE telegram_message_id = 123 (se 1 falhar)
Resultado: ✅ Encontra e deleta
```

## 📤 Arquivos para Subir (PRODUÇÃO)

```
✅ api/deletar-mensagem.php           [CONSOLIDADO - única versão]
✅ api/carregar-mensagens-banco.php   [MODIFICADO - retorna ID correto]
✅ js/telegram-mensagens.js           [MODIFICADO - usa API correta]
```

**❌ DELETADO:**
- `deletar-mensagem-v2.php` (removido para simplificar)

## 🧪 Como Testar

1. **Local:**
   - Recarregue: `http://localhost/gestao/gestao_banca/bot_aovivo.php`
   - Clique no botão 🗑️ (lixeira)
   - Confirme delete
   - Mensagem deve desaparecer ✅

2. **Produção:**
   - Acesse: `https://analisegb.com/gestao/gestao_banca/bot_aovivo.php`
   - Teste delete de várias mensagens
   - Verifique se funciona

3. **Debug:**
   - Abra Console (F12)
   - Verifique logs em: `/logs/deletar-mensagem.log`

## 🔍 Logs de Debug

Se tiver erro, procure em `/logs/deletar-mensagem.log`:

```
[2025-11-13 21:30:00] DEBUG DELETE
  messageId: 123 (tipo: integer)
  usuarioId: 23
  Input recebido: {"message_id":123}
  ✅ Deletado por ID primário
```

ou

```
[2025-11-13 21:31:00] DEBUG DELETE
  messageId: 5 (tipo: integer)
  usuarioId: 23
  Input recebido: {"message_id":5}
  ⚠️ ID primário não encontrado, tentando telegram_message_id...
  ✅ Deletado por telegram_message_id
```

## 📊 Status FINAL

| Componente | Status | Descrição |
|-----------|--------|-----------|
| **deletar-mensagem.php** | ✅ CONSOLIDADO | Única versão - tenta 2 formas |
| **carregar-mensagens-banco.php** | ✅ CORRETO | Retorna ID primário |
| **js/telegram-mensagens.js** | ✅ ATUALIZADO | Usa API correta |
| **Teste Local** | ⏳ TESTE | Você precisa testar |
| **Produção** | ⏳ DEPLOY | Faz upload dos 3 arquivos |

## 🚀 Próximos Passos

1. **Teste local** - Delete algumas mensagens
2. **Upload** dos 3 arquivos para produção
3. **Teste em produção** - Verifique se funciona
4. **Monitor** logs em `/logs/deletar-mensagem.log`
5. **Feedback** - Compartilhe resultado

---

**✅ Arquitetura simplificada = Menos confusão, mais eficiência!**
