# 🚀 INSTRUÇÕES DE INSTALAÇÃO E USO
## Sistema de Gerenciamento de Administradores

---

## ✅ O QUE FOI IMPLEMENTADO

Você agora pode **gerenciar os IDs dos administradores de forma visual**, sem necessidade de editar o código!

### Arquivos Criados:
1. ✨ **`admin-ids-config.php`** - Classe que gerencia os IDs
2. ✨ **`dados/admin_ids.json`** - Arquivo que armazena os IDs
3. 📄 **`ADMIN_IDS_README.md`** - Documentação completa
4. 🎨 **`ADMIN_IDS_GUIA_VISUAL.html`** - Guia visual interativo
5. 🧪 **`TESTE_ADMIN_IDS.html`** - Página de testes

### Arquivos Modificados:
1. 🔄 **`administrativa.php`** - Adicionado modal e botão "Gerenciar Admins"
2. 🔄 **`verificar-limite.php`** - Atualizado para usar o novo sistema

---

## 🎯 COMO USAR

### Passo 1: Acesse a Área Administrativa
```
URL: http://localhost/gestao/gestao_banca/administrativa.php
Login: Use um usuário com ID 23 (ou outro admin autorizado)
```

### Passo 2: Procure o Botão Verde
```
Você verá um botão verde no topo da página:
[ 👤 Gerenciar Admins ]
```

### Passo 3: Abra o Modal
- Clique no botão "Gerenciar Admins"
- Uma modal elegante aparecerá

### Passo 4: Adicione ou Remova IDs
```
✅ ADICIONAR:
  1. Digite o ID do novo admin
  2. Clique em "Adicionar" ou pressione Enter
  3. Pronto! O ID foi adicionado

🗑️ REMOVER:
  1. Procure o ID na lista
  2. Clique no botão "Remover"
  3. Confirme a exclusão
  4. Pronto! O ID foi removido
```

---

## 🔒 SEGURANÇA

✅ **Apenas ID 23 pode gerenciar admins**
- Outras contas não veem o botão "Gerenciar Admins"
- A API valida a permissão no servidor

✅ **Validações Automáticas**
- IDs duplicados são rejeitados
- Apenas números positivos são aceitos
- Confirmação antes de remover

✅ **Arquivo JSON Protegido**
- Armazenado em pasta privada (`dados/`)
- Validação de acesso server-side

---

## 📁 ESTRUTURA DE ARQUIVOS

```
gestao_banca/
├── admin-ids-config.php           ← NOVO: Gerenciador de IDs
├── administrativa.php              ← MODIFICADO: Com modal
├── verificar-limite.php            ← MODIFICADO: Usa novo sistema
├── dados/
│   └── admin_ids.json             ← NOVO: Armazena IDs
├── ADMIN_IDS_README.md             ← Documentação (Markdown)
├── ADMIN_IDS_GUIA_VISUAL.html     ← Guia Visual (HTML)
└── TESTE_ADMIN_IDS.html           ← Página de Testes
```

---

## 📋 IDs DE ADMIN PADRÃO

O sistema começa com os seguintes IDs como admins:
- `23` → CARLOS
- `42` → ALANNES

Você pode adicionar mais IDs através da interface!

---

## 🧪 TESTAR A INSTALAÇÃO

### Teste Online (Recomendado)
```
Acesse: http://localhost/gestao/gestao_banca/TESTE_ADMIN_IDS.html
Clique em: "Testar Tudo"
```

### Teste Manual

#### 1️⃣ Verificar se o arquivo JSON existe
```
Deve existir: c:\xampp\htdocs\gestao\gestao_banca\dados\admin_ids.json
Conteúdo deve ser: [23, 42]
```

#### 2️⃣ Verificar permissões
```
A pasta dados/ deve ter permissão de escrita
Se tiver erro, execute: chmod 755 dados/
```

#### 3️⃣ Testar a modal
```
1. Faça login como ID 23
2. Vá para: administrativa.php
3. Procure o botão "Gerenciar Admins"
4. Clique e a modal deve aparecer
```

---

## 💻 USAR EM OUTROS ARQUIVOS PHP

Se você quiser verificar se um usuário é admin em outros arquivos:

```php
<?php
require_once 'admin-ids-config.php';

$id_usuario = $_SESSION['usuario_id'];

if (AdminIdManager::ehAdmin($id_usuario)) {
    echo "Usuário é administrador!";
    // Permitir acesso especial
} else {
    echo "Usuário comum";
    // Acesso restrito
}
?>
```

### Obter lista de todos os admins

```php
<?php
require_once 'admin-ids-config.php';

$ids_admin = AdminIdManager::obterAdminIds();
echo "Total de admins: " . count($ids_admin);
echo "IDs: " . implode(", ", $ids_admin);
?>
```

---

## 🆘 TROUBLESHOOTING

### ❓ O botão "Gerenciar Admins" não aparece
**Solução:**
- Verifique se você está logado como ID 23
- Atualize a página (Ctrl+F5)
- Verifique o console do navegador (F12) para erros

### ❓ "Permissão negada" ao tentar adicionar ID
**Solução:**
- Apenas ID 23 pode gerenciar
- Use uma conta com ID 23

### ❓ Erro ao salvar IDs
**Solução:**
- Verifique se o diretório `dados/` existe
- Se não existir, crie manualmente
- Verifique permissões: `chmod 755 dados/`

### ❓ Modal não abre
**Solução:**
- Verifique se há erros no console (F12)
- Limpe o cache (Ctrl+Shift+Delete)
- Recarregue a página

### ❓ API retorna erro 404
**Solução:**
- Verifique se `admin-ids-config.php` existe
- Verifique o caminho da URL
- Verifique se o arquivo tem permissão de leitura

---

## 📝 FORMATO DO ARQUIVO JSON

Arquivo: `dados/admin_ids.json`

```json
[
  23,
  42,
  15,
  8,
  100
]
```

- Sempre um array de números
- Sem duplicatas
- Mantido em ordem crescente
- Salvo automaticamente

---

## 🎨 CUSTOMIZAÇÃO

### Mudar cores do modal

Edite o arquivo `administrativa.php` e procure por:
```css
:root {
    --cor-principal: #667eea;
    --cor-secundaria: #764ba2;
    /* ... mais cores ... */
}
```

### Mudar apenas o ID do super-admin

Edite `admin-ids-config.php` e procure por:
```php
if ($id_usuario !== 23) {
    // Mudar 23 para outro ID se desejado
}
```

---

## 📊 COMO FUNCIONA INTERNAMENTE

```
Usuário clica em "Gerenciar Admins"
                    ↓
Modal aparece com interface
                    ↓
JavaScript faz requisição AJAX para admin-ids-config.php
                    ↓
PHP valida se é ID 23
                    ↓
Se OK: Lê admin_ids.json
                    ↓
Retorna JSON com lista de IDs
                    ↓
JavaScript renderiza lista na modal
                    ↓
Usuário adiciona/remove IDs
                    ↓
JavaScript envia requisição para admin-ids-config.php
                    ↓
PHP valida, modifica admin_ids.json
                    ↓
Retorna confirmação
                    ↓
JavaScript mostra notificação (toast)
```

---

## 🎯 PRÓXIMOS PASSOS

Agora você pode:

1. ✅ Adicionar novos administradores sem editar código
2. ✅ Remover administradores com um clique
3. ✅ Ver lista de todos os admins
4. ✅ Usar em qualquer arquivo PHP com `AdminIdManager::ehAdmin($id)`

---

## 📞 SUPORTE

Se encontrar algum problema:

1. Verifique o console do navegador (F12)
2. Verifique o arquivo de log do servidor
3. Confirme que os arquivos foram criados corretamente
4. Verifique as permissões das pastas

---

## ✨ RESUMO

| Funcionalidade | Status |
|---|---|
| Adicionar admins | ✅ Funcionando |
| Remover admins | ✅ Funcionando |
| Validação de IDs | ✅ Funcionando |
| Proteção contra duplicatas | ✅ Funcionando |
| Interface visual | ✅ Funcionando |
| Notificações | ✅ Funcionando |
| Segurança | ✅ Implementada |
| Responsividade | ✅ Implementada |

---

**Sistema implementado com sucesso! 🎉**

Obrigado por usar o gerenciador de administradores!
