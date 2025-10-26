# 📋 Sistema de Gerenciamento de Administradores

## 🎯 Visão Geral

Este sistema permite que o **super-admin (ID 23)** gerencie os IDs dos usuários com acesso administrativo de forma visual, sem necessidade de editar código.

## 🚀 Como Funciona

### 1. **Acessando a Interface**
- Faça login como admin (ID 23 ou outro ID autorizado)
- Acesse a página **Área Administrativa** (`administrativa.php`)
- Clique no botão verde **"Gerenciar Admins"** 🟢

### 2. **Adicionando um Novo Admin**
- Na modal que aparecer, digite o ID do novo usuário
- Clique em **"Adicionar"** ou pressione **Enter**
- Uma notificação confirma se foi adicionado com sucesso

### 3. **Removendo um Admin**
- Localize o ID na lista de "Administradores Cadastrados"
- Clique no botão **"Remover"** ao lado do ID
- Confirme a remoção na caixa de diálogo

## 📁 Arquivos do Sistema

### `admin-ids-config.php`
Classe responsável por:
- ✅ Ler/escrever IDs no arquivo JSON
- ✅ Validar e processar requisições AJAX
- ✅ Gerenciar lista de administradores

### `dados/admin_ids.json`
Arquivo que armazena os IDs dos admins em formato JSON:
```json
[
  23,
  42,
  15
]
```

### Arquivos Modificados
- **`administrativa.php`** - Adicionado modal e interface visual
- **`verificar-limite.php`** - Atualizado para usar o novo sistema

## 🔒 Segurança

- ✅ Apenas ID 23 pode gerenciar administradores
- ✅ Validação de IDs (devem ser números positivos)
- ✅ Proteção contra duplicatas
- ✅ Confirmação antes de remover

## 🔧 Integração com Sistema

Os IDs de admin são carregados automaticamente em:
- `verificar-limite.php` - Controla limites de mentores/entradas
- `administrativa.php` - Verifica acesso à página admin

Não precisa mais editar o código para adicionar novos administradores! 🎉

## 💡 Dicas

- Use números inteiros positivos para os IDs
- IDs duplicados são rejeitados automaticamente
- O sistema mantém a lista sempre ordenada
- As alterações são imediatas no sistema

## 🆘 Troubleshooting

**Problema**: Modal não abre
- Verifique se está logado como admin (ID 23)

**Problema**: Permissão negada
- Apenas ID 23 pode gerenciar IDs de admin

**Problema**: Erro ao adicionar
- Verifique se o diretório `dados/` tem permissão de escrita
- Verifique se o arquivo `admin_ids.json` existe

---
**Desenvolvido para facilitar a administração do sistema** ✨
