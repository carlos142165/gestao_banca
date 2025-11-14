<?php
// ============================================
// CONFIGURAÇÃO CENTRALIZADA DO BANCO DE DADOS
// ============================================

// ✅ INCLUIR CONFIGURAÇÃO DO TELEGRAM PRIMEIRO
require_once __DIR__ . '/telegram-config.php';

// ✅ SIMPLES: Se não é localhost, então é produção
$serverHost = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'unknown';

// ✅ VERIFICAÇÃO LOCALHOST
$isLocalhost = (
    strpos($serverHost, 'localhost') !== false ||
    strpos($serverHost, '127.0.0.1') !== false ||
    empty($serverHost)
);

error_log("CONFIG.PHP CARREGADO: serverHost='$serverHost', isLocalhost=" . ($isLocalhost ? "1" : "0"));

// ✅ DEFINIR CONSTANTES - Sempre executa UM dos blocos abaixo
if ($isLocalhost) {
    // LOCAL - XAMPP
    define('DB_HOST', 'localhost');
    define('DB_USERNAME', 'root');
    define('DB_PASSWORD', '');
    define('DB_NAME', 'formulario-carlos');
    define('ENVIRONMENT', 'local');
    error_log("✓ CONFIG: LOCAL environment");
} else {
    // PRODUCTION - Hostinger
    define('DB_HOST', '127.0.0.1');
    define('DB_USERNAME', 'u857325944_formu');
    define('DB_PASSWORD', 'JkF4B7N1');
    define('DB_NAME', 'u857325944_formu');
    define('ENVIRONMENT', 'production');
    error_log("✓ CONFIG: PRODUCTION environment");
}

// Variáveis globais para compatibilidade
$dbHost = DB_HOST;
$dbUsername = DB_USERNAME;
$dbPassword = DB_PASSWORD;
$dbname = DB_NAME;

// ✅ Criar conexão MySQLi global
$conexao = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Verificar erro na conexão
if ($conexao->connect_error) {
    error_log("❌ ERRO conexão: " . $conexao->connect_error);
    die("Erro ao conectar com o banco de dados.");
}

// ✅ Configurar timezone
date_default_timezone_set('America/Sao_Paulo');
$conexao->set_charset("utf8mb4");
$conexao->query("SET time_zone = '-03:00'");

// ============================================
// FUNÇÕES AUXILIARES
// ============================================

function getPDOConnection() {
    try {
        return new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
            DB_USERNAME,
            DB_PASSWORD
        );
    } catch (PDOException $e) {
        error_log("Erro PDO: " . $e->getMessage());
        return null;
    }
}

function getMySQLiConnection() {
    $conn = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
    if ($conn->connect_error) {
        error_log("Erro MySQLi: " . $conn->connect_error);
        return null;
    }
    return $conn;
}

// ============================================
// ✅ FUNÇÃO OBTER CONEXÃO COM RECONEXÃO AUTOMÁTICA (ROBUSTA)
// ============================================
function obterConexao() {
    global $conexao;
    
    // ✅ PASSO 1: Verificar se conexão global existe
    if (!$conexao) {
        error_log("⚠️ Conexão global é NULL - criando nova conexão");
        return criarNovaConexao();
    }
    
    // ✅ PASSO 2: Verificar com ping() - mais confiável que apenas verificar se existe
    if ($conexao->ping()) {
        // Conexão está ativa e respondendo
        return $conexao;
    }
    
    // ✅ PASSO 3: Se ping falhou, reconectar
    error_log("⚠️ Conexão perdida (ping falhou) - reconectando...");
    return criarNovaConexao();
}

// ============================================
// ✅ FUNÇÃO AUXILIAR: CRIAR NOVA CONEXÃO
// ============================================
function criarNovaConexao() {
    global $conexao;
    
    try {
        $novaConexao = new mysqli(
            DB_HOST,
            DB_USERNAME,
            DB_PASSWORD,
            DB_NAME
        );
        
        if ($novaConexao->connect_error) {
            error_log("❌ ERRO CRÍTICO: Falha ao conectar ao banco: " . $novaConexao->connect_error);
            return null;
        }
        
        // ✅ CONFIGURAR TIMEOUTS AGGRESSIVOS (7 dias = 604800 segundos)
        $novaConexao->query("SET SESSION wait_timeout = 604800");
        $novaConexao->query("SET SESSION interactive_timeout = 604800");
        $novaConexao->query("SET SESSION net_read_timeout = 604800");
        $novaConexao->query("SET SESSION net_write_timeout = 604800");
        
        // ✅ CONFIGURAR CHARSET E TIMEZONE
        $novaConexao->set_charset("utf8mb4");
        $novaConexao->query("SET time_zone = '-03:00'");
        
        // ✅ ATIVAR RECONNECT (MySQL < 5.7.3)
        $novaConexao->query("SET SESSION autocommit = 1");
        
        // Atualizar variável global
        $conexao = $novaConexao;
        
        error_log("✅ CONEXÃO ESTABELECIDA COM SUCESSO - Timeouts: 7 dias");
        return $conexao;
        
    } catch (Exception $e) {
        error_log("❌ EXCEÇÃO ao criar conexão: " . $e->getMessage());
        return null;
    }
}

// ✅ Configurar timeouts iniciais na conexão global (7 dias = 604800 segundos)
$conexao->query("SET SESSION wait_timeout = 604800");
$conexao->query("SET SESSION interactive_timeout = 604800");

?>
