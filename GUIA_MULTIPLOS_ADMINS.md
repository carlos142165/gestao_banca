# 🔓 MÚLTIPLOS ADMINS - GUIA COMPLETO

## 📌 O QUE FOI MUDADO

Agora em vez de um **ID único**, você pode adicionar **múltiplos IDs** que terão acesso ilimitado!

---

## 🎯 COMO FUNCIONA

### Antes (Um único admin)
```php
define('ADMIN_USER_ID', 23); // Apenas ID 23
```

### Agora (Múltiplos admins)
```php
define('ADMIN_USER_IDS', [
    23,    // ID 1 com acesso ilimitado
    15,    // ID 2 com acesso ilimitado
    8,     // ID 3 com acesso ilimitado
    45,    // ID 4 com acesso ilimitado
]);
```

---

## 🚀 COMO ADICIONAR NOVOS ADMINS

### PASSO 1: Abrir `verificar-limite.php`

Localize as linhas 11-20:

```php
define('ADMIN_USER_IDS', [
    23,    // 👈 ID principal (você) - ACESSO ILIMITADO
    // 15,  // 👈 Descomente para adicionar outro usuário com acesso ilimitado
    // 8,   // 👈 Descomente para adicionar outro usuário com acesso ilimitado
    // 45,  // 👈 Adicione quantos IDs quiser neste formato
]);
```

### PASSO 2: Adicionar o novo ID

Digamos que você quer dar acesso ilimitado ao usuário com ID **15**. Faça assim:

**De:**
```php
define('ADMIN_USER_IDS', [
    23,    // Você
    // 15,  // ← Comentado
    // 8,
    // 45,
]);
```

**Para:**
```php
define('ADMIN_USER_IDS', [
    23,    // Você
    15,    // ← Descomente removendo //
    // 8,
    // 45,
]);
```

### PASSO 3: Salvar (Ctrl+S)

Pronto! ✅ Agora o usuário ID 15 tem acesso ilimitado.

---

## 📋 EXEMPLOS PRÁTICOS

### Exemplo 1: Apenas você (ID 23)
```php
define('ADMIN_USER_IDS', [
    23,
]);
```

### Exemplo 2: Você + outro usuário (ID 15)
```php
define('ADMIN_USER_IDS', [
    23,
    15,
]);
```

### Exemplo 3: Você + 3 usuários
```php
define('ADMIN_USER_IDS', [
    23,   // Você
    15,   // Seu assistente
    8,    // Gerente
    45,   // Supervisor
]);
```

### Exemplo 4: Muitos usuários
```php
define('ADMIN_USER_IDS', [
    23, 15, 8, 45, 99, 102, 156, 203, 500,
]);
```

---

## 🔄 COMO FUNCIONA A VERIFICAÇÃO

Quando um usuário tenta adicionar mentor ou entrada:

```
1️⃣ Usuário ID 15 tenta adicionar mentor
        ↓
2️⃣ JavaScript chama verificar-limite.php
        ↓
3️⃣ PHP verifica:
   in_array(15, [23, 15, 8, 45]) ?
        ↓
   SIM ✅ (ID 15 está na lista)
        ↓
4️⃣ Retorna: pode_prosseguir = true
        ↓
5️⃣ Mentor adicionado SEM LIMITE ✅
```

---

## 🎯 CASOS DE USO

### ✅ Caso 1: Você + Sócio
```php
define('ADMIN_USER_IDS', [
    23,   // Você
    12,   // Seu sócio
]);
```

### ✅ Caso 2: Você + Gerente + Assistente
```php
define('ADMIN_USER_IDS', [
    23,   // Você
    7,    // Gerente
    19,   // Assistente
]);
```

### ✅ Caso 3: Apenas você
```php
define('ADMIN_USER_IDS', [
    23,
]);
```

### ✅ Caso 4: Todos da sua equipe
```php
define('ADMIN_USER_IDS', [
    23, 7, 12, 19, 31, 44, 55, 68, 77,
]);
```

---

## ⚡ DICAS ÚTEIS

### 💡 Dica 1: Como descobrir o ID de um usuário?

Abra phpMyAdmin e execute:
```sql
SELECT id, nome, email FROM usuarios;
```

### 💡 Dica 2: Adicionar com comentário
```php
define('ADMIN_USER_IDS', [
    23,   // Carlos (você) - acesso total
    15,   // João (sócio) - acesso total
    8,    // Maria (gerente) - acesso total
    45,   // Pedro (supervisor) - acesso total
]);
```

### 💡 Dica 3: Copiar e colar rápido
```php
// IDs com acesso ilimitado (admin, sócios, gerentes)
define('ADMIN_USER_IDS', [23, 15, 8, 45]);
```

---

## 🔐 SEGURANÇA

### ✅ Como funciona a proteção

1. **Apenas IDs da lista têm acesso**
   - ID 50 tentou acessar? ❌ Bloqueado
   - ID 23 tentou acessar? ✅ Permitido

2. **Outros usuários continuam com limites**
   - Usuário ID 30 continua com limite de plano
   - Usuário ID 40 continua com limite de plano

3. **Tudo é registrado em logs**
   - Cada ação é rastreada
   - Quem fez o quê e quando

---

## 🧪 COMO TESTAR

### Teste 1: Seu acesso (ID 23)
```
1. Faça login como ID 23
2. Vá para gestao-diaria.php
3. Tente adicionar mentor
4. Resultado: ✅ Deve deixar adicionar ilimitado
```

### Teste 2: Outro admin (ID 15)
```
1. Faça logout
2. Faça login como ID 15
3. Vá para gestao-diaria.php
4. Tente adicionar mentor
5. Resultado: ✅ Deve deixar adicionar ilimitado
```

### Teste 3: Usuário normal (ID 50)
```
1. Faça logout
2. Faça login como ID 50
3. Vá para gestao-diaria.php
4. Tente adicionar mentor
5. Resultado: ❌ Deve mostrar limite de plano
```

---

## 📊 JSON RETORNADO

Quando um admin tenta acessar:

```json
{
  "success": true,
  "pode_prosseguir": true,
  "plano_atual": "ADMIN - Ilimitado",
  "mensagem": "",
  "admin_mode": true,
  "user_id": 23
}
```

Quando um usuário normal tenta acessar (com limite):

```json
{
  "success": true,
  "pode_prosseguir": false,
  "plano_atual": "Plano Básico",
  "mensagem": "Você atingiu o limite de mentores no plano Plano Básico. Faça upgrade!"
}
```

---

## 🔄 COMO REMOVER UM ADMIN

### Se você quer remover acesso ilimitado de um usuário:

**De:**
```php
define('ADMIN_USER_IDS', [
    23,
    15,   // ← Remove este
    8,
    45,
]);
```

**Para:**
```php
define('ADMIN_USER_IDS', [
    23,
    // 15,   // ← Comentado (removido)
    8,
    45,
]);
```

Ou delete a linha:

```php
define('ADMIN_USER_IDS', [
    23,
    8,
    45,
]);
```

---

## 🎯 RESUMO RÁPIDO

### Adicionar 1 admin:
```php
define('ADMIN_USER_IDS', [23]);
```

### Adicionar 2 admins:
```php
define('ADMIN_USER_IDS', [23, 15]);
```

### Adicionar 5 admins:
```php
define('ADMIN_USER_IDS', [23, 15, 8, 45, 99]);
```

### Adicionar muitos admins:
```php
define('ADMIN_USER_IDS', [23, 15, 8, 45, 99, 102, 156, 203, 500, 1000]);
```

---

## ❓ FAQ

### P: Posso adicionar o mesmo ID duas vezes?
**R:** Sim, mas não fará diferença:
```php
define('ADMIN_USER_IDS', [23, 23, 23]); // Mesma coisa que [23]
```

### P: Qual é o limite de IDs?
**R:** Não há limite. Adicione quantos quiser:
```php
define('ADMIN_USER_IDS', [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, ..., 1000]);
```

### P: Se eu errar o ID do admin, o que acontece?
**R:** Nada de grave. Apenas esse ID não terá acesso ilimitado. Corrija e salve de novo.

### P: Como remover todos os admins?
**R:** Deixe o array vazio (não recomendado):
```php
define('ADMIN_USER_IDS', []); // Nenhum admin - todos com limite
```

---

## ✨ PRONTO!

Agora você pode:

✅ Adicionar múltiplos IDs de admin  
✅ Dar acesso ilimitado a quantas pessoas quiser  
✅ Remover acesso alterando a lista  
✅ Testar facilmente  
✅ Manter segurança dos outros usuários  

**Bom uso! 🚀**
