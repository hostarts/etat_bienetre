<?php
/**
 * CSRF Protection Middleware
 * Handles Cross-Site Request Forgery protection
 */
class CSRFMiddleware {
    
    /**
     * Generate CSRF token for forms
     */
    public static function generateToken() {
        if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time']) || 
            time() - $_SESSION['csrf_token_time'] > ($_ENV['CSRF_TOKEN_LIFETIME'] ?? 3600)) {
            
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['csrf_token_time'] = time();
        }
        
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Verify CSRF token from request
     */
    public static function verifyToken($token = null) {
        if ($token === null) {
            $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
        }
        
        if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time'])) {
            return false;
        }
        
        // Check if token has expired
        if (time() - $_SESSION['csrf_token_time'] > ($_ENV['CSRF_TOKEN_LIFETIME'] ?? 3600)) {
            unset($_SESSION['csrf_token'], $_SESSION['csrf_token_time']);
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Require valid CSRF token for POST requests
     */
    public static function requireValidToken() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!self::verifyToken()) {
                self::handleCSRFFailure();
            }
        }
    }
    
    /**
     * Handle CSRF token verification failure
     */
    private static function handleCSRFFailure() {
        // Log the security event
        Security::logSecurityEvent('CSRF token verification failed');
        
        // Return appropriate response
        if (self::isAjaxRequest()) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode([
                'error' => 'Token de sécurité invalide. Veuillez recharger la page.',
                'code' => 'CSRF_TOKEN_INVALID'
            ]);
        } else {
            http_response_code(403);
            include dirname(__DIR__) . '/Views/errors/403.php';
        }
        exit;
    }
    
    /**
     * Check if request is AJAX
     */
    private static function isAjaxRequest() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    /**
     * Get CSRF token for JavaScript
     */
    public static function getTokenForJS() {
        return [
            'token' => self::generateToken(),
            'header' => 'X-CSRF-Token'
        ];
    }
    
    /**
     * Embed CSRF token in forms automatically
     */
    public static function embedTokenInForms($html) {
        $token = self::generateToken();
        $tokenField = "<input type=\"hidden\" name=\"csrf_token\" value=\"{$token}\">";
        
        // Insert token field into all forms
        return preg_replace(
            '/<form([^>]*)>/i',
            "<form$1>\n    {$tokenField}",
            $html
        );
    }
    
    /**
     * Validate CSRF token from header (for AJAX requests)
     */
    public static function validateFromHeader() {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        return self::verifyToken($token);
    }
}