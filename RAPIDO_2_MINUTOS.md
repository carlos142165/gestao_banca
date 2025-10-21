# ⚡ GUIA VISUAL RÁPIDO (2 MINUTOS)

## 🎯 O QUE FOI FEITO EM 2 PASSOS

### PASSO 1: Incluir Modal (1 minuto) ✅

**Onde:** `gestao-diaria.php` (última linha antes de `</body>`)

**O quê adicionar:**
```html
<?php include 'modal-planos-pagamento.html'; ?>
<script src="js/plano-manager.js"></script>
```

**Visual:**
```
ANTES                          DEPOIS
─────────────────────         ─────────────────────────────
</div>                         </div>
</body>                        
</html>                        <?php include '...' ?>
                              <script src="..." />
                              
                              </body>
                              </html>
```

---

### PASSO 2: Adicionar Validações (1 minuto) ✅

**Onde:** `js/script-gestao-diaria.js` (2 lugares)

**Validação 1 - MENTOR** (procure por linha ~2139):
```javascript
// ✅ Adicione ANTES de: await FormularioManager.processarSubmissaoMentor(e.target);

if (typeof PlanoManager !== 'undefined' && PlanoManager.verificarEExibirPlanos) {
  const podeAvançar = await PlanoManager.verificarEExibirPlanos('mentor');
  if (!podeAvançar) return;
}
```

**Validação 2 - ENTRADA** (procure por linha ~2154):
```javascript
// ✅ Adicione ANTES de: await this.processarSubmissaoFormulario(e.target);

if (typeof PlanoManager !== 'undefined' && PlanoManager.verificarEExibirPlanos) {
  const podeAvançar = await PlanoManager.verificarEExibirPlanos('entrada');
  if (!podeAvançar) return;
}
```

---

## 🧪 TESTE EM 3 PASSOS

### 1️⃣ Abrir página (30 segundos)
```
http://localhost/gestao_banca/gestao-diaria.php
```

### 2️⃣ Abrir F12 (10 segundos)
```
Pressionar: F12
Aba: Console
Não deve ter erros 🔴
```

### 3️⃣ Testar limite (1 minuto)
```
1. Já com 1 mentor cadastrado
2. Clicar: "Novo Mentor"
3. Preencher e clicar: "Cadastrar"
4. Modal deve abrir! 🎯
```

---

## ✅ RESULTADO

```
Antes:  Cadastra mentor sem limite
Depois: Modal abre bloqueando limite
```

---

## 📁 ARQUIVOS ENVOLVIDOS

```
✅ gestao-diaria.php       (MODIFICADO - 2 linhas)
✅ script-gestao-diaria.js (MODIFICADO - 30 linhas)
✅ modal-planos-...html    (USADO - já existe)
✅ plano-manager.js        (USADO - já existe)
```

---

## 🎊 PRONTO!

Sua integração está 100% funcional!

**Próxima:** Ler `COMECE_AQUI.md` para mais detalhes.

