<?php
/**
 * Contrôleur de base pour tous les contrôleurs
 * Fournit les fonctionnalités communes
 */
class BaseController {
    protected $db;
    protected $data = [];
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->initializeSession();
        $this->setupCSRF();
    }
    
    /**
     * Initialisation de la session
     */
    protected function initializeSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Régénération de l'ID de session pour la sécurité
        if (!isset($_SESSION['last_regeneration'])) {
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        } elseif (time() - $_SESSION['last_regeneration'] > 300) { // 5 minutes
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
    }
    
    /**
     * Configuration CSRF
     */
    protected function setupCSRF() {
        $this->data['csrf_token'] = Security::generateCSRFToken();
    }
    
    /**
     * Vérification de l'authentification (pour plus tard)
     */
    protected function requireAuth() {
        // Pour l'instant, on laisse passer
        // Plus tard, on ajoutera la vérification d'authentification
        return true;
    }
    
    /**
     * Validation du token CSRF
     */
    protected function validateCSRF() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['csrf_token'] ?? '';
            if (!Security::verifyCSRFToken($token)) {
                $this->error('Token de sécurité invalide', 403);
            }
        }
    }
    
    /**
     * Validation des données avec règles
     */
    protected function validate($data, $rules) {
        $validator = new Validator();
        $result = $validator->validate($data, $rules);
        
        if (!$result['valid']) {
            $this->error('Données invalides: ' . implode(', ', $result['errors']), 400);
        }
        
        return $result['data'];
    }
    
    /**
     * Rendu d'une vue
     */
    protected function render($view, $data = []) {
        // Fusion des données du contrôleur avec les données de la vue
        $this->data = array_merge($this->data, $data);
        
        // Extraction des variables pour la vue
        extract($this->data);
        
        // Définition du chemin de la vue
        $viewPath = dirname(__DIR__) . '/Views/' . $view . '.php';
        
        if (!file_exists($viewPath)) {
            throw new Exception("Vue non trouvée: {$view}");
        }
        
        // Démarrage du buffer de sortie
        ob_start();
        
        // Inclusion de la vue
        include $viewPath;
        
        // Récupération du contenu
        $content = ob_get_clean();
        
        // Inclusion du layout principal
        $this->renderLayout($content);
    }
    
    /**
     * Rendu du layout principal
     */
    protected function renderLayout($content) {
        $layoutPath = dirname(__DIR__) . '/Views/layouts/app.php';
        
        if (file_exists($layoutPath)) {
            // Les données sont déjà extraites dans render()
            extract($this->data);
            include $layoutPath;
        } else {
            echo $content;
        }
    }
    
    /**
     * Redirection avec message flash
     */
    protected function redirect($url, $message = null, $type = 'success') {
        if ($message) {
            $_SESSION['flash_message'] = $message;
            $_SESSION['flash_type'] = $type;
        }
        
        // Construction de l'URL complète
        $baseUrl = $this->getBaseUrl();
        $redirectUrl = $baseUrl . ltrim($url, '/');
        
        header("Location: {$redirectUrl}");
        exit;
    }
    
    /**
     * Retour JSON
     */
    protected function json($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Gestion des erreurs
     */
    protected function error($message, $statusCode = 500) {
        http_response_code($statusCode);
        
        if ($this->isAjaxRequest()) {
            $this->json(['error' => $message], $statusCode);
        } else {
            $this->render('errors/error', [
                'message' => $message,
                'code' => $statusCode
            ]);
        }
        exit;
    }
    
    /**
     * Vérification si la requête est AJAX
     */
    protected function isAjaxRequest() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    /**
     * Récupération de l'URL de base
     */
    protected function getBaseUrl() {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'];
        $script = $_SERVER['SCRIPT_NAME'];
        $path = dirname($script);
        
        return $protocol . $host . $path . '/';
    }
    
    /**
     * Récupération des messages flash
     */
    protected function getFlashMessage() {
        $message = $_SESSION['flash_message'] ?? null;
        $type = $_SESSION['flash_type'] ?? 'info';
        
        if ($message) {
            unset($_SESSION['flash_message'], $_SESSION['flash_type']);
            return ['message' => $message, 'type' => $type];
        }
        
        return null;
    }
    
    /**
     * Logging des actions
     */
    protected function logAction($action, $details = []) {
        $logFile = dirname(dirname(dirname(__FILE__))) . '/storage/logs/actions.log';
        $logDir = dirname($logFile);
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $detailsJson = json_encode($details);
        
        $logMessage = "[{$timestamp}] ACTION: {$action} - IP: {$ip} - Details: {$detailsJson}" . PHP_EOL;
        file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Pagination des résultats
     */
    protected function paginate($query, $params, $page, $perPage) {
        $offset = ($page - 1) * $perPage;
        
        // Comptage du total
        $countQuery = "SELECT COUNT(*) as total FROM ({$query}) as count_table";
        $totalResult = $this->db->fetch($countQuery, $params);
        $total = $totalResult['total'];
        
        // Requête avec limite
        $limitQuery = $query . " LIMIT {$offset}, {$perPage}";
        $results = $this->db->fetchAll($limitQuery, $params);
        
        return [
            'data' => $results,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => ceil($total / $perPage),
                'has_next' => $page < ceil($total / $perPage),
                'has_prev' => $page > 1
            ]
        ];
    }
}