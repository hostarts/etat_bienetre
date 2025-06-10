<?php
// Transaction Month View
?>
<div class="page-header">
    <div class="page-title">
        <h1><i class="fas fa-file-invoice-dollar"></i> Transactions - <?= $monthYear ?></h1>
        <p><?= htmlspecialchars($client['name']) ?></p>
    </div>
    <div class="page-actions">
        <a href="/clients/<?= $client['id'] ?>/months" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Retour aux Mois
        </a>
        <button onclick="window.print()" class="btn btn-success">
            <i class="fas fa-print"></i> Imprimer
        </button>
    </div>
</div>

<!-- Monthly Summary -->
<div class="summary-cards">
    <div class="summary-card invoices">
        <div class="summary-icon">
            <i class="fas fa-file-invoice"></i>
        </div>
        <div class="summary-content">
            <h3><?= number_format($totals['invoice_total'], 2) ?> DA</h3>
            <p>Total Factures</p>
        </div>
    </div>
    
    <div class="summary-card returns">
        <div class="summary-icon">
            <i class="fas fa-undo"></i>
        </div>
        <div class="summary-content">
            <h3><?= number_format($totals['return_total'], 2) ?> DA</h3>
            <p>Total Retours</p>
        </div>
    </div>
    
    <div class="summary-card discount">
        <div class="summary-icon">
            <i class="fas fa-percent"></i>
        </div>
        <div class="summary-content">
            <h3><?= $discountPercent ?>%</h3>
            <p>Remise Appliquée</p>
        </div>
    </div>
    
    <div class="summary-card total">
        <div class="summary-icon">
            <i class="fas fa-calculator"></i>
        </div>
        <div class="summary-content">
            <h3><?= number_format($finalTotal, 2) ?> DA</h3>
            <p>Total Final</p>
        </div>
    </div>
</div>

<!-- Forms Grid -->
<div class="forms-grid">
    <!-- Invoice Form -->
    <?php include APP_PATH . '/Views/transactions/forms/invoice_form.php'; ?>
    
    <!-- Return Form -->
    <?php include APP_PATH . '/Views/transactions/forms/return_form.php'; ?>
    
    <!-- Discount Form -->
    <?php include APP_PATH . '/Views/transactions/forms/discount_form.php'; ?>
</div>

<!-- Transactions List -->
<?php if (!empty($transactions)): ?>
    <div class="transactions-section">
        <h3><i class="fas fa-list"></i> Liste des Transactions</h3>
        <div class="transactions-table">
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Numéro</th>
                        <th>Montant</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $transaction): ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($transaction['date'])) ?></td>
                            <td>
                                <span class="transaction-type <?= $transaction['type'] ?>">
                                    <i class="fas fa-<?= $transaction['type'] === 'invoice' ? 'file-invoice' : 'undo' ?>"></i>
                                    <?= $transaction['type'] === 'invoice' ? 'Facture' : 'Retour' ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($transaction['order_number']) ?></td>
                            <td class="amount <?= $transaction['type'] ?>">
                                <?= $transaction['type'] === 'return' ? '-' : '' ?><?= number_format($transaction['amount'], 2) ?> DA
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-danger" onclick="deleteTransaction(<?= $transaction['id'] ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php else: ?>
    <div class="no-transactions">
        <i class="fas fa-inbox"></i>
        <p>Aucune transaction pour ce mois</p>
    </div>
<?php endif; ?>

<style>
.summary-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.summary-card {
    background: white;
    padding: 1.5rem;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 1rem;
}

.summary-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: white;
}

.summary-card.invoices .summary-icon { background: #28a745; }
.summary-card.returns .summary-icon { background: #dc3545; }
.summary-card.discount .summary-icon { background: #ffc107; }
.summary-card.total .summary-icon { background: #007bff; }

.summary-content h3 {
    margin: 0;
    font-size: 1.5rem;
    color: #333;
}

.summary-content p {
    margin: 0;
    color: #666;
    font-size: 0.9rem;
}

.forms-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.transactions-section {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    overflow: hidden;
    margin-bottom: 2rem;
}

.transactions-section h3 {
    background: #f8f9fa;
    padding: 1.5rem;
    margin: 0;
    border-bottom: 1px solid #eee;
}

.transactions-table {
    overflow-x: auto;
}

.transaction-type {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.85rem;
    font-weight: 500;
}

.transaction-type.invoice {
    background: rgba(40, 167, 69, 0.1);
    color: #28a745;
}

.transaction-type.return {
    background: rgba(220, 53, 69, 0.1);
    color: #dc3545;
}

.amount.invoice {
    color: #28a745;
    font-weight: 600;
}

.amount.return {
    color: #dc3545;
    font-weight: 600;
}

.no-transactions {
    text-align: center;
    padding: 3rem;
    color: #666;
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.no-transactions i {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

@media (max-width: 768px) {
    .forms-grid {
        grid-template-columns: 1fr;
    }
    
    .summary-cards {
        grid-template-columns: 1fr 1fr;
    }
}

@media print {
    .page-actions,
    .forms-grid {
        display: none !important;
    }
}
</style>

<script>
function deleteTransaction(id) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette transaction ?')) {
        // Add AJAX delete functionality here
        console.log('Delete transaction:', id);
    }
}
</script>