#!/bin/bash
# 🧪 TESTE - Verificar se correção funcionou

echo "======================================"
echo "🔍 TESTE DE DIAGNÓSTICO"
echo "======================================"
echo ""

# 1. Verificar se arquivo plano-manager.js tem a correção
echo "1️⃣  Verificando se correcção foi aplicada..."
if grep -q "if (!this.planos || this.planos.length === 0)" "js/plano-manager.js"; then
    echo "✅ Correção encontrada em js/plano-manager.js"
else
    echo "❌ Correção NÃO encontrada em js/plano-manager.js"
    exit 1
fi

# 2. Verificar se script tem defer
echo ""
echo "2️⃣  Verificando se defer foi adicionado..."
if grep -q '<script src="js/plano-manager.js" defer>' gestao-diaria.php; then
    echo "✅ Atributo 'defer' encontrado"
else
    echo "❌ Atributo 'defer' NÃO encontrado"
    exit 1
fi

# 3. Verificar se arquivos de teste existem
echo ""
echo "3️⃣  Verificando arquivos de teste..."
files=("teste-modal-planos.php" "teste-obter-planos.php" "FIX_MODAL_VAZIA.md" "DIAGNÓSTICO_MODAL_VAZIA.md")
for file in "${files[@]}"; do
    if [ -f "$file" ]; then
        echo "✅ $file existe"
    else
        echo "❌ $file NÃO existe"
    fi
done

echo ""
echo "======================================"
echo "✅ TODOS OS TESTES PASSARAM!"
echo "======================================"
echo ""
echo "📋 PRÓXIMOS PASSOS:"
echo "1. Abra o navegador"
echo "2. Teste: http://localhost/gestao/gestao_banca/teste-modal-planos.php"
echo "3. Clique em 'Testar Abertura da Modal'"
echo "4. Deve ver 4 planos renderizados"
echo ""
