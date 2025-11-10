# 🔧 GUIA: Arquivos Transferidos Mas Mudanças Não Aparecem

## ⚠️ CAUSAS MAIS COMUNS

### 1. **CACHE DO NAVEGADOR** (Problema #1 mais comum)
- Navegadores guardam versões antigas de arquivos CSS, JS, imagens
- **Solução rápida:** 
  - `Ctrl + Shift + Delete` (limpar cache)
  - Ou `Ctrl + F5` (reload forçado)
  - Ou abrir em **modo anônimo/privado**

### 2. **CACHE DO PHP/SERVIDOR**
- `Ctrl + Shift + Del` não resolve
- Arquivo foi transferido mas servidor está servindo versão cached
- **Solução:** Aguarde 5-10 minutos ou entre em contato com suporte Hostinger

### 3. **ARQUIVO NÃO FOI TRANSFERIDO CORRETAMENTE**
- Arquivo está em seu PC mas não foi enviado ao servidor
- Status SFTP mostrou sucesso mas arquivo não chegou
- **Verificar:** Use gerenciador de arquivos do Hostinger (cPanel > File Manager)

### 4. **ARQUIVO FOI TRANSFERIDO PARA LOCAL ERRADO**
- Transferiu para pasta `public_html` mas o site está em subpasta
- Transferiu para `gestao_banca` mas deveria ser em `gestao_banca/public` ou similar
- **Verificar:** Qual é a URL do seu site?

### 5. **SITE USA VERSÃO DIFERENTE**
- Você tem múltiplas pastas de projeto
- Site aponta para outra pasta (não para `gestao_banca`)
- **Verificar:** No `config.php` qual é o `ENVIRONMENT` que está sendo usado?

### 6. **CDN OU CACHE CLOUDFLARE**
- Se usa Cloudflare, ele guarda versão antiga
- **Solução:** Limpar cache no Cloudflare ou esperar TTL expirar

### 7. **ARQUIVO PHP NÃO FOI ATUALIZADO**
- Se mudou PHP, arquivo não recarrega automaticamente
- Sessions podem estar cacheadas
- **Solução:** 
  ```php
  header("Cache-Control: no-cache, no-store, must-revalidate");
  header("Pragma: no-cache");
  header("Expires: 0");
  ```

---

## ✅ PASSO A PASSO PARA RESOLVER

### **PASSO 1: Verificar o que foi transferido**
1. Acesse **cPanel → File Manager**
2. Navegue até `public_html/gestao_banca` (ou onde seu site está)
3. Procure o arquivo que modificou
4. Clique com botão direito → **Edit** ou **View**
5. Verifique se tem sua mudança

### **PASSO 2: Se arquivo está desatualizado no servidor**
**Opção A - Transferir novamente:**
1. Use VS Code com extensão SFTP
2. Clique com direito no arquivo → **Upload**
3. Aguarde confirmação

**Opção B - Editar direto no cPanel:**
1. Abra em **File Manager → Edit**
2. Faça a mudança lá
3. Salve (aperta Ctrl+S)

### **PASSO 3: Limpar cache**
```javascript
// Adicionar ao final de cada arquivo JS/CSS:
?v=<?php echo time(); ?>

// Exemplo no HTML/PHP:
<link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
<script src="js/script.js?v=<?php echo time(); ?>"></script>
```

Isso força o navegador a baixar versão nova toda vez.

### **PASSO 4: Se é arquivo PHP**
1. Adicione isso no topo do arquivo:
```php
<?php
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
?>
```

### **PASSO 5: Testar com browser developer**
1. Abra site (F12)
2. Vá para **Network**
3. Recarregue (Ctrl+Shift+R)
4. Procure seu arquivo
5. Veja a **coluna "Size"**:
   - Se disser **(from cache)** = problema é cache local
   - Se disser **(memory cache)** = problema é cache local
   - Se mostrar tamanho = arquivo foi baixado do servidor

---

## 🎯 CHECKLIST RÁPIDO

- [ ] Abri site em modo **incógnito/privado**?
- [ ] Fiz **Ctrl + F5** (reload forçado)?
- [ ] Arquivo aparece correto no **cPanel File Manager**?
- [ ] Arquivo tem data de modificação **recente**?
- [ ] Transferi para pasta **correta**?
- [ ] É realmente o arquivo que **o site está usando**?
- [ ] Arquivo tem permissões corretas (644 para arquivos)?
- [ ] Aguardei **5 minutos** para cache do servidor expirar?

---

## 🚀 DICA: Validar Transferência Automaticamente

Crie este arquivo para verificar se transferência foi bem-sucedida:

**`verificar-transferencia.php`**
```php
<?php
echo "✓ Arquivo atualizado em: " . date("Y-m-d H:i:s");
echo "<br>";
echo "✓ Servidor: " . ENVIRONMENT;
echo "<br>";
echo "✓ Hash do arquivo: " . md5_file(__FILE__);
?>
```

Depois de transferir, acesse:
```
https://seusite.com/verificar-transferencia.php
```

Se a hora é recente = transferência OK

---

## 📞 PRÓXIMOS PASSOS

**Se nada funcionar:**
1. Confirme URL exata do seu site
2. Verifique qual pasta `public_html` está usando
3. Faça uma mudança visível (ex: adicione "TESTE 123" em vermelho)
4. Verifique se aparece no navegador (incógnito + F5)
5. Se não aparecer = arquivo não foi transferido ou está em pasta errada
