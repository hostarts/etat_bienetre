<?php
// Alert Messages Component
if (isset($messages) && !empty($messages)):
?>
<div class="alerts-container">
    <?php foreach ($messages as $type => $messageList): ?>
        <?php if (is_array($messageList)): ?>
            <?php foreach ($messageList as $message): ?>
                <div class="alert alert-<?= $type ?>" data-auto-dismiss="5000">
                    <div class="alert-content">
                        <i class="fas fa-<?= $type === 'success' ? 'check-circle' : ($type === 'error' ? 'exclamation-triangle' : ($type === 'warning' ? 'exclamation-circle' : 'info-circle')) ?>"></i>
                        <span class="alert-message"><?= htmlspecialchars($message) ?></span>
                    </div>
                    <button class="alert-close" onclick="this.parentElement.remove()" title="Fermer">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="alert alert-<?= $type ?>" data-auto-dismiss="5000">
                <div class="alert-content">
                    <i class="fas fa-<?= $type === 'success' ? 'check-circle' : ($type === 'error' ? 'exclamation-triangle' : ($type === 'warning' ? 'exclamation-circle' : 'info-circle')) ?>"></i>
                    <span class="alert-message"><?= htmlspecialchars($messageList) ?></span>
                </div>
                <button class="alert-close" onclick="this.parentElement.remove()" title="Fermer">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
</div>
<?php endif; ?>