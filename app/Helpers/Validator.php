<?php
/**
 * Classe de validation des données
 */
class Validator {
    private $errors = [];
    private $data = [];
    
    /**
     * Validation des données selon les règles
     */
    public function validate($data, $rules) {
        $this->errors = [];
        $this->data = [];
        
        foreach ($rules as $field => $ruleString) {
            $fieldRules = explode('|', $ruleString);
            $value = $data[$field] ?? null;
            
            // Application des règles
            foreach ($fieldRules as $rule) {
                $this->applyRule($field, $value, $rule);
            }
            
            // Si pas d'erreur, on garde la valeur nettoyée
            if (!isset($this->errors[$field])) {
                $this->data[$field] = $this->cleanValue($value);
            }
        }
        
        return [
            'valid' => empty($this->errors),
            'errors' => $this->errors,
            'data' => $this->data
        ];
    }
    
    /**
     * Application d'une règle de validation
     */
    private function applyRule($field, $value, $rule) {
        // Si le champ a déjà une erreur, on passe
        if (isset($this->errors[$field])) {
            return;
        }
        
        // Parsing de la règle (ex: max:255)
        $ruleParts = explode(':', $rule);
        $ruleName = $ruleParts[0];
        $ruleParam = $ruleParts[1] ?? null;
        
        switch ($ruleName) {
            case 'required':
                $this->validateRequired($field, $value);
                break;
                
            case 'string':
                $this->validateString($field, $value);
                break;
                
            case 'integer':
                $this->validateInteger($field, $value);
                break;
                
            case 'numeric':
                $this->validateNumeric($field, $value);
                break;
                
            case 'email':
                $this->validateEmail($field, $value);
                break;
                
            case 'date':
                $this->validateDate($field, $value);
                break;
                
            case 'min':
                $this->validateMin($field, $value, $ruleParam);
                break;
                
            case 'max':
                $this->validateMax($field, $value, $ruleParam);
                break;
                
            case 'between':
                $this->validateBetween($field, $value, $ruleParam);
                break;
                
            case 'in':
                $this->validateIn($field, $value, $ruleParam);
                break;
                
            case 'regex':
                $this->validateRegex($field, $value, $ruleParam);
                break;
                
            case 'unique':
                $this->validateUnique($field, $value, $ruleParam);
                break;
                
            case 'exists':
                $this->validateExists($field, $value, $ruleParam);
                break;
        }
    }
    
    /**
     * Validation: champ requis
     */
    private function validateRequired($field, $value) {
        if ($value === null || $value === '' || (is_array($value) && empty($value))) {
            $this->addError($field, 'Le champ ' . $this->getFieldName($field) . ' est requis');
        }
    }
    
    /**
     * Validation: chaîne de caractères
     */
    private function validateString($field, $value) {
        if ($value !== null && !is_string($value)) {
            $this->addError($field, 'Le champ ' . $this->getFieldName($field) . ' doit être une chaîne de caractères');
        }
    }
    
    /**
     * Validation: entier
     */
    private function validateInteger($field, $value) {
        if ($value !== null && !filter_var($value, FILTER_VALIDATE_INT)) {
            $this->addError($field, 'Le champ ' . $this->getFieldName($field) . ' doit être un entier');
        }
    }
    
    /**
     * Validation: numérique
     */
    private function validateNumeric($field, $value) {
        if ($value !== null && !is_numeric($value)) {
            $this->addError($field, 'Le champ ' . $this->getFieldName($field) . ' doit être numérique');
        }
    }
    
    /**
     * Validation: email
     */
    private function validateEmail($field, $value) {
        if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, 'Le champ ' . $this->getFieldName($field) . ' doit être un email valide');
        }
    }
    
    /**
     * Validation: date
     */
    private function validateDate($field, $value) {
        if ($value !== null && $value !== '') {
            $date = DateTime::createFromFormat('Y-m-d', $value);
            if (!$date || $date->format('Y-m-d') !== $value) {
                $this->addError($field, 'Le champ ' . $this->getFieldName($field) . ' doit être une date valide (YYYY-MM-DD)');
            }
        }
    }
    
    /**
     * Validation: minimum
     */
    private function validateMin($field, $value, $min) {
        if ($value === null || $value === '') {
            return;
        }
        
        if (is_numeric($value)) {
            if ((float)$value < (float)$min) {
                $this->addError($field, 'Le champ ' . $this->getFieldName($field) . " doit être supérieur ou égal à {$min}");
            }
        } elseif (is_string($value)) {
            if (mb_strlen($value) < (int)$min) {
                $this->addError($field, 'Le champ ' . $this->getFieldName($field) . " doit contenir au moins {$min} caractères");
            }
        }
    }
    
    /**
     * Validation: maximum
     */
    private function validateMax($field, $value, $max) {
        if ($value === null || $value === '') {
            return;
        }
        
        if (is_numeric($value)) {
            if ((float)$value > (float)$max) {
                $this->addError($field, 'Le champ ' . $this->getFieldName($field) . " doit être inférieur ou égal à {$max}");
            }
        } elseif (is_string($value)) {
            if (mb_strlen($value) > (int)$max) {
                $this->addError($field, 'Le champ ' . $this->getFieldName($field) . " ne peut pas dépasser {$max} caractères");
            }
        }
    }
    
    /**
     * Validation: entre deux valeurs
     */
    private function validateBetween($field, $value, $range) {
        if ($value === null || $value === '') {
            return;
        }
        
        $rangeParts = explode(',', $range);
        if (count($rangeParts) !== 2) {
            return;
        }
        
        $min = trim($rangeParts[0]);
        $max = trim($rangeParts[1]);
        
        if (is_numeric($value)) {
            $numValue = (float)$value;
            if ($numValue < (float)$min || $numValue > (float)$max) {
                $this->addError($field, 'Le champ ' . $this->getFieldName($field) . " doit être entre {$min} et {$max}");
            }
        }
    }
    
    /**
     * Validation: valeur dans une liste
     */
    private function validateIn($field, $value, $list) {
        if ($value === null || $value === '') {
            return;
        }
        
        $allowedValues = explode(',', $list);
        $allowedValues = array_map('trim', $allowedValues);
        
        if (!in_array($value, $allowedValues)) {
            $this->addError($field, 'Le champ ' . $this->getFieldName($field) . ' doit être une des valeurs suivantes: ' . implode(', ', $allowedValues));
        }
    }
    
    /**
     * Validation: expression régulière
     */
    private function validateRegex($field, $value, $pattern) {
        if ($value !== null && $value !== '' && !preg_match($pattern, $value)) {
            $this->addError($field, 'Le champ ' . $this->getFieldName($field) . ' a un format invalide');
        }
    }
    
    /**
     * Validation: unicité en base de données
     */
    private function validateUnique($field, $value, $table) {
        if ($value === null || $value === '') {
            return;
        }
        
        try {
            $db = Database::getInstance();
            $result = $db->fetch("SELECT COUNT(*) as count FROM {$table} WHERE {$field} = ?", [$value]);
            
            if ($result['count'] > 0) {
                $this->addError($field, 'Cette valeur existe déjà pour le champ ' . $this->getFieldName($field));
            }
        } catch (Exception $e) {
            // En cas d'erreur de base de données, on ne valide pas mais on ne bloque pas
        }
    }
    
    /**
     * Validation: existence en base de données
     */
    private function validateExists($field, $value, $table) {
        if ($value === null || $value === '') {
            return;
        }
        
        try {
            $db = Database::getInstance();
            $result = $db->fetch("SELECT COUNT(*) as count FROM {$table} WHERE id = ?", [$value]);
            
            if ($result['count'] === 0) {
                $this->addError($field, 'Cette valeur n\'existe pas pour le champ ' . $this->getFieldName($field));
            }
        } catch (Exception $e) {
            $this->addError($field, 'Erreur de validation pour le champ ' . $this->getFieldName($field));
        }
    }
    
    /**
     * Ajout d'une erreur
     */
    private function addError($field, $message) {
        $this->errors[$field] = $message;
    }
    
    /**
     * Nettoyage d'une valeur
     */
    private function cleanValue($value) {
        if ($value === null || $value === '') {
            return $value;
        }
        
        if (is_string($value)) {
            // Suppression des espaces en début et fin
            $value = trim($value);
            
            // Échappement HTML
            $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        }
        
        return $value;
    }
    
    /**
     * Récupération du nom convivial d'un champ
     */
    private function getFieldName($field) {
        $fieldNames = [
            'name' => 'nom',
            'email' => 'email',
            'phone' => 'téléphone',
            'address' => 'adresse',
            'client_id' => 'client',
            'order_number' => 'numéro de commande',
            'amount' => 'montant',
            'date' => 'date',
            'month_year' => 'mois',
            'discount_percent' => 'pourcentage de remise',
            'password' => 'mot de passe',
            'confirm_password' => 'confirmation du mot de passe'
        ];
        
        return $fieldNames[$field] ?? $field;
    }
    
    /**
     * Validation personnalisée avec fonction callback
     */
    public function customValidation($field, $value, $callback, $message = null) {
        if (!call_user_func($callback, $value)) {
            $errorMessage = $message ?? 'Le champ ' . $this->getFieldName($field) . ' est invalide';
            $this->addError($field, $errorMessage);
        }
    }
    
    /**
     * Validation de la correspondance entre deux champs
     */
    public function validateMatch($field1, $field2, $data, $message = null) {
        $value1 = $data[$field1] ?? null;
        $value2 = $data[$field2] ?? null;
        
        if ($value1 !== $value2) {
            $errorMessage = $message ?? 'Les champs ' . $this->getFieldName($field1) . ' et ' . $this->getFieldName($field2) . ' doivent correspondre';
            $this->addError($field2, $errorMessage);
        }
    }
    
    /**
     * Validation d'upload de fichier
     */
    public function validateFile($field, $file, $rules = []) {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            if (in_array('required', $rules)) {
                $this->addError($field, 'Le fichier ' . $this->getFieldName($field) . ' est requis');
            }
            return;
        }
        
        // Validation de la taille
        $maxSize = 5242880; // 5MB par défaut
        foreach ($rules as $rule) {
            if (strpos($rule, 'max_size:') === 0) {
                $maxSize = (int)str_replace('max_size:', '', $rule);
                break;
            }
        }
        
        if ($file['size'] > $maxSize) {
            $this->addError($field, 'Le fichier ' . $this->getFieldName($field) . ' est trop volumineux');
            return;
        }
        
        // Validation du type MIME
        $allowedTypes = [];
        foreach ($rules as $rule) {
            if (strpos($rule, 'mimes:') === 0) {
                $allowedTypes = explode(',', str_replace('mimes:', '', $rule));
                break;
            }
        }
        
        if (!empty($allowedTypes)) {
            $fileType = mime_content_type($file['tmp_name']);
            if (!in_array($fileType, $allowedTypes)) {
                $this->addError($field, 'Le type de fichier ' . $this->getFieldName($field) . ' n\'est pas autorisé');
                return;
            }
        }
        
        // Validation de l'extension
        $allowedExtensions = [];
        foreach ($rules as $rule) {
            if (strpos($rule, 'extensions:') === 0) {
                $allowedExtensions = explode(',', str_replace('extensions:', '', $rule));
                break;
            }
        }
        
        if (!empty($allowedExtensions)) {
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($extension, $allowedExtensions)) {
                $this->addError($field, 'L\'extension du fichier ' . $this->getFieldName($field) . ' n\'est pas autorisée');
                return;
            }
        }
    }
    
    /**
     * Récupération de toutes les erreurs
     */
    public function getErrors() {
        return $this->errors;
    }
    
    /**
     * Récupération d'une erreur spécifique
     */
    public function getError($field) {
        return $this->errors[$field] ?? null;
    }
    
    /**
     * Vérification s'il y a des erreurs
     */
    public function hasErrors() {
        return !empty($this->errors);
    }
    
    /**
     * Nettoyage de toutes les erreurs
     */
    public function clearErrors() {
        $this->errors = [];
    }
    
    /**
     * Validation groupée avec conditions
     */
    public function validateGroup($data, $groups) {
        foreach ($groups as $condition => $rules) {
            if ($condition === 'always' || $this->evaluateCondition($condition, $data)) {
                $result = $this->validate($data, $rules);
                if (!$result['valid']) {
                    return $result;
                }
            }
        }
        
        return [
            'valid' => true,
            'errors' => [],
            'data' => $this->data
        ];
    }
    
    /**
     * Évaluation d'une condition
     */
    private function evaluateCondition($condition, $data) {
        // Format: field:value ou field:!value
        if (strpos($condition, ':') !== false) {
            list($field, $expectedValue) = explode(':', $condition, 2);
            $actualValue = $data[$field] ?? null;
            
            if (strpos($expectedValue, '!') === 0) {
                return $actualValue !== substr($expectedValue, 1);
            } else {
                return $actualValue === $expectedValue;
            }
        }
        
        return true;
    }
    
    /**
     * Validation de données JSON
     */
    public function validateJSON($field, $value) {
        if ($value !== null && $value !== '') {
            json_decode($value);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->addError($field, 'Le champ ' . $this->getFieldName($field) . ' doit être un JSON valide');
            }
        }
    }
    
    /**
     * Validation d'URL
     */
    public function validateURL($field, $value) {
        if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_URL)) {
            $this->addError($field, 'Le champ ' . $this->getFieldName($field) . ' doit être une URL valide');
        }
    }
    
    /**
     * Validation de numéro de téléphone (format français/algérien)
     */
    public function validatePhone($field, $value) {
        if ($value !== null && $value !== '') {
            // Formats acceptés: +213XXXXXXXXX, 0XXXXXXXXX, XXXXXXXXXX
            $pattern = '/^(\+213|0)?[1-9][0-9]{8}$/';
            if (!preg_match($pattern, $value)) {
                $this->addError($field, 'Le champ ' . $this->getFieldName($field) . ' doit être un numéro de téléphone valide');
            }
        }
    }
    
    /**
     * Sanitization des données après validation
     */
    public function sanitize($data, $rules = []) {
        $sanitized = [];
        
        foreach ($data as $field => $value) {
            $sanitized[$field] = $this->sanitizeField($value, $rules[$field] ?? []);
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitization d'un champ spécifique
     */
    private function sanitizeField($value, $rules) {
        if ($value === null) {
            return null;
        }
        
        foreach ($rules as $rule) {
            switch ($rule) {
                case 'trim':
                    $value = trim($value);
                    break;
                case 'lowercase':
                    $value = strtolower($value);
                    break;
                case 'uppercase':
                    $value = strtoupper($value);
                    break;
                case 'ucfirst':
                    $value = ucfirst(strtolower($value));
                    break;
                case 'strip_tags':
                    $value = strip_tags($value);
                    break;
                case 'escape_html':
                    $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                    break;
            }
        }
        
        return $value;
    }
}