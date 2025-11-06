# 🎠 Carousel Responsivo para Blocos

## 📋 Descrição

Implementação de um carousel/slider responsivo para as páginas **gestao-diaria.php** e **bot_aovivo.php** que permite:

- ✅ **Desktop (≥1025px)**: Layout normal com 3 blocos lado a lado
- ✅ **Mobile/Tablet (≤1024px)**: Carousel deslizável com um bloco por tela
- ✅ **Indicadores visuais**: Pontos na base indicando qual bloco está ativo
- ✅ **Navegação**: Swipe, clique nos pontos, teclado (setas)
- ✅ **Smooth scrolling**: Deslizamento suave e automático

## 📁 Arquivos Adicionados/Modificados

### 1. **CSS: `css/carousel-blocos.css`**
Arquivo de estilos completo com:
- Media queries para diferentes tamanhos de tela
- Indicadores com animações
- Barra de progresso
- Responsividade total

### 2. **JavaScript: `js/carousel-blocos.js`**
Script responsável por:
- Detectar tamanho da tela (mobile/desktop)
- Controlar scroll horizontal
- Atualizar indicadores
- Gestionar eventos de toque (swipe)
- Navegação por teclado

### 3. **Páginas Modificadas**:
- `gestao-diaria.php` - Adicionado CSS e JS do carousel
- `bot_aovivo.php` - Adicionado CSS e JS do carousel

## 🎯 Como Funciona

### No Desktop (≥1025px)
```
┌─────────┬─────────┬─────────┐
│ Bloco 1 │ Bloco 2 │ Bloco 3 │
│   420px │  420px  │  420px  │
└─────────┴─────────┴─────────┘
```

### No Mobile (≤1024px)
```
Bloco 1           Bloco 2           Bloco 3
┌──────────────┐  ┌──────────────┐  ┌──────────────┐
│              │  │              │  │              │
│   100vw      │  │   100vw      │  │   100vw      │
│              │  │              │  │              │
└──────────────┘  └──────────────┘  └──────────────┘
← swipe / scroll →

[●] [○] [○]  ← Indicadores (bottom)
```

## 🎮 Interações

### 1. **Swipe (Toque)**
```javascript
- Swipe para esquerda → Próximo bloco
- Swipe para direita ← Bloco anterior
```

### 2. **Clique nos Pontos**
```javascript
Clique em qualquer ponto indicador para ir direto àquele bloco
```

### 3. **Teclado**
```javascript
- Seta direita (→) → Próximo bloco
- Seta esquerda (←) ← Bloco anterior
```

### 4. **Scroll Manual**
```javascript
Scroll horizontal automático com snap para cada bloco
```

## 🎨 Indicadores Visuais

### Estados dos Pontos

| Estado | Aparência | Animação |
|--------|-----------|----------|
| **Inativo** | Cinza pequeno | Sem animação |
| **Ativo** | Verde + Pulso | Pulse infinito |
| **Hover** | Ampliado | Scale 1.15 |

### Cores
- **Ponto inativo**: `rgba(255, 255, 255, 0.4)`
- **Ponto ativo**: Gradiente verde `#4CAF50 → #8BC34A`
- **Sombra**: Glow verde `rgba(76, 175, 80, 0.6)`

## 📐 Breakpoints

```css
Desktop:     ≥ 1025px  → Layout normal 3 blocos
Tablet:      ≤ 1024px  → Carousel 1 bloco
Mobile:      ≤ 768px   → Carousel otimizado
Small:       ≤ 480px   → Indicadores compactados
XSmall:      ≤ 360px   → Ultra compactado
```

## ⚙️ Propriedades JavaScript

```javascript
CarouselBlocos = {
  currentBloco: 0,           // Bloco atual (0-2)
  totalBlocos: 3,            // Total de blocos
  isMobile: false,           // Flag mobile detection
  mainContent: null,         // Referência ao .main-content
  container: null,           // Referência ao .container
  blocos: null,              // NodeList dos .bloco
  indicators: null,          // NodeList dos .carousel-dot
  progressBar: null,         // Barra de progresso
  isScrolling: false,        // Flag de scroll ativo
  touchStartX: 0,            // Coordenada X inicial do toque
  touchEndX: 0               // Coordenada X final do toque
}
```

## 🔧 Métodos Disponíveis

```javascript
// Navegar para bloco específico
CarouselBlocos.scrollToBloco(blocoIndex)

// Próximo bloco
CarouselBlocos.nextBloco()

// Bloco anterior
CarouselBlocos.prevBloco()

// Atualizar indicadores
CarouselBlocos.updateIndicators()

// Verificar se está em mobile
CarouselBlocos.checkIsMobile()
```

## 📱 Teste em Mobile

### Chrome DevTools
1. Abrir DevTools (F12)
2. Clicar no ícone de dispositivo mobile
3. Selecionar um dispositivo ou definir tamanho customizado
4. Testar swipe e scroll

### Teste Real
1. Abrir a página em um telefone/tablet
2. Fazer swipe horizontal para navegar
3. Tocar nos pontos indicadores
4. Testar redimensionamento

## 🐛 Debug

O script inclui logs na console:
```javascript
// Para ativar debug:
console.log(CarouselBlocos)  // Ver estado atual

// Ou chamar diretamente:
CarouselBlocos.scrollToBloco(1)  // Ir para bloco 2
```

## 🚀 Performance

- ✅ CSS media queries sem JavaScript para desktop
- ✅ Smooth scrolling nativo do navegador
- ✅ Touch-action otimizada para swipe
- ✅ Will-change para aceleração de GPU
- ✅ Sem animações em mobile reduz lag

## 🎓 Estrutura HTML Esperada

```html
<main class="main-content">
  <div class="container">
    <div class="bloco bloco-1">Conteúdo 1</div>
    <div class="bloco bloco-2">Conteúdo 2</div>
    <div class="bloco bloco-3">Conteúdo 3</div>
  </div>
</main>

<!-- Indicadores são criados dinamicamente por JS -->
<div class="carousel-indicators">
  <div class="carousel-dot active" data-bloco="0">
    <span class="carousel-indicator-label">Bloco 1</span>
  </div>
  ...
</div>
```

## 🔄 Compatibilidade

| Navegador | Desktop | Mobile | Notas |
|-----------|---------|--------|-------|
| Chrome | ✅ | ✅ | Suporte completo |
| Firefox | ✅ | ✅ | Suporte completo |
| Safari | ✅ | ✅ | Suporte completo |
| Edge | ✅ | ✅ | Suporte completo |
| IE11 | ⚠️ | ❌ | Não suportado |

## 🎯 Próximas Melhorias (Opcional)

- [ ] Drag and drop com mouse no desktop
- [ ] Teclado: Home/End para primeiro/último bloco
- [ ] Indicador numérico (1/3, 2/3, etc)
- [ ] Botões de navegação na lateral
- [ ] Auto-play após inatividade
- [ ] Persistence: Lembrar último bloco visitado

## 📝 Notas

- O carousel é **responsivo** automaticamente
- Não requer bibliotecas externas (CSS puro + Vanilla JS)
- Compatível com todos os browsers modernos
- Performance otimizada com GPU acceleration
