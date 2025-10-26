<?php
/**
 * SINCRONIZADOR DE ADMIN IDs - sync-admin-ids.php
 * ================================================
 * 
 * Este arquivo é útil se você quer sincronizar IDs de admin entre
 * ambientes (desenvolvimento, produção, etc) ou fazer backup/restore
 * 
 * IMPORTANTE: Coloque este arquivo em um local seguro, pois pode expor dados sensíveis
 */

require_once 'config.php';
require_once 'admin-ids-config.php';

// Apenas super-admin (ID 23) pode usar este arquivo
if (($_SESSION['usuario_id'] ?? null) !== 23) {
    die('❌ Acesso negado. Apenas ID 23 pode usar este arquivo.');
}

// ==================================================================================================================== 
// ========================== AÇÕES ==========================
// ==================================================================================================================== 

$acao = $_GET['acao'] ?? $_POST['acao'] ?? null;

if ($acao === 'exportar') {
    // Exportar IDs como JSON
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="admin_ids_backup_' . date('Y-m-d_H-i-s') . '.json"');
    
    $ids = AdminIdManager::obterAdminIds();
    echo json_encode($ids, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

elseif ($acao === 'importar') {
    // Importar IDs de um arquivo JSON
    if (!isset($_FILES['arquivo'])) {
        echo '<script>alert("❌ Nenhum arquivo enviado!"); history.back();</script>';
        exit;
    }
    
    $arquivo = $_FILES['arquivo'];
    
    // Validar tipo de arquivo
    if ($arquivo['type'] !== 'application/json') {
        echo '<script>alert("❌ Apenas arquivos JSON são aceitos!"); history.back();</script>';
        exit;
    }
    
    // Ler arquivo
    $conteudo = file_get_contents($arquivo['tmp_name']);
    $ids = json_decode($conteudo, true);
    
    // Validar formato
    if (!is_array($ids)) {
        echo '<script>alert("❌ Formato JSON inválido!"); history.back();</script>';
        exit;
    }
    
    // Validar se todos são números
    foreach ($ids as $id) {
        if (!is_int($id) || $id <= 0) {
            echo '<script>alert("❌ IDs devem ser números positivos!"); history.back();</script>';
            exit;
        }
    }
    
    // Remover duplicatas e ordenar
    $ids = array_unique($ids);
    sort($ids);
    
    // Salvar (pode usar reflexão ou criar função pública)
    try {
        $file = dirname(__FILE__) . '/dados/admin_ids.json';
        $json = json_encode($ids, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        
        if (file_put_contents($file, $json) === false) {
            throw new Exception('Erro ao salvar arquivo');
        }
        
        echo '<script>alert("✅ IDs importados com sucesso! Total: ' . count($ids) . '"); window.location.href="administrativa.php";</script>';
        exit;
        
    } catch (Exception $e) {
        echo '<script>alert("❌ Erro ao importar: ' . $e->getMessage() . '"); history.back();</script>';
        exit;
    }
}

elseif ($acao === 'resetar') {
    // Resetar para IDs padrão
    if ($_POST['confirmar'] !== 'sim') {
        echo '<script>alert("❌ Confirmação não foi recebida!"); history.back();</script>';
        exit;
    }
    
    try {
        $file = dirname(__FILE__) . '/dados/admin_ids.json';
        $ids_padrao = [23, 42]; // IDs padrão
        $json = json_encode($ids_padrao, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        
        if (file_put_contents($file, $json) === false) {
            throw new Exception('Erro ao salvar arquivo');
        }
        
        echo '<script>alert("✅ IDs resetados para padrão: [23, 42]"); window.location.href="administrativa.php";</script>';
        exit;
        
    } catch (Exception $e) {
        echo '<script>alert("❌ Erro ao resetar: ' . $e->getMessage() . '"); history.back();</script>';
        exit;
    }
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sincronizador de Admin IDs</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Rajdhani', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        .card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        h1 {
            color: #667eea;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        h2 {
            color: #764ba2;
            margin: 25px 0 15px 0;
            font-size: 18px;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }
        .secao {
            margin-bottom: 25px;
            padding: 20px;
            background: #f8fafc;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }
        .botoes {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 15px;
        }
        button, a.btn {
            padding: 12px 24px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        button:hover, a.btn:hover {
            background: #764ba2;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        button.perigo {
            background: #ef4444;
        }
        button.perigo:hover {
            background: #dc2626;
        }
        input[type="file"] {
            padding: 10px;
            border: 2px solid #667eea;
            border-radius: 8px;
            flex: 1;
        }
        .info {
            background: #dbeafe;
            border-left-color: #0284c7;
            color: #0c2d6b;
        }
        .aviso {
            background: #fef3c7;
            border-left-color: #f59e0b;
            color: #92400e;
        }
        code {
            background: #e2e8f0;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
        }
        .lista-ids {
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
        }
        .lista-ids p {
            margin: 5px 0;
            font-family: 'Courier New', monospace;
            color: #667eea;
            font-weight: 600;
        }
        .voltar {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

<div class="container">
    
    <div class="card">
        <div class="voltar">
            <a href="administrativa.php" class="btn" style="background: #f8fafc; color: #667eea; border: 2px solid #667eea;">
                <i class="fas fa-arrow-left"></i>
                Voltar para Administrativa
            </a>
        </div>
        
        <h1>
            <i class="fas fa-sync"></i>
            Sincronizador de Admin IDs
        </h1>
        
        <!-- ============== INFORMAÇÃO ============== -->
        <div class="secao info">
            <strong>ℹ️ O que é isto?</strong><br>
            Esta ferramenta permite que você faça backup, restaure e sincronize os IDs de administrador entre ambientes.
        </div>
        
        <!-- ============== IDs ATUAIS ============== -->
        <div class="secao">
            <h2>📋 IDs de Administrador Atuais</h2>
            <div class="lista-ids">
                <?php
                $ids = AdminIdManager::obterAdminIds();
                if (!empty($ids)) {
                    foreach ($ids as $id) {
                        echo "<p>📌 ID: $id</p>";
                    }
                } else {
                    echo "<p style='color: #64748b;'>Nenhum ID cadastrado</p>";
                }
                ?>
            </div>
            <p style="margin-top: 15px; color: #64748b; font-size: 13px;">
                <i class="fas fa-info-circle"></i>
                Total: <strong><?php echo count($ids); ?></strong> administrador(es)
            </p>
        </div>
        
        <!-- ============== EXPORTAR ============== -->
        <div class="secao">
            <h2><i class="fas fa-download"></i> Exportar IDs</h2>
            <p style="margin-bottom: 15px; color: #64748b;">
                Baixe os IDs de administrador como arquivo JSON para fazer backup ou transferir para outro ambiente.
            </p>
            <form method="get" action="">
                <input type="hidden" name="acao" value="exportar">
                <button type="submit">
                    <i class="fas fa-download"></i>
                    Baixar Backup (JSON)
                </button>
            </form>
        </div>
        
        <!-- ============== IMPORTAR ============== -->
        <div class="secao">
            <h2><i class="fas fa-upload"></i> Importar IDs</h2>
            <p style="margin-bottom: 15px; color: #64748b;">
                Selecione um arquivo JSON (exportado anteriormente) para restaurar os IDs.
            </p>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="acao" value="importar">
                <div class="botoes" style="gap: 0;">
                    <input type="file" name="arquivo" accept=".json" required>
                    <button type="submit">
                        <i class="fas fa-upload"></i>
                        Importar
                    </button>
                </div>
            </form>
        </div>
        
        <!-- ============== RESETAR ============== -->
        <div class="secao aviso">
            <h2>
                <i class="fas fa-exclamation-triangle"></i>
                Resetar para Padrão
            </h2>
            <p style="margin-bottom: 15px;">
                ⚠️ <strong>CUIDADO!</strong> Esta ação resetará todos os IDs para o padrão: <code>23, 42</code><br>
                Os IDs atuais serão perdidos!
            </p>
            <form method="post" onsubmit="return confirm('Tem certeza? Esta ação não pode ser desfeita!')">
                <input type="hidden" name="acao" value="resetar">
                <input type="hidden" name="confirmar" value="sim">
                <button type="submit" class="perigo">
                    <i class="fas fa-redo"></i>
                    Resetar para Padrão
                </button>
            </form>
        </div>
        
        <!-- ============== INFORMAÇÕES ============== -->
        <div class="secao info">
            <h2>
                <i class="fas fa-book"></i>
                Informações Úteis
            </h2>
            <ul style="margin-left: 20px; color: #0c2d6b;">
                <li>📄 Formato: O arquivo deve ser JSON válido com um array de números</li>
                <li>🔒 Segurança: Apenas ID 23 pode acessar esta página</li>
                <li>💾 Backup: Sempre faça backup antes de importar</li>
                <li>🔄 Sincronização: Use para sincronizar entre desenvolvimento e produção</li>
                <li>✨ Limpeza: IDs duplicados são removidos automaticamente</li>
            </ul>
        </div>
        
    </div>
    
</div>

</body>
</html>
<?php
?>
