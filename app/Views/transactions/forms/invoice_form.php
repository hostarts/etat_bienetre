<?php
// Invoice Form Partial
?>
<div class="card">
    <h3><i class="fas fa-plus-circle"></i> Ajouter Facture</h3>
    <form method="POST" action="/clients/<?= $client['id'] ?>/months/<?= $monthYear ?>/invoices" class="transaction-form">
        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
        <input type="hidden" name="client_id" value="<?= $client['id'] ?>">
        <input type="hidden" name="month_year" value="<?= $monthYear ?>">
        
        <div class="form-group">
            <label for="invoice_order_number">Numéro de Commande/BL *</label>
            <input type="text" id="invoice_order_number" name="order_number" required 
                   placeholder="ex: 557-2024" autocomplete="off">
        </div>
        
        <div class="form-group">
            <label for="invoice_amount">Montant (DA) *</label>
            <input type="number" id="invoice_amount" name="amount" step="0.01" min="0.01" required 
                   placeholder="ex: 25924.64">
        </div>
        
        <div class="form-group">
            <label for="invoice_date">Date *</label>
            <input type="date" id="invoice_date" name="date" required value="<?= date('Y-m-d') ?>">
        </div>
        
        <button type="submit" class="btn btn-success btn-block">
            <i class="fas fa-plus"></i> Ajouter Facture
        </button>
    </form>
</div>