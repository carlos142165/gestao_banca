<?php
// ============================================
// CONFIGURAÇÃO CENTRALIZADA DO BANCO DE DADOS
// ============================================
// Este arquivo contém todas as configurações de conexão
// Modifique aqui e TODOS os arquivos usarão as novas configurações

// Configurações de conexão
// 🔧 PREENCHA COM OS DADOS DO SEU BANCO NA HOSTINGER
define('DB_HOST', 'localhost');  // Geralmente 127.0.0.1 na Hostinger
define('DB_USERNAME', 'root');  // ⚠️ SUBSTITUA COM SEU USUÁRIO
define('DB_PASSWORD', '');    // ⚠️ SUBSTITUA COM SUA SENHA
define('DB_NAME', 'formulario-carlos');   // ⚠️ SUBSTITUA COM SEU BANCO

// Variáveis globais para compatibilidade com código existente
$dbHost = DB_HOST;
$dbUsername = DB_USERNAME;
$dbPassword = DB_PASSWORD;
$dbname = DB_NAME;

// Criar conexão MySQLi global
$conexao = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Verificar erro na conexão
if ($conexao->connect_error) {
    error_log("Erro de conexão com banco de dados: " . $conexao->connect_error);
    die("Erro na conexão com o banco de dados. Por favor, tente novamente mais tarde.");
}

// ============================================
// FUNÇÕES AUXILIARES
// ============================================

/**
 * Obter uma conexão PDO para uso com PDO
 * @return PDO|null
 */
function getPDOConnection() {
    try {
        return new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
            DB_USERNAME,
            DB_PASSWORD
        );
    } catch (PDOException $e) {
        error_log("Erro ao conectar com PDO: " . $e->getMessage());
        return null;
    }
}

/**
 * Obter uma conexão MySQLi
 * @return mysqli|null
 */
function getMySQLiConnection() {
    $conn = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
    if ($conn->connect_error) {
        error_log("Erro ao conectar com MySQLi: " . $conn->connect_error);
        return null;
    }
    return $conn;
}

// Definir charset UTF-8 por padrão
$conexao->set_charset("utf8mb4");

