<?php
// Pagination Component
if (isset($pagination) && $pagination['total_pages'] > 1):
?>
<div class="pagination-wrapper">
    <div class="pagination">
        <?php if ($pagination['has_prev']): ?>
            <a href="<?= $baseUrl ?>?page=1" class="pagination-btn first" title="Première page">
                <i class="fas fa-angle-double-left"></i>
            </a>
            <a href="<?= $baseUrl ?>?page=<?= $pagination['current_page'] - 1 ?>" class="pagination-btn prev" title="Page précédente">
                <i class="fas fa-angle-left"></i>
            </a>
        <?php endif; ?>
        
        <!-- Page Numbers -->
        <?php
        $start = max(1, $pagination['current_page'] - 2);
        $end = min($pagination['total_pages'], $pagination['current_page'] + 2);
        
        if ($start > 1): ?>
            <a href="<?= $baseUrl ?>?page=1" class="pagination-btn">1</a>
            <?php if ($start > 2): ?>
                <span class="pagination-dots">...</span>
            <?php endif; ?>
        <?php endif; ?>
        
        <?php for ($i = $start; $i <= $end; $i++): ?>
            <a href="<?= $baseUrl ?>?page=<?= $i ?>" 
               class="pagination-btn <?= $i === $pagination['current_page'] ? 'active' : '' ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>
        
        <?php if ($end < $pagination['total_pages']): ?>
            <?php if ($end < $pagination['total_pages'] - 1): ?>
                <span class="pagination-dots">...</span>
            <?php endif; ?>
            <a href="<?= $baseUrl ?>?page=<?= $pagination['total_pages'] ?>" class="pagination-btn">
                <?= $pagination['total_pages'] ?>
            </a>
        <?php endif; ?>
        
        <?php if ($pagination['has_next']): ?>
            <a href="<?= $baseUrl ?>?page=<?= $pagination['current_page'] + 1 ?>" class="pagination-btn next" title="Page suivante">
                <i class="fas fa-angle-right"></i>
            </a>
            <a href="<?= $baseUrl ?>?page=<?= $pagination['total_pages'] ?>" class="pagination-btn last" title="Dernière page">
                <i class="fas fa-angle-double-right"></i>
            </a>
        <?php endif; ?>
    </div>
    
    <div class="pagination-info">
        Affichage de <?= $pagination['from'] ?? 1 ?> à <?= $pagination['to'] ?? 0 ?> sur <?= $pagination['total'] ?> éléments
    </div>
</div>
<?php endif; ?>