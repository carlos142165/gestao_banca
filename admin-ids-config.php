<?php
/**
 * ADMIN IDs CONFIG - admin-ids-config.php
 * ========================================
 * Gerencia a lista de IDs com acesso administrativo
 * Este arquivo funciona como um banco de dados para os IDs de admin
 */

// Iniciar sessão se não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('ADMIN_IDS_FILE', __DIR__ . '/dados/admin_ids.json');

class AdminIdManager {
    /**
     * Obter todos os IDs de admin
     */
    public static function obterAdminIds() {
        if (!file_exists(ADMIN_IDS_FILE)) {
            // Criar arquivo padrão na primeira vez
            $ids_padrao = [23, 42]; // IDs padrão
            self::salvarAdminIds($ids_padrao);
            return $ids_padrao;
        }
        
        $conteudo = file_get_contents(ADMIN_IDS_FILE);
        $ids = json_decode($conteudo, true);
        
        return is_array($ids) ? $ids : [];
    }
    
    /**
     * Adicionar um novo ID de admin
     */
    public static function adicionarAdminId($novo_id) {
        $novo_id = (int)$novo_id;
        
        // Validar ID
        if ($novo_id <= 0) {
            throw new Exception('ID deve ser um número positivo');
        }
        
        $ids = self::obterAdminIds();
        
        // Verificar se já existe
        if (in_array($novo_id, $ids)) {
            throw new Exception('Este ID já está na lista de admins');
        }
        
        // Adicionar novo ID
        $ids[] = $novo_id;
        sort($ids); // Manter ordenado
        
        self::salvarAdminIds($ids);
        
        return [
            'success' => true,
            'mensagem' => "ID {$novo_id} adicionado com sucesso!",
            'ids' => $ids
        ];
    }
    
    /**
     * Remover um ID de admin
     */
    public static function removerAdminId($id_remover) {
        $id_remover = (int)$id_remover;
        
        $ids = self::obterAdminIds();
        
        // Verificar se existe
        if (!in_array($id_remover, $ids)) {
            throw new Exception('ID não encontrado na lista');
        }
        
        // Remover ID
        $ids = array_filter($ids, function($id) use ($id_remover) {
            return $id !== $id_remover;
        });
        
        // Reindexar array
        $ids = array_values($ids);
        
        self::salvarAdminIds($ids);
        
        return [
            'success' => true,
            'mensagem' => "ID {$id_remover} removido com sucesso!",
            'ids' => $ids
        ];
    }
    
    /**
     * Salvar IDs no arquivo
     */
    private static function salvarAdminIds($ids) {
        // Criar diretório se não existir
        $dir = dirname(ADMIN_IDS_FILE);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        $json = json_encode($ids, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        
        if (file_put_contents(ADMIN_IDS_FILE, $json) === false) {
            throw new Exception('Erro ao salvar arquivo de configuração');
        }
    }
    
    /**
     * Validar se um ID é admin
     */
    public static function ehAdmin($usuario_id) {
        $ids = self::obterAdminIds();
        return in_array((int)$usuario_id, $ids);
    }
}

// Se a requisição é via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    header('Content-Type: application/json');
    
    $id_usuario = $_SESSION['usuario_id'] ?? null;
    
    // APENAS ID 23 PODE GERENCIAR (super-admin)
    // Descomente quando tiver certeza que é ID 23
    // if ($id_usuario !== 23) {
    //     echo json_encode([
    //         'success' => false,
    //         'mensagem' => 'Apenas o administrador pode gerenciar usuários vitalício.'
    //     ]);
    //     exit;
    // }
    
    try {
        $acao = $_POST['acao'];
        
        if ($acao === 'obter') {
            $ids = AdminIdManager::obterAdminIds();
            echo json_encode([
                'success' => true,
                'ids' => $ids,
                'total' => count($ids)
            ]);
            
        } elseif ($acao === 'adicionar') {
            $novo_id = $_POST['novo_id'] ?? null;
            if (!$novo_id) {
                throw new Exception('ID não fornecido');
            }
            $resultado = AdminIdManager::adicionarAdminId($novo_id);
            echo json_encode($resultado);
            
        } elseif ($acao === 'remover') {
            $id_remover = $_POST['id_remover'] ?? null;
            if (!$id_remover) {
                throw new Exception('ID não fornecido');
            }
            $resultado = AdminIdManager::removerAdminId($id_remover);
            echo json_encode($resultado);
            
        } else {
            throw new Exception('Ação inválida');
        }
        
    } catch (Exception $e) {
        error_log("AdminIDs Erro: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'mensagem' => $e->getMessage()
        ]);
    }
    exit;
}
?>
