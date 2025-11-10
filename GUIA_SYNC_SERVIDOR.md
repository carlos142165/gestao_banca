# 🚀 Guia: Sincronizar Arquivos com Servidor Hostinger

## Opção 1: Git + GitHub (⭐ RECOMENDADO)

### Vantagens:
- ✅ Controle de versão completo
- ✅ Histórico de todas as mudanças
- ✅ Simples e profissional
- ✅ Gratuito
- ✅ Deploy automático opcional

### Como configurar:

#### Passo 1: Criar repositório no GitHub
1. Ir para https://github.com/new
2. Nome: `gestao_banca`
3. Descrição: "Sistema de gestão de banca de apostas"
4. Clicar em "Create repository"

#### Passo 2: Configurar Git no servidor Hostinger
Via painel Hostinger:
```bash
# SSH para o servidor
ssh seu_usuario@seu_dominio.com

# Navegar para a pasta
cd /home/seu_usuario/public_html/gestao_banca

# Inicializar git (se ainda não estiver)
git init
git remote add origin https://github.com/seu_usuario/gestao_banca.git
git branch -M main
git pull origin main
```

#### Passo 3: Fazer push local para GitHub
No seu computador (VS Code):
```bash
cd c:\xampp\htdocs\gestao\gestao_banca

# Adicionar todos os arquivos
git add .

# Commit com mensagem descritiva
git commit -m "Atualização do modal com cores e limpeza de nomes"

# Fazer push para GitHub
git push -u origin main
```

#### Passo 4: Puxar mudanças no servidor
Via SSH no servidor Hostinger:
```bash
cd /home/seu_usuario/public_html/gestao_banca
git pull origin main
```

**Resumo do fluxo:**
1. Edita localmente
2. `git commit` + `git push` (2 comandos)
3. No servidor: `git pull` (1 comando)

---

## Opção 2: FTP com Software (Bom)

### Softwares Recomendados:
- **FileZilla** (Gratuito) - https://filezilla-project.org/
- **WinSCP** (Gratuito) - https://winscp.net/
- **Cyberduck** (Gratuito) - https://cyberduck.io/

### Credenciais FTP (Hostinger):
- Servidor: Você encontra no painel da Hostinger
- Usuário: Geralmente seu domínio ou usuário FTP
- Senha: Gerada no painel
- Porta: 21 (ou 990 para FTPS)

### Workflow com FileZilla:
1. Conectar ao servidor via FTP
2. Arrastar arquivo da pasta local para remota
3. Pronto! (⚠️ Rápido mas sem versionamento)

---

## Opção 3: SFTP em VS Code (⭐ RECOMENDADO TAMBÉM)

### Extensão: SFTP
Instale: "SFTP" (Nativedisk)

### Arquivo de configuração (`sftp-config.json`):
```json
{
  "name": "Hostinger",
  "host": "seu_dominio.com",
  "protocol": "sftp",
  "port": 22,
  "username": "seu_usuario",
  "password": "sua_senha",
  "remotePath": "/home/seu_usuario/public_html/gestao_banca",
  "uploadOnSave": true,
  "syncMode": "local",
  "ignore": [".git", ".env", "node_modules"]
}
```

### Workflow:
1. Salvar arquivo (Ctrl+S)
2. Extensão faz upload automaticamente!
3. Super rápido e conveniente

---

## Opção 4: GitHub Actions (Deploy Automático)

Se usar Git + GitHub, pode configurar deploy automático:

### Arquivo `.github/workflows/deploy.yml`:
```yaml
name: Deploy para Hostinger

on:
  push:
    branches: [ main ]

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      
      - name: Fazer upload via FTP
        uses: SamKirkland/FTP-Deploy-Action@4.3.4
        with:
          server: ${{ secrets.FTP_SERVER }}
          username: ${{ secrets.FTP_USER }}
          password: ${{ secrets.FTP_PASSWORD }}
          local-dir: ./
          server-dir: /public_html/gestao_banca/
```

**Resultado:** Ao fazer `git push`, o código sobe automaticamente para o servidor! 🚀

---

## Comparação Rápida

| Método | Facilidade | Velocidade | Segurança | Versionamento | Automático |
|--------|-----------|-----------|-----------|---------------|-----------|
| Git | ⭐⭐⭐ | ⭐⭐ | ⭐⭐⭐ | ✅ | Sim (com Actions) |
| FTP | ⭐⭐ | ⭐⭐⭐ | ⭐ | ❌ | Não |
| SFTP VS Code | ⭐⭐⭐⭐ | ⭐⭐⭐ | ⭐⭐ | ❌ | Sim (ao salvar) |
| GitHub Actions | ⭐⭐⭐ | ⭐⭐⭐ | ⭐⭐⭐ | ✅ | Sim (ao push) |

---

## 🏆 Recomendação Final

### Para máxima produtividade:
**Git + GitHub Actions**
1. Edita e faz commit/push localmente
2. GitHub Actions faz deploy automático
3. Sem precisar acessar servidor
4. Histórico completo de mudanças

### Para simplicidade imediata:
**SFTP em VS Code**
1. Instala extensão SFTP
2. Configura credenciais FTP
3. Salva arquivo = upload automático
4. Pronto em minutos

---

## Comandos Git Essenciais

```bash
# Status dos arquivos modificados
git status

# Ver último commit
git log --oneline -5

# Ver diferenças do último commit
git diff

# Desfazer última mudança (antes de commit)
git restore arquivo.js

# Desfazer último commit (mantém mudanças)
git reset --soft HEAD~1

# Sincronizar com servidor remoto
git pull origin main
```

---

## Credenciais Hostinger

Para encontrar credenciais FTP/SFTP:
1. Painel Hostinger → Conta
2. Gerenciador de Arquivos ou FTP
3. Clique em "Conectar via FTP"
4. Você verá host, usuário, senha

**Segurança:** 
- ❌ Não compartilhe senhas em texto
- ✅ Use arquivo `.env` localmente
- ✅ Use secrets no GitHub

---

## Próximos Passos

1. **Qual método você prefere?** (Git, SFTP, ou FTP)
2. **Você já tem GitHub?** (Se não, crie em 2 minutos)
3. **Quer que eu te ajude a configurar?** (Avise qual escolheu)
