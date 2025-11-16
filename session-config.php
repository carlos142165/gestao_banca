<?php
/**
 * 🔐 CONFIGURAÇÃO GLOBAL DE SESSÃO
 * 
 * Este arquivo DEVE ser incluído ANTES de session_start()
 * em TODOS os arquivos PHP que usam sessão
 * 
 * Uso:
 * require_once __DIR__ . '/session-config.php';
 * session_start();
 */

// Detectar se é HTTPS
$is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
            (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

// ✅ Configurar opções de cookie de sessão ANTES de session_start()
ini_set('session.cookie_httponly', 1);           // ✅ JavaScript não pode acessar (segurança)
ini_set('session.use_only_cookies', 1);          // ✅ Apenas cookies (sem URL rewriting)
ini_set('session.cookie_secure', $is_https ? 1 : 0);  // ✅ Apenas HTTPS em produção
ini_set('session.cookie_samesite', 'Lax');       // ✅ CSRF protection
ini_set('session.cookie_path', '/');             // ✅ Acessível em todo domínio
ini_set('session.cookie_domain', '');            // ✅ Cookie domain automático
ini_set('session.gc_maxlifetime', 86400);        // ✅ 24 horas
ini_set('session.sid_length', 48);               // ✅ 48 caracteres de sessão ID
ini_set('session.use_strict_mode', 1);           // ✅ Strict mode para segurança

// Log de debug (remover em produção se não precisar)
if (php_sapi_name() !== 'cli') {
    error_log("SESSION CONFIG: HTTPS=" . ($is_https ? "1" : "0") . ", Host=" . ($_SERVER['HTTP_HOST'] ?? 'unknown'));
}

?>
