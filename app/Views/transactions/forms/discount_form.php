<?php
// Discount Form Partial
?>
<div class="card">
    <h3><i class="fas fa-percent"></i> Gestion Remise</h3>
    <form method="POST" action="/clients/<?= $client['id'] ?>/months/<?= $monthYear ?>/discount" class="discount-form">
        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
        <input type="hidden" name="client_id" value="<?= $client['id'] ?>">
        <input type="hidden" name="month_year" value="<?= $monthYear ?>">
        
        <div class="form-group">
            <label for="discount_percent">Remise (%) *</label>
            <input type="number" id="discount_percent" name="discount_percent" 
                   step="0.01" min="0" max="100" 
                   value="<?= $discountPercent ?>" 
                   placeholder="ex: 5.5">
            <small class="form-help">
                <i class="fas fa-info-circle"></i> Entrez 0 pour supprimer la remise
            </small>
        </div>
        
        <button type="submit" class="btn btn-primary btn-block">
            <i class="fas fa-percent"></i> <?= $discountPercent > 0 ? 'Modifier' : 'Appliquer' ?> Remise
        </button>
    </form>
    
    <!-- Discount Status -->
    <?php if ($discountPercent > 0): ?>
        <div class="discount-status active">
            <div class="discount-header">
                <span class="discount-label"><i class="fas fa-check-circle"></i> Remise Active</span>
                <span class="discount-value"><?= $discountPercent ?>%</span>
            </div>
            <div class="discount-calculation">
                <div>Sous-total: <?= number_format($monthTotal, 2) ?> DA</div>
                <div>Remise: -<?= number_format($discountAmount, 2) ?> DA</div>
                <div class="discount-total">Total: <?= number_format($finalTotal, 2) ?> DA</div>
            </div>
        </div>
    <?php else: ?>
        <div class="discount-status inactive">
            <i class="fas fa-info-circle"></i> Aucune remise appliquée
        </div>
    <?php endif; ?>
</div>