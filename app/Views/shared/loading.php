<?php
// Loading Component
?>
<div class="loading-overlay" id="loadingOverlay" style="display: none;">
    <div class="loading-spinner">
        <div class="spinner">
            <div class="bounce1"></div>
            <div class="bounce2"></div>
            <div class="bounce3"></div>
        </div>
        <p class="loading-text"><?= $text ?? 'Chargement en cours...' ?></p>
    </div>
</div>