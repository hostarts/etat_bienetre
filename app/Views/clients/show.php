<?php
// Client Details View
?>
<div class="page-header">
    <div class="page-title">
        <h1><i class="fas fa-user"></i> <?= htmlspecialchars($client['name']) ?></h1>
        <p>Détails et statistiques du client</p>
    </div>
    <div class="page-actions">
        <a href="/clients" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Retour à la liste
        </a>
        <a href="/clients/<?= $client['id'] ?>/months" class="btn btn-primary">
            <i class="fas fa-calendar-alt"></i> Voir les mois
        </a>
    </div>
</div>

<!-- Client Information Card -->
<div class="client-details-grid">
    <div class="client-info-card">
        <h3><i class="fas fa-info-circle"></i> Informations Client</h3>
        <div class="info-grid">
            <div class="info-item">
                <label>Nom:</label>
                <span><?= htmlspecialchars($client['name']) ?></span>
            </div>
            <div class="info-item">
                <label>Adresse:</label>
                <span><?= htmlspecialchars($client['address']) ?></span>
            </div>
            <div class="info-item">
                <label>Téléphone:</label>
                <span><?= htmlspecialchars($client['phone']) ?></span>
            </div>
            <?php if (!empty($client['email'])): ?>
                <div class="info-item">
                    <label>Email:</label>
                    <span><?= htmlspecialchars($client['email']) ?></span>
                </div>
            <?php endif; ?>
            <div class="info-item">
                <label>Client depuis:</label>
                <span><?= date('d/m/Y', strtotime($client['created_at'])) ?></span>
            </div>
        </div>
    </div>

    <!-- Statistics Card -->
    <div class="client-stats-card">
        <h3><i class="fas fa-chart-bar"></i> Statistiques</h3>
        <div class="stats-grid">
            <div class="stat-item">
                <div class="stat-value"><?= number_format($stats['total_invoices'], 2) ?> DA</div>
                <div class="stat-label">Total Factures</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?= number_format($stats['total_returns'], 2) ?> DA</div>
                <div class="stat-label">Total Retours</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?= number_format($stats['net_total'], 2) ?> DA</div>
                <div class="stat-label">Chiffre d'Affaires Net</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?= $stats['invoice_count'] ?></div>
                <div class="stat-label">Nombre de Factures</div>
            </div>
        </div>
    </div>
</div>

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

.client-details-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
    margin-bottom: 2rem;
}

.client-info-card,
.client-stats-card {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    overflow: hidden;
}

.client-info-card h3,
.client-stats-card h3 {
    background: #f8f9fa;
    padding: 1.5rem;
    margin: 0;
    border-bottom: 1px solid #eee;
    color: #333;
}

.info-grid {
    padding: 1.5rem;
}

.info-item {
    display: grid;
    grid-template-columns: 120px 1fr;
    gap: 1rem;
    margin-bottom: 1rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #f0f0f0;
}

.info-item:last-child {
    margin-bottom: 0;
    padding-bottom: 0;
    border-bottom: none;
}

.info-item label {
    font-weight: 600;
    color: #555;
}

.info-item span {
    color: #333;
}

.stats-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    padding: 1.5rem;
}

.stat-item {
    text-align: center;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 8px;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: #007bff;
    margin-bottom: 0.5rem;
}

.stat-label {
    font-size: 0.9rem;
    color: #666;
}

@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
    }
    
    .client-details-grid {
        grid-template-columns: 1fr;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
}
</style>