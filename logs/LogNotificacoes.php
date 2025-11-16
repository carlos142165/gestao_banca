<?php
/**
 * 📝 SISTEMA DE LOGS PARA NOTIFICAÇÕES
 * Registra todas as ações em um arquivo de log
 */

class LogNotificacoes {
    private $arquivoLog;
    private $pastaLogs;
    
    public function __construct() {
        $this->pastaLogs = __DIR__ . '/../logs';
        $this->arquivoLog = $this->pastaLogs . '/notificacoes.log';
        
        // Criar pasta de logs se não existir
        if (!is_dir($this->pastaLogs)) {
            mkdir($this->pastaLogs, 0777, true);
        }
    }
    
    /**
     * Registrar evento no log
     */
    public function registrar($tipo, $mensagem, $dados = []) {
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'desconhecido';
        
        $logEntry = [
            'timestamp' => $timestamp,
            'tipo' => $tipo,
            'ip' => $ip,
            'mensagem' => $mensagem,
            'dados' => $dados,
            'url' => $_SERVER['REQUEST_URI'] ?? '',
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 100)
        ];
        
        // Escrever no arquivo
        $linha = json_encode($logEntry, JSON_UNESCAPED_UNICODE) . "\n";
        file_put_contents($this->arquivoLog, $linha, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Obter logs com filtro
     */
    public function obterLogs($filtro = '', $linhas = 100) {
        if (!file_exists($this->arquivoLog)) {
            return [];
        }
        
        $linhasArquivo = file($this->arquivoLog, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $logs = [];
        
        // Pegar últimas N linhas
        $linhasArquivo = array_slice($linhasArquivo, -$linhas);
        
        foreach ($linhasArquivo as $linha) {
            $log = json_decode($linha, true);
            if ($log) {
                // Filtrar se necessário
                if (empty($filtro) || stripos(json_encode($log), $filtro) !== false) {
                    $logs[] = $log;
                }
            }
        }
        
        return array_reverse($logs);
    }
    
    /**
     * Limpar logs antigos (mais de 7 dias)
     */
    public function limparLogsAntigos($diasRetencao = 7) {
        if (!file_exists($this->arquivoLog)) {
            return;
        }
        
        $linhasArquivo = file($this->arquivoLog, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $logsRecentes = [];
        $dataLimite = strtotime("-{$diasRetencao} days");
        
        foreach ($linhasArquivo as $linha) {
            $log = json_decode($linha, true);
            if ($log && strtotime($log['timestamp']) > $dataLimite) {
                $logsRecentes[] = $linha;
            }
        }
        
        file_put_contents($this->arquivoLog, implode("\n", $logsRecentes) . "\n");
    }
}

// Criar instância global
$logNotificacoes = new LogNotificacoes();

// Se requisição é GET para visualizar logs
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['acao']) && $_GET['acao'] === 'visualizar_logs') {
    header('Content-Type: application/json');
    
    $filtro = $_GET['filtro'] ?? '';
    $logs = $logNotificacoes->obterLogs($filtro, 200);
    
    echo json_encode([
        'sucesso' => true,
        'total' => count($logs),
        'logs' => $logs
    ]);
    exit;
}

// Se requisição é POST para registrar log do cliente
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'registrar_log') {
    $tipo = $_POST['tipo'] ?? 'info';
    $mensagem = $_POST['mensagem'] ?? '';
    $dados = isset($_POST['dados']) ? json_decode($_POST['dados'], true) : [];
    
    $logNotificacoes->registrar($tipo, $mensagem, $dados);
    
    echo json_encode(['sucesso' => true]);
    exit;
}
