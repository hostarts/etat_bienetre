<?php
/**
 * Contrôleur pour la gestion des clients
 */
class ClientController extends BaseController {
    private $clientModel;
    private $transactionModel;
    
    public function __construct() {
        parent::__construct();
        $this->clientModel = new Client();
        $this->transactionModel = new Transaction();
    }
    
    /**
     * Liste des clients
     */
    public function index() {
        $this->requireAuth();
        
        try {
            $page = max(1, (int)($_GET['page'] ?? 1));
            $perPage = (int)($_ENV['ITEMS_PER_PAGE'] ?? 12);
            $search = $_GET['search'] ?? '';
            
            if ($search) {
                $clients = $this->clientModel->search($search, $perPage, $page);
            } else {
                $clients = $this->clientModel->paginate($page, $perPage);
            }
            
            $this->logAction('clients_list_view', ['page' => $page, 'search' => $search]);
            
            $this->render('clients/index', [
                'clients' => $clients['data'],
                'pagination' => $clients['pagination'],
                'search' => $search,
                'flash' => $this->getFlashMessage()
            ]);
            
        } catch (Exception $e) {
            $this->error('Erreur lors du chargement des clients: ' . $e->getMessage());
        }
    }
    
    /**
     * Affichage des mois pour un client
     */
    public function months($clientId) {
        $this->requireAuth();
        
        try {
            $client = $this->clientModel->findById($clientId);
            if (!$client) {
                $this->error('Client non trouvé', 404);
            }
            
            $page = max(1, (int)($_GET['page'] ?? 1));
            $perPage = (int)($_ENV['MONTHS_PER_PAGE'] ?? 12);
            
            // Récupération des mois avec pagination
            $months = $this->transactionModel->getClientMonths($clientId, $page, $perPage);
            
            $this->logAction('client_months_view', ['client_id' => $clientId, 'page' => $page]);
            
            $this->render('clients/months', [
                'client' => $client,
                'months' => $months['data'],
                'pagination' => $months['pagination'],
                'flash' => $this->getFlashMessage()
            ]);
            
        } catch (Exception $e) {
            $this->error('Erreur lors du chargement des mois: ' . $e->getMessage());
        }
    }
    
    /**
     * Création d'un nouveau client
     */
    public function create() {
        $this->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $this->render('clients/create', [
                'flash' => $this->getFlashMessage()
            ]);
            return;
        }
        
        $this->validateCSRF();
        
        try {
            $data = $this->validate($_POST, [
                'name' => 'required|string|max:255',
                'address' => 'string|max:500',
                'phone' => 'string|max:20',
                'email' => 'email|max:100'
            ]);
            
            // Vérification d'unicité du nom
            if ($this->clientModel->existsByName($data['name'])) {
                $this->redirect('/clients/create', 'Un client avec ce nom existe déjà', 'error');
            }
            
            $clientId = $this->clientModel->create($data);
            
            $this->logAction('client_created', ['client_id' => $clientId, 'name' => $data['name']]);
            
            if ($this->isAjaxRequest()) {
                $this->json(['success' => true, 'client_id' => $clientId]);
            } else {
                $this->redirect('/clients', 'Client créé avec succès');
            }
            
        } catch (Exception $e) {
            $message = 'Erreur lors de la création du client: ' . $e->getMessage();
            
            if ($this->isAjaxRequest()) {
                $this->json(['error' => $message], 500);
            } else {
                $this->redirect('/clients/create', $message, 'error');
            }
        }
    }
    
    /**
     * Modification d'un client
     */
    public function edit($clientId) {
        $this->requireAuth();
        
        try {
            $client = $this->clientModel->findById($clientId);
            if (!$client) {
                $this->error('Client non trouvé', 404);
            }
            
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $this->render('clients/edit', [
                    'client' => $client,
                    'flash' => $this->getFlashMessage()
                ]);
                return;
            }
            
            $this->validateCSRF();
            
            $data = $this->validate($_POST, [
                'name' => 'required|string|max:255',
                'address' => 'string|max:500',
                'phone' => 'string|max:20',
                'email' => 'email|max:100'
            ]);
            
            // Vérification d'unicité du nom (sauf pour le client actuel)
            if ($this->clientModel->existsByName($data['name'], $clientId)) {
                $this->redirect("/clients/edit/{$clientId}", 'Un client avec ce nom existe déjà', 'error');
            }
            
            $this->clientModel->update($clientId, $data);
            
            $this->logAction('client_updated', ['client_id' => $clientId, 'name' => $data['name']]);
            
            if ($this->isAjaxRequest()) {
                $this->json(['success' => true]);
            } else {
                $this->redirect('/clients', 'Client modifié avec succès');
            }
            
        } catch (Exception $e) {
            $message = 'Erreur lors de la modification du client: ' . $e->getMessage();
            
            if ($this->isAjaxRequest()) {
                $this->json(['error' => $message], 500);
            } else {
                $this->redirect('/clients', $message, 'error');
            }
        }
    }
    
    /**
     * Suppression d'un client
     */
    public function delete($clientId) {
        $this->requireAuth();
        $this->validateCSRF();
        
        try {
            $client = $this->clientModel->findById($clientId);
            if (!$client) {
                $this->error('Client non trouvé', 404);
            }
            
            // Vérification qu'il n'y a pas de transactions
            $hasTransactions = $this->transactionModel->clientHasTransactions($clientId);
            if ($hasTransactions) {
                $message = 'Impossible de supprimer ce client car il a des transactions associées';
                
                if ($this->isAjaxRequest()) {
                    $this->json(['error' => $message], 400);
                } else {
                    $this->redirect('/clients', $message, 'error');
                }
            }
            
            $this->clientModel->delete($clientId);
            
            $this->logAction('client_deleted', ['client_id' => $clientId, 'name' => $client['name']]);
            
            if ($this->isAjaxRequest()) {
                $this->json(['success' => true]);
            } else {
                $this->redirect('/clients', 'Client supprimé avec succès');
            }
            
        } catch (Exception $e) {
            $message = 'Erreur lors de la suppression du client: ' . $e->getMessage();
            
            if ($this->isAjaxRequest()) {
                $this->json(['error' => $message], 500);
            } else {
                $this->redirect('/clients', $message, 'error');
            }
        }
    }
    
    /**
     * Détails d'un client
     */
    public function show($clientId) {
        $this->requireAuth();
        
        try {
            $client = $this->clientModel->findById($clientId);
            if (!$client) {
                $this->error('Client non trouvé', 404);
            }
            
            // Statistiques du client
            $stats = $this->transactionModel->getClientStats($clientId);
            
            // Transactions récentes
            $recentTransactions = $this->transactionModel->getClientRecentTransactions($clientId, 10);
            
            // Mois d'activité
            $activeMonths = $this->transactionModel->getClientActiveMonths($clientId);
            
            $this->logAction('client_detail_view', ['client_id' => $clientId]);
            
            $this->render('clients/show', [
                'client' => $client,
                'stats' => $stats,
                'recent_transactions' => $recentTransactions,
                'active_months' => $activeMonths,
                'flash' => $this->getFlashMessage()
            ]);
            
        } catch (Exception $e) {
            $this->error('Erreur lors du chargement du client: ' . $e->getMessage());
        }
    }
    
    /**
     * Export des clients
     */
    public function export($format = 'csv') {
        $this->requireAuth();
        
        try {
            $clients = $this->clientModel->getAllWithStats();
            
            switch ($format) {
                case 'csv':
                    $this->exportCSV($clients);
                    break;
                case 'json':
                    $this->exportJSON($clients);
                    break;
                default:
                    throw new Exception('Format d\'export invalide');
            }
            
        } catch (Exception $e) {
            $this->error('Erreur lors de l\'export: ' . $e->getMessage());
        }
    }
    
    /**
     * Export CSV des clients
     */
    private function exportCSV($clients) {
        $filename = 'clients_' . date('Y-m-d_H-i-s') . '.csv';
        
        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename={$filename}");
        
        $output = fopen('php://output', 'w');
        
        // En-têtes
        fputcsv($output, [
            'ID',
            'Nom',
            'Adresse',
            'Téléphone',
            'Email',
            'Total Transactions',
            'Chiffre d\'Affaires',
            'Dernière Transaction',
            'Date Création'
        ]);
        
        // Données
        foreach ($clients as $client) {
            fputcsv($output, [
                $client['id'],
                $client['name'],
                $client['address'],
                $client['phone'],
                $client['email'],
                $client['total_transactions'] ?? 0,
                $client['total_amount'] ?? 0,
                $client['last_transaction'] ?? '',
                $client['created_at']
            ]);
        }
        
        fclose($output);
        
        $this->logAction('clients_export_csv', ['count' => count($clients)]);
        exit;
    }
    
    /**
     * Export JSON des clients
     */
    private function exportJSON($clients) {
        $filename = 'clients_' . date('Y-m-d_H-i-s') . '.json';
        
        header('Content-Type: application/json; charset=utf-8');
        header("Content-Disposition: attachment; filename={$filename}");
        
        echo json_encode([
            'export_date' => date('Y-m-d H:i:s'),
            'total_clients' => count($clients),
            'clients' => $clients
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        $this->logAction('clients_export_json', ['count' => count($clients)]);
        exit;
    }
    
    /**
     * API pour l'autocomplétion
     */
    public function autocomplete() {
        $this->requireAuth();
        
        $query = $_GET['q'] ?? '';
        
        if (strlen($query) < 2) {
            $this->json([]);
        }
        
        try {
            $clients = $this->clientModel->searchForAutocomplete($query);
            $this->json($clients);
        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Statistiques d'un client en AJAX
     */
    public function stats($clientId) {
        $this->requireAuth();
        
        try {
            $stats = $this->transactionModel->getClientStats($clientId);
            $this->json($stats);
        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }
}