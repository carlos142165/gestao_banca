# 🗑️ Como Desinstalar Extensões SFTP Desnecessárias

## Método Manual (Rápido)

### Passo 1: Abrir Extensions
- Pressione `Ctrl+Shift+X` no VS Code
- Ou clique no ícone de Extensions na barra lateral esquerda

### Passo 2: Procurar por cada uma

Na barra de busca, procure por cada extensão abaixo e clique em **Uninstall**:

#### ❌ Desinstale estas:

1. **SFTP/FTP sync** (Nativedisk)
   - Procure: `SFTP/FTP sync`
   - Clique em **Uninstall**

2. **VS Code SFTP** (Jowayeb)
   - Procure: `VS Code SFTP`
   - Clique em **Uninstall**

3. **SFTP CN** (codemile)
   - Procure: `SFTP CN`
   - Clique em **Uninstall**

4. **SFtp Snippet** (hello.niels.me)
   - Procure: `SFtp Snippet`
   - Clique em **Uninstall**

5. **PRO Deployer SFTP** (homerjing)
   - Procure: `PRO Deployer`
   - Clique em **Uninstall**

6. **SFTP 一键连接** (joy)
   - Procure: `一键连接`
   - Clique em **Uninstall**

7. **SFTP Tools** (cangyu)
   - Procure: `SFTP Tools`
   - Clique em **Uninstall**

8. **SFTP PS** (lindexi)
   - Procure: `SFTP PS`
   - Clique em **Uninstall**

9. **Sync SFTP** (Vadim Ricchov)
   - Procure: `Sync SFTP`
   - Clique em **Uninstall**

10. **EASY SFTP** (anikzin)
    - Procure: `EASY SFTP`
    - Clique em **Uninstall**

---

### Passo 3: Manter esta

✅ **SFTP** (Nativedisk)
- Esta é a que você vai manter!
- Procure para confirmar que está instalada
- Se tiver "Uninstall" é porque está instalada ✓

---

## ✅ Depois de Desinstalar Tudo

1. Reinicie o VS Code (Ctrl+Shift+P → "Reload Window")
2. Você verá apenas 1 extensão SFTP: **SFTP** (Nativedisk)
3. Pronto! Muito mais limpo!

---

## Próximo Passo

Após desinstalar, configure o arquivo `sftp-config.json` conforme o guia anterior:

**SETUP_SFTP_VSCODE.md**

---

## Se precisar desinstalar via Terminal

```powershell
# PowerShell
code --uninstall-extension Nativedisk.sftp-sync
code --uninstall-extension jowayeb.vscode-sftp
code --uninstall-extension codemile.sftp-cn
code --uninstall-extension hello.niels.me
code --uninstall-extension homerjing.pro-deployer
code --uninstall-extension joy.sftp
code --uninstall-extension cangyu.sftp-tools
code --uninstall-extension lindexi.sftp-ps
code --uninstall-extension vadim.ricchov.sftp
code --uninstall-extension anikzin.easy-sftp
```

Execute no PowerShell e todas serão desinstaladas! ⚡
