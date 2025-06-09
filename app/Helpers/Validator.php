<?php
/**
 * Validator Helper
 * Provides input validation functionality
 */
class Validator {
    
    private $errors = [];
    
    /**
     * Validate data against rules
     */
    public function validate($data, $rules) {
        $this->errors = [];
        
        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;
            $ruleArray = explode('|', $fieldRules);
            
            foreach ($ruleArray as $rule) {
                $this->validateField($field, $value, $rule);
            }
        }
        
        return [
            'valid' => empty($this->errors),
            'errors' => $this->errors
        ];
    }
    
    /**
     * Validate individual field
     */
    private function validateField($field, $value, $rule) {
        // Parse rule with parameters
        $ruleParts = explode(':', $rule);
        $ruleName = $ruleParts[0];
        $ruleParams = isset($ruleParts[1]) ? explode(',', $ruleParts[1]) : [];
        
        switch ($ruleName) {
            case 'required':
                if (empty($value) && $value !== '0') {
                    $this->addError($field, "Le champ {$field} est requis.");
                }
                break;
                
            case 'string':
                if ($value !== null && !is_string($value)) {
                    $this->addError($field, "Le champ {$field} doit être une chaîne de caractères.");
                }
                break;
                
            case 'integer':
                if ($value !== null && !filter_var($value, FILTER_VALIDATE_INT)) {
                    $this->addError($field, "Le champ {$field} doit être un nombre entier.");
                }
                break;
                
            case 'numeric':
                if ($value !== null && !is_numeric($value)) {
                    $this->addError($field, "Le champ {$field} doit être un nombre.");
                }
                break;
                
            case 'email':
                if ($value !== null && !empty($value) && !Security::validateEmail($value)) {
                    $this->addError($field, "Le champ {$field} doit être une adresse email valide.");
                }
                break;
                
            case 'date':
                if ($value !== null && !empty($value)) {
                    $date = DateTime::createFromFormat('Y-m-d', $value);
                    if (!$date || $date->format('Y-m-d') !== $value) {
                        $this->addError($field, "Le champ {$field} doit être une date valide (YYYY-MM-DD).");
                    }
                }
                break;
                
            case 'min':
                if (isset($ruleParams[0]) && $value !== null) {
                    $min = floatval($ruleParams[0]);
                    if (is_numeric($value) && floatval($value) < $min) {
                        $this->addError($field, "Le champ {$field} doit être supérieur ou égal à {$min}.");
                    }
                }
                break;
                
            case 'max':
                if (isset($ruleParams[0]) && $value !== null) {
                    $max = intval($ruleParams[0]);
                    if (is_string($value) && strlen($value) > $max) {
                        $this->addError($field, "Le champ {$field} ne peut pas dépasser {$max} caractères.");
                    } elseif (is_numeric($value) && floatval($value) > $max) {
                        $this->addError($field, "Le champ {$field} ne peut pas être supérieur à {$max}.");
                    }
                }
                break;
                
            case 'phone':
                if ($value !== null && !empty($value)) {
                    // Simple phone validation for Algerian numbers
                    $phonePattern = '/^(\+213|0)[567]\d{8}$/';
                    if (!preg_match($phonePattern, $value)) {
                        $this->addError($field, "Le champ {$field} doit être un numéro de téléphone valide.");
                    }
                }
                break;
                
            case 'alpha':
                if ($value !== null && !empty($value) && !ctype_alpha(str_replace(' ', '', $value))) {
                    $this->addError($field, "Le champ {$field} ne peut contenir que des lettres.");
                }
                break;
                
            case 'alphanumeric':
                if ($value !== null && !empty($value) && !ctype_alnum(str_replace(' ', '', $value))) {
                    $this->addError($field, "Le champ {$field} ne peut contenir que des lettres et des chiffres.");
                }
                break;
                
            case 'url':
                if ($value !== null && !empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
                    $this->addError($field, "Le champ {$field} doit être une URL valide.");
                }
                break;
                
            case 'in':
                if ($value !== null && !empty($value) && !in_array($value, $ruleParams)) {
                    $allowed = implode(', ', $ruleParams);
                    $this->addError($field, "Le champ {$field} doit être l'une des valeurs suivantes: {$allowed}.");
                }
                break;
                
            case 'regex':
                if (isset($ruleParams[0]) && $value !== null && !empty($value)) {
                    $pattern = $ruleParams[0];
                    if (!preg_match($pattern, $value)) {
                        $this->addError($field, "Le format du champ {$field} est invalide.");
                    }
                }
                break;
        }
    }
    
    /**
     * Add validation error
     */
    private function addError($field, $message) {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
    }
    
    /**
     * Get all errors
     */
    public function getErrors() {
        return $this->errors;
    }
    
    /**
     * Get errors for specific field
     */
    public function getFieldErrors($field) {
        return $this->errors[$field] ?? [];
    }
    
    /**
     * Check if field has errors
     */
    public function hasErrors($field = null) {
        if ($field === null) {
            return !empty($this->errors);
        }
        return isset($this->errors[$field]);
    }
    
    /**
     * Get first error message
     */
    public function getFirstError() {
        if (empty($this->errors)) {
            return null;
        }
        
        $firstField = array_keys($this->errors)[0];
        return $this->errors[$firstField][0];
    }
    
    /**
     * Validate file upload
     */
    public function validateFile($file, $rules = []) {
        $this->errors = [];
        
        // Check if file was uploaded
        if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
            $this->addError('file', 'Erreur lors du téléchargement du fichier.');
            return false;
        }
        
        // Default rules
        $defaultRules = [
            'max_size' => 5242880, // 5MB
            'allowed_types' => ['jpg', 'jpeg', 'png', 'gif', 'pdf']
        ];
        
        $rules = array_merge($defaultRules, $rules);
        
        // Check file size
        if ($file['size'] > $rules['max_size']) {
            $maxSizeMB = round($rules['max_size'] / 1024 / 1024, 2);
            $this->addError('file', "Le fichier est trop volumineux. Taille maximum: {$maxSizeMB}MB.");
        }
        
        // Check file type
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $rules['allowed_types'])) {
            $allowedList = implode(', ', $rules['allowed_types']);
            $this->addError('file', "Type de fichier non autorisé. Types autorisés: {$allowedList}.");
        }
        
        return empty($this->errors);
    }
}