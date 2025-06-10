<?php
// Client Months View - app/Views/clients/months.php
// This is a VIEW file, not a controller - it should only contain HTML and display logic
?>
<div class="page-header">
    <div class="page-title">
        <h1><i class="fas fa-calendar-alt"></i> Mois d'Activité</h1>
        <p><?= htmlspecialchars($client['name']) ?></p>
    </div>
    <div class="page-actions">
        <a href="/clients/<?= $client['id'] ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Détails Client
        </a>
        <a href="/clients" class="btn btn-outline-primary">
            <i class="fas fa-users"></i> Tous les Clients
        </a>
    </div>
</div>

<!-- Months Grid -->
<?php if (!empty($months)): ?>
    <div class="months-grid">
        <?php foreach ($months as $month): ?>
            <div class="month-card">
                <div class="month-header">
                    <h3><?= date('F Y', strtotime($month['month_year'] . '-01')) ?></h3>
                    <a href="/clients/<?= $client['id'] ?>/months/<?= $month['month_year'] ?>" class="btn btn-sm btn-primary">
                        <i class="fas fa-eye"></i> Voir Détails
                    </a>
                </div>
                
                <div class="month-stats">
                    <div class="stat-row">
                        <span class="stat-label">Factures:</span>
                        <span class="stat-value invoice"><?= number_format($month['total_invoices'], 2) ?> DA</span>
                    </div>
                    <div class="stat-row">
                        <span class="stat-label">Retours:</span>
                        <span class="stat-value return">-<?= number_format($month['total_returns'], 2) ?> DA</span>
                    </div>
                    <div class="stat-row">
                        <span class="stat-label">Sous-total:</span>
                        <span class="stat-value"><?= number_format($month['total_invoices'] - $month['total_returns'], 2) ?> DA</span>
                    </div>
                    <?php if ($month['discount_percent'] > 0): ?>
                        <div class="stat-row">
                            <span class="stat-label">Remise (<?= $month['discount_percent'] ?>%):</span>
                            <span class="stat-value discount">-<?= number_format(($month['total_invoices'] - $month['total_returns']) * ($month['discount_percent'] / 100), 2) ?> DA</span>
                        </div>
                    <?php endif; ?>
                    <div class="stat-row total">
                        <span class="stat-label">Total Final:</span>
                        <span class="stat-value"><?= number_format($month['final_total'], 2) ?> DA</span>
                    </div>
                </div>
                
                <div class="month-footer">
                    <small>
                        <?= $month['invoice_count'] ?> facture(s) • 
                        <?= $month['return_count'] ?> retour(s)
                    </small>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="empty-state">
        <div class="empty-icon">
            <i class="fas fa-calendar-alt"></i>
        </div>
        <h3 class="empty-title">Aucune activité trouvée</h3>
        <p class="empty-description">Ce client n'a pas encore d'activité enregistrée.</p>
        <div class="empty-action">
            <a href="/clients/<?= $client['id'] ?>/months/<?= date('Y-m') ?>" class="btn btn-primary">
                <i class="fas fa-plus"></i> Ajouter des Transactions
            </a>
        </div>
    </div>
<?php endif; ?>

<style>
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #eee;
}

.page-title h1 {
    margin: 0;
    color: #333;
}

.page-title p {
    margin: 0.5rem 0 0 0;
    color: #666;
}

.page-actions {
    display: flex;
    gap: 1rem;
}

.months-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.month-card {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    overflow: hidden;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.month-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
}

.month-header {
    background: #f8f9fa;
    padding: 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #eee;
}

.month-header h3 {
    margin: 0;
    color: #333;
    font-size: 1.2rem;
}

.month-stats {
    padding: 1.5rem;
}

.stat-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.75rem;
}

.stat-row:last-child {
    margin-bottom: 0;
}

.stat-row.total {
    padding-top: 0.75rem;
    border-top: 1px solid #eee;
    font-weight: 600;
}

.stat-label {
    color: #666;
    font-size: 0.95rem;
}

.stat-value {
    font-weight: 500;
    color: #333;
}

.stat-value.invoice {
    color: #28a745;
}

.stat-value.return {
    color: #dc3545;
}

.stat-value.discount {
    color: #ffc107;
}

.month-footer {
    background: #f8f9fa;
    padding: 1rem 1.5rem;
    border-top: 1px solid #eee;
    color: #666;
    font-size: 0.9rem;
}

.empty-state {
    text-align: center;
    padding: 3rem;
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.empty-icon {
    font-size: 4rem;
    color: #007bff;
    margin-bottom: 1rem;
}

.empty-title {
    margin: 0 0 1rem 0;
    color: #333;
}

.empty-description {
    margin: 0 0 2rem 0;
    color: #666;
}

.empty-action {
    margin-top: 1rem;
}

@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
    }
    
    .months-grid {
        grid-template-columns: 1fr;
    }
}
</style>