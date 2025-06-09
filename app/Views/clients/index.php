<?php
// app/Views/clients/index.php
?>

<!-- Client Management -->
<div class="page-header">
    <h1><i class="fas fa-users"></i> Gestion des Clients</h1>
    <p>Gérez vos pharmacies partenaires</p>
</div>

<div class="clients-grid">
    <!-- Add Client Card -->
    <div class="add-client-card" onclick="openModal('clientModal')">
        <i class="fas fa-plus-circle"></i>
        <h3>Nouveau Client</h3>
        <p>Ajouter une nouvelle pharmacie</p>
    </div>
    
    <!-- Client Cards -->
    <?php foreach ($clients as $client): ?>
        <?= $this->render('clients/partials/client_card', ['client' => $client, 'stats' => $client['stats']]) ?>
    <?php endforeach; ?>
</div>

<!-- Client Modal -->
<?= $this->render('clients/modals/client_form', ['editClient' => $editClient ?? null]) ?>

<?php
// app/Views/clients/show.php - Client Details
?>

<!-- Client Details -->
<div class="page-header">
    <h1><i class="fas fa-user"></i> <?= htmlspecialchars($client['name']) ?></h1>
    <p>Détails et statistiques du client</p>
</div>

<div class="grid">
    <!-- Client Info Card -->
    <div class="card">
        <h3><i class="fas fa-info-circle"></i> Informations</h3>
        <div class="client-details">
            <div class="detail-row">
                <span class="label">Nom:</span>
                <span class="value"><?= htmlspecialchars($client['name']) ?></span>
            </div>
            <?php if ($client['address']): ?>
                <div class="detail-row">
                    <span class="label">Adresse:</span>
                    <span class="value"><?= htmlspecialchars($client['address']) ?></span>
                </div>
            <?php endif; ?>
            <?php if ($client['phone']): ?>
                <div class="detail-row">
                    <span class="label">Téléphone:</span>
                    <span class="value"><?= htmlspecialchars($client['phone']) ?></span>
                </div>
            <?php endif; ?>
            <?php if ($client['email']): ?>
                <div class="detail-row">
                    <span class="label">Email:</span>
                    <span class="value"><?= htmlspecialchars($client['email']) ?></span>
                </div>
            <?php endif; ?>
            <div class="detail-row">
                <span class="label">Client depuis:</span>
                <span class="value"><?= date('d/m/Y', strtotime($client['created_at'])) ?></span>
            </div>
        </div>
    </div>

    <!-- Client Stats Card -->
    <div class="card">
        <h3><i class="fas fa-chart-bar"></i> Statistiques</h3>
        <div class="stats-list">
            <div class="stat-item">
                <span class="stat-label">Total Mois:</span>
                <span class="stat-value"><?= $stats['total_months'] ?></span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Total Factures:</span>
                <span class="stat-value"><?= number_format($stats['total_invoices'], 2) ?> DA</span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Total Retours:</span>
                <span class="stat-value"><?= number_format(abs($stats['total_returns']), 2) ?> DA</span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Solde Total:</span>
                <span class="stat-value <?= $stats['total_balance'] >= 0 ? 'positive' : 'negative' ?>">
                    <?= number_format($stats['total_balance'], 2) ?> DA
                </span>
            </div>
        </div>
    </div>
</div>

<!-- Actions -->
<div class="card">
    <h3><i class="fas fa-cogs"></i> Actions</h3>
    <div class="action-buttons">
        <a href="/clients/<?= $client['id'] ?>/months" class="btn btn-primary">
            <i class="fas fa-calendar-alt"></i> Voir Mois
        </a>
        <a href="/clients/<?= $client['id'] ?>/edit" class="btn btn-warning">
            <i class="fas fa-edit"></i> Modifier
        </a>
        <button onclick="confirmDelete(<?= $client['id'] ?>)" class="btn btn-danger">
            <i class="fas fa-trash"></i> Supprimer
        </button>
    </div>
</div>

<?php
// app/Views/clients/months.php - Month Selection
?>

<!-- Month Selection Section -->
<div class="page-header">
    <h1><i class="fas fa-calendar-alt"></i> Sélection du Mois - <?= htmlspecialchars($client['name']) ?></h1>
    <p>Choisissez un mois pour gérer les factures</p>
</div>

<div class="clients-grid">
    <?php if (empty($months)): ?>
        <div class="client-card" style="text-align: center; border-left-color: #f59e0b;">
            <div class="client-header">
                <div class="client-name"><i class="fas fa-info-circle"></i> Aucune Transaction</div>
            </div>
            <div class="client-info">
                <span>Aucune transaction enregistrée pour ce client.</span>
                <span>Commencez par ajouter des factures pour le mois en cours.</span>
            </div>
            <div class="client-actions">
                <a href="/clients/<?= $client['id'] ?>/months/<?= date('Y-m') ?>" class="btn btn-success">
                    <i class="fas fa-plus"></i> Nouveau Mois
                </a>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Pagination Controls -->
    <?php if ($pagination['total_pages'] > 1): ?>
        <?= $this->render('shared/pagination', ['pagination' => $pagination, 'baseUrl' => "/clients/{$client['id']}/months"]) ?>
    <?php endif; ?>
    
    <!-- Month Cards -->
    <?php foreach ($months as $month): ?>
        <?= $this->render('clients/partials/month_card', ['month' => $month, 'client' => $client]) ?>
    <?php endforeach; ?>
    
    <!-- Add New Month -->
    <div class="add-client-card" onclick="showNewMonthForm()">
        <i class="fas fa-calendar-plus"></i>
        <h3>Nouveau Mois</h3>
        <p>Ajouter des factures pour un nouveau mois</p>
    </div>
</div>

<!-- New Month Form -->
<?= $this->render('clients/modals/new_month_form', ['client' => $client]) ?>

<?php
// app/Views/clients/edit.php - Edit Client Form
?>

<div class="page-header">
    <h1><i class="fas fa-edit"></i> Modifier Client</h1>
    <p>Mettre à jour les informations du client</p>
</div>

<div class="card">
    <form method="POST" action="/clients/<?= $client['id'] ?>/update">
        <?= $this->render('clients/partials/client_form_fields', ['client' => $client]) ?>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-success">
                <i class="fas fa-save"></i> Mettre à jour
            </button>
            <a href="/clients/<?= $client['id'] ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Retour
            </a>
        </div>
    </form>
</div>