<?php
/**
 * Modèle pour la gestion des remises mensuelles
 */
class Discount extends BaseModel {
    protected $table = 'month_discounts';
    protected $primaryKey = ['client_id', 'month_year']; // Clé primaire composite
    protected $fillable = ['client_id', 'month_year', 'discount_percent'];
    protected $timestamps = true;

    /**
     * Application ou mise à jour d'une remise
     */
    public function setDiscount($clientId, $monthYear, $discountPercent) {
        // Validation des données
        $this->validateDiscountData($clientId, $monthYear, $discountPercent);

        // Vérification si la remise existe déjà
        $existing = $this->getByClientAndMonth($clientId, $monthYear);

        if ($existing) {
            return $this->updateDiscount($clientId, $monthYear, $discountPercent);
        } else {
            return $this->createDiscount($clientId, $monthYear, $discountPercent);
        }
    }

    /**
     * Création d'une nouvelle remise
     */
    private function createDiscount($clientId, $monthYear, $discountPercent) {
        $data = [
            'client_id' => $clientId,
            'month_year' => $monthYear,
            'discount_percent' => $discountPercent,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $stmt = $this->db->prepare(
            "INSERT INTO {$this->table} (client_id, month_year, discount_percent, created_at, updated_at) 
             VALUES (?, ?, ?, ?, ?)"
        );

        return $stmt->execute(array_values($data));
    }

    /**
     * Mise à jour d'une remise existante
     */
    private function updateDiscount($clientId, $monthYear, $discountPercent) {
        $stmt = $this->db->prepare(
            "UPDATE {$this->table} 
             SET discount_percent = ?, updated_at = ? 
             WHERE client_id = ? AND month_year = ?"
        );

        return $stmt->execute([
            $discountPercent,
            date('Y-m-d H:i:s'),
            $clientId,
            $monthYear
        ]);
    }

    /**
     * Récupération d'une remise par client et mois
     */
    public function getByClientAndMonth($clientId, $monthYear) {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE client_id = ? AND month_year = ?"
        );
        $stmt->execute([$clientId, $monthYear]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Récupération du pourcentage de remise
     */
    public function getDiscountPercent($clientId, $monthYear) {
        $discount = $this->getByClientAndMonth($clientId, $monthYear);
        return $discount ? (float)$discount['discount_percent'] : 0;
    }

    /**
     * Calcul du montant de la remise
     */
    public function calculateDiscountAmount($clientId, $monthYear, $totalAmount) {
        $discountPercent = $this->getDiscountPercent($clientId, $monthYear);
        return ($totalAmount * $discountPercent) / 100;
    }

    /**
     * Calcul du montant final après remise
     */
    public function calculateFinalAmount($clientId, $monthYear, $totalAmount) {
        $discountAmount = $this->calculateDiscountAmount($clientId, $monthYear, $totalAmount);
        return $totalAmount - $discountAmount;
    }

    /**
     * Suppression d'une remise
     */
    public function removeDiscount($clientId, $monthYear) {
        $stmt = $this->db->prepare(
            "DELETE FROM {$this->table} WHERE client_id = ? AND month_year = ?"
        );
        return $stmt->execute([$clientId, $monthYear]);
    }

    /**
     * Récupération de toutes les remises d'un client
     */
    public function getClientDiscounts($clientId) {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} 
             WHERE client_id = ? 
             ORDER BY month_year DESC"
        );
        $stmt->execute([$clientId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupération des remises actives pour un mois donné
     */
    public function getActiveDiscountsForMonth($monthYear) {
        $stmt = $this->db->prepare(
            "SELECT md.*, c.name as client_name 
             FROM {$this->table} md
             JOIN clients c ON md.client_id = c.id
             WHERE md.month_year = ? AND md.discount_percent > 0
             ORDER BY c.name"
        );
        $stmt->execute([$monthYear]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Statistiques des remises
     */
    public function getDiscountStats() {
        $stmt = $this->db->query(
            "SELECT 
                COUNT(*) as total_discounts,
                AVG(discount_percent) as avg_discount,
                MIN(discount_percent) as min_discount,
                MAX(discount_percent) as max_discount,
                COUNT(DISTINCT client_id) as clients_with_discounts
             FROM {$this->table}
             WHERE discount_percent > 0"
        );
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Validation des données de remise
     */
    private function validateDiscountData($clientId, $monthYear, $discountPercent) {
        if (empty($clientId) || !is_numeric($clientId)) {
            throw new InvalidArgumentException('ID client invalide');
        }

        if (empty($monthYear) || !preg_match('/^\d{4}-\d{2}$/', $monthYear)) {
            throw new InvalidArgumentException('Format de mois invalide (YYYY-MM attendu)');
        }

        if (!is_numeric($discountPercent) || $discountPercent < 0 || $discountPercent > 100) {
            throw new InvalidArgumentException('Pourcentage de remise invalide (0-100 attendu)');
        }

        // Vérifier que le client existe
        $clientStmt = $this->db->prepare("SELECT id FROM clients WHERE id = ?");
        $clientStmt->execute([$clientId]);
        if (!$clientStmt->fetch()) {
            throw new InvalidArgumentException('Client inexistant');
        }
    }

    /**
     * Mise à jour en lot des remises
     */
    public function bulkUpdateDiscounts($discounts) {
        try {
            $this->db->beginTransaction();

            foreach ($discounts as $discount) {
                $this->setDiscount(
                    $discount['client_id'],
                    $discount['month_year'],
                    $discount['discount_percent']
                );
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Rapport des remises pour un mois
     */
    public function getMonthDiscountReport($monthYear) {
        $stmt = $this->db->prepare(
            "SELECT 
                c.name as client_name,
                md.discount_percent,
                COALESCE(SUM(t.amount), 0) as total_amount,
                COALESCE(SUM(t.amount) * md.discount_percent / 100, 0) as discount_amount,
                COALESCE(SUM(t.amount) - (SUM(t.amount) * md.discount_percent / 100), 0) as final_amount
             FROM {$this->table} md
             JOIN clients c ON md.client_id = c.id
             LEFT JOIN transactions t ON t.client_id = c.id AND t.month_year = md.month_year
             WHERE md.month_year = ? AND md.discount_percent > 0
             GROUP BY c.id, c.name, md.discount_percent
             ORDER BY c.name"
        );
        $stmt->execute([$monthYear]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Copie des remises d'un mois vers un autre
     */
    public function copyDiscountsToMonth($fromMonth, $toMonth) {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare(
                "INSERT INTO {$this->table} (client_id, month_year, discount_percent, created_at, updated_at)
                 SELECT client_id, ?, discount_percent, NOW(), NOW()
                 FROM {$this->table}
                 WHERE month_year = ?
                 ON DUPLICATE KEY UPDATE 
                    discount_percent = VALUES(discount_percent),
                    updated_at = NOW()"
            );

            $result = $stmt->execute([$toMonth, $fromMonth]);
            $this->db->commit();
            
            return $result;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
?>