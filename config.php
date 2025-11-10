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

