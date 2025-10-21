# 📋 GUIA PASSO A PASSO - INCLUIR MODAL E JAVASCRIPT

## 🎯 Objetivo
Integrar o sistema de planos na página `gestao-diaria.php`

---

## ✅ PASSO 1: Abrir o Arquivo Principal

1. Em **VS Code**, abra: `gestao-diaria.php`
2. Vá até o **final do arquivo** (Use `Ctrl+End`)
3. Procure por: `</body>` (última linha)
4. Você verá algo assim:
   ```html
   </div>
   </body>
   </html>
   ```

---

## ✅ PASSO 2: Adicionar Duas Linhas ANTES de `</body>`

Adicione exatamente AQUI (entre `</div>` e `</body>`):

```php
<?php include 'modal-planos-pagamento.html'; ?>
<script src="js/plano-manager.js"></script>
```

**Exemplo completo:**
```html
                </div>
            </div>
        </div>
    </div>

    <!-- ✅ ADICIONE AQUI -->
    <?php include 'modal-planos-pagamento.html'; ?>
    <script src="js/plano-manager.js"></script>
    <!-- ✅ FIM -->

</body>
</html>
```

---

## ✅ PASSO 3: Adicionar Validação no JavaScript

Agora você precisa adicionar a validação que **bloqueia cadastro** se o usuário atingir limite.

Localizar o arquivo: `js/script-gestao-diaria.js`

### 3.1 - Procure pela função de CADASTRO DE MENTOR

Procure por: `function cadastrarMentor` ou `cadastrar mentor`

Você encontrará algo similar a:
```javascript
function cadastrarMentor() {
    // ... código do formulário ...
    // Enviar dados...
}
```

### 3.2 - ANTES do envio do formulário, adicione:

```javascript
// ✅ VALIDAR LIMITE ANTES DE CADASTRAR
const podeAvançar = await PlanoManager.verificarEExibirPlanos('mentor');
if (!podeAvançar) {
    return; // Para aqui e mostra o modal
}
```

**Exemplo completo:**
```javascript
function cadastrarMentor() {
    // ✅ VALIDAR LIMITE ANTES DE CADASTRAR
    const podeAvançar = await PlanoManager.verificarEExibirPlanos('mentor');
    if (!podeAvançar) {
        return; // Para aqui e mostra o modal
    }
    
    // ... resto do código original ...
    // Enviar dados para servidor...
}
```

---

## ✅ PASSO 4: Adicionar Validação para ENTRADAS

Procure por: `function adicionarEntrada` ou similar

Adicione a mesma validação:

```javascript
async function adicionarEntrada() {
    // ✅ VALIDAR LIMITE ANTES DE ADICIONAR ENTRADA
    const podeAvançar = await PlanoManager.verificarEExibirPlanos('entrada');
    if (!podeAvançar) {
        return; // Para aqui e mostra o modal
    }
    
    // ... resto do código original ...
}
```

---

## 📁 Estrutura de Arquivos Esperada

Seus arquivos devem estar assim:

```
gestao_banca/
├─ gestao-diaria.php          ← MODIFICAR (adicionar includes)
├─ modal-planos-pagamento.html ← DEVE EXISTIR
├─ config_mercadopago.php      ← DEVE EXISTIR
├─ config.php                  ← JÁ EXISTE
│
├─ 📁 js/
│  ├─ plano-manager.js         ← DEVE EXISTIR
│  ├─ script-gestao-diaria.js  ← MODIFICAR (adicionar validações)
│  └─ ... outros scripts
│
├─ 📁 ajax/ (ou similar)
│  ├─ obter-planos.php         ← DEVE EXISTIR
│  ├─ verificar-limite.php     ← DEVE EXISTIR
│  ├─ processar-pagamento.php  ← DEVE EXISTIR
│  └─ ... outros endpoints
│
└─ ... outros arquivos
```

---

## 🔍 VERIFICAÇÃO - Teste Rápido

### Teste 1: Modal carrega?
1. Abra `http://localhost/gestao_banca/gestao-diaria.php`
2. Abra **F12** (Console)
3. Se ver erro de `plano-manager.js not found`, é porque:
   - ❌ Arquivo não existe
   - ❌ Caminho errado
   - ❌ Arquivo não foi copiado

### Teste 2: Planos carregam?
1. Abra **F12 > Network**
2. Procure por requisição: `obter-planos.php`
3. Deve retornar JSON com 4 planos

### Teste 3: Validação funciona?
1. Tente cadastrar mentor com plano GRATUITO (após 1)
2. Deve abrir o modal automaticamente

---

## ⚠️ ERROS COMUNS E SOLUÇÕES

### Erro: "Cannot find module plano-manager.js"
**Solução:** Verifique se está no diretório `js/`
```
❌ Errado: <script src="plano-manager.js"></script>
✅ Correto: <script src="js/plano-manager.js"></script>
```

### Erro: "PlanoManager is not defined"
**Solução:** O arquivo `plano-manager.js` não carregou. Verifique:
1. Caminho está correto?
2. Arquivo existe?
3. Abra F12 > Network e procure por `plano-manager.js`

### Erro: "404 on obter-planos.php"
**Solução:** O arquivo PHP não existe. Crie em `ajax/obter-planos.php` ou ajuste o caminho no `plano-manager.js`

### Erro: "Syntax error in gestao-diaria.php"
**Solução:** Verifique as tags:
```
❌ Errado: <?php include modal-planos-pagamento.html; ?>
✅ Correto: <?php include 'modal-planos-pagamento.html'; ?>
```

---

## 📊 Resumo Visual

```
ANTES:
    </div>
</body>
</html>

DEPOIS:
    </div>
    
    <!-- ✅ INCLUI MODAL E JAVASCRIPT -->
    <?php include 'modal-planos-pagamento.html'; ?>
    <script src="js/plano-manager.js"></script>
    
</body>
</html>
```

---

## 🎬 Ordem de Execução Correta

1. ✅ Executar SQL (`db_schema_planos.sql`)
2. ✅ Configurar credenciais MP em `config_mercadopago.php`
3. ✅ **AGORA:** Incluir modal e JS em `gestao-diaria.php`
4. ✅ Adicionar validações em `script-gestao-diaria.js`
5. ✅ Testar em `teste-planos.php`

---

## 💡 DICAS

### Dica 1: Usar Busca e Substituição
Em VS Code, use **Ctrl+H** (Find and Replace):
- Procure por: `</body>`
- Substitua por:
  ```
  <?php include 'modal-planos-pagamento.html'; ?>
  <script src="js/plano-manager.js"></script>

  </body>
  ```

### Dica 2: Verificar Sintaxe
Após editar, use:
```bash
php -l gestao-diaria.php
```
Se retornar "No syntax errors", está OK!

### Dica 3: Testar em Etapas
1. Primeiro: Só incluir o HTML (sem JS)
2. Depois: Adicionar o JS
3. Depois: Adicionar validações

---

## 🆘 Precisa de Ajuda?

### Cenário 1: "Não consigo encontrar </body>"
- Use `Ctrl+End` para ir ao final
- Ou `Ctrl+F` e procure por `</body>`

### Cenário 2: "Tem múltiplos </body>"
- Adicione APENAS antes da ÚLTIMA `</body>`

### Cenário 3: "Não sei onde adicionar validação"
- Procure por `if (document.getElementById('form-adicionar-entrada'))`
- Adicione a validação ANTES desse `if`

---

## ✨ Próximo Passo

Após fazer isso, teste:
1. Abra http://localhost/gestao_banca/gestao-diaria.php
2. Abra F12 (Console)
3. Não deve ter erros em vermelho
4. Tente cadastrar mentor (deve abrir modal)

**Sucesso? Continue! 🎉**

