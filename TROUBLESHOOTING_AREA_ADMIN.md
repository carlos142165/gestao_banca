# 🔧 TROUBLESHOOTING - Guia de Banca Administrativa

## ❓ A Guia "Área Administrativa" Não Aparece?

Siga os passos abaixo:

---

## PASSO 1: Verificar seu ID

Acesse: `http://localhost/gestao/gestao_banca/teste-id.php`

Isso vai mostrar:

- ✅ Se você está logado
- ✅ Qual é seu ID
- ✅ Se o ID é 23 ou não

---

## PASSO 2: Verificar o Menu

### Importante: O menu é RESPONSIVO!

A guia só aparece quando:

1. ✅ Você clica no botão **☰** (hambúrguer)
2. ✅ O menu dropdown abre
3. ✅ A guia aparece lá

**NÃO é uma guia sempre visível!** É um menu dropdown.

---

## PASSO 3: Passo-a-Passo para Ver a Guia

### No Desktop:

1. Clique no botão **☰** (canto superior esquerdo)
2. Um menu vai abrir
3. Procure por **"📊 Área Administrativa"** (com fundo amarelo)
4. Clique nela

### No Mobile:

1. Clique no botão **☰**
2. Menu abre
3. Procure por **"📊 Área Administrativa"**
4. Clique

---

## ✅ CHECKLIST

Verifique se:

- [ ] Você está logado com ID **23**
- [ ] Clicou no botão **☰** para abrir o menu
- [ ] Procurou a guia com fundo **amarelo**
- [ ] Ela tem o emoji **📊** na frente
- [ ] Ela tem o texto **"Área Administrativa"**

---

## 🎯 Cenários Comuns

### Cenário 1: Menu fechado

**Problema:** Você não vê nenhuma guia
**Solução:** Clique no botão ☰ para abrir o menu

### Cenário 2: ID errado

**Problema:** Você vê o menu, mas sem "Área Administrativa"
**Solução:** Faça logout e login com ID 23

### Cenário 3: Guia com fundo amarelo

**Problema:** Vê a guia mas não consegue clicar
**Solução:** Clique no link "📊 Área Administrativa"

---

## 🔍 Debug

Se ainda não funcionar:

1. Abra o Console do Navegador: **F12**
2. Vá na aba **Console**
3. Procure por erros vermelhos
4. Screenshot e me envie

---

## 📱 Responsividade

A guia aparece em:

- ✅ Desktop (1200px+)
- ✅ Tablet (768px - 1199px)
- ✅ Mobile (até 767px)

Todos têm o menu com botão **☰**

---

## 💡 Dica

Olhe para o **fundo amarelo** da guia "Área Administrativa":

```
┌────────────────────────────────┐
│ Home                           │
│ Gestão de Banca               │
│ Gerenciar Banca               │
│ Bot ao Vivo                   │
│ 📊 Área Administrativa 🟨     ← Fundo amarelo
│ Minha Conta                   │
│ Sair                          │
└────────────────────────────────┘
```

Essa cor amarela facilita encontrá-la!

---

## 🆘 Se Ainda Não Funcionar

1. Acesse `teste-id.php` e confirme seu ID
2. Clique em ☰ para abrir o menu
3. Se ainda não aparecer, me avise com:
   - Seu ID (de teste-id.php)
   - Screenshot do menu aberto
   - Qual navegador está usando
