<?php
/**
 * Sistema de Logging para Notificações
 * Registra todas as atividades de notificações na pasta logs/
 */

class LogNotificacoes {
    private $pasta_logs = __DIR__ . '/logs/';
    private $arquivo_log;
    
    public function __construct() {
        // Criar pasta logs se não existir
        if (!is_dir($this->pasta_logs)) {
            mkdir($this->pasta_logs, 0755, true);
        }
        
        // Arquivo com data do dia: notificacoes-2025-11-17.log
        $data = date('Y-m-d');
        $this->arquivo_log = $this->pasta_logs . 'notificacoes-' . $data . '.log';
    }
    
    /**
     * Escreve um log no arquivo
     * 
     * @param string $tipo - Tipo de log (DETECTAR_TIPO, NOTIFICACAO, ERRO, etc)
     * @param mixed $dados - Dados a serem registrados
     * @param string $nivel - Nível de severidade (INFO, DEBUG, ERRO, AVISO)
     */
    public function registrar($tipo, $dados, $nivel = 'INFO') {
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'CLI';
        
        // Formatar dados para exibição
        if (is_array($dados) || is_object($dados)) {
            $dados_str = json_encode($dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } else {
            $dados_str = (string)$dados;
        }
        
        // Construir linha de log
        $linha_log = sprintf(
            "[%s] [%s] [%s] [%s] %s\n---\n%s\n\n",
            $timestamp,
            $nivel,
            $tipo,
            $ip,
            str_repeat("=", 80),
            $dados_str
        );
        
        // Escrever no arquivo
        file_put_contents($this->arquivo_log, $linha_log, FILE_APPEND);
        
        return true;
    }
    
    /**
     * Log de detecção de tipo de aposta
     */
    public function registrarDeteccaoTipo($titulo, $texto, $tipo_detectado, $msg_obj = []) {
        $dados = [
            'titulo' => $titulo,
            'texto_primeiras_linhas' => substr($texto, 0, 200),
            'tipo_detectado' => $tipo_detectado,
            'msg_object' => $msg_obj
        ];
        
        $this->registrar('DETECTAR_TIPO', $dados, 'DEBUG');
    }
    
    /**
     * Log de notificação exibida
     */
    public function registrarNotificacao($titulo, $tipo, $icone, $msg_completa) {
        $dados = [
            'titulo' => $titulo,
            'tipo_detectado' => $tipo,
            'icone_url' => $icone,
            'mensagem' => substr($msg_completa, 0, 300)
        ];
        
        $this->registrar('NOTIFICACAO_ENVIADA', $dados, 'INFO');
    }
    
    /**
     * Log de erro na notificação
     */
    public function registrarErro($mensagem, $exception = null) {
        $dados = [
            'erro' => $mensagem,
            'trace' => $exception ? $exception->getTraceAsString() : 'N/A'
        ];
        
        $this->registrar('ERRO_NOTIFICACAO', $dados, 'ERRO');
    }
    
    /**
     * Log de webhook recebido
     */
    public function registrarWebhook($dados_webhook) {
        $this->registrar('WEBHOOK_RECEBIDO', $dados_webhook, 'DEBUG');
    }
    
    /**
     * Log de mensagem carregada
     */
    public function registrarMensagemCarregada($msg) {
        $dados = [
            'id' => $msg['id'] ?? 'N/A',
            'titulo' => $msg['titulo'] ?? 'N/A',
            'time_1' => $msg['time_1'] ?? 'N/A',
            'time_2' => $msg['time_2'] ?? 'N/A',
            'resultado' => $msg['resultado'] ?? 'N/A'
        ];
        
        $this->registrar('MENSAGEM_CARREGADA', $dados, 'DEBUG');
    }
    
    /**
     * Obtém o caminho do arquivo de log atual
     */
    public function obterCaminhoLog() {
        return $this->arquivo_log;
    }
    
    /**
     * Retorna todas as linhas do log de hoje
     */
    public function obterLogsHoje() {
        if (file_exists($this->arquivo_log)) {
            return file_get_contents($this->arquivo_log);
        }
        return '';
    }
}

// Singleton para uso global
$GLOBALS['log_notificacoes'] = new LogNotificacoes();

?>
