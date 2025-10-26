# 🔐 Área Administrativa

## 📋 Visão Geral

A **Área Administrativa** é um painel exclusivo para gerenciadores do site onde você pode visualizar estatísticas completas sobre os usuários, planos e assinaturas.

## 🎯 Como Acessar

1. **Acesse a Gestão de Banca** (gestao-diaria.php)
2. **Procure no menu superior** a opção **"Área Administrativa"** (com ícone de gráfico)
3. **Clique** para abrir o painel

> ⚠️ **IMPORTANTE**: A guia "Área Administrativa" **apenas aparece para o ID 23**. Para outros usuários, a guia fica oculta.

## 📊 Estatísticas Disponíveis

### Cards de Resumo Rápido
- 👥 **Total de Usuários** - Todos os usuários cadastrados
- 🎁 **Plano Gratuito** - Quantos usuários estão no plano gratuito
- ⭐ **Plano Prata** - Quantos usuários estão no plano prata
- 👑 **Plano Ouro** - Quantos usuários estão no plano ouro
- 💎 **Plano Diamante** - Quantos usuários estão no plano diamante
- 🌐 **Usuários Online** - Quantos usuários ativos nas últimas 24h
- 📅 **Assinaturas Anuais** - Total de assinaturas de 12 meses
- 📆 **Assinaturas Mensais** - Total de assinaturas de 1 mês
- 📈 **Taxa de Conversão** - Percentual de usuários pagantes

### Tabela de Resumo Geral
A tabela completa mostra todos os dados em um formato detalhado:
- Total de usuários por tipo de plano
- Usuários ativos
- Quantidade de assinaturas pagas
- Taxa de conversão

## 🎨 Design

- **Cards Interativos** - Cada card possui cor única e efeito hover
- **Animações Suaves** - Cards aparecem com animação fade-in
- **Responsivo** - Funciona em desktop, tablet e mobile
- **Atualização Automática** - A página se atualiza a cada 30 segundos

## 🔧 Configuração

### Adicionar Novos Admins

Se você deseja dar acesso à Área Administrativa para outro usuário:

**Em `gestao-diaria.php` (linha ~700):**
```php
<?php if (isset($_SESSION['usuario_id']) && $_SESSION['usuario_id'] === 23): ?>
```

**Mude para:**
```php
<?php if (isset($_SESSION['usuario_id']) && in_array($_SESSION['usuario_id'], [23, 15, 8])): ?>
```

Assim, os usuários com IDs 23, 15 e 8 terão acesso à Área Administrativa.

### Acessar Diretamente via URL

Você também pode acessar diretamente digitando na URL:
```
https://seusite.com/administrativa.php
```

Mas será redirecionado para home.php se não for admin (ID 23).

## 📁 Arquivo Único

Todo o código da Área Administrativa está em:
- `administrativa.php` - Contém todo PHP, CSS e JavaScript em um único arquivo

## 🔒 Segurança

- Apenas o ID 23 pode acessar a página
- Se alguém tenta acessar sem ser admin, é redirecionado
- Todas as queries estão protegidas contra SQL Injection

## 📈 Dados Rastreados

Os dados vêm de:
- Tabela `usuarios` - Total de usuários e planos
- Tabela `planos` - Informações dos planos
- Tabela `admin_logs` - Usuários ativos nas últimas 24h

## 🚀 Melhorias Futuras (Opcionais)

Se desejar melhorar a Área Administrativa no futuro:
- Adicionar gráficos de crescimento (Chart.js)
- Filtrar por data específica
- Exportar dados em PDF
- Gerenciamento de usuários
- Editar planos e preços
