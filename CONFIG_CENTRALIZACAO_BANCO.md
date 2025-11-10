# 🔧 Configuração Centralizada do Banco de Dados

## 📋 O que foi mudado?

Todo o projeto agora usa um **arquivo centralizado de configuração** (`config.php`) para gerenciar as credenciais e nome do banco de dados. Isso significa:

✅ **Uma única fonte de verdade** - Todas as configurações em um único lugar  
✅ **Fácil manutenção** - Mude o nome do banco em um único arquivo e todos os outros arquivos seguem automaticamente  
✅ **Segurança** - Facilita o gerenciamento de credenciais  
✅ **Compatibilidade** - Suporta MySQLi, PDO e funções auxiliares

---

## 📝 Como Usar

### 1️⃣ Modificar o Banco de Dados

Para **trocar o nome do banco de dados** ou **credenciais**, edite o arquivo `config.php`:

```php
define('DB_HOST', 'localhost');           // Endereço do servidor
define('DB_USERNAME', 'root');             // Usuário do banco
define('DB_PASSWORD', '');                 // Senha
define('DB_NAME', 'formulario-carlos');    // ← NOME DO BANCO (Modifique aqui!)
```

### 2️⃣ Usar em Seus Arquivos PHP

#### Opção A: Usar a conexão global (recomendado)

```php
<?php
require_once __DIR__ . '/config.php';

// A variável $conexao já está disponível
// Você pode usar normalmente:
$resultado = $conexao->query("SELECT * FROM usuarios");
```

#### Opção B: Usar constantes (para referências)

```php
<?php
require_once __DIR__ . '/config.php';

echo "Banco de dados: " . DB_NAME;
echo "Host: " . DB_HOST;
echo "Usuário: " . DB_USERNAME;
```

#### Opção C: Criar nova conexão (se necessário)

```php
<?php
require_once __DIR__ . '/config.php';

// Para MySQLi:
$novaConexao = getMySQLiConnection();

// Para PDO:
$pdoConexao = getPDOConnection();
```

---

## 📊 Arquivos Atualizados

Os seguintes arquivos foram **atualizados para usar a configuração centralizada**:

- ✅ `login-user.php`
- ✅ `excluir-entrada.php`
- ✅ `buscar_mentores.php`
- ✅ `recuperar_senha.php`
- ✅ `teste-basico.php`
- ✅ `migrar-banco.php`

---

## 🔄 Estrutura do config.php

### Constantes Definidas
```php
DB_HOST          // Endereço do servidor MySQL
DB_USERNAME      // Usuário do banco de dados
DB_PASSWORD      // Senha do banco de dados
DB_NAME          // Nome do banco de dados
```

### Variáveis Globais (Compatibilidade)
```php
$dbHost          // Igual a DB_HOST
$dbUsername      // Igual a DB_USERNAME
$dbPassword      // Igual a DB_PASSWORD
$dbname          // Igual a DB_NAME
$conexao         // Conexão MySQLi global já criada
```

### Funções Disponíveis
```php
getPDOConnection()      // Retorna nova conexão PDO
getMySQLiConnection()   // Retorna nova conexão MySQLi
```

---

## ⚡ Exemplos Práticos

### Exemplo 1: Fazer uma Query
```php
<?php
require_once __DIR__ . '/config.php';

$resultado = $conexao->query("SELECT * FROM usuarios");
while ($row = $resultado->fetch_assoc()) {
    echo $row['nome'];
}
```

### Exemplo 2: Usar em um Arquivo AJAX
```php
<?php
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

$stmt = $conexao->prepare("SELECT id, nome FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $_GET['id']);
$stmt->execute();

// ... resto do código
```

### Exemplo 3: Usar PDO
```php
<?php
require_once __DIR__ . '/config.php';

$pdo = getPDOConnection();
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
$stmt->execute([$_POST['email']]);
```

---

## 🎯 Checklist de Implementação

Se você tiver **outros arquivos** que precisam usar a configuração centralizada, siga este checklist:

- [ ] Remova as linhas de definição de `$dbHost`, `$dbUsername`, `$dbPassword`, `$dbname` do arquivo
- [ ] Remova a linha `new mysqli(...)` do arquivo
- [ ] Adicione `require_once __DIR__ . '/config.php';` no topo do arquivo
- [ ] Use `$conexao` diretamente (a conexão já está criada)

---

## 📌 Observações Importantes

1. **Use `require_once`** - Sempre use `require_once` para evitar múltiplas inclusões
2. **Use caminhos absolutos** - Use `__DIR__` para referências de arquivo relativas
3. **Tratamento de erro** - O `config.php` já trata erros de conexão automaticamente
4. **Charset UTF-8** - O charset é definido automaticamente como `utf8mb4`

---

## 🔐 Segurança

⚠️ **Nunca commite credenciais reais no Git!**

Para ambiente de produção, considere:
- Usar variáveis de ambiente
- Usar um arquivo `.env` (com a biblioteca `dotenv`)
- Colocar `config.php` em um nível acima da raiz web

Exemplo com variáveis de ambiente:
```php
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USERNAME', getenv('DB_USERNAME') ?: 'root');
define('DB_PASSWORD', getenv('DB_PASSWORD') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'formulario-carlos');
```

---

## ✅ Conclusão

Agora você tem um **sistema centralizado de configuração**! 

**Para mudar o banco de dados:** Edite apenas `config.php` e todos os outros arquivos seguem automaticamente! 🎉
