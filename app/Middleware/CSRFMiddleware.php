<?php
/**
 * Updated CSRFMiddleware.php - Fix token validation
 * Replace your app/Middleware/CSRFMiddleware.php with this:
 */
class CSRFMiddleware {
    
    /**
     * Generate CSRF token for forms
     */
    public static function generateToken() {
        // Start session if not started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Generate new token if none exists or expired
        if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time']) || 
            time() - $_SESSION['csrf_token_time'] > ($_ENV['CSRF_TOKEN_LIFETIME'] ?? 3600)) {
            
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['csrf_token_time'] = time();
            
            error_log("CSRF: Generated new token: " . $_SESSION['csrf_token']);
        } else {
            error_log("CSRF: Using existing token: " . $_SESSION['csrf_token']);
        }
        
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Verify CSRF token from request
     */
    public static function verifyToken($token = null) {
        // Start session if not started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if ($token === null) {
            $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
        }
        
        error_log("CSRF: Verifying token: '$token'");
        error_log("CSRF: Session token: '" . ($_SESSION['csrf_token'] ?? 'NOT SET') . "'");
        error_log("CSRF: Token time: " . ($_SESSION['csrf_token_time'] ?? 'NOT SET'));
        
        if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time'])) {
            error_log("CSRF: No session token found");
            return false;
        }
        
        // Check if token has expired
        if (time() - $_SESSION['csrf_token_time'] > ($_ENV['CSRF_TOKEN_LIFETIME'] ?? 3600)) {
            error_log("CSRF: Token expired");
            unset($_SESSION['csrf_token'], $_SESSION['csrf_token_time']);
            return false;
        }
        
        $isValid = hash_equals($_SESSION['csrf_token'], $token);
        error_log("CSRF: Token validation result: " . ($isValid ? 'VALID' : 'INVALID'));
        
        return $isValid;
    }
    
    /**
     * Require valid CSRF token for POST requests
     */
    public static function requireValidToken() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            error_log("CSRF: POST request detected, validating token");
            if (!self::verifyToken()) {
                error_log("CSRF: Token validation failed, handling failure");
                self::handleCSRFFailure();
            } else {
                error_log("CSRF: Token validation successful");
            }
        }
    }
    
    /**
     * Handle CSRF token verification failure
     */
    private static function handleCSRFFailure() {
        // Log the security event
        error_log("CSRF FAILURE: Token verification failed");
        error_log("CSRF FAILURE: POST data: " . print_r($_POST, true));
        error_log("CSRF FAILURE: Session data: " . print_r($_SESSION ?? [], true));
        
        // Return appropriate response
        if (self::isAjaxRequest()) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode([
                'error' => 'Token de sécurité invalide. Veuillez recharger la page.',
                'code' => 'CSRF_TOKEN_INVALID'
            ]);
        } else {
            // For regular form submissions, set flash message and redirect
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['flash_messages'] = [
                'type' => 'error',
                'message' => 'Token de sécurité invalide. Veuillez réessayer.',
                'timestamp' => time()
            ];
            
            $referer = $_SERVER['HTTP_REFERER'] ?? '/clients';
            header("Location: {$referer}");
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
}
?>