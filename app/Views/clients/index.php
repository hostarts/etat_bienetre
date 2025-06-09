<?php
// Clients Index View
?>
<div class="page-header">
    <div class="page-title">
        <h1><i class="fas fa-users"></i> Gestion Clients</h1>
        <p>Gérez vos clients et leurs informations</p>
    </div>
    <div class="page-actions">
        <button type="button" class="btn btn-primary" onclick="openAddClientModal()">
            <i class="fas fa-plus"></i> Nouveau Client
        </button>
    </div>
</div>

<!-- Search and Filters -->
<div class="search-filters">
    <div class="search-box">
        <input type="text" id="clientSearch" placeholder="Rechercher un client..." onkeyup="filterClients()">
        <i class="fas fa-search"></i>
    </div>
</div>

<!-- Clients Grid -->
<?php if (!empty($clients)): ?>
    <div class="clients-grid" id="clientsGrid">
        <?php foreach ($clients as $client): ?>
            <div class="client-card" data-name="<?= strtolower(htmlspecialchars($client['name'])) ?>">
                <div class="client-header">
                    <h3><?= htmlspecialchars($client['name']) ?></h3>
                    <div class="client-actions">
                        <a href="/clients/<?= $client['id'] ?>" class="btn btn-sm btn-outline-primary" title="Voir détails">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="/clients/<?= $client['id'] ?>/months" class="btn btn-sm btn-primary" title="Voir mois">
                            <i class="fas fa-calendar-alt"></i>
                        </a>
                    </div>
                </div>
                <div class="client-info">
                    <div class="info-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <span><?= htmlspecialchars($client['address']) ?></span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-phone"></i>
                        <span><?= htmlspecialchars($client['phone']) ?></span>
                    </div>
                    <?php if (!empty($client['email'])): ?>
                        <div class="info-item">
                            <i class="fas fa-envelope"></i>
                            <span><?= htmlspecialchars($client['email']) ?></span>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="client-footer">
                    <small class="text-muted">
                        <i class="fas fa-calendar"></i>
                        Ajouté le <?= date('d/m/Y', strtotime($client['created_at'])) ?>
                    </small>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Pagination -->
    <?php if (isset($pagination)): ?>
        <?php 
        $baseUrl = '/clients';
        include APP_PATH . '/Views/shared/pagination.php'; 
        ?>
    <?php endif; ?>
    
<?php else: ?>
    <?php
    $icon = 'users';
    $title = 'Aucun client trouvé';
    $description = 'Commencez par ajouter votre premier client pour gérer vos transactions.';
    $action = [
        'url' => '#',
        'text' => 'Ajouter un Client',
        'icon' => 'plus',
        'type' => 'primary'
    ];
    include APP_PATH . '/Views/shared/empty_state.php';
    ?>
<?php endif; ?>

<!-- Add Client Modal -->
<div class="modal" id="addClientModal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-plus"></i> Nouveau Client</h3>
            <button type="button" class="modal-close" onclick="closeAddClientModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST" action="/clients" class="client-form">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            
            <div class="form-group">
                <label for="client_name">Nom du Client *</label>
                <input type="text" id="client_name" name="name" required 
                       placeholder="ex: Pharmacie Central">
            </div>
            
            <div class="form-group">
                <label for="client_address">Adresse *</label>
                <textarea id="client_address" name="address" required 
                          placeholder="Adresse complète du client"></textarea>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="client_phone">Téléphone *</label>
                    <input type="tel" id="client_phone" name="phone" required 
                           placeholder="ex: 0555123456">
                </div>
                
                <div class="form-group">
                    <label for="client_email">Email</label>
                    <input type="email" id="client_email" name="email" 
                           placeholder="email@exemple.com">
                </div>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeAddClientModal()">
                    Annuler
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Ajouter Client
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Modal functions
function openAddClientModal() {
    document.getElementById('addClientModal').style.display = 'flex';
    document.getElementById('client_name').focus();
}

function closeAddClientModal() {
    document.getElementById('addClientModal').style.display = 'none';
    document.querySelector('.client-form').reset();
}

// Search function
function filterClients() {
    const searchTerm = document.getElementById('clientSearch').value.toLowerCase();
    const clientCards = document.querySelectorAll('.client-card');
    
    clientCards.forEach(card => {
        const clientName = card.getAttribute('data-name');
        if (clientName.includes(searchTerm)) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

// Close modal when clicking outside
document.getElementById('addClientModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeAddClientModal();
    }
});

// Handle action button click from empty state
document.addEventListener('click', function(e) {
    if (e.target.closest('.empty-action a')) {
        e.preventDefault();
        openAddClientModal();
    }
});
</script>

<style>
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #eee;
}

.page-title h1 {
    margin: 0;
    color: #333;
}

.page-title p {
    margin: 0.5rem 0 0 0;
    color: #666;
}

.search-filters {
    margin-bottom: 2rem;
}

.search-box {
    position: relative;
    max-width: 400px;
}

.search-box input {
    width: 100%;
    padding: 0.75rem 2.5rem 0.75rem 1rem;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 1rem;
}

.search-box i {
    position: absolute;
    right: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: #666;
}

.clients-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.client-card {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    overflow: hidden;
}

.client-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
}

.client-header {
    background: #f8f9fa;
    padding: 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #eee;
}

.client-header h3 {
    margin: 0;
    color: #333;
    font-size: 1.2rem;
}

.client-actions {
    display: flex;
    gap: 0.5rem;
}

.client-info {
    padding: 1.5rem;
}

.info-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.75rem;
    color: #666;
}

.info-item:last-child {
    margin-bottom: 0;
}

.info-item i {
    width: 16px;
    color: #007bff;
}

.client-footer {
    padding: 1rem 1.5rem;
    background: #f8f9fa;
    border-top: 1px solid #eee;
}

.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background: white;
    border-radius: 10px;
    max-width: 600px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-header {
    padding: 1.5rem;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h3 {
    margin: 0;
    color: #333;
}

.modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    color: #666;
    cursor: pointer;
    padding: 0.5rem;
    border-radius: 50%;
    transition: background-color 0.2s ease;
}

.modal-close:hover {
    background: #f8f9fa;
}

.client-form {
    padding: 1.5rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: #333;
}

.form-group input,
.form-group textarea {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 1rem;
    transition: border-color 0.2s ease;
}

.form-group input:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #007bff;
    box-shadow: 0 0 0 2px rgba(0,123,255,0.25);
}

.form-group textarea {
    resize: vertical;
    min-height: 100px;
}

.modal-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
    margin-top: 2rem;
    padding-top: 1rem;
    border-top: 1px solid #eee;
}

@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
    }
    
    .clients-grid {
        grid-template-columns: 1fr;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .modal-content {
        width: 95%;
        margin: 1rem;
    }
}
</style>