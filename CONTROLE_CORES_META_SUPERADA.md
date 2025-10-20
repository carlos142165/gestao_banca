# 🎨 Controle de Cores - Meta Superada

## 📋 Resumo
As cores dos valores extras (quando a meta é superada) agora são **totalmente controladas por variáveis CSS** no arquivo `css/estilo-painel-controle.css`.

## 🎯 Variáveis CSS Disponíveis

Todas as variáveis estão localizadas no `:root` do arquivo `css/estilo-painel-controle.css`:

### 📊 Cores Principais

```css
/* Cor do amarelo (Meta Batida/Superada) */
--valor-extra-cor-amarelo: #FFD700;

/* Cor dos ícones do troféu (Meta Batida) */
--valor-extra-cor-trofeu: #FFD700;

/* Cor do foguete (Meta Superada) */
--valor-extra-cor-foguete: #FF6B6B;

/* Cor do parabéns/celebração */
--valor-extra-cor-parabens: #FFD700;
```

## 🖌️ Como Alterar as Cores

### Exemplo 1: Mudar Amarelo para Outra Cor

Vá para `css/estilo-painel-controle.css` e procure pela seção `VALORES EXTRAS`:

```css
:root {
  /* Mude AQUI: */
  --valor-extra-cor-amarelo: #00FF00;  /* Verde em vez de amarelo */
  --valor-extra-cor-trofeu: #00FF00;   /* Troféu verde */
  --valor-extra-cor-parabens: #00FF00; /* Parabéns verde */
}
```

### Exemplo 2: Cores Diferentes para Cada Elemento

```css
:root {
  /* Amarelo para o texto do excedente */
  --valor-extra-cor-amarelo: #FFD700;
  
  /* Roxo para o troféu */
  --valor-extra-cor-trofeu: #9370DB;
  
  /* Vermelho para o foguete */
  --valor-extra-cor-foguete: #FF4444;
  
  /* Verde para parabéns */
  --valor-extra-cor-parabens: #00FF00;
}
```

## 📍 Onde as Cores São Usadas

### No Dashboard - Área de Resultados:

1. **Meta do Dia Superada**
   ```
   R$ 100,00 Superada! +R$ 50,00 🚀
   ↑ amarelo ↑            ↑ foguete ↑
   ```

2. **Meta Batida**
   ```
   ~~R$ 100,00~~ Meta Batida! 🏆
   ↑ amarelo ↑         ↑ troféu ↑
   ```

3. **Parabéns**
   ```
   🎉 Parabéns!
   ↑ parabéns ↑
   ```

### No Modal de Banca - Mesmos Padrões

As cores aplicadas ao dashboard se refletem automaticamente no modal de gerenciamento.

## 🔧 Integração com JavaScript

O JavaScript agora **lê as cores do CSS automaticamente** usando:

```javascript
var corAmarelo = getComputedStyle(document.documentElement)
  .getPropertyValue('--valor-extra-cor-amarelo').trim();
```

Também existe um **cache de cores** para melhor performance:

```javascript
window.coresMetaModal.amarelo   // Amarelo
window.coresMetaModal.trofeu    // Troféu
window.coresMetaModal.foguete   // Foguete
window.coresMetaModal.parabens  // Parabéns
```

## 🎨 Cores Recomendadas

### Tema Energético (Atual)
- Amarelo: `#FFD700`
- Troféu: `#FFD700`
- Foguete: `#FF6B6B`
- Parabéns: `#FFD700`

### Tema Verde (Alternativo)
- Amarelo: `#00FF00`
- Troféu: `#00FF00`
- Foguete: `#FF6B6B`
- Parabéns: `#00FF00`

### Tema Roxo (Luxo)
- Amarelo: `#9370DB`
- Troféu: `#9370DB`
- Foguete: `#FF1493`
- Parabéns: `#9370DB`

### Tema Azul (Corporativo)
- Amarelo: `#1E90FF`
- Troféu: `#1E90FF`
- Foguete: `#FF6347`
- Parabéns: `#1E90FF`

## 📝 Arquivos Modificados

1. **css/estilo-painel-controle.css**
   - Adicionadas 4 variáveis CSS para controlar as cores
   - Localizadas na seção `:root` com comentários

2. **js/script-painel-controle.js**
   - Substituídas cores hardcoded pela variáveis CSS
   - Adicionada função `obterCorCSS()` para facilitar acesso
   - Adicionado cache `coresMetaModal` para performance

## ✅ Checklist de Uso

- [ ] Abra `css/estilo-painel-controle.css`
- [ ] Localize a seção `VALORES EXTRAS (Meta Superada)`
- [ ] Altere as cores conforme desejado
- [ ] Salve o arquivo
- [ ] Atualize o navegador (Ctrl+F5 para limpar cache)
- [ ] Verifique se as cores foram aplicadas corretamente

## 🐛 Troubleshooting

### As cores não estão mudando?

1. Verifique se salvou o arquivo CSS
2. Limpe o cache do navegador (Ctrl+Shift+Delete)
3. Recarregue a página (Ctrl+F5)
4. Verifique se está editando o arquivo correto

### As cores estão diferentes em diferentes telas?

Certifique-se de que:
- Ambas as telas estão carregando o mesmo arquivo CSS
- Não existem estilos inline conflitantes
- O arquivo CSS foi salvo corretamente

## 🚀 Dicas Avançadas

### Adicionar Animação às Cores

No CSS, você pode adicionar transição:

```css
:root {
  /* Adicionar transição */
  --valor-extra-transition: color 0.5s ease;
}
```

### Usar Gradientes

```css
:root {
  /* Usar propriedade de background (requer ajustes no HTML) */
  --valor-extra-gradiente: linear-gradient(135deg, #FFD700, #FFA500);
}
```

## 📞 Suporte

Se precisar ajustar as cores novamente, basta:
1. Abrir `css/estilo-painel-controle.css`
2. Encontrar a seção `VALORES EXTRAS`
3. Modificar as variáveis desejadas
4. Salvar e recarregar a página

---

**Última atualização:** 19 de Outubro de 2025
**Versão:** 1.0
