#!/bin/bash
# 🔍 Script de Diagnóstico para Notificações

echo "════════════════════════════════════════════════════════════"
echo "🔔 DIAGNÓSTICO DO SISTEMA DE NOTIFICAÇÕES"
echo "════════════════════════════════════════════════════════════"
echo ""

# Verificar se os arquivos existem
echo "📂 Verificando Arquivos Necessários..."
echo ""

files=(
    "js/notificacoes-sistema.js"
    "js/telegram-mensagens.js"
    "bot_aovivo.php"
    "registrar-log-notificacao.php"
    "api/carregar-mensagens-banco.php"
)

for file in "${files[@]}"; do
    if [ -f "$file" ]; then
        echo "✅ $file"
    else
        echo "❌ $file - NÃO ENCONTRADO"
    fi
done

echo ""
echo "════════════════════════════════════════════════════════════"
echo "🔧 Verificando Configurações..."
echo "════════════════════════════════════════════════════════════"
echo ""

# Verificar se notificacoes-sistema.js carrega antes de telegram-mensagens.js
echo "📋 Verificando ordem de carregamento dos scripts em bot_aovivo.php..."
echo ""

notif_line=$(grep -n "notificacoes-sistema.js" bot_aovivo.php | head -1 | cut -d: -f1)
telegram_line=$(grep -n "telegram-mensagens.js" bot_aovivo.php | head -1 | cut -d: -f1)

if [ ! -z "$notif_line" ] && [ ! -z "$telegram_line" ]; then
    if [ "$notif_line" -lt "$telegram_line" ]; then
        echo "✅ notificacoes-sistema.js (linha $notif_line) carrega ANTES de telegram-mensagens.js (linha $telegram_line)"
    else
        echo "❌ ERRO! telegram-mensagens.js carrega ANTES de notificacoes-sistema.js"
        echo "   Isso causará erro: NotificacoesSistema não estará definido"
    fi
else
    echo "⚠️  Não foi possível encontrar os arquivos de script"
fi

echo ""
echo "════════════════════════════════════════════════════════════"
echo "🔐 Verificando Detecção de Duplicatas..."
echo "════════════════════════════════════════════════════════════"
echo ""

# Verificar se está usando msg.id
if grep -q "const msgId = msg?.id" js/notificacoes-sistema.js; then
    echo "✅ Sistema usa msg.id para detecção de duplicatas (CORRETO)"
else
    echo "⚠️  Verificar se msg.id está sendo usado corretamente"
fi

# Verificar timeout de duplicatas
timeout=$(grep -o "setTimeout.*[0-9]\+0*" js/notificacoes-sistema.js | tail -1)
echo "⏱️  Timeout de duplicatas: $timeout"

echo ""
echo "════════════════════════════════════════════════════════════"
echo "📊 Verificando Logs..."
echo "════════════════════════════════════════════════════════════"
echo ""

if [ -d "logs" ]; then
    echo "✅ Diretório logs/ existe"
    logcount=$(ls -1 logs/notif-*.log 2>/dev/null | wc -l)
    if [ "$logcount" -gt 0 ]; then
        echo "📋 Arquivos de log encontrados: $logcount"
        latest=$(ls -t logs/notif-*.log 2>/dev/null | head -1)
        if [ ! -z "$latest" ]; then
            echo "📄 Log mais recente: $(basename $latest)"
            echo "   Tamanho: $(wc -c < $latest) bytes"
            echo "   Últimas 5 linhas:"
            tail -5 "$latest" | sed 's/^/     /'
        fi
    else
        echo "⚠️  Nenhum arquivo de log de notificações encontrado"
    fi
else
    echo "❌ Diretório logs/ não existe"
fi

echo ""
echo "════════════════════════════════════════════════════════════"
echo "💾 Verificando Banco de Dados..."
echo "════════════════════════════════════════════════════════════"
echo ""

if [ -f "config.php" ]; then
    echo "✅ config.php encontrado"
    if grep -q "bote" config.php || grep -q "'bote'" config.php; then
        echo "✅ Tabela 'bote' referenciada em config.php"
    fi
else
    echo "❌ config.php não encontrado"
fi

echo ""
echo "════════════════════════════════════════════════════════════"
echo "🧪 Resumo do Diagnóstico"
echo "════════════════════════════════════════════════════════════"
echo ""
echo "Para testar manualmente:"
echo "1. Abra bot_aovivo.php no navegador"
echo "2. Abra o console (F12)"
echo "3. Execute: teste-notificacoes-fluxo.html"
echo "4. Verifique logs em: visualizar-logs-notificacoes.php"
echo ""
echo "Para teste via linha de comando:"
echo "curl 'http://localhost/gestao/gestao_banca/teste-notificacoes-fluxo.html'"
echo ""
echo "════════════════════════════════════════════════════════════"
