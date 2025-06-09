<?php
// Dashboard View
?>
<div class="dashboard-header">
    <h1>
        <i class="fas fa-chart-line"></i>
        Tableau de Bord
    </h1>
    <p class="dashboard-subtitle">Aperçu de l'activité commerciale</p>
</div>

<!-- Statistics Cards -->
<div class="stats-grid">
    <div class="stat-card primary">
        <div class="stat-icon">
            <i class="fas fa-users"></i>
        </div>
        <div class="stat-content">
            <h3><?= number_format($stats['total_clients']) ?></h3>
            <p>Clients Actifs</p>
        </div>
    </div>
    
    <div class="stat-card success">
        <div class="stat-icon">
            <i class="fas fa-euro-sign"></i>
        </div>
        <div class="stat-content">
            <h3><?= number_format($stats['current_month']['revenue'], 2) ?> DA</h3>
            <p>CA du Mois</p>
        </div>
    </div>
    
    <div class="stat-card info">
        <div class="stat-icon">
            <i class="fas fa-file-invoice"></i>
        </div>
        <div class="stat-content">
            <h3><?= number_format($stats['current_month']['invoice_count']) ?></h3>
            <p>Factures ce Mois</p>
        </div>
    </div>
    
    <div class="stat-card warning">
        <div class="stat-icon">
            <i class="fas fa-undo"></i>
        </div>
        <div class="stat-content">
            <h3><?= number_format($stats['current_month']['returns'], 2) ?> DA</h3>
            <p>Retours ce Mois</p>
        </div>
    </div>
</div>

<!-- Main Content Grid -->
<div class="dashboard-grid">
    <!-- Recent Clients -->
    <div class="dashboard-card">
        <div class="card-header">
            <h3><i class="fas fa-users"></i> Clients Récents</h3>
            <a href="/clients" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-eye"></i> Voir Tous
            </a>
        </div>
        <div class="card-content">
            <?php if (!empty($stats['recent_clients'])): ?>
                <div class="client-list">
                    <?php foreach ($stats['recent_clients'] as $client): ?>
                        <div class="client-item">
                            <div class="client-info">
                                <h4><?= htmlspecialchars($client['name']) ?></h4>
                                <small>Ajouté le <?= date('d/m/Y', strtotime($client['created_at'])) ?></small>
                            </div>
                            <a href="/clients/<?= $client['id'] ?>" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-eye"></i>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-users"></i>
                    <p>Aucun client récent</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Recent Transactions -->
    <div class="dashboard-card">
        <div class="card-header">
            <h3><i class="fas fa-receipt"></i> Transactions Récentes</h3>
        </div>
        <div class="card-content">
            <?php if (!empty($stats['recent_transactions'])): ?>
                <div class="transaction-list">
                    <?php foreach (array_slice($stats['recent_transactions'], 0, 5) as $transaction): ?>
                        <div class="transaction-item">
                            <div class="transaction-info">
                                <div class="transaction-type">
                                    <i class="fas fa-<?= $transaction['type'] === 'invoice' ? 'file-invoice' : 'undo' ?>"></i>
                                    <?= ucfirst($transaction['type'] === 'invoice' ? 'Facture' : 'Retour') ?>
                                </div>
                                <div class="transaction-details">
                                    <strong><?= htmlspecialchars($transaction['client_name']) ?></strong>
                                    <small><?= htmlspecialchars($transaction['order_number']) ?></small>
                                </div>
                            </div>
                            <div class="transaction-amount <?= $transaction['type'] ?>">
                                <?= $transaction['type'] === 'return' ? '-' : '' ?><?= number_format($transaction['amount'], 2) ?> DA
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-receipt"></i>
                    <p>Aucune transaction récente</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Monthly Trends -->
    <div class="dashboard-card full-width">
        <div class="card-header">
            <h3><i class="fas fa-chart-bar"></i> Tendances Mensuelles</h3>
        </div>
        <div class="card-content">
            <?php if (!empty($stats['monthly_trends'])): ?>
                <div class="chart-container">
                    <canvas id="monthlyChart" width="400" height="200"></canvas>
                </div>
                <script>
                // Simple chart with vanilla JavaScript
                document.addEventListener('DOMContentLoaded', function() {
                    const canvas = document.getElementById('monthlyChart');
                    const ctx = canvas.getContext('2d');
                    
                    const data = <?= json_encode($stats['monthly_trends']) ?>;
                    
                    // Simple bar chart implementation
                    const chartWidth = canvas.width - 80;
                    const chartHeight = canvas.height - 80;
                    const barWidth = chartWidth / data.length;
                    
                    let maxValue = 0;
                    data.forEach(item => {
                        maxValue = Math.max(maxValue, parseFloat(item.invoices) || 0);
                    });
                    
                    ctx.fillStyle = '#f8f9fa';
                    ctx.fillRect(0, 0, canvas.width, canvas.height);
                    
                    data.forEach((item, index) => {
                        const barHeight = ((parseFloat(item.invoices) || 0) / maxValue) * chartHeight;
                        const x = 40 + index * barWidth;
                        const y = canvas.height - 40 - barHeight;
                        
                        ctx.fillStyle = '#007bff';
                        ctx.fillRect(x, y, barWidth - 10, barHeight);
                        
                        // Labels
                        ctx.fillStyle = '#333';
                        ctx.font = '12px Arial';
                        ctx.textAlign = 'center';
                        ctx.fillText(item.month, x + barWidth/2, canvas.height - 10);
                    });
                });
                </script>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-chart-bar"></i>
                    <p>Pas assez de données pour afficher les tendances</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="quick-actions">
    <h3>Actions Rapides</h3>
    <div class="action-buttons">
        <a href="/clients" class="btn btn-primary">
            <i class="fas fa-users"></i>
            Gérer Clients
        </a>
        <a href="/clients" class="btn btn-success">
            <i class="fas fa-plus"></i>
            Nouveau Client
        </a>
        <button onclick="window.print()" class="btn btn-outline-secondary">
            <i class="fas fa-print"></i>
            Imprimer Rapport
        </button>
    </div>
</div>

<style>
.dashboard-header {
    margin-bottom: 2rem;
    text-align: center;
}

.dashboard-header h1 {
    color: #333;
    margin-bottom: 0.5rem;
}

.dashboard-subtitle {
    color: #666;
    font-size: 1.1rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    padding: 1.5rem;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 1rem;
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: white;
}

.stat-card.primary .stat-icon { background: #007bff; }
.stat-card.success .stat-icon { background: #28a745; }
.stat-card.info .stat-icon { background: #17a2b8; }
.stat-card.warning .stat-icon { background: #ffc107; }

.stat-content h3 {
    margin: 0;
    font-size: 1.8rem;
    font-weight: 700;
    color: #333;
}

.stat-content p {
    margin: 0;
    color: #666;
    font-size: 0.9rem;
}

.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 2rem;
    margin-bottom: 2rem;
}

.dashboard-card.full-width {
    grid-column: 1 / -1;
}

.dashboard-card {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    overflow: hidden;
}

.card-header {
    padding: 1.5rem;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.card-header h3 {
    margin: 0;
    color: #333;
}

.card-content {
    padding: 1.5rem;
}

.client-list, .transaction-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.client-item, .transaction-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 8px;
}

.client-info h4, .transaction-details strong {
    margin: 0;
    color: #333;
}

.client-info small, .transaction-details small {
    color: #666;
    display: block;
}

.transaction-amount {
    font-weight: 600;
    font-size: 1.1rem;
}

.transaction-amount.invoice { color: #28a745; }
.transaction-amount.return { color: #dc3545; }

.empty-state {
    text-align: center;
    padding: 2rem;
    color: #666;
}

.empty-state i {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.quick-actions {
    background: white;
    padding: 2rem;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    text-align: center;
}

.quick-actions h3 {
    margin-bottom: 1.5rem;
    color: #333;
}

.action-buttons {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}

.chart-container {
    margin: 1rem 0;
    text-align: center;
}

#monthlyChart {
    max-width: 100%;
    height: auto;
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .dashboard-grid {
        grid-template-columns: 1fr;
    }
    
    .action-buttons {
        flex-direction: column;
        align-items: center;
    }
}
</style>