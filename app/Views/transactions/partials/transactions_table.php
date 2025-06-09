<?php
// app/Views/transactions/partials/transactions_table.php
?>
<table class="table">
    <thead>
        <tr>
            <th>N° Commande/BL</th>
            <th>Type</th>
            <th>Date</th>
            <th>Montant</th>
            <th class="no-print">Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($transactions as $transaction): ?>
            <tr class="<?= $transaction['type'] ?>-row">
                <td>
                    <strong><?= htmlspecialchars($transaction['order_number']) ?></strong>
                </td>
                <td>
                    <span class="transaction-type <?= $transaction['type'] ?>">
                        <?= $transaction['type'] === 'invoice' ? '📄 Facture' : '↩️ Retour' ?>
                    </span>
                </td>
                <td><?= date('d/m/Y', strtotime($transaction['transaction_date'])) ?></td>
                <td class="amount <?= $transaction['amount'] < 0 ? 'negative' : 'positive' ?>">
                    <?= number_format($transaction['amount'], 2) ?> DA
                </td>
                <td class="no-print">
                    <button onclick="confirmDeleteTransaction(<?= $transaction['id'] ?>)" class="btn btn-danger btn-sm">
                        <i class="fas fa-trash"></i> Supprimer
                    </button>
                </td>
            </tr>
        <?php endforeach; ?>
        
        <!-- Summary Rows for Print -->
        <tr class="print-only summary-row">
            <td colspan="3" class="summary-label">Sous-total</td>
            <td class="summary-value"><?= number_format($monthTotal, 2) ?> DA</td>
            <td class="no-print"></td>
        </tr>
        
        <?php if ($discountPercent > 0): ?>
            <tr class="print-only summary-row">
                <td colspan="3" class="summary-label">Remise (<?= $discountPercent ?>%)</td>
                <td class="summary-value discount">-<?= number_format($discountAmount, 2) ?> DA</td>
                <td class="no-print"></td>
            </tr>
            <tr class="print-only total-row">
                <td colspan="3" class="total-label">Total Final</td>
                <td class="total-value"><?= number_format($finalTotal, 2) ?> DA</td>
                <td class="no-print"></td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<?php
// app/Views/transactions/partials/print_footer.php
?>
<div class="print-only print-footer">
    <?php if ($discountPercent > 0): ?>
        <div class="print-summary">
            <table class="summary-table">
                <tr>
                    <td class="summary-label">Sous-total</td>
                    <td class="summary-amount"><?= number_format($monthTotal, 2) ?> DA</td>
                </tr>
                <tr>
                    <td class="summary-label">Remise (<?= $discountPercent ?>%)</td>
                    <td class="summary-amount discount">-<?= number_format($discountAmount, 2) ?> DA</td>
                </tr>
                <tr class="total-row">
                    <td class="total-label"><strong>Montant Total</strong></td>
                    <td class="total-amount"><strong><?= number_format($finalTotal, 2) ?> DA</strong></td>
                </tr>
            </table>
        </div>
    <?php endif; ?>
    
    <div class="print-signature">
        <div class="signature-section">
            <p>Signature Client:</p>
            <div class="signature-line"></div>
        </div>
        <div class="signature-section">
            <p>Signature Bienetre Pharma:</p>
            <div class="signature-line"></div>
        </div>
    </div>
</div>

<?php
// app/Views/shared/pagination.php
?>
<div class="pagination-wrapper">
    <div class="pagination">
        <?php if ($pagination['current_page'] > 1): ?>
            <a href="<?= $baseUrl ?>?page=1" class="pagination-btn first">
                <i class="fas fa-angle-double-left"></i> Premier
            </a>
            <a href="<?= $baseUrl ?>?page=<?= $pagination['current_page'] - 1 ?>" class="pagination-btn prev">
                <i class="fas fa-angle-left"></i> Précédent
            </a>
        <?php endif; ?>
        
        <span class="pagination-info">
            Page <?= $pagination['current_page'] ?> sur <?= $pagination['total_pages'] ?>
            (<?= $pagination['total_items'] ?> éléments)
        </span>
        
        <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
            <a href="<?= $baseUrl ?>?page=<?= $pagination['current_page'] + 1 ?>" class="pagination-btn next">
                Suivant <i class="fas fa-angle-right"></i>
            </a>
            <a href="<?= $baseUrl ?>?page=<?= $pagination['total_pages'] ?>" class="pagination-btn last">
                Dernier <i class="fas fa-angle-double-right"></i>
            </a>
        <?php endif; ?>
    </div>
</div>

<?php
// app/Views/shared/alerts.php
?>
<?php if (isset($messages) && !empty($messages)): ?>
    <div class="alerts-container">
        <?php foreach ($messages as $type => $message): ?>
            <div class="alert alert-<?= $type ?>" id="alert-<?= uniqid() ?>">
                <div class="alert-content">
                    <i class="fas fa-<?= $type === 'success' ? 'check-circle' : ($type === 'error' ? 'exclamation-triangle' : 'info-circle') ?>"></i>
                    <span class="alert-message"><?= htmlspecialchars($message) ?></span>
                </div>
                <button class="alert-close" onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php
// app/Views/shared/empty_state.php
?>
<div class="empty-state">
    <div class="empty-icon">
        <i class="fas fa-<?= $icon ?? 'inbox' ?>"></i>
    </div>
    <h3 class="empty-title"><?= $title ?? 'Aucun élément trouvé' ?></h3>
    <p class="empty-description"><?= $description ?? 'Il n\'y a rien à afficher pour le moment.' ?></p>
    <?php if (isset($action)): ?>
        <div class="empty-action">
            <a href="<?= $action['url'] ?>" class="btn btn-primary">
                <i class="fas fa-<?= $action['icon'] ?? 'plus' ?>"></i>
                <?= $action['text'] ?>
            </a>
        </div>
    <?php endif; ?>
</div>

<?php
// app/Views/shared/loading.php
?>
<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-spinner">
        <div class="spinner"></div>
        <p>Chargement en cours...</p>
    </div>
</div>

<?php
// app/Views/errors/404.php
?>
<div class="error-page">
    <div class="error-content">
        <div class="error-code">404</div>
        <h1 class="error-title">Page Non Trouvée</h1>
        <p class="error-description">
            La page que vous cherchez n'existe pas ou a été déplacée.
        </p>
        <div class="error-actions">
            <a href="/" class="btn btn-primary">
                <i class="fas fa-home"></i> Retour à l'Accueil
            </a>
            <button onclick="history.back()" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Page Précédente
            </button>
        </div>
    </div>
</div>

<?php
// app/Views/errors/500.php
?>
<div class="error-page">
    <div class="error-content">
        <div class="error-code">500</div>
        <h1 class="error-title">Erreur Serveur</h1>
        <p class="error-description">
            Une erreur inattendue s'est produite. Veuillez réessayer plus tard.
        </p>
        <?php if (isset($error) && APP_DEBUG): ?>
            <div class="error-details">
                <h3>Détails de l'erreur:</h3>
                <pre><?= htmlspecialchars($error) ?></pre>
            </div>
        <?php endif; ?>
        <div class="error-actions">
            <a href="/" class="btn btn-primary">
                <i class="fas fa-home"></i> Retour à l'Accueil
            </a>
            <button onclick="location.reload()" class="btn btn-secondary">
                <i class="fas fa-redo"></i> Recharger
            </button>
        </div>
    </div>
</div>