<?php
// app/Views/transactions/index.php
?>

<!-- Transaction Management Section -->
<div class="page-header">
    <h1><i class="fas fa-file-invoice-dollar"></i> Factures - <?= htmlspecialchars($client['name']) ?> - <?= $monthYear ?></h1>
    <p>Gestion des commandes et retours</p>
</div>

<!-- Print Header (only visible when printing) -->
<div class="print-only">
    <div style="text-align: center; margin-bottom: 30px;">
        <img src="/assets/images/logo_bienetre.webp" alt="Bienetre Pharma" style="height: 60px; margin-bottom: 15px;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div style="text-align: left;">
                <strong>Client: <?= htmlspecialchars($client['name']) ?></strong>
            </div>
            <div style="text-align: right;">
                <strong>Date: <?= date('d/m/Y H:i') ?></strong><br>
                <strong>Période: <?= $monthYear ?></strong>
            </div>
        </div>
    </div>
</div>

<!-- Total Display -->
<div class="total-box <?= $finalTotal < 0 ? 'negative' : '' ?>">
    TOTAL À PAYER: <?= number_format($finalTotal, 2) ?> DA
    <?php if ($discountPercent > 0): ?>
        <div style="font-size: 1rem; opacity: 0.8; margin-top: 8px;">
            (Avant remise: <?= number_format($monthTotal, 2) ?> DA - Remise <?= $discountPercent ?>%: -<?= number_format($discountAmount, 2) ?> DA)
        </div>
    <?php endif; ?>
</div>

<!-- Add Transaction Forms -->
<div class="grid">
    <!-- Add Invoice -->
    <?= $this->render('transactions/forms/invoice_form', ['client' => $client, 'monthYear' => $monthYear]) ?>
    
    <!-- Add Return -->
    <?= $this->render('transactions/forms/return_form', ['client' => $client, 'monthYear' => $monthYear]) ?>
    
    <!-- Discount Management -->
    <?= $this->render('transactions/forms/discount_form', [
        'client' => $client, 
        'monthYear' => $monthYear,
        'discountPercent' => $discountPercent,
        'discountAmount' => $discountAmount,
        'monthTotal' => $monthTotal,
        'finalTotal' => $finalTotal
    ]) ?>
</div>

<!-- Transactions List -->
<div class="card">
    <h3><i class="fas fa-list"></i> Liste des Transactions</h3>
    
    <?php if (empty($transactions)): ?>
        <div class="empty-state">
            <i class="fas fa-inbox"></i>
            <p>Aucune transaction pour ce mois.</p>
            <small>Utilisez les formulaires ci-dessus pour ajouter des factures ou retours.</small>
        </div>
    <?php else: ?>
        <?= $this->render('transactions/partials/transactions_table', [
            'transactions' => $transactions,
            'monthTotal' => $monthTotal,
            'discountPercent' => $discountPercent,
            'discountAmount' => $discountAmount,
            'finalTotal' => $finalTotal
        ]) ?>
    <?php endif; ?>
</div>

<!-- Print Footer -->
<?= $this->render('transactions/partials/print_footer', [
    'monthTotal' => $monthTotal,
    'discountPercent' => $discountPercent,
    'discountAmount' => $discountAmount,
    'finalTotal' => $finalTotal
]) ?>

<?php
// app/Views/transactions/forms/invoice_form.php
?>
<div class="card">
    <h3>➕ Ajouter Facture</h3>
    <form method="POST" action="/clients/<?= $client['id'] ?>/months/<?= $monthYear ?>/invoices">
        <div class="form-group">
            <label>Numéro de Commande *</label>
            <input type="text" name="order_number" required placeholder="ex: 557-2024">
        </div>
        <div class="form-group">
            <label>Montant (DA) *</label>
            <input type="number" name="amount" step="0.01" required placeholder="ex: 25924.64">
        </div>
        <div class="form-group">
            <label>Date *</label>
            <input type="date" name="date" required value="<?= date('Y-m-d') ?>">
        </div>
        
        <button type="submit" class="btn btn-success">
            <i class="fas fa-plus"></i> Ajouter Facture
        </button>
    </form>
</div>

<?php
// app/Views/transactions/forms/return_form.php
?>
<div class="card">
    <h3>↩️ Ajouter Retour</h3>
    <form method="POST" action="/clients/<?= $client['id'] ?>/months/<?= $monthYear ?>/returns">
        <div class="form-group">
            <label>Numéro de Retour *</label>
            <input type="text" name="order_number" required placeholder="ex: R77-2024">
        </div>
        <div class="form-group">
            <label>Montant (DA) *</label>
            <input type="number" name="amount" step="0.01" required placeholder="ex: 8706.74">
        </div>
        <div class="form-group">
            <label>Date *</label>
            <input type="date" name="date" required value="<?= date('Y-m-d') ?>">
        </div>
        
        <button type="submit" class="btn btn-warning">
            <i class="fas fa-undo"></i> Ajouter Retour
        </button>
    </form>
</div>

<?php
// app/Views/transactions/forms/discount_form.php
?>
<div class="card">
    <h3>💰 Gestion Remise</h3>
    <form method="POST" action="/clients/<?= $client['id'] ?>/months/<?= $monthYear ?>/discount">
        <div class="form-group">
            <label>Remise (%) *</label>
            <input type="number" name="discount_percent" step="0.01" min="0" max="100" 
                   value="<?= $discountPercent ?>" placeholder="ex: 5.5">
            <small class="form-help">
                💡 Entrez 0 pour supprimer la remise
            </small>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-warning">
                <i class="fas fa-percent"></i> Appliquer Remise
            </button>
            <?php if ($discountPercent > 0): ?>
                <button type="submit" name="remove_discount" value="1" class="btn btn-danger" 
                        onclick="return confirm('Supprimer la remise de <?= $discountPercent ?>% ?')">
                    <i class="fas fa-trash"></i> Supprimer Remise
                </button>
            <?php endif; ?>
        </div>
    </form>
    
    <!-- Discount Status -->
    <?php if ($discountPercent > 0): ?>
        <div class="discount-status active">
            <div class="discount-header">
                <span class="discount-label">✅ Remise Active</span>
                <span class="discount-value"><?= $discountPercent ?>%</span>
            </div>
            <div class="discount-details">
                <div>💰 Montant remise: <strong><?= number_format($discountAmount, 2) ?> DA</strong></div>
                <div>📊 Sous-total: <?= number_format($monthTotal, 2) ?> DA</div>
                <div>🎯 <strong>Total final: <?= number_format($finalTotal, 2) ?> DA</strong></div>
            </div>
        </div>
    <?php else: ?>
        <div class="discount-status inactive">
            <i class="fas fa-info-circle"></i> Aucune remise appliquée pour ce mois
        </div>
    <?php endif; ?>
</div>