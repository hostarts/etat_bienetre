<?php
// Return Form Partial
?>
<div class="card">
    <h3><i class="fas fa-undo"></i> Ajouter Retour</h3>
    <form method="POST" action="/clients/<?= $client['id'] ?>/months/<?= $monthYear ?>/returns" class="transaction-form">
        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
        <input type="hidden" name="client_id" value="<?= $client['id'] ?>">
        <input type="hidden" name="month_year" value="<?= $monthYear ?>">
        
        <div class="form-group">
            <label for="return_order_number">Numéro de Retour *</label>
            <input type="text" id="return_order_number" name="order_number" required 
                   placeholder="ex: R77-2024" autocomplete="off">
        </div>
        
        <div class="form-group">
            <label for="return_amount">Montant (DA) *</label>
            <input type="number" id="return_amount" name="amount" step="0.01" min="0.01" required 
                   placeholder="ex: 8706.74">
        </div>
        
        <div class="form-group">
            <label for="return_date">Date *</label>
            <input type="date" id="return_date" name="date" required value="<?= date('Y-m-d') ?>">
        </div>
        
        <button type="submit" class="btn btn-warning btn-block">
            <i class="fas fa-undo"></i> Ajouter Retour
        </button>
    </form>
</div>