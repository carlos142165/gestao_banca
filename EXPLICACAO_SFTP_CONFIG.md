# 📖 Explicação do Arquivo sftp-config.json

## O que cada campo faz:

### 1. **"name": "Hostinger - Gestão Banca"**
```
Identifica a conexão
```
- Nome da sua conexão SFTP
- Aparece no VS Code quando você clica em "Connect"
- Você pode ter múltiplas conexões, cada uma com um nome diferente
- **Exemplo:** "Hostinger", "Servidor Principal", "Produção"

---

### 2. **"host": "45.132.157.168"**
```
Endereço do servidor
```
- **IP do servidor Hostinger** onde seus arquivos estão hospedados
- É como o "endereço" do seu servidor
- Pode ser um IP (45.132.157.168) ou domínio (seu_dominio.com)
- **Função:** Conecta ao servidor correto

---

### 3. **"protocol": "sftp"**
```
Tipo de conexão
```
- **SFTP** = SSH File Transfer Protocol (mais seguro)
- Outras opções: "ftp" (menos seguro), "ssh" (avançado)
- **Por que SFTP?** Criptografa sua senha durante a transmissão
- **Conclusão:** Use sempre SFTP (mais seguro que FTP)

---

### 4. **"port": 22**
```
Porta de acesso
```
- **Porta 22** = SFTP padrão (seguro)
- **Porta 21** = FTP antigo (não recomendado)
- **Por que 22?** É a porta segura para SSH/SFTP
- **Função:** Define como vai se conectar (qual "porta" usar)

---

### 5. **"username": "u857325944"**
```
Seu usuário FTP
```
- Identificador para acessar o servidor
- Gerado automaticamente pela Hostinger
- **Aparência típica:** u857325944 ou seu_email@dominio.com
- **Função:** "Quem é você" no servidor
- **Exemplo de uso:** Quando conecta, servidor pergunta "Quem é?" → Responde: u857325944

---

### 6. **"password": "sua_senha_aqui"**
```
Sua senha FTP
```
- **⚠️ IMPORTANTE:** Substitua por sua senha real!
- Protege seu acesso ao servidor
- **Nunca compartilhe** essa senha
- **Função:** "Qual é sua prova que é você?"
- **Segurança:** Arquivo `.vscode` é local, não vai para o servidor

**Exemplo:** Se sua senha é "MinhaSenha123!", fica assim:
```json
"password": "MinhaSenha123!"
```

---

### 7. **"remotePath": "/home/u857325944/public_html"**
```
Caminho no servidor
```
- **Onde seus arquivos estão armazenados** no servidor Hostinger
- Dividido em 3 partes:
  - `/home/` = pasta home do servidor
  - `u857325944/` = sua pasta de usuário
  - `public_html/` = pasta pública (site fica aqui)

- **Equivalente local:** `c:\xampp\htdocs\gestao\gestao_banca`
- **Função:** "Vou conectar nisso caminho específico do servidor"

**Estrutura do servidor:**
```
/home/
  └── u857325944/
       └── public_html/  ⬅️ Seus arquivos de site
            └── gestao_banca/
                 ├── js/
                 ├── css/
                 └── arquivos...
```

---

### 8. **"uploadOnSave": true**
```
Upload automático ao salvar
```
- **true** = Sempre que você salva (Ctrl+S), sobe automaticamente
- **false** = Você controla manualmente
- **Exemplo:**
  - Edita: `modal-historico-resultados.js`
  - Salva: Ctrl+S
  - ⚡ Arquivo sobe sozinho para o servidor

**Função:** Automatiza o processo de upload

---

### 9. **"useTempFile": false**
```
Usar arquivo temporário
```
- **false** = Upload normal (recomendado)
- **true** = Cria arquivo temp antes de substituir o original
- **Quando usar true:** Se o servidor tiver muitas mudanças ao mesmo tempo
- **Para você:** Deixe como **false** (mais rápido)

---

### 10. **"openSsh": false**
```
Usar OpenSSH do VS Code
```
- **false** = Usa SFTP padrão do VS Code
- **true** = Usa OpenSSH (apenas se tiver instalado)
- **Para você:** Deixe como **false** (mais compatível)

---

### 11. **"syncMode": "local"**
```
Direção da sincronização
```
- **"local"** = Seu computador → Servidor
- **"remote"** = Servidor → Seu computador
- **Você tem:** "local" (certo!)
- **Função:** Define para onde os arquivos vão

**Fluxo:**
```
Seu PC (local) → Upload → Servidor (remote)
```

---

### 12. **"ignore": [...]**
```
Arquivos que NÃO faz upload
```
Esses arquivos são ignorados (não sobem):

```json
".vscode"         // Configuração do VS Code (local)
".git"            // Histórico Git (não precisa)
".env"            // Variáveis sensíveis (não compartilhar)
"node_modules"    // Dependências (muito grande)
"*.md"            // Arquivos Markdown (documentação)
".DS_Store"       // Arquivo do macOS (não precisa)
"css.8070"        // Backup antigo (não precisa)
"js.8070"         // Backup antigo (não precisa)
```

**Por quê?**
- Economiza tempo de upload
- Evita sincronizar arquivos desnecessários
- Protege dados sensíveis

---

## 🎯 Resumo Visual

```
┌─────────────────────────────────────────────────────────┐
│ SEU COMPUTADOR (Local)                                  │
│ c:\xampp\htdocs\gestao\gestao_banca\                   │
│ ├── modal-historico-resultados.js                       │
│ ├── css/                                                │
│ └── ...                                                 │
└────────────────┬────────────────────────────────────────┘
                 │
        uploadOnSave: true
        (Ctrl+S = sobe automático)
                 │
                 ▼
┌─────────────────────────────────────────────────────────┐
│ SERVIDOR HOSTINGER (Remote)                             │
│ /home/u857325944/public_html/                           │
│ ├── modal-historico-resultados.js ✅                    │
│ ├── css/                                                │
│ └── ...                                                 │
└─────────────────────────────────────────────────────────┘
```

---

## ✅ Workflow Prático

```
1. Edita arquivo localmente
   ↓
2. Salva (Ctrl+S)
   ↓
3. uploadOnSave: true ativa
   ↓
4. Arquivo detectado em "ignore"?
   - Sim? → Não faz upload
   - Não? → Faz upload
   ↓
5. Conecta ao servidor (host + port + username + password)
   ↓
6. Coloca arquivo no caminho remoto
   (remotePath: /home/u857325944/public_html)
   ↓
7. ✅ Arquivo está live no servidor!
   ↓
8. Atualiza browser (F5) e vê mudança
```

---

## 🔐 Segurança

**⚠️ Cuidado:**
- Não compartilhe `sftp.json` com ninguém
- Não faça commit desse arquivo no Git
- Adicione `.vscode/sftp.json` ao `.gitignore`

**Arquivo `.gitignore`:**
```
.vscode/sftp.json
.env
```

---

## 📝 Checklist Final

- [ ] "name" → Identifica sua conexão
- [ ] "host" → IP correto do servidor
- [ ] "protocol" → SFTP (seguro)
- [ ] "port" → 22 (SFTP padrão)
- [ ] "username" → u857325944 (seu usuário)
- [ ] "password" → Sua senha real (⚠️ importante!)
- [ ] "remotePath" → /home/u857325944/public_html
- [ ] "uploadOnSave" → true (automático)
- [ ] "ignore" → Arquivos que não quer subir

---

## 🚀 Próximo Passo

1. **Preenchа "password"** com sua senha real
2. **Salve** o arquivo (Ctrl+S)
3. **Reinicie VS Code** (Ctrl+Shift+P → "Reload Window")
4. **Clique em SFTP** (barra lateral)
5. **Clique em Connect**
6. Se conectou ✓ → Pronto para usar!

---

## 💡 Dica Final

Sempre que editar um arquivo:
```
Edita → Salva (Ctrl+S) → ⚡ Sobe automático → Pronto!
```

**Sem mais uploads manuais! 🎉**
