<?php
// Empty State Component
?>
<div class="empty-state">
    <div class="empty-icon">
        <i class="fas fa-<?= $icon ?? 'inbox' ?>"></i>
    </div>
    <h3 class="empty-title"><?= $title ?? 'Aucun élément trouvé' ?></h3>
    <p class="empty-description"><?= $description ?? 'Il n\'y a rien à afficher pour le moment.' ?></p>
    <?php if (isset($action)): ?>
        <div class="empty-action">
            <a href="<?= $action['url'] ?>" class="btn btn-<?= $action['type'] ?? 'primary' ?>">
                <i class="fas fa-<?= $action['icon'] ?? 'plus' ?>"></i>
                <?= $action['text'] ?>
            </a>
        </div>
    <?php endif; ?>
</div>