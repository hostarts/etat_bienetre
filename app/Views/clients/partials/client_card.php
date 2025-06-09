<?php
// app/Views/clients/partials/client_card.php
?>
<div class="client-card">
    <div class="client-header">
        <div>
            <div class="client-name"><?= htmlspecialchars($client['name']) ?></div>
            <div class="client-total" style="background: <?= $stats['total_balance'] >= 0 ? 'linear-gradient(135deg, #10b981, #059669)' : 'linear-gradient(135deg, #ef4444, #dc2626)' ?>">
                <?= number_format($stats['total_balance'] ?? 0, 2) ?> DA
            </div>
        </div>
    </div>
    
    <div class="client-info">
        <?php if ($client['address']): ?>
            <span><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($client['address']) ?></span>
        <?php endif; ?>
        <?php if ($client['phone']): ?>
            <span><i class="fas fa-phone"></i> <?= htmlspecialchars($client['phone']) ?></span>
        <?php endif; ?>
        <?php if ($client['email']): ?>
            <span><i class="fas fa-envelope"></i> <?= htmlspecialchars($client['email']) ?></span>
        <?php endif; ?>
        <span><i class="fas fa-calendar"></i> <?= $stats['total_months'] ?? 0 ?> mois d'activité</span>
    </div>
    
    <div class="client-actions">
        <a href="/clients/<?= $client['id'] ?>/months" class="btn btn-primary">
            <i class="fas fa-calendar-alt"></i> Mois
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
// app/Views/clients/partials/month_card.php
?>
<div class="client-card" style="border-left-color: <?= $month['total'] >= 0 ? '#10b981' : '#ef4444' ?>; cursor: pointer;" 
     onclick="location.href='/clients/<?= $client['id'] ?>/months/<?= $month['month_year'] ?>'">
    <div class="client-header">
        <div>
            <div class="client-name"><?= $month['month_year'] ?></div>
            <div class="client-total" style="background: <?= $month['total'] >= 0 ? 'linear-gradient(135deg, #10b981, #059669)' : 'linear-gradient(135deg, #ef4444, #dc2626)' ?>">
                <?= number_format($month['total'], 2) ?> DA
            </div>
        </div>
    </div>
    <div class="client-info">
        <span><i class="fas fa-receipt"></i> <?= $month['transaction_count'] ?? 0 ?> transaction(s)</span>
        <?php if ($month['discount_percent'] > 0): ?>
            <span><i class="fas fa-percent"></i> Remise: <?= $month['discount_percent'] ?>%</span>
        <?php endif; ?>
    </div>
    <div class="client-actions">
        <a href="/clients/<?= $client['id'] ?>/months/<?= $month['month_year'] ?>" class="btn btn-primary">
            <i class="fas fa-eye"></i> Voir Factures
        </a>
    </div>
</div>

<?php
// app/Views/clients/partials/client_form_fields.php
?>
<div class="form-group">
    <label><i class="fas fa-store"></i> Nom de la Pharmacie *</label>
    <input type="text" name="name" required 
           value="<?= isset($client) ? htmlspecialchars($client['name']) : '' ?>"
           placeholder="ex: PHARMACIE AISSI ABDOUNE">
</div>

<div class="form-group">
    <label><i class="fas fa-map-marker-alt"></i> Adresse</label>
    <input type="text" name="address" 
           value="<?= isset($client) ? htmlspecialchars($client['address']) : '' ?>"
           placeholder="Adresse complète">
</div>

<div class="form-group">
    <label><i class="fas fa-phone"></i> Téléphone</label>
    <input type="text" name="phone" 
           value="<?= isset($client) ? htmlspecialchars($client['phone']) : '' ?>"
           placeholder="Numéro de téléphone">
</div>

<div class="form-group">
    <label><i class="fas fa-envelope"></i> Email</label>
    <input type="email" name="email" 
           value="<?= isset($client) ? htmlspecialchars($client['email']) : '' ?>"
           placeholder="adresse@email.com">
</div>

<?php
// app/Views/clients/modals/client_form.php
?>
<div class="modal" id="clientModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-user-plus"></i> <?= isset($editClient) ? 'Modifier Client' : 'Nouveau Client' ?></h3>
            <button class="modal-close" onclick="closeModal('clientModal')">&times;</button>
        </div>
        
        <form method="POST" action="<?= isset($editClient) ? '/clients/' . $editClient['id'] . '/update' : '/clients/store' ?>">
            <?= $this->render('clients/partials/client_form_fields', ['client' => $editClient ?? null]) ?>
            
            <div class="modal-actions">
                <button type="button" onclick="closeModal('clientModal')" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Annuler
                </button>
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> <?= isset($editClient) ? 'Mettre à jour' : 'Ajouter' ?>
                </button>
            </div>
        </form>
    </div>
</div>

<?php
// app/Views/clients/modals/new_month_form.php
?>
<div class="modal" id="newMonthModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-calendar-plus"></i> Nouveau Mois</h3>
            <button class="modal-close" onclick="closeModal('newMonthModal')">&times;</button>
        </div>
        
        <form method="GET" action="/clients/<?= $client['id'] ?>/months">
            <div class="form-group">
                <label><i class="fas fa-calendar"></i> Sélectionner le Mois</label>
                <input type="month" name="month" required value="<?= date('Y-m') ?>">
            </div>
            
            <div class="modal-actions">
                <button type="button" onclick="closeModal('newMonthModal')" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Annuler
                </button>
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-arrow-right"></i> Aller au Mois
                </button>
            </div>
        </form>
    </div>
</div>