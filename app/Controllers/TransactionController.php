<?php
/**
 * Contrôleur pour la gestion des transactions
 */
class TransactionController extends BaseController {
    private $transactionModel;
    private $clientModel;
    private $discountModel;
    
    public function __construct() {
        parent::__construct();
        $this->transactionModel = new Transaction();
        $this->clientModel = new Client();
        $this->discountModel = new Discount();
    }
    
    /**
     * Gestion des factures pour un client et un mois
     */
    public function index($clientId, $month) {
        $this->requireAuth();
        
        try {
            // Validation du client
            $client = $this->clientModel->findById($clientId);
            if (!$client) {
                $this->error('Client non trouvé', 404);
            }
            
            // Validation du format du mois
            if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
                $this->error('Format de mois invalide', 400);
            }
            
            // Récupération des transactions
            $transactions = $this->transactionModel->getByClientAndMonth($clientId, $month);
            
            // Calcul du total
            $monthTotal = $this->transactionModel->getClientMonthTotal($clientId, $month);
            
            // Récupération de la remise
            $discount = $this->discountModel->getByClientAndMonth($clientId, $month);
            $discountPercent = $discount['discount_percent'] ?? 0;
            $discountAmount = ($monthTotal * $discountPercent) / 100;
            $finalTotal = $monthTotal - $discountAmount;
            
            $this->logAction('transactions_view', [
                'client_id' => $clientId,
                'month' => $month,
                'total' => $finalTotal
            ]);
            
            $this->render('transactions/index', [
                'client' => $client,
                'month' => $month,
                'transactions' => $transactions,
                'month_total' => $monthTotal,
                'discount_percent' => $discountPercent,
                'discount_amount' => $discountAmount,
                'final_total' => $finalTotal,
                'flash' => $this->getFlashMessage()
            ]);
            
        } catch (Exception $e) {
            $this->error('Erreur lors du chargement des transactions: ' . $e->getMessage());
        }
    }
    
    /**
     * Ajout d'une facture
     */
    public function addInvoice() {
        $this->requireAuth();
        $this->validateCSRF();
        
        try {
            $data = $this->validate($_POST, [
                'client_id' => 'required|integer',
                'order_number' => 'required|string|max:100',
                'amount' => 'required|numeric|min:0.01',
                'date' => 'required|date',
                'month_year' => 'required|string'
            ]);
            
            // Validation du client
            $client = $this->clientModel->findById($data['client_id']);
            if (!$client) {
                throw new Exception('Client non trouvé');
            }
            
            // Vérification d'unicité du numéro de commande
            if ($this->transactionModel->orderNumberExists($data['order_number'], $data['client_id'])) {
                throw new Exception('Ce numéro de commande existe déjà pour ce client');
            }
            
            // Ajout de la transaction
            $transactionId = $this->transactionModel->createInvoice(
                $data['client_id'],
                $data['order_number'],
                $data['amount'],
                $data['date'],
                $data['month_year']
            );
            
            $this->logAction('invoice_created', [
                'transaction_id' => $transactionId,
                'client_id' => $data['client_id'],
                'amount' => $data['amount'],
                'order_number' => $data['order_number']
            ]);
            
            if ($this->isAjaxRequest()) {
                $this->json(['success' => true, 'transaction_id' => $transactionId]);
            } else {
                $this->redirect(
                    "/transactions/{$data['client_id']}/{$data['month_year']}",
                    'Facture ajoutée avec succès'
                );
            }
            
        } catch (Exception $e) {
            $message = 'Erreur lors de l\'ajout de la facture: ' . $e->getMessage();
            
            if ($this->isAjaxRequest()) {
                $this->json(['error' => $message], 500);
            } else {
                $clientId = $_POST['client_id'] ?? '';
                $monthYear = $_POST['month_year'] ?? '';
                $this->redirect("/transactions/{$clientId}/{$monthYear}", $message, 'error');
            }
        }
    }
    
    /**
     * Ajout d'un retour
     */
    public function addReturn() {
        $this->requireAuth();
        $this->validateCSRF();
        
        try {
            $data = $this->validate($_POST, [
                'client_id' => 'required|integer',
                'order_number' => 'required|string|max:100',
                'amount' => 'required|numeric|min:0.01',
                'date' => 'required|date',
                'month_year' => 'required|string'
            ]);
            
            // Validation du client
            $client = $this->clientModel->findById($data['client_id']);
            if (!$client) {
                throw new Exception('Client non trouvé');
            }
            
            // Ajout du retour (montant négatif)
            $transactionId = $this->transactionModel->createReturn(
                $data['client_id'],
                $data['order_number'],
                $data['amount'], // Le modèle se charge de mettre le montant en négatif
                $data['date'],
                $data['month_year']
            );
            
            $this->logAction('return_created', [
                'transaction_id' => $transactionId,
                'client_id' => $data['client_id'],
                'amount' => $data['amount'],
                'order_number' => $data['order_number']
            ]);
            
            if ($this->isAjaxRequest()) {
                $this->json(['success' => true, 'transaction_id' => $transactionId]);
            } else {
                $this->redirect(
                    "/transactions/{$data['client_id']}/{$data['month_year']}",
                    'Retour ajouté avec succès'
                );
            }
            
        } catch (Exception $e) {
            $message = 'Erreur lors de l\'ajout du retour: ' . $e->getMessage();
            
            if ($this->isAjaxRequest()) {
                $this->json(['error' => $message], 500);
            } else {
                $clientId = $_POST['client_id'] ?? '';
                $monthYear = $_POST['month_year'] ?? '';
                $this->redirect("/transactions/{$clientId}/{$monthYear}", $message, 'error');
            }
        }
    }
    
    /**
     * Suppression d'une transaction
     */
    public function delete($transactionId) {
        $this->requireAuth();
        $this->validateCSRF();
        
        try {
            $transaction = $this->transactionModel->findById($transactionId);
            if (!$transaction) {
                $this->error('Transaction non trouvée', 404);
            }
            
            $this->transactionModel->delete($transactionId);
            
            $this->logAction('transaction_deleted', [
                'transaction_id' => $transactionId,
                'client_id' => $transaction['client_id'],
                'amount' => $transaction['amount'],
                'order_number' => $transaction['order_number']
            ]);
            
            if ($this->isAjaxRequest()) {
                $this->json(['success' => true]);
            } else {
                $this->redirect(
                    "/transactions/{$transaction['client_id']}/{$transaction['month_year']}",
                    'Transaction supprimée avec succès'
                );
            }
            
        } catch (Exception $e) {
            $message = 'Erreur lors de la suppression: ' . $e->getMessage();
            
            if ($this->isAjaxRequest()) {
                $this->json(['error' => $message], 500);
            } else {
                $this->redirect('/clients', $message, 'error');
            }
        }
    }
    
    /**
     * Gestion des remises
     */
    public function setDiscount() {
        $this->requireAuth();
        $this->validateCSRF();
        
        try {
            $data = $this->validate($_POST, [
                'client_id' => 'required|integer',
                'month_year' => 'required|string',
                'discount_percent' => 'required|numeric|min:0|max:100'
            ]);
            
            // Validation du client
            $client = $this->clientModel->findById($data['client_id']);
            if (!$client) {
                throw new Exception('Client non trouvé');
            }
            
            // Mise à jour ou création de la remise
            $this->discountModel->setDiscount(
                $data['client_id'],
                $data['month_year'],
                $data['discount_percent']
            );
            
            $this->logAction('discount_set', [
                'client_id' => $data['client_id'],
                'month_year' => $data['month_year'],
                'discount_percent' => $data['discount_percent']
            ]);
            
            $message = $data['discount_percent'] > 0 
                ? "Remise de {$data['discount_percent']}% appliquée"
                : 'Remise supprimée';
            
            if ($this->isAjaxRequest()) {
                $this->json(['success' => true, 'message' => $message]);
            } else {
                $this->redirect(
                    "/transactions/{$data['client_id']}/{$data['month_year']}",
                    $message
                );
            }
            
        } catch (Exception $e) {
            $message = 'Erreur lors de la gestion de la remise: ' . $e->getMessage();
            
            if ($this->isAjaxRequest()) {
                $this->json(['error' => $message], 500);
            } else {
                $clientId = $_POST['client_id'] ?? '';
                $monthYear = $_POST['month_year'] ?? '';
                $this->redirect("/transactions/{$clientId}/{$monthYear}", $message, 'error');
            }
        }
    }
    
    /**
     * Export des transactions
     */
    public function export($clientId, $month, $format = 'pdf') {
        $this->requireAuth();
        
        try {
            $client = $this->clientModel->findById($clientId);
            if (!$client) {
                $this->error('Client non trouvé', 404);
            }
            
            $transactions = $this->transactionModel->getByClientAndMonth($clientId, $month);
            $monthTotal = $this->transactionModel->getClientMonthTotal($clientId, $month);
            $discount = $this->discountModel->getByClientAndMonth($clientId, $month);
            
            switch ($format) {
                case 'pdf':
                    $this->exportPDF($client, $month, $transactions, $monthTotal, $discount);
                    break;
                case 'csv':
                    $this->exportCSV($client, $month, $transactions, $monthTotal, $discount);
                    break;
                default:
                    throw new Exception('Format d\'export invalide');
            }
            
        } catch (Exception $e) {
            $this->error('Erreur lors de l\'export: ' . $e->getMessage());
        }
    }
    
    /**
     * Export PDF (imprimable)
     */
    private function exportPDF($client, $month, $transactions, $monthTotal, $discount) {
        // Pour l'instant, on génère une page HTML imprimable
        // Plus tard, on pourra intégrer une librairie PDF
        
        $discountPercent = $discount['discount_percent'] ?? 0;
        $discountAmount = ($monthTotal * $discountPercent) / 100;
        $finalTotal = $monthTotal - $discountAmount;
        
        $this->render('transactions/print', [
            'client' => $client,
            'month' => $month,
            'transactions' => $transactions,
            'month_total' => $monthTotal,
            'discount_percent' => $discountPercent,
            'discount_amount' => $discountAmount,
            'final_total' => $finalTotal,
            'print_date' => date('d/m/Y H:i')
        ]);
    }
    
    /**
     * Export CSV
     */
    private function exportCSV($client, $month, $transactions, $monthTotal, $discount) {
        $filename = "factures_{$client['name']}_{$month}.csv";
        $filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $filename);
        
        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename={$filename}");
        
        $output = fopen('php://output', 'w');
        
        // En-têtes
        fputcsv($output, ['Client', $client['name']]);
        fputcsv($output, ['Période', $month]);
        fputcsv($output, ['Date Export', date('d/m/Y H:i')]);
        fputcsv($output, []);
        
        fputcsv($output, ['N° Commande', 'Date', 'Type', 'Montant']);
        
        foreach ($transactions as $transaction) {
            fputcsv($output, [
                $transaction['order_number'],
                date('d/m/Y', strtotime($transaction['transaction_date'])),
                $transaction['type'] === 'invoice' ? 'Facture' : 'Retour',
                $transaction['amount']
            ]);
        }
        
        fputcsv($output, []);
        fputcsv($output, ['Total', '', '', $monthTotal]);
        
        if ($discount['discount_percent'] ?? 0 > 0) {
            fputcsv($output, ['Remise (' . $discount['discount_percent'] . '%)', '', '', -($monthTotal * $discount['discount_percent'] / 100)]);
            fputcsv($output, ['Total Final', '', '', $monthTotal - ($monthTotal * $discount['discount_percent'] / 100)]);
        }
        
        fclose($output);
        
        $this->logAction('transactions_export_csv', [
            'client_id' => $client['id'],
            'month' => $month,
            'count' => count($transactions)
        ]);
        
        exit;
    }
    
    /**
     * API pour les statistiques des transactions
     */
    public function stats($clientId, $month) {
        $this->requireAuth();
        
        try {
            $stats = [
                'total' => $this->transactionModel->getClientMonthTotal($clientId, $month),
                'count' => $this->transactionModel->getClientMonthTransactionCount($clientId, $month),
                'invoices' => $this->transactionModel->getClientMonthInvoicesTotal($clientId, $month),
                'returns' => $this->transactionModel->getClientMonthReturnsTotal($clientId, $month),
                'discount' => $this->discountModel->getByClientAndMonth($clientId, $month)
            ];
            
            $this->json($stats);
        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }
}