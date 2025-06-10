<?php
/**
 * Authentication Controller
 * Handles user login, logout, and authentication
 */
class AuthController {
    
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Show login form
     */
    public function showLogin() {
        // If already logged in, redirect to dashboard
        if (AuthMiddleware::authenticate()) {
            header('Location: /dashboard');
            exit;
        }
        
        $data = [
            'title' => 'Connexion',
            'csrf_token' => CSRFMiddleware::generateToken(),
            'flash' => Session::getFlash()
        ];
        
        $this->renderLogin($data);
    }
    
    /**
     * Process login attempt
     */
    public function processLogin() {
        try {
            // Rate limiting check
            if (!$this->checkLoginRateLimit()) {
                Session::setFlash('error', 'Trop de tentatives de connexion. Veuillez réessayer plus tard.');
                header('Location: /login');
                exit;
            }
            
            // Validate input
            $validator = new Validator();
            $rules = [
                'username' => 'required|string|max:50',
                'password' => 'required|string'
            ];
            
            $validation = $validator->validate($_POST, $rules);
            if (!$validation['valid']) {
                $this->logLoginAttempt($_POST['username'] ?? '', false);
                Session::setFlash('error', 'Nom d\'utilisateur et mot de passe requis.');
                header('Location: /login');
                exit;
            }
            
            $username = Security::sanitizeString($_POST['username']);
            $password = $_POST['password'];
            
            // Get user from database
            $user = $this->getUserByUsername($username);
            
            if (!$user) {
                $this->logLoginAttempt($username, false);
                Session::setFlash('error', 'Nom d\'utilisateur ou mot de passe incorrect.');
                header('Location: /login');
                exit;
            }
            
            // Check if account is locked
            if ($this->isAccountLocked($user)) {
                $this->logLoginAttempt($username, false);
                Session::setFlash('error', 'Compte temporairement verrouillé. Veuillez réessayer plus tard.');
                header('Location: /login');
                exit;
            }
            
            // Verify password
            if (!Security::verifyPassword($password, $user['password_hash'])) {
                $this->handleFailedLogin($user);
                Session::setFlash('error', 'Nom d\'utilisateur ou mot de passe incorrect.');
                header('Location: /login');
                exit;
            }
            
            // Check if account is active
            if (!$user['is_active']) {
                $this->logLoginAttempt($username, false);
                Session::setFlash('error', 'Compte désactivé. Contactez l\'administrateur.');
                header('Location: /login');
                exit;
            }
            
            // Successful login
            $this->handleSuccessfulLogin($user);
            
            // Redirect to intended page or dashboard
            $redirectUrl = Session::get('redirect_after_login', '/dashboard');
            Session::remove('redirect_after_login');
            
            header('Location: ' . $redirectUrl);
            exit;
            
        } catch (Exception $e) {
            Session::setFlash('error', 'Erreur lors de la connexion. Veuillez réessayer.');
            header('Location: /login');
            exit;
        }
    }
    
    /**
     * Process logout
     */
    public function logout() {
        try {
            $user = Session::getUser();
            
            if ($user) {
                // Log the logout
                AuthMiddleware::logActivity('logout', ['user_id' => $user['id']]);
                
                // Remove session from database
                $this->removeUserSession(session_id());
            }
            
            // Clear session
            Session::logout();
            Session::setFlash('success', 'Vous avez été déconnecté avec succès.');
            
            header('Location: /login');
            exit;
            
        } catch (Exception $e) {
            // Force logout anyway
            Session::destroy();
            header('Location: /login');
            exit;
        }
    }
    
    /**
     * Get user by username or email
     */
    private function getUserByUsername($username) {
        $stmt = $this->db->prepare("
            SELECT * FROM users 
            WHERE (username = ? OR email = ?) 
            AND is_active = 1
        ");
        $stmt->execute([$username, $username]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Check if account is locked
     */
    private function isAccountLocked($user) {
        if (!$user['locked_until']) {
            return false;
        }
        
        $lockedUntil = strtotime($user['locked_until']);
        $now = time();
        
        if ($now < $lockedUntil) {
            return true;
        }
        
        // Unlock account if lock period has passed
        $this->unlockAccount($user['id']);
        return false;
    }
    
    /**
     * Handle failed login attempt
     */
    private function handleFailedLogin($user) {
        $this->logLoginAttempt($user['username'], false);
        
        // Increment login attempts
        $attempts = $user['login_attempts'] + 1;
        $maxAttempts = (int)($_ENV['MAX_LOGIN_ATTEMPTS'] ?? 5);
        
        if ($attempts >= $maxAttempts) {
            // Lock account
            $lockDuration = (int)($_ENV['LOCKOUT_DURATION'] ?? 900); // 15 minutes
            $lockedUntil = date('Y-m-d H:i:s', time() + $lockDuration);
            
            $stmt = $this->db->prepare("
                UPDATE users 
                SET login_attempts = ?, locked_until = ? 
                WHERE id = ?
            ");
            $stmt->execute([$attempts, $lockedUntil, $user['id']]);
            
            Security::logSecurityEvent('Account locked due to failed login attempts', [
                'user_id' => $user['id'],
                'username' => $user['username'],
                'attempts' => $attempts
            ]);
        } else {
            $stmt = $this->db->prepare("
                UPDATE users 
                SET login_attempts = ? 
                WHERE id = ?
            ");
            $stmt->execute([$attempts, $user['id']]);
        }
    }
    
    /**
     * Handle successful login
     */
    private function handleSuccessfulLogin($user) {
        // Reset login attempts
        $stmt = $this->db->prepare("
            UPDATE users 
            SET login_attempts = 0, locked_until = NULL, last_login = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$user['id']]);
        
        // Log successful login
        $this->logLoginAttempt($user['username'], true);
        
        // Create session
        $this->createUserSession($user);
        
        // Set user in session
        Session::setUser([
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'full_name' => $user['full_name'],
            'role' => $user['role']
        ]);
        
        // Log activity
        AuthMiddleware::logActivity('login', ['user_id' => $user['id']]);
        
        Session::setFlash('success', 'Connexion réussie. Bienvenue ' . $user['full_name'] . '!');
    }
    
    /**
     * Unlock account
     */
    private function unlockAccount($userId) {
        $stmt = $this->db->prepare("
            UPDATE users 
            SET login_attempts = 0, locked_until = NULL 
            WHERE id = ?
        ");
        $stmt->execute([$userId]);
    }
    
    /**
     * Create user session in database
     */
    private function createUserSession($user) {
        $sessionId = session_id();
        $expiresAt = date('Y-m-d H:i:s', time() + (int)($_ENV['SESSION_LIFETIME'] ?? 7200));
        
        $stmt = $this->db->prepare("
            INSERT INTO user_sessions (id, user_id, ip_address, user_agent, expires_at) 
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            expires_at = VALUES(expires_at)
        ");
        $stmt->execute([
            $sessionId,
            $user['id'],
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null,
            $expiresAt
        ]);
    }
    
    /**
     * Remove user session from database
     */
    private function removeUserSession($sessionId) {
        $stmt = $this->db->prepare("DELETE FROM user_sessions WHERE id = ?");
        $stmt->execute([$sessionId]);
    }
    
    /**
     * Check login rate limiting
     */
    private function checkLoginRateLimit() {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $maxAttempts = 10;
        $timeWindow = 900; // 15 minutes
        
        // Clean old attempts
        $stmt = $this->db->prepare("
            DELETE FROM login_attempts 
            WHERE attempted_at < DATE_SUB(NOW(), INTERVAL ? SECOND)
        ");
        $stmt->execute([$timeWindow]);
        
        // Count recent attempts from this IP
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as attempts 
            FROM login_attempts 
            WHERE ip_address = ? 
            AND attempted_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
        ");
        $stmt->execute([$ip, $timeWindow]);
        $attempts = $stmt->fetch(PDO::FETCH_ASSOC)['attempts'];
        
        return $attempts < $maxAttempts;
    }
    
    /**
     * Log login attempt
     */
    private function logLoginAttempt($username, $success) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        $stmt = $this->db->prepare("
            INSERT INTO login_attempts (ip_address, username, success) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$ip, $username, $success ? 1 : 0]);
    }
    
    /**
     * Render login view
     */
    private function renderLogin($data = []) {
        extract($data);
        include APP_PATH . '/Views/auth/login.php';
    }
}