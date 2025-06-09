<?php
/**
 * Transaction Controller
 * Handles invoices, returns, and discounts
 */
class TransactionController {
    
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Show month transactions for a client
     */
    public function showMonth($clientId, $monthYear) {
        try {
            // Get client
            $stmt = $this->db->prepare("SELECT * FROM clients WHERE id = ? AND deleted_at IS NULL");
            $stmt->execute([$clientId]);
            $client = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$client) {
                http_response_code(404);
                require_once APP_PATH . '/Views/errors/404.php';
                return;
            }
            
            // Validate month format
            if (!preg_match('/^\d{4}-\d{2}$/', $monthYear)) {
                Session::setFlash('error', 'Format de mois invalide.');
                header('Location: /clients/' . $clientId . '/months');
                exit;
            }
            
            // Get transactions for the month
            $transactions = $this->getMonthTransactions($clientId, $monthYear);
            
            // Get discount information
            $discount = $this->getMonthDiscount($clientId, $monthYear);
            
            // Calculate totals
            $totals = $this->calculateTotals($transactions, $discount);
            
            $data = [
                'title' => 'Factures - ' . $client['name'] . ' - ' . $monthYear,
                'currentRoute' => 'clients/' . $clientId . '/months/' . $monthYear,
                'client' => $client,
                'currentClient' => $client,
                'currentMonth' => $monthYear,
                'monthYear' => $monthYear,
                'transactions' => $transactions,
                'totals' => $totals,
                'discountPercent' => $discount['percent'] ?? 0,
                'discountAmount' => $totals['discount_amount'],
                'monthTotal' => $totals['subtotal'],
                'finalTotal' => $totals['total'],
                'csrf_token' => CSRFMiddleware::generateToken(),
                'flash' => Session::getFlash()
            ];
            
            $this->render('transactions/month', $data);
            
        } catch (Exception $e) {
            Session::setFlash('error', 'Erreur lors du chargement des transactions.');
            header('Location: /clients/' . $clientId . '/months');
            exit;
        }
    }
    
    /**
     * Store new invoice
     */
    public function storeInvoice() {
        try {
            // Validate input
            $validator = new Validator();
            $rules = [
                'client_id' => 'required|integer',
                'month_year' => 'required|string',
                'order_number' => 'required|string|max:100',
                'amount' => 'required|numeric|min:0.01',
                'date' => 'required|date'
            ];
            
            $validation = $validator->validate($_POST, $rules);
            if (!$validation['valid']) {
                Session::setFlash('error', implode(', ', $validation['errors']));
                header('Location: /clients/' . $_POST['client_id'] . '/months/' . $_POST['month_year']);
                exit;
            }
            
            // Check for duplicate order number
            $stmt = $this->db->prepare("
                SELECT id FROM transactions 
                WHERE order_number = ? AND type = 'invoice' AND deleted_at IS NULL
            ");
            $stmt->execute([$_POST['order_number']]);
            if ($stmt->fetch()) {
                Session::setFlash('error', 'Une facture avec ce numéro de commande existe déjà.');
                header('Location: /clients/' . $_POST['client_id'] . '/months/' . $_POST['month_year']);
                exit;
            }
            
            // Insert transaction
            $stmt = $this->db->prepare("
                INSERT INTO transactions (client_id, type, order_number, amount, date, created_at) 
                VALUES (?, 'invoice', ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $_POST['client_id'],
                Security::sanitizeString($_POST['order_number']),
                floatval($_POST['amount']),
                $_POST['date']
            ]);
            
            Session::setFlash('success', 'Facture ajoutée avec succès.');
            header('Location: /clients/' . $_POST['client_id'] . '/months/' . $_POST['month_year']);
            exit;
            
        } catch (Exception $e) {
            Session::setFlash('error', 'Erreur lors de l\'ajout de la facture.');
            header('Location: /clients/' . ($_POST['client_id'] ?? '') . '/months/' . ($_POST['month_year'] ?? ''));
            exit;
        }
    }
    
    /**
     * Store new return
     */
    public function storeReturn() {
        try {
            // Validate input
            $validator = new Validator();
            $rules = [
                'client_id' => 'required|integer',
                'month_year' => 'required|string',
                'order_number' => 'required|string|max:100',
                'amount' => 'required|numeric|min:0.01',
                'date' => 'required|date'
            ];
            
            $validation = $validator->validate($_POST, $rules);
            if (!$validation['valid']) {
                Session::setFlash('error', implode(', ', $validation['errors']));
                header('Location: /clients/' . $_POST['client_id'] . '/months/' . $_POST['month_year']);
                exit;
            }
            
            // Check for duplicate order number
            $stmt = $this->db->prepare("
                SELECT id FROM transactions 
                WHERE order_number = ? AND type = 'return' AND deleted_at IS NULL
            ");
            $stmt->execute([$_POST['order_number']]);
            if ($stmt->fetch()) {
                Session::setFlash('error', 'Un retour avec ce numéro existe déjà.');
                header('Location: /clients/' . $_POST['client_id'] . '/months/' . $_POST['month_year']);
                exit;
            }
            
            // Insert transaction
            $stmt = $this->db->prepare("
                INSERT INTO transactions (client_id, type, order_number, amount, date, created_at) 
                VALUES (?, 'return', ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $_POST['client_id'],
                Security::sanitizeString($_POST['order_number']),
                floatval($_POST['amount']),
                $_POST['date']
            ]);
            
            Session::setFlash('success', 'Retour ajouté avec succès.');
            header('Location: /clients/' . $_POST['client_id'] . '/months/' . $_POST['month_year']);
            exit;
            
        } catch (Exception $e) {
            Session::setFlash('error', 'Erreur lors de l\'ajout du retour.');
            header('Location: /clients/' . ($_POST['client_id'] ?? '') . '/months/' . ($_POST['month_year'] ?? ''));
            exit;
        }
    }
    
    /**
     * Update discount for a month
     */
    public function updateDiscount() {
        try {
            // Validate input
            $validator = new Validator();
            $rules = [
                'client_id' => 'required|integer',
                'month_year' => 'required|string',
                'discount_percent' => 'required|numeric|min:0|max:100'
            ];
            
            $validation = $validator->validate($_POST, $rules);
            if (!$validation['valid']) {
                Session::setFlash('error', implode(', ', $validation['errors']));
                header('Location: /clients/' . $_POST['client_id'] . '/months/' . $_POST['month_year']);
                exit;
            }
            
            $discountPercent = floatval($_POST['discount_percent']);
            
            // Check if discount record exists
            $stmt = $this->db->prepare("
                SELECT id FROM client_discounts 
                WHERE client_id = ? AND month_year = ?
            ");
            $stmt->execute([$_POST['client_id'], $_POST['month_year']]);
            $existingDiscount = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existingDiscount) {
                // Update existing discount
                $stmt = $this->db->prepare("
                    UPDATE client_discounts 
                    SET discount_percent = ?, updated_at = NOW() 
                    WHERE id = ?
                ");
                $stmt->execute([$discountPercent, $existingDiscount['id']]);
            } else {
                // Insert new discount
                $stmt = $this->db->prepare("
                    INSERT INTO client_discounts (client_id, month_year, discount_percent, created_at) 
                    VALUES (?, ?, ?, NOW())
                ");
                $stmt->execute([$_POST['client_id'], $_POST['month_year'], $discountPercent]);
            }
            
            $message = $discountPercent > 0 
                ? "Remise de {$discountPercent}% appliquée avec succès."
                : "Remise supprimée avec succès.";
            
            Session::setFlash('success', $message);
            header('Location: /clients/' . $_POST['client_id'] . '/months/' . $_POST['month_year']);
            exit;
            
        } catch (Exception $e) {
            Session::setFlash('error', 'Erreur lors de la mise à jour de la remise.');
            header('Location: /clients/' . ($_POST['client_id'] ?? '') . '/months/' . ($_POST['month_year'] ?? ''));
            exit;
        }
    }
    
    /**
     * Get transactions for a specific month
     */
    private function getMonthTransactions($clientId, $monthYear) {
        $stmt = $this->db->prepare("
            SELECT * FROM transactions 
            WHERE client_id = ? 
            AND DATE_FORMAT(date, '%Y-%m') = ?
            AND deleted_at IS NULL
            ORDER BY date DESC, created_at DESC
        ");
        $stmt->execute([$clientId, $monthYear]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get discount for a specific month
     */
    private function getMonthDiscount($clientId, $monthYear) {
        $stmt = $this->db->prepare("
            SELECT * FROM client_discounts 
            WHERE client_id = ? AND month_year = ?
        ");
        $stmt->execute([$clientId, $monthYear]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['percent' => 0];
    }
    
    /**
     * Calculate totals including discount
     */
    private function calculateTotals($transactions, $discount) {
        $invoiceTotal = 0;
        $returnTotal = 0;
        
        foreach ($transactions as $transaction) {
            if ($transaction['type'] === 'invoice') {
                $invoiceTotal += $transaction['amount'];
            } else {
                $returnTotal += $transaction['amount'];
            }
        }
        
        $subtotal = $invoiceTotal - $returnTotal;
        $discountPercent = $discount['percent'] ?? 0;
        $discountAmount = $subtotal * ($discountPercent / 100);
        $total = $subtotal - $discountAmount;
        
        return [
            'invoice_total' => $invoiceTotal,
            'return_total' => $returnTotal,
            'subtotal' => $subtotal,
            'discount_percent' => $discountPercent,
            'discount_amount' => $discountAmount,
            'total' => $total
        ];
    }
    
    /**
     * Render view with layout
     */
    private function render($view, $data = []) {
        extract($data);
        ob_start();
        
        $viewFile = APP_PATH . '/Views/' . str_replace('.', '/', $view) . '.php';
        if (file_exists($viewFile)) {
            include $viewFile;
        } else {
            throw new Exception("View file not found: {$view}");
        }
        
        $content = ob_get_clean();
        include APP_PATH . '/Views/layouts/app.php';
    }
}